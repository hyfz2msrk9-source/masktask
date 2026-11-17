<?php
require_once(MODULE_DIR . '/common/common.php');

class Admin_Config_Section_Form_Import_Done {
    var $session;
    var $fileUpload;
    var $DtoSection;
    var $MdbSection;
    var $DtoSectionusers;
    var $MdbSectionusers;
    var $DtoUser;
    var $MdbUser;
    var $DtoLog;
    var $MdbLog;
    var $SectionLogic;
    var $MdbGroupsusers;
    var $GroupsusersLogic;
    var $MdbUpload;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Section_Form_Import_Doneが実行されました", "Admin_Config_Section_Form_Import_Done#execute");

        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        // IDチェック
        if(!is_numeric($this->DtoSection->getSection_id())) {
            $errorList->add("Admin_Config_Section_Form_Import_Done::error", "不正なアクセスを検知しました。");
            return "error";
        }

        // 部署情報の取得と存在確認
        $this->MdbSection->getInfo($this->DtoSection);
        if (!$this->DtoSection->getSection_name()) {
            $errorList->add("Admin_Config_Section_Form_Import_Done::error", "部署情報を取得できませんでした。");
            $errorList->add("Admin_Config_Section_From_Import_Done::error", "部署は既に削除されている可能性があります。");
            return "error";
        }

        // サブ管理者編集許可チェック
        if( $this->session->getParameter("login_admin_level") < 5 ) {
            $where = array();
            $where[] = "section_id = " . Db_Common::escape_string($this->DtoSection->getSection_id()) . "";
            $where[] = "subadmin_editable_flg = '1'";
            if (!$this->MdbSection->getExistCheck($where)) {
                $errorList->add("Admin_Config_Section_Add::error", "この部署グループの編集を許可されていません。");
                return "error";
            }
        }

        // ファイルチェック
        $filename = $this->fileUpload->getTmpName();
        $filesize = $this->fileUpload->getFilesize();
        if($filesize <= 0) {
            $errorList->add("Admin_Config_Section_Form_Import_Done::error", "CSVファイルにエラーがありました。");
            $errorList->add("Admin_Config_Section_Form_Import_Done::error", "ファイルを確認してください。");
            return "error";
        }
        if(!preg_match("/\.csv/i",$this->fileUpload->getOriginalName())) {
            $errorList->add("Admin_Config_Section_Form_Import_Done::error", "インポートファイルは、CSV形式で作成してください。");
            return "error";
        }
        
        // インポート
        $this->SectionLogic->getSectionImport($filename,
                                              $filesize,
                                              $this->DtoSection,
                                              $this->DtoSectionusers,
                                              $this->MdbSectionusers,
                                              $this->DtoUser,
                                              $this->MdbUser,
                                              $this->MdbGroupsusers,
                                              $this->GroupsusersLogic,
                                              $this->MdbUpload
                                              );

        // Log
        $history_id = 134; // 134:各種設定＞部署グループ機能設定＞CSVインポート
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id($this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $memo = "部署グループ名：".$this->DtoSection->getSection_name() ."\n";
        $memo.= "ファイル名：".$this->fileUpload->getOriginalName()."\n";
        $memo.= "追加ユーザ：".$this->DtoSectionusers->getAddcount()."件\n";
        $memo.= "更新ユーザ：".$this->DtoSectionusers->getUpdatecount()."件\n";
        $memo.= "削除ユーザ：".$this->DtoSectionusers->getDelcount()."件\n";
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        $this->session->removeParameter("group_id");
        $this->session->removeParameter("name");
        $this->session->removeParameter("folder_name");
        $this->session->removeParameter('section_section_name');

        return "success";
    }

    //************************************************************
    // setter
    //************************************************************
    function setSession($session) {
        $this->session = $session;
    }
    function setFileUpload(&$fileUpload) {
        $this->fileUpload =& $fileUpload;
    }
    function setDtoSection($DtoSection) {
        $this->DtoSection = $DtoSection;
    }
    function setMdbSection($MdbSection) {
        $this->MdbSection = $MdbSection;
    }
    function setDtoSectionusers($DtoSectionusers) {
        $this->DtoSectionusers = $DtoSectionusers;
    }
    function setMdbSectionusers($MdbSectionusers) {
        $this->MdbSectionusers = $MdbSectionusers;
    }
    function setDtoUser($DtoUser) {
        $this->DtoUser = $DtoUser;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = $MdbUser;
    }
    function setDtoLog($DtoLog){
    	$this->DtoLog = $DtoLog;
    }    
    function setMdbLog($MdbLog){
    	$this->MdbLog = $MdbLog;
    }
    function setSectionLogic($SectionLogic) {
        $this->SectionLogic = $SectionLogic;
    }
    function setMdbGroupsusers(&$MdbGroupsusers) {
        $this->MdbGroupsusers = & $MdbGroupsusers;
    }
    function setGroupsusersLogic(&$GroupsusersLogic) {
        $this->GroupsusersLogic = & $GroupsusersLogic;
    }
    function setMdbUpload($MdbUpload) {
        $this->MdbUpload = $MdbUpload;
    }

    //************************************************************
    // getter
    //************************************************************
    function getSection_id() {
        return $this->DtoSection->getSection_id();
    }
    function getSection_name() {
        $ret = $this->DtoSection->getSection_name();
        if(!$ret) {
            return $this->session->getParameter('section_section_name');
        }
    }
    function getAddCount()
    {
        return $this->DtoSectionusers->getAddcount();
    }
    function getUpdateCount()
    {
        return $this->DtoSectionusers->getUpdatecount();
    }
    function getDelCount()
    {
        return $this->DtoSectionusers->getDelcount();
    }
}
?>
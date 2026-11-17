<?php
require_once(MODULE_DIR . '/common/common.php');

class Admin_Config_Section_Form_Export {
    var $session;
    var $DtoSection;
    var $MdbSection;
    var $DtoSectionusers;
    var $MdbSectionusers;
    var $DtoUser;
    var $MdbUser;
    var $DtoLog;
    var $MdbLog;
    var $SectionLogic;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Section_Form_Exportが実行されました", "Admin_Config_Section_Form_Export#execute");

        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        //IDチェック
        if(!is_numeric($this->DtoSection->getSection_id())) {
            $errorList->add("Admin_Config_Section_Form_Export::error", "不正なアクセスを検知しました。");
            return "error";
        }

        // 部署情報の取得と存在確認
        $this->MdbSection->getInfo($this->DtoSection);
        if (!$this->DtoSection->getSection_name()) {
            $errorList->add("Admin_Config_Section_Form_Export::error", "部署情報を取得できませんでした。");
            $errorList->add("Admin_Config_Section_From_Export::error", "部署は既に削除されている可能性があります。");
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

        // エクスポート
        $result = null;
        $result = $this->SectionLogic->getSectionExport($this->DtoSection,
                                                        $this->DtoSectionusers,
                                                        $this->MdbSectionusers,
                                                        $this->DtoUser,
                                                        $this->MdbUser);
        if (!$result) {
            return "error";
        }

        // Log
        $history_id = 133; // 133:各種設定＞部署グループ機能設定＞CSVエクスポート
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $memo = "部署グループ名：".$this->DtoSection->getSection_name() ."\n";
        $memo.= "所属ユーザ：" . $this->DtoSectionusers->getIncount() . "件\n";
        $memo.= "未所属ユーザ：" .($this->DtoSectionusers->getRow_count() - $this->DtoSectionusers->getIncount()) . "件\n";
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);
        
        return "success";
    }

    //************************************************************
    // setter
    //************************************************************
    function setSession($session) {
        $this->session = $session;
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

    //************************************************************
    // getter
    //************************************************************



}
?>
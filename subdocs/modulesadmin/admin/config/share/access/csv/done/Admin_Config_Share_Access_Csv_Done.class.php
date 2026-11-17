<?php
require_once(MODULE_DIR . '/common/common.php');

class Admin_Config_Share_Access_Csv_Done { 
    var $session;
    var $fileUpload;
    var $DtoGroups;
    var $MdbGroups;
    var $MdbGroupsaccesses;
    var $DtoGroupsaccesses;
    var $GroupsaccessesLogic;
    var $GroupsaccessesDto;
    var $DtoLog;
    var $MdbLog;
    var $DtoAdminUser;
    var $AdminUserLogic;
    
    function execute() {
        // ログ
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Share_Access_Csv_Doneが実行されました", "Admin_Config_Share_Access_Csv_Done#execute");
        $filename = $this->fileUpload->getTmpName();
        $filesize = $this->fileUpload->getFilesize();

        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        if($filesize <= 0) {
            $errorList->add("Admin_Config_Share_Access_Csv_Done::error", "CSVファイルにエラーがありました。");
            $errorList->add("Admin_Config_Share_Access_Csv_Done::error", "ファイルを確認してください。");
            return "upload";
        }
        if(!preg_match("/\.csv/i",$this->fileUpload->getOriginalName())) {
            $errorList->add("Admin_Config_Share_Access_Csv_Done::error", "インポートファイルは、CSV形式で作成してください。");
            return "upload";
        }

        // パラメータチェック
        if (!is_numeric($this->DtoGroups->getId())) {
            return "input";
        }

        // グループ情報の取得
        $this->MdbGroups->getInfo($this->DtoGroups);

        if (!$this->DtoGroups->getId()) {
            // 存在しないグループの選択
            return "error";
        }

        //管理者チェック
        if(!$this->AdminUserLogic->checkAdminGroup($this->DtoAdminUser, $this->DtoGroups)) {
            $errorList->add("Admin_Config_Share_Access_Csv_Done::error", "権限がありません。再度、一覧からグループを選択してください。");
            return "error";
        }

        // グループIDをセット
        $this->DtoGroupsaccesses->setGroup_id($this->DtoGroups->getId());

        $this->GroupsaccessesLogic->addCsvAccess( $filename, $this->DtoGroupsaccesses, $this->GroupsaccessesDto, $this->MdbGroupsaccesses, $this->DtoGroups, $this->MdbGroups);

        $this->DtoGroups->setId($this->DtoGroupsaccesses->getGroup_id());

        // Log
        $history_id = 177; // 177:グループ共有に関する設定>IP制限>IPアドレス CSVインポート
        $memo = "";
        $memo.= "共有グループ名：" . $this->DtoGroups->getName() . "\n";
        $memo.= "ファイル名：" . $this->fileUpload->getOriginalName() . "\n";
        $memo.= "設定：" . $this->GroupsaccessesDto->getRow_count() . "件\n";

        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
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
    function setFileUpload(&$fileUpload) {
        $this->fileUpload =& $fileUpload;
    }
    function setDtoGroups($DtoGroups){
        $this->DtoGroups = $DtoGroups;
    }
    function setMdbGroups($MdbGroups) {
        $this->MdbGroups = $MdbGroups;
    }
    function setDtoGroupsaccesses($DtoGroupsaccesses) {
        $this->DtoGroupsaccesses = $DtoGroupsaccesses;
    }
    function setMdbGroupsaccesses($MdbGroupsaccesses) {
    	$this->MdbGroupsaccesses = $MdbGroupsaccesses;
    }
    function setGroupsaccessesLogic($GroupsaccessesLogic) {
        $this->GroupsaccessesLogic = $GroupsaccessesLogic;
    }
    function setGroupsaccessesDto($GroupsaccessesDto) {
    	$this->GroupsaccessesDto = $GroupsaccessesDto;
    }
    function setDtoLog ( &$DtoLog ) {
        $this->DtoLog =& $DtoLog;
    }
    function setMdbLog ( &$MdbLog ) {
        $this->MdbLog =& $MdbLog;
    }
    function setDtoAdminUser($DtoAdminUser) {
        $this->DtoAdminUser = $DtoAdminUser;
    }
    function setAdminUserLogic($AdminUserLogic) {
        $this->AdminUserLogic = $AdminUserLogic;
    }
    //************************************************************
    // getter
    //************************************************************
    // エラーメッセージ
    function getErr_msg(){
        return $this->GroupsaccessesDto->getErr_msg();
    }
    // 何件追加したか
    function getIncount(){
        return $this->GroupsaccessesDto->getRow_count();
    }

}
?>

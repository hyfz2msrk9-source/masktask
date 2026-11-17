<?php
  /************************************************************
   * last update :
   ************************************************************/
require_once( MODULE_DIR . "/common/common.php");

class Admin_Superior_Import_Done {
    var $session;
    var $fileUpload;
    var $MdbUserSuperior;
    var $DtoUserSuperior;
    var $MdbSuperiorMulti;
    var $MdbSuperiorSend;
    var $UserLogic;
    var $AdminuserLogic;
    var $SuperiorLogic;
    var $DtoUser;
    var $MdbUser;
    var $count;
    var $rejected_flg;
    var $DtoLog;
    var $MdbLog;
    var $back_btn_flg;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Superior_Import_Doneが実行されました","Admin_Superior_Import_Done#execute");

        // エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        $filename = $this->fileUpload->getTmpName();
        $filesize = $this->fileUpload->getFilesize();

        // 上長オプション設定チェック
        if(!SUPERIOR_OPTION_FLG) {
            $errorList->add("Admin_Superior_Import_Done::error", "この機能をご利用頂くためには、上長承認スキャンオプションが必要です。");
            $this->back_btn_flg = 1;
            return "error";
        }

        if($filesize <= 0) {
            $errorList->add("Admin_Superior_Import_Done::error", "CSVファイルにエラーがありました。");
            $errorList->add("Admin_Superior_Import_Done::error", "ファイルを確認してください。");
            return "upload";
        }
        if(!preg_match("/\.csv/i",$this->fileUpload->getOriginalName())) {
            $errorList->add("Admin_Superior_Import_Done::error", "インポートファイルは、CSV形式で作成してください。");
            return "upload";
        }
        $admin_id = $this->session->getParameter('admin_id');
        // 自分の権限を確認 (ユーザ管理=3)であれば 自分の追加したユーザのみをインポートするように条件を設定する
        $owner_admin_id = 0; //importUserSuperior では  if($owner_admin_id) で判定
        if( $this->session->getParameter("login_admin_level") == "3" ) {
            $owner_admin_id = $admin_id;
        }
        $level = $this->session->getParameter("login_admin_level");
        list($this->count,$this->rejected_flg)= $this->SuperiorLogic->importUserSuperior(
            $filename, $filesize,
            $this->DtoUser, $this->MdbUser, $this->DtoUserSuperior, $this->MdbUserSuperior,$owner_admin_id,
            $this->MdbSuperiorSend,$this->UserLogic, $this->MdbSuperiorMulti,$this->AdminuserLogic,$level

        );
        
        // Log
        $history_id = 122; // 122:上長承認設定＞CSVファイルでの上長設定
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id($admin_id );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $memo = "";
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        return "success";
    }
    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setFileUpload(&$fileUpload) {
        $this->fileUpload =& $fileUpload;
    }
    function setMdbUserSuperior($MdbUserSuperior) {
        $this->MdbUserSuperior = & $MdbUserSuperior;
    }
    function setDtoUserSuperior($DtoUserSuperior) {
        $this->DtoUserSuperior = & $DtoUserSuperior;
    }
    function setMdbSuperiorMulti($MdbSuperiorMulti) {
        $this->MdbSuperiorMulti = & $MdbSuperiorMulti;
    }
    function setMdbSuperiorSend($MdbSuperiorSend) {
        $this->MdbSuperiorSend = & $MdbSuperiorSend;
    }
    function setSuperiorLogic($SuperiorLogic) {
        $this->SuperiorLogic = & $SuperiorLogic;
    }
    function setUserLogic($UserLogic) {
        $this->UserLogic = & $UserLogic;
    }
    function setAdminuserLogic($AdminuserLogic) {
        $this->AdminuserLogic = & $AdminuserLogic;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = & $MdbUser;
    }
    function setDtoUser($DtoUser) {
        $this->DtoUser = & $DtoUser;
    }
    function setDtoLog ( &$DtoLog ) {
        $this->DtoLog =& $DtoLog;
    }
    function setMdbLog ( &$MdbLog ) {
        $this->MdbLog =& $MdbLog;
    }
    function setCount($count) {
        $this->count = $count;
    }
    function setBack_btn_flg($back_btn_flg) {
        $this->back_btn_flg = $back_btn_flg;
    }

    // getter
    function getCount() {
        return $this->count;
    }
    function getBackBtnFlg() {
        return $this->back_btn_flg;
    }
    function getMaxSuperior() {
        return ( defined('MAX_SUPERIOR') ? MAX_SUPERIOR : 5 );
    }
    function getRejected_flg(){
        return $this->rejected_flg;
    }

}
?>

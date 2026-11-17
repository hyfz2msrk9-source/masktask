<?php
require_once(MODULE_DIR . '/common/common.php');

class Admin_Superior_Export {
    var $session;
    var $MdbUserSuperior;
    var $DtoUserSuperior;
    var $SuperiorLogic;
    var $MdbUser;
    var $DtoUser;
    var $DtoLog;
    var $MdbLog;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Superior_Exportが実行されました", "Admin_Superior_Export#execute");

        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        //上長承認オプションつきかどうか
        if(!SUPERIOR_OPTION_FLG) {
            $errorList->add("Admin_Superior_Export::error", "この機能をご利用頂くためには、上長承認スキャンオプションが必要です。");
            return "error";
        }
        // 自分の権限を確認 (ユーザ管理=3)であれば 自分の追加したユーザのみをエクスポートするように条件を設定する
        $admin_id = "";
        if( $this->session->getParameter("login_admin_level") == "3" ) {
            $admin_id = $this->session->getParameter('admin_id');
        }
        $level = $this->session->getParameter("login_admin_level");
        // エスクポートを実行、エクスポートの対象がなかった場合はエラーを出力する
        if(!$this->SuperiorLogic->getAllUserSuperiorExport($this->DtoUser, $this->MdbUser, $this->DtoUserSuperior, $this->MdbUserSuperior,$admin_id,$level)) {
            $errorList->add("Admin_Superior_Export::error", "ユーザ一覧が取得できませんでした。");
            $errorList->add("Admin_Superior_Export::error", "ユーザが存在するか確認してください。");
            return "error";
        }

        // Log
        $history_id = 123; // 123:上長承認設定＞上長設定一覧エクスポート
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id($this->session->getParameter('admin_id'));
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo("");
        $this->MdbLog->Logging($this->DtoLog);
        exit;
    }

    //************************************************************
    // setter
    //************************************************************
    function setSession($session) {
        $this->session = $session;
    }
    function setMdbUserSuperior($MdbUserSuperior) {
        $this->MdbUserSuperior = & $MdbUserSuperior;
    }
    function setDtoUserSuperior($DtoUserSuperior) {
        $this->DtoUserSuperior = & $DtoUserSuperior;
    }
    function setSuperiorLogic($SuperiorLogic) {
        $this->SuperiorLogic = & $SuperiorLogic;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = & $MdbUser;
    }
    function setDtoUser($DtoUser) {
        $this->DtoUser = & $DtoUser;
    }
    function setDtoLog($DtoLog){
        $this->DtoLog = $DtoLog;
    }
    function setMdbLog($MdbLog){
        $this->MdbLog = $MdbLog;
    }
    //************************************************************
    // getter
    //************************************************************
    function getMode() {
        return "export";
    }

}
?>

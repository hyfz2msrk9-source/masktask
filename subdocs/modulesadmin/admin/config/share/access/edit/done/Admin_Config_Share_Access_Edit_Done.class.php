<?php
require_once( MODULE_DIR . "/common/common.php");

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * [機能説明] 共有グループ単位のIPアクセス制限、IP設定完了
 */
class Admin_Config_Share_Access_Edit_Done {
    var $session;
    var $DtoGroups;
    var $MdbGroups;
    var $DtoGroupsaccesses;
    var $MdbGroupsaccesses;
    var $GroupsaccessesDto;
    var $GroupsaccessesLogic;
    var $DtoLog;
    var $MdbLog;
    var $DtoAdminUser;
    var $AdminUserLogic;

    function execute() {

        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Share_Access_Edit_Doneが実行されました","Admin_Config_Share_Access_Edit_Done#execute");

        // エラー発生
        $container =& DIContainerFactory::getContainer();
        $actionChain =& $container->getComponent("ActionChain");
        $errorList =& $actionChain->getCurErrorList();

        $proxy = new Proxy();

        // パラメータチェック
        if (!is_numeric($this->DtoGroups->getId())) {
            return "error";
        }

        // グループ情報の取得
        $this->MdbGroups->getInfo($this->DtoGroups);

        if (!$this->DtoGroups->getId()) {
            // 存在しないグループの選択
            return "error";
        }

        //管理者チェック
        if(!$this->AdminUserLogic->checkAdminGroup($this->DtoAdminUser, $this->DtoGroups)) {
            $errorList->add("Admin_Config_Share_Access::error", "権限がありません。再度、一覧からグループを選択してください。");
            return "error";
        }

        if(!$proxy->check_ipaddr($this->DtoGroupsaccesses->getIp_addr())) {
            $errorList->add("Admin_Config_Share_Access_Edit::error", "IPアドレスを正しく入力してください。");
            return "input";
        }

        //存在チェック
        $this->DtoGroupsaccesses->setGroup_id($this->DtoGroups->getId());
        if($this->GroupsaccessesLogic->checkIpAddr($this->DtoGroupsaccesses, $this->MdbGroupsaccesses)) {
            $errorList->add("Admin_Config_Share_Access_Edit::error", "既にこのIPアドレスは登録されています。");
            return "input";
        }

        //登録
        $this->GroupsaccessesLogic->addGroupsaccesses($this->DtoGroupsaccesses, $this->MdbGroupsaccesses);

        // Log
        // 174: グループ共有に関する設定>IP制限>IPアドレス追加
        $history_id = 174;
        $memo  = "";
        if($this->DtoGroupsaccesses->getGroup_id()) {
            // グループ情報の取得
            $this->DtoGroups->setId($this->DtoGroupsaccesses->getGroup_id());
            $this->MdbGroups->getInfo($this->DtoGroups);

            $memo .= "グループ名：". $this->DtoGroups->getName() . "\n";
        }
        if($this->DtoGroupsaccesses->getIp_addr()) {
            $memo .= "IPアドレス：". $this->DtoGroupsaccesses->getIp_addr() . "\n";
        }

        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        return "success";
    }

    // setter
    function setSession($session) {
        $this->session = $session;
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
    function setGroupsaccessesDto($GroupsaccessesDto) {
        $this->GroupsaccessesDto = $GroupsaccessesDto;
    }
    function setGroupsaccessesLogic($GroupsaccessesLogic) {
        $this->GroupsaccessesLogic = $GroupsaccessesLogic;
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

    // getter

    // IPアドレス
    function getIp_addr(){
      return $this->DtoGroupsaccesses->getIp_addr();
    }
    // IPアドレス一覧取得
    function getAccessList(){
        return $this->GroupsaccessesLogic->getAccessKeyList($this->MdbGroupsaccesses, $this->DtoGroups->getId());
    }

/*
 * Local Variables:
 * mode: php
 * coding: utf-8-unix
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * indent-tabs-mode: nil
 * End:
 */
}
?>

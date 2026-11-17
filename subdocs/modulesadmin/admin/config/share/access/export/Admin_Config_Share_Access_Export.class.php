<?php
require_once( MODULE_DIR . "/common/common.php");

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * [機能説明] 共有グループ単位のIPアクセス制限、CSVエクスポート
 */
class Admin_Config_Share_Access_Export {
    var $session;
    var $DtoGroups;
    var $MdbGroups;
    var $DtoLog; 
    var $MdbLog;
    var $DtoGroupsaccesses;
    var $MdbGroupsaccesses;
    var $GroupsaccessesDto;
    var $GroupsaccessesLogic;
    var $DtoAdminUser;
    var $AdminUserLogic;

    var $id;

    function execute() {

        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Share_Access_Exportが実行されました","Admin_Config_Share_Access_Export#execute");

        //エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

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
            $errorList->add("Admin_Config_Share_Access_Export::error", "権限がありません。再度、一覧からグループを選択してください。");
            return "error";
        }

        // セッションセット
        if($this->id){
            $this->session->setParameter("id", $this->id);
        }

        // エクスポート(iplist.csv)
        $this->GroupsaccessesLogic->csvExport($this->MdbGroupsaccesses,$this->DtoGroups->getId(),$this->DtoGroups->getName());

        // Log
        $history_id = 178; // 178:グループ共有に関する設定>IP制限>IPアドレス CSVエクスポート
        $memo = "";
        $this->MdbGroupsaccesses->getGroupsaccessesCont($this->GroupsaccessesDto,$this->DtoGroups);
        $memo.= "件数：" . $this->GroupsaccessesDto->getRow_count() . "件";
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        exit;
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
    function setDtoLog($DtoLog){
        $this->DtoLog = $DtoLog;
    }
    function setMdbLog($MdbLog) {
        $this->MdbLog = $MdbLog;
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
?>

<?php
require_once( MODULE_DIR . "/common/common.php");

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * [機能説明] 共有グループ単位のIPアクセス制限
 */
class Admin_Config_Share_Access {
    var $session;
    var $DtoGroups;
    var $MdbGroups;
    var $groupDto;
    var $DtoFolder;
    var $MdbFolder;
    var $DtoGroupsaccesses;
    var $MdbGroupsaccesses;
    var $GroupsaccessesLogic;
    var $DtoAdminUser;
    var $AdminUserLogic;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Share_Accessが実行されました","Admin_Config_Share_Access#execute");

        //エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

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
            $errorList->add("Admin_Config_Share_Access::error", "権限がありません。再度、一覧からグループを選択してください。");
            return "error";
        }

        // フォルダ情報の取得
        $this->DtoFolder->setFolder_id($this->DtoGroups->getFolder_id());
        $this->MdbFolder->setFolderInfo($this->DtoFolder);

        // DTOへセット
        $this->groupDto->setAccess_flg($this->DtoGroups->getAccess_flg());
        $this->groupDto->setAccess_share_admin_flg($this->DtoGroups->getAccess_share_admin_flg());

        // sessionに記入
        $this->session->setParameter("log_access_flg",$this->groupDto->getAccess_flg());
        $this->session->setParameter("log_access_share_admin_flg",$this->groupDto->getAccess_share_admin_flg());

        // session クリア

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
    function setGroupDto($groupDto) {
        $this->groupDto = $groupDto;
    }
    function setDtoFolder($DtoFolder){
        $this->DtoFolder = $DtoFolder;
    }
    function setMdbFolder($MdbFolder) {
        $this->MdbFolder = $MdbFolder;
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
    function setDtoAdminUser($DtoAdminUser) {
        $this->DtoAdminUser = $DtoAdminUser;
    }
    function setAdminUserLogic($AdminUserLogic) {
        $this->AdminUserLogic = $AdminUserLogic;
    }

    // getter
    // アクセス制限のリスト
    function getAccess_Flg_Options(){
        return GlobalArray::getAccess_Flg_Options();
    }
    // グループID
    function getId() {
        return $this->DtoGroups->getId();
    }
    // グループ名
    function getName() {
        return $this->DtoGroups->getName();
    }
    // 共有フォルダ名
    function getFolder_name() {
        return $this->DtoFolder->getFolder_name();
    }
    // グループのIPアクセス制限の設定
    function getAccess_flg() {
        return $this->groupDto->getAccess_flg();
    }
    // IPアクセス制限の設定(グループ管理者)
    function getAccess_share_admin_flg() {
        return $this->groupDto->getAccess_share_admin_flg();
    }
    // IPアドレス一覧取得
    function getAccess_list(){
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

<?php
  /************************************************************
   * last update :
   ************************************************************/
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Share_Import {
    var $session;
    var $DtoGroups;
    var $MdbGroups;
    var $MdbGroups_users;
    var $DtoFolder;
    var $MdbFolder;
    var $groupDto;
    var $MdbUpload;
    var $DtoAdminUser;
    var $AdminUserLogic;

    var $limit_capacity;
    var $user_array;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Share_Importが実行されました","Admin_Config_Share_Import#execute");

        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        // パラメータチェック
        if (!is_numeric($this->DtoGroups->getId())) {
            $errorList->add("Admin_Config_Share_Import::error", "不正な操作が実行されました。");
            return "error";
        }

        // グループ情報の取得
        $this->MdbGroups->getInfo($this->DtoGroups);
        if (!$this->DtoGroups->id) {
            // 存在しないグループの選択
            $errorList->add("Admin_Config_Share_Import::error", "グループ情報が取得できませんでした。");
            $errorList->add("Admin_Config_Share_Import::error", "グループは既に削除されている可能性があります。");

            return "error";
        }

        //管理者チェック
        if(!$this->AdminUserLogic->checkAdminGroup($this->DtoAdminUser, $this->DtoGroups)) {
            $errorList->add("Admin_Config_Share_Import::error", "権限がありません。再度、一覧からグループを選択してください。");
            return "error";
        }

        // フォルダ名の取得
        $this->DtoFolder->setFolder_id($this->DtoGroups->folder_id);
        $this->MdbFolder->setFolderInfo($this->DtoFolder);

        $this->session->setParameter("group_id", $this->DtoGroups->id);
        $this->session->setParameter("name", $this->DtoGroups->name);
        $this->session->setParameter("folder_name", $this->DtoFolder->folder_name);
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
    function setDtoFolder($DtoFolder) {
        $this->DtoFolder = $DtoFolder;
    }
    function setMdbFolder($MdbFolder) {
        $this->MdbFolder = $MdbFolder;
    }
    function setGroupDto($groupDto) {
        $this->groupDto = $groupDto;
    }
    function setMdbGroups_users($MdbGroups_users) {
        $this->MdbGroups_users = $MdbGroups_users;
    }
    function setDtoAdminUser($DtoAdminUser) {
        $this->DtoAdminUser = $DtoAdminUser;
    }
    function setAdminUserLogic($AdminUserLogic) {
        $this->AdminUserLogic = $AdminUserLogic;
    }

    // getter
    function getGroupInfo(){
        return $this->groupDto;
    }
    function getName()
    {
        return $this->DtoGroups->name;
    }
    function getFolder_name()
    {
        return $this->DtoFolder->folder_name;
    }
    function getId()
    {
        return $this->DtoGroups->id;
    }
    function getMode() {
        return "imoprt";
    }
}
?>
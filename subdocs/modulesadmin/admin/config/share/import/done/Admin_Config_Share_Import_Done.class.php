<?php
  /************************************************************
   * last update :
   ************************************************************/
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Share_Import_Done {
    var $session;
    var $fileUpload;
    var $DtoGroups;
    var $MdbGroups;
    var $MdbGroups_users;
    var $DtoGroups_users;
    var $GroupLogic;
    var $DtoFolder;
    var $MdbFolder;
    var $DtoUser;
    var $MdbUser;
    var $DtoSection;
    var $MdbSection;
    var $count;
    var $DtoLog;
    var $MdbLog;
    var $MdbUpload;
    var $DtoAdminUser;
    var $AdminUserLogic;
    var $DtoGroupsAdminUsers;
    var $MdbGroupsAdminUsers;
    var $import_case;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Share_Import_Doneが実行されました","Admin_Config_Share_Import_Done#execute");
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        $filename = $this->fileUpload->getTmpName();
        $filesize = $this->fileUpload->getFilesize();

        // パラメータチェック
        if (!is_numeric($this->DtoGroups_users->getGroup_id())) {
            $errorList->add("Admin_Config_Share_Import_Done::error", "不正な操作が実行されました。");
            return "error";
        }

        // グループ情報の取得
        $this->DtoGroups->setId($this->DtoGroups_users->getGroup_id());
        $this->MdbGroups->getInfo($this->DtoGroups);

        if (!$this->DtoGroups->id) {
            // 存在しないグループの選択
            $errorList->add("Admin_Config_Share_Import_Done::error", "グループ情報が取得できませんでした。");
            $errorList->add("Admin_Config_Share_Import_Done::error", "グループは既に削除されている可能性があります。");
            return "error";
        }

        //管理者チェック
        if(!$this->AdminUserLogic->checkAdminGroup($this->DtoAdminUser, $this->DtoGroups)) {
            $errorList->add("Admin_Config_Share_Edit_Done::error", "権限がありません。再度、一覧からグループを選択してください。");
            return "error";
        }

        if($filesize <= 0) {
            $errorList->add("Admin_Config_Share_Import_Done::error", "CSVファイルにエラーがありました。");
            $errorList->add("Admin_Config_Share_Import_Done::error", "ファイルを確認してください。");
            return "error";
        }
        if(!preg_match("/\.csv/i",$this->fileUpload->getOriginalName())) {
            $errorList->add("Admin_Config_Share_Import_Done::error", "インポートファイルは、CSV形式で作成してください。");
            return "error";
        }
        // フォルダ名の取得
        $this->DtoFolder->setFolder_id($this->DtoGroups->folder_id);
        $this->MdbFolder->setFolderInfo($this->DtoFolder);
        if ($this->import_case == 1) {
            $counts = $this->GroupLogic->importGroupMember(
                                                           $filename,$filesize,$this->DtoGroups,
                                                           $this->DtoGroups_users,$this->MdbGroups_users,
                                                           $this->DtoGroupsAdminUsers,$this->MdbGroupsAdminUsers,
                                                           $this->DtoUser,$this->MdbUser,
                                                           $this->MdbFolder, $this->MdbUpload,1
                                                           );
            $this->count = $counts["1"]+$counts["5"]+$counts["9"]+$counts["99"];
        } else {
            $counts = $this->GroupLogic->importGroupMember(
                                                           $filename,$filesize,$this->DtoGroups,
                                                           $this->DtoGroups_users,$this->MdbGroups_users,
                                                           $this->DtoGroupsAdminUsers,$this->MdbGroupsAdminUsers,
                                                           $this->DtoSection, $this->MdbSection,
                                                           $this->MdbFolder, $this->MdbUpload,2
                                                           );
            $this->count = $counts["1"]+$counts["5"]+$counts["9"];
        }

        // Log
        $history_id = 121; // 121:各種設定＞グループ共有機能設定＞CSVインポート
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id($this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $memo = "";
        $memo.= "グループ名 : ".$this->session->getParameter("name") ."\n";
        $memo.= "共有フォルダ名 : ".$this->session->getParameter("folder_name")."\n";
        $memo.= "ファイル名：".$this->fileUpload->getOriginalName()."\n";
        if ($this->import_case == 1) {
            $memo.= "ユーザメンバー追加\n";
        } else {
            $memo.= "部署メンバー追加\n";
        }
        $memo.= "　権限なし：" . $counts['0']."件\n";
        if ($this->import_case == 1) {
            $memo.= "　グループ管理者：".$counts['99']."件\n";
        }
        $memo.= "　読み出しのみ：" . $counts['1']."件\n";
        $memo.= "　読み/書き：" . $counts['5']."件\n";
        $memo.= "　読み/書き/フォルダ操作：" . $counts['9']."件\n";
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        $this->session->removeParameter("group_id");
        $this->session->removeParameter("name");
        $this->session->removeParameter("folder_name");

        return "success";
    }
    // setter
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
    function setDtoFolder($DtoFolder) {
        $this->DtoFolder = $DtoFolder;
    }
    function setMdbFolder($MdbFolder) {
        $this->MdbFolder = $MdbFolder;
    }
    function setDtoUser($DtoUser) {
        $this->DtoUser = $DtoUser;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = $MdbUser;
    }
    function setDtoSection($DtoSection) {
        $this->DtoSection = $DtoSection;
    }
    function setMdbSection($MdbSection) {
        $this->MdbSection = $MdbSection;
    }
    function setMdbGroups_users($MdbGroups_users) {
        $this->MdbGroups_users = $MdbGroups_users;
    }
    function setDtoGroups_users($DtoGroups_users) {
        $this->DtoGroups_users = $DtoGroups_users;
    }
    function setGroupLogic($GroupLogic) {
        $this->GroupLogic = $GroupLogic;
    }
    function setCount($count) {
        $this->count = $count;
    }
    function setDtoLog ( &$DtoLog ) {
        $this->DtoLog =& $DtoLog;
    }
    function setMdbLog ( &$MdbLog ) {
        $this->MdbLog =& $MdbLog;
    }
    function setMdbUpload($MdbUpload) {
        $this->MdbUpload = $MdbUpload;
    }
    function setDtoAdminUser($DtoAdminUser) {
        $this->DtoAdminUser = $DtoAdminUser;
    }
    function setAdminUserLogic($AdminUserLogic) {
        $this->AdminUserLogic = $AdminUserLogic;
    }
    function setDtoGroupsAdminUsers($DtoGroupsAdminUsers) {
        $this->DtoGroupsAdminUsers = $DtoGroupsAdminUsers;
    }
    function setMdbGroupsAdminUsers($MdbGroupsAdminUsers) {
        $this->MdbGroupsAdminUsers = $MdbGroupsAdminUsers;
    }
    function setImport_case($import_case) {
        $this->import_case =& $import_case;
    }

    // getter
    function getGroupInfo(){
        return $this->groupDto;
    }
    function getMode()
    {
        return "import";
    }
    function getName()
    {
        return $this->DtoGroups->getName();
    }
    function getFolder_name()
    {
        return $this->DtoFolder->folder_name;
    }
    function getId()
    {
        return $this->DtoGroups->id;
    }
    function getCount() {
        return $this->count;
    }
}
?>
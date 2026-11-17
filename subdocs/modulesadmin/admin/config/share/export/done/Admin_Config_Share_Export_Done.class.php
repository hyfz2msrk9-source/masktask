<?php
require_once(MODULE_DIR . '/common/common.php');

class Admin_Config_Share_Export_Done {
    var $session;
    var $DtoGroups;
    var $MdbGroups;
    var $GroupDto;
    var $GroupLogic;
    var $DtoSection;
    var $MdbSection;
    var $DtoUser;
    var $MdbUser;
    var $MdbGroups_users;
    var $MdbGroups_admin_users;
    var $DtoFolder;
    var $MdbFolder;
    var $DtoLog;
    var $MdbLog;
    var $DtoAdminUser;
    var $AdminUserLogic;

    var $export_case;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Share_Export_Doneが実行されました", "Admin_Config_Share_Export_Done#execute");

        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        // パラメータチェック
        if (!is_numeric($this->DtoGroups->getId()) || !is_numeric($this->export_case)) {
            $errorList->add("Admin_Config_Share_Export_Done::error", "不正な操作が実行されました。");
            return "error";
        }

        //グループ情報取得
        $this->MdbGroups->getInfo($this->DtoGroups);

        if(!$this->DtoGroups->getId()) {
            $errorList->add("Admin_Config_Share_Export_Done::error", "グループ情報が取得できませんでした。");
            $errorList->add("Admin_Config_Share_Export_Done::error", "グループは既に削除されている可能性があります。");
            return "error";
        }

        //管理者チェック
        if(!$this->AdminUserLogic->checkAdminGroup($this->DtoAdminUser, $this->DtoGroups)) {
            $errorList->add("Admin_Config_Share_Export_Done::error", "権限がありません。再度、一覧からグループを選択してください。");
            return "error";
        }

        if ($this->export_case == 1) {
            $counts = $this->GroupLogic->getGroupAllUserExport($this->DtoGroups,$this->DtoUser,$this->MdbUser,$this->MdbGroups_users,$this->MdbGroups_admin_users,1);
            if($counts === false) {
                $errorList->add("Admin_Config_Share_Export_Done::error", "ユーザ一覧が取得できませんでした。");
                $errorList->add("Admin_Config_Share_Export_Done::error", "ユーザが存在するか確認してください。");
                return "error";
            }
        } else {
            $counts = $this->GroupLogic->getGroupAllUserExport($this->DtoGroups,$this->DtoSection,$this->MdbSection,$this->MdbGroups_users,$this->MdbGroups_admin_users,2);
            if($counts === false) {
                $errorList->add("Admin_Config_Share_Export_Done::error", "部署一覧が取得できませんでした。");
                $errorList->add("Admin_Config_Share_Export_Done::error", "部署が存在するか確認してください。");
                return "error";
            }
        }

        // フォルダ名取得
        $this->DtoFolder->setFolder_id($this->DtoGroups->getFolder_id());
        $this->MdbFolder->setFolderInfo($this->DtoFolder);

        // Log
        $history_id = 120; // 120:各種設定＞グループ共有機能設定＞CSVエクスポート
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $memo = "グループ名 : ".$this->DtoGroups->getName() ."\n";
        $memo.= "共有フォルダ名 : ".$this->DtoFolder->getFolder_name(). "\n";
        if ($this->export_case == 1) {
            $memo.= "ユーザメンバーのエクスポート\n";
            $memo.= "　管理者権限ユーザ：" . $counts["admin_user"] . "件\n";
        } else {
            $memo.= "部署メンバーのエクスポート\n";
        }
        $memo.= "　権限なし：" . $counts['0']."件\n";
        $memo.= "　読み出しのみ：" . $counts['1']."件\n";
        $memo.= "　読み/書き：" . $counts['5']."件\n";
        $memo.= "　読み/書き/フォルダ操作：" . $counts['9']."件\n";
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);
        exit;
    }

    //************************************************************
    // setter
    //************************************************************
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoGroups($DtoGroups) {
        $this->DtoGroups = $DtoGroups;
    }
    function setMdbGroups($MdbGroups) {
        $this->MdbGroups = $MdbGroups;
    }
    function setGroupDto($GroupDto) {
        $this->GroupDto = $GroupDto;
    }
    function setGroupLogic($GroupLogic) {
        $this->GroupLogic = $GroupLogic;
    }
    function setDtoSection($DtoSection) {
        $this->DtoSection = $DtoSection;
    }
    function setMdbSection($MdbSection) {
        $this->MdbSection = $MdbSection;
    }
    function setDtoUser($DtoUser) {
        $this->DtoUser = $DtoUser;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = $MdbUser;
    }
    function setMdbGroups_users($MdbGroups_users) {
        $this->MdbGroups_users = $MdbGroups_users;
    }
    function setMdbGroups_admin_users($MdbGroups_admin_users) {
        $this->MdbGroups_admin_users = $MdbGroups_admin_users;
    }
    function setDtoFolder($DtoFolder) {
        $this->DtoFolder = $DtoFolder;
    }
    function setMdbFolder($MdbFolder) {
        $this->MdbFolder = $MdbFolder;
    }
    function setDtoLog($DtoLog){
        $this->DtoLog = $DtoLog;
    }
    function setMdbLog($MdbLog){
        $this->MdbLog = $MdbLog;
    }
    function setDtoAdminUser($DtoAdminUser) {
        $this->DtoAdminUser = $DtoAdminUser;
    }
    function setAdminUserLogic($AdminUserLogic) {
        $this->AdminUserLogic = $AdminUserLogic;
    }
    function setExport_case($export_case) {
        $this->export_case = $export_case;
    }

    //************************************************************
    // getter
    //************************************************************
    function getMode() {
        return "export";
    }

}
?>
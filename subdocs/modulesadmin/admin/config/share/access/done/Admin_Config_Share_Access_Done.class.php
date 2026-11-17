<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Share_Access_Done {
    var $session;
    var $DtoGroups;
    var $MdbGroups;
    var $groupDto;
    var $DtoFolder;
    var $MdbFolder;
    var $DtoLog;
    var $MdbLog;
    var $GroupLogic;
    var $DtoAdminUser;
    var $AdminUserLogic;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Share_Access_Doneが実行されました","Admin_Config_Share_Access_Done#execute");

        // エラーの初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        // パラメータチェック
        if (!$this->groupDto->getId() ) {
            return "error";
        }
        if (!is_numeric($this->groupDto->getId())) {
            return "error";
        }

        // グループ情報の取得
        $this->DtoGroups->setId($this->groupDto->id);
        $this->MdbGroups->getInfo($this->DtoGroups);

        //管理者チェック
        if(!$this->AdminUserLogic->checkAdminGroup($this->DtoAdminUser, $this->DtoGroups)) {
            $errorList->add("Admin_Config_Share_Access::error", "権限がありません。再度、一覧からグループを選択してください。");
            return "error";
        }

        // フォルダ情報の取得
        $this->DtoFolder->setFolder_id($this->DtoGroups->getFolder_id());
        $this->MdbFolder->setFolderInfo($this->DtoFolder);

        // IPアクセス制限(グループ管理者)
        $a_s_a_flg = $this->groupDto->getAccess_share_admin_flg();
        if(strval($a_s_a_flg)==strval(intval($a_s_a_flg))){
            if ($a_s_a_flg < 0 || $a_s_a_flg > 1 || !is_numeric($a_s_a_flg)) {
                $this->groupDto->setAccess_share_admin_flg(0);
                $errorList->add("Admin_Config_Share_Edit_Done::error", "IP制限(グループ管理者)設定を正しく選択してください。");
                return "input";
            }
        } else {
            $this->groupDto->setAccess_share_admin_flg(0);
            $errorList->add("Admin_Config_Share_Edit_Done::error", "IP制限(グループ管理者)設定を正しく選択してください。");
            return "input";
        }

        // IPアクセス制限
        $a_flg = $this->groupDto->getAccess_flg();
        if(strval($a_flg)==strval(intval($a_flg))){
            if ($a_flg < 0 || $a_flg > 1 || !is_numeric($a_flg)) {
                $errorList->add("Admin_Config_Share_Edit_Done::error", "IP制限設定を正しく選択してください。");
                return "input";
            }
        } else {
            $errorList->add("Admin_Config_Share_Edit_Done::error", "IP制限設定を正しく選択してください。");
            return "input";
        }

        // groupを変更する
        $this->DtoGroups->setAccess_flg($this->groupDto->getAccess_flg());
        $this->DtoGroups->setAccess_share_admin_flg($this->groupDto->getAccess_share_admin_flg());

        $this->MdbGroups->edit($this->DtoGroups);
        
        // Log
        $history_id = 173; // 173:グループ共有に関する設定>IP制限

        $memo = "";
        $memo .= "グループ名：".$this->DtoGroups->getName()."\n";
        $memo .= "共有フォルダ名：".$this->DtoFolder->getFolder_name()."\n";
        // IPによるアクセス制限設定(グループ管理者)
        if($this->session->getParameter('log_access_share_admin_flg') != $this->groupDto->getAccess_share_admin_flg()) {
            $arr = array("OFF","ON");
            $memo .= "IP制限(グループ管理者)：".$arr[$this->session->getParameter('log_access_share_admin_flg')]. "→" .$arr[$this->groupDto->getAccess_share_admin_flg()]."\n";
        }
        // グループのIP制限
        if($this->session->getParameter('log_access_flg') != $this->groupDto->getAccess_flg()) {
            $arr = array("全てのIP","登録IPのみ");
            $memo .= "グループのIP制限設定：".$arr[$this->session->getParameter('log_access_flg')]. "→" .$arr[$this->groupDto->getAccess_flg()]."\n";
        }

        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        //sessionを削除
        $this->session->removeParameter("id");
        $this->session->removeParameter("log_access_flg");
        $this->session->removeParameter("log_access_share_admin_flg");

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
    function setGroupDto($groupDto){
        $this->groupDto = $groupDto;
    }
    function setDtoFolder($DtoFolder){
        $this->DtoFolder = $DtoFolder;
    }
    function setMdbFolder($MdbFolder){
        $this->MdbFolder = $MdbFolder;
    }
    function setDtoLog ( &$DtoLog ){
        $this->DtoLog =& $DtoLog;
    }
    function setMdbLog ( &$MdbLog ){
        $this->MdbLog =& $MdbLog;
    }
    function setGroupLogic($GroupLogic){
        $this->GroupLogic = $GroupLogic;
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
}
?>
<?php
/************************************************************
 * last update :
 ************************************************************/
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Share_Del_Done {
    var $session;
    var $DtoGroups;
    var $MdbGroups;
    var $MdbGroupsusers;
    var $DtoFolder;
    var $MdbFolder;
    var $MdbUpload;
    var $MdbCollect;
    var $GroupLogic;
    var $groupDto;
    var $DtoLog;
    var $MdbLog;
    var $DtoGroupsadminusers;
    var $MdbGroupsadminusers;
    var $MdbGroupsAccess;
    var $DtoAdminUser;
    var $AdminUserLogic;
    /** @var \Wf_Mdb_User */
    var $MdbUser;

    var $limit_capacity;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Share_Del_Doneが実行されました","Admin_Config_Share_Del_Done#execute");


        // パラメータチェック
        if (!is_numeric($this->DtoGroups->getId())) {
            return "error";
        }

        // グループ情報の取得
        $this->MdbGroups->getInfo($this->DtoGroups);

        //レベルによって編集可能かチェックする
        if(!$this->AdminUserLogic->checkAdminGroup($this->DtoAdminUser, $this->DtoGroups)) {
            $errorList->add("Admin_Config_Share_Del_Done::error", "権限がありません。再度、一覧からグループを選択してください。");
            return "error";
        }

        //削除
        $this->GroupLogic->group_delete($this->DtoGroups,$this->DtoFolder,$this->MdbGroups,$this->MdbGroupsusers,$this->MdbFolder,$this->MdbUpload,$this->MdbCollect,$this->MdbGroupsadminusers,$this->MdbGroupsAccess, $this->MdbUser);

        /*
        // フォルダ内のファイル一覧を取得
        $list = $this->MdbUpload->getFilesByFolderId($this->DtoGroups->folder_id);

        // フォルダ内のファイルの削除
        foreach ($list as $val) {
            $this->MdbUpload->del($val['file_id']);
        }

        // ファイル自体削除
        foreach ($list as $val) {
            unlink(SAVE_PATH . "/" . $val['file_id'] . ".upload");
        }

        // フォルダの削除
        $this->DtoFolder->setFolder_id($this->DtoGroups->folder_id);
        $this->MdbFolder->deleteFolder($this->DtoFolder);

        // グループの削除
        $this->MdbGroups->delete($this->DtoGroups);

        // グループのユーザ情報の削除
        $this->MdbGroups_users->delete($this->DtoGroups->id);
        */

        // Log
        $history_id = 114; // 114:各種設定＞グループ共有機能＞グループ削除
        $memo = "";
        $memo .= "グループ名：".$this->session->getParameter("log_g_name")."\n";
        $memo .= "共有フォルダ名：".$this->session->getParameter("log_f_name");
        
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        //sessionを削除
        $this->session->removeParameter("id");
        $this->session->removeParameter("log_g_name");
        $this->session->removeParameter("log_f_name");
        $this->session->removeParameter("log_del_span");

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
    function setMdbGroupsusers($MdbGroupsusers) {
        $this->MdbGroupsusers = $MdbGroupsusers;
    }
    function setDtoFolder($DtoFolder) {
        $this->DtoFolder = $DtoFolder;
    }
    function setMdbFolder($MdbFolder) {
        $this->MdbFolder = $MdbFolder;
    }
    function setMdbUpload($MdbUpload) {
        $this->MdbUpload = $MdbUpload;
    }
    function setGroupDto($groupDto) {
        $this->groupDto = $groupDto;
    }
    function setMdbCollect($MdbCollect) {
        $this->MdbCollect = $MdbCollect;
    }
    function setGroupLogic($GroupLogic) {
        $this->GroupLogic = $GroupLogic;
    }
    function setDtoLog ( &$DtoLog ) {
        $this->DtoLog =& $DtoLog;
    }
    function setMdbLog ( &$MdbLog ) {
        $this->MdbLog =& $MdbLog;
    }
    function setDtoGroupsadminusers($DtoGroupsadminusers) {
        $this->DtoGroupsadminusers = $DtoGroupsadminusers;
    }
    function setMdbGroupsadminusers($MdbGroupsadminusers) {
        $this->MdbGroupsadminusers = $MdbGroupsadminusers;
    }
    function setMdbGroupsAccess($MdbGroupsAccess) {
        $this->MdbGroupsAccess = $MdbGroupsAccess;
    }
    function setDtoAdminUser($DtoAdminUser) {
        $this->DtoAdminUser = $DtoAdminUser;
    }
    function setAdminUserLogic($AdminUserLogic) {
        $this->AdminUserLogic = $AdminUserLogic;
    }
    function setMdbUser ($MdbUser)    
    {
        $this->MdbUser = $MdbUser;
    }

    // getter
    function getGroupInfo(){
        return $this->groupDto;
    }
    function getMode()
    {
        return "削除";
    }
}
?>

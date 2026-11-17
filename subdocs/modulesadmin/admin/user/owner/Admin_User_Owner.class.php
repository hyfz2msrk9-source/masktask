<?php
require_once(MODULE_DIR . '/common/common.php');
/**
 * [機能説明] ユーザ管理 - 管理者の変更
 * @updater
 *
 * @package     WebFile
 * @version     3.?.?
 * @author
 * @copyright   KeyPoint
 * @access      public
 */

class Admin_User_Owner {

    var $session;
    var $DtoUser;
    var $MdbUser;
    var $DtoAdminUser;
    var $MdbAdminUser;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_User_Ownerが実行されました", "Admin_User_Owner#execute");

        if (!preg_match("/^[0-9]+$/", $this->DtoUser->getUser_id())) {
            return "error";
        }
        $this->MdbUser->getUserInfo($this->DtoUser);

        //ユーザ情報が取得できなかったらエラー
        if ($this->DtoUser->getUser_id() == '' || $this->DtoUser->getDel_flg() == '1') {
            return "error";
        }
        $this->session->setParameter("user_id",$this->DtoUser->getUser_id());
        $this->session->setParameter("log_owner_admin_id",$this->DtoUser->getOwner_admin_id());

        return "success";
    }
    //************************************************************
    // setter
    //************************************************************
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoUser($DtoUser) {
        $this->DtoUser = $DtoUser;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = $MdbUser;
    }
    function setDtoAdminUser($DtoAdminUser) {
        $this->DtoAdminUser = $DtoAdminUser;
    }
    function setMdbAdminUser($MdbAdminUser) {
        $this->MdbAdminUser = $MdbAdminUser;
    }
    //************************************************************
    // getter
    //************************************************************
    // 管理者リスト
    function getOwner_admin_list() {
        $select_list = array();
        $order = array("account");
        $list = $this->MdbAdminUser->getAdminUserSelect();
        foreach ($list as $key => $val) {
            $select_list[$val["admin_id"]] = $val["account"];
        }
        return $select_list;
    }
    function getOwner_admin_id() {
        return $this->DtoUser->getOwner_admin_id();
    }
    function getOwner_name() {
        $this->DtoAdminUser->setAdmin_id($this->DtoUser->getOwner_admin_id());
        $this->MdbAdminUser->getAdminuserInfo($this->DtoAdminUser);
        return $this->DtoAdminUser->getAccount();
    }
    function getUser_name() {
        return $this->DtoUser->getUser_name();
    }
    function getMailaddr() {
        return $this->DtoUser->getMailaddr();
    }

}
?>

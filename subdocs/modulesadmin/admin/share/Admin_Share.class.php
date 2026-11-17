<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Share {

    var $session;
    var $MdbSection;
    var $DtoConfigs;
    var $MdbConfigs;
    var $MdbGroups;
    var $MdbUpload;
    var $DtoAdminUser;

    var $use_capacity;
    var $group_count;

    function execute() {

        $log =& LogFactory::getLog();
        $log->debug("Admin_Configが実行されました","Admin_Config#execute");

        $this->MdbConfigs->getList($this->DtoConfigs);

        //割当て機能設定毎に使用容量を取得
        if(!defined('SHARE_SPACE_FLG') || SHARE_SPACE_FLG == 0) {
            //割当て機能あり
            $this->use_capacity = $this->MdbGroups->getUseCapacity();
        } else {
            //割当て機能なし : 全使用容量取得
            $this->use_capacity = $this->MdbUpload->getAllUserShareCapacity();
        }
        $this->group_count = $this->MdbGroups->getCount($this->DtoAdminUser);

        return "success";
    }

    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setMdbSection($MdbSection){
        $this->MdbSection = $MdbSection;
    }
    function setDtoConfigs($DtoConfigs){
        $this->DtoConfigs = $DtoConfigs;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setMdbGroups($MdbGroups) {
        $this->MdbGroups = $MdbGroups;
    }
    function setMdbUpload($MdbUpload) {
        $this->MdbUpload = $MdbUpload;
    }
    function setDtoAdminUser($DtoAdminUser) {
        $this->DtoAdminUser = $DtoAdminUser;
    }

    // getter
    function getConfig() {
        return $this->DtoConfigs->configslist;
    }
    function getShareMaxCapacity()
    {
        if (defined('SHARE_MAX_CAPACITY')) {
            return SHARE_MAX_CAPACITY."G";
        } else {
            return "SHARE_MAX_CAPACITYを設定してください。";
        }
    }
    function getUseCapacity()
    {
        return StrChange::CapacityUnit($this->use_capacity);
    }
    function getGroupCount()
    {
        return $this->group_count;
    }
    function getSectionCount() {
        $where = 0;
        if( $this->session->getParameter("login_admin_level") < 5 ) {
            $where = array( "subadmin_editable_flg=1" );
        }
        return $this->MdbSection->getSectionCount($where);
    }
}
?>
<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Access {
    var $session;
    var $DtoConfigs;
    var $MdbConfigs;
    var $DtoAccess;
    var $AccessDto;
    var $MdbAccess;
    var $AccessLogic;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Accessが実行されました","Admin_Config_Access#execute");

        //Configを拾う
        $this->MdbConfigs->getList($this->DtoConfigs);
        $list = $this->DtoConfigs->getConfigslist();

        //sessionに記入
        $this->session->setParameter("access_admin_flg", $list['access_admin_flg']);
        $this->session->setParameter("access_user_flg",  $list['access_user_flg']);
        $this->session->setParameter("access_other_flg", $list['access_other_flg']);
        $this->session->setParameter("access_api_flg",   $list['access_api_flg']);

        //sessionクリア
        $this->session->removeParameter("area");

        return "success";
    }
    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoConfigs($DtoConfigs){
        $this->DtoConfigs = $DtoConfigs;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setMdbAccess($MdbAccess) {
        $this->MdbAccess = $MdbAccess;
    }
    function setAccessDto($AccessDto) {
        $this->AccessDto = $AccessDto;
    }
    function setAccessLogic($AccessLogic) {
        $this->AccessLogic = $AccessLogic;
    }

    // getter
    function getConfig() {
        return $this->DtoConfigs->configslist;
    }

    function getAccess_Flg_Options()
    {
        return GlobalArray::getAccess_Flg_Options();
    }

    // 管理者IP一覧
    function getAccessList_Admin()
    {
        return $this->AccessLogic->getAccessKeyList($this->MdbAccess, 1);
    }

    // ユーザIP一覧
    function getAccessList_User()
    {
        return $this->AccessLogic->getAccessKeyList($this->MdbAccess, 2);
    }

    function getAccessList_Internet_User()
    {
        return $this->AccessLogic->getAccessKeyList($this->MdbAccess, 2, 1);
    }

    // 引取・ダウンロードIP一覧
    function getAccessList_Other()
    {
        return $this->AccessLogic->getAccessKeyList($this->MdbAccess, 3);
    }

    // 引取・ダウンロードIP一覧
    function getAccessList_Api()
    {
        return $this->AccessLogic->getAccessKeyList($this->MdbAccess, 4);
    }

}
?>

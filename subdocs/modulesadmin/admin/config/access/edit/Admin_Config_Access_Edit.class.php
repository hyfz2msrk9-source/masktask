<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Access_Edit {
    var $session;
    var $DtoAccess;
    var $MdbAccess;
    var $AccessDto;
    var $AccessLogic;
    var $MdbConfigs;
    var $DtoConfigs;

    var $area;
    function execute() {

        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Access_Editが実行されました","Admin_Config_Access_Edit#execute");

        // 不正なエリアを指定
        if (!array_key_exists($this->area, GlobalArray::getAccessArea_Options())) {
            return "error";
        }
        if (API_FLG == 0 and $this->area == 4)
        {
            return "error";
        }

        // セッションセット
        if($this->area){
            $this->session->setParameter("area", $this->area);
        }

        return "success";
    }

    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoAccess($DtoAccess){
        $this->DtoAccess = $DtoAccess;
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
    function setDtoConfigs($DtoConfigs){
        $this->DtoConfigs = $DtoConfigs;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setArea($area) {
        $this->area = $area;
    }

    // getter
    function getConfig() {
        return $this->DtoConfigs->configslist;
    }
    function getArea()
    {
        return $this->area;
    }
    function getIp_addr() {
        return $this->DtoAccess->getIp_addr();
    }
    function getInternet_ip_addr() {
        return $this->DtoAccess->getInternet_ip_addr();
    }

    // IPアドレス一覧取得
    function getAccessList()
    {
        return $this->AccessLogic->getAccessKeyList($this->MdbAccess, $this->DtoAccess->getArea());
    }

    function getInternetAccessList()
    {
        return $this->AccessLogic->getAccessKeyList($this->MdbAccess, $this->DtoAccess->getArea(), 1);
    }


}
?>

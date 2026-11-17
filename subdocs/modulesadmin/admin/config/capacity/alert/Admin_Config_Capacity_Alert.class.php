<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Capacity_Alert {
    var $session;
    var $MdbConfigs;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Capacity_Alertが実行されました", "Admin_Config_Capacity_Alert#execute");

        $this->session->setParameter("capacity_alert_notice",       $this->getCapacity_alert_notice());
        $this->session->setParameter("capacity_alert_notice_addr",  $this->getCapacity_alert_notice_addr());
        $this->session->setParameter("notify_personal_folder_usage",  $this->getNotify_personal_folder_usage());
        $this->session->setParameter("notify_share_folder_usage",  $this->getNotify_share_folder_usage());

        return "success";
    }
    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }

    // getter
    function getCapacity_alert_notice()
    {
        return $this->MdbConfigs->getDirectValue("capacity_alert_notice");
    }
    function getCapacity_alert_notice_addr()
    {
        return $this->MdbConfigs->getDirectValue("capacity_alert_notice_addr");
    }
    function getNotify_personal_folder_usage()
    {
        return $this->MdbConfigs->getDirectValue("notify_personal_folder_usage");
    }
    function getNotify_share_folder_usage()
    {
        return $this->MdbConfigs->getDirectValue("notify_share_folder_usage");
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

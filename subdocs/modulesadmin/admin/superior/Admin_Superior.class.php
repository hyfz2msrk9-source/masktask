<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Superior {
    var $session;
    var $list_back_flg;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Superior executed.","Admin_Superior#execute");
        if(!$this->list_back_flg) {
            $this->session->removeParameter('query');
            $this->session->removeParameter('qtype');
            $this->session->removeParameter('page');
            $this->session->removeParameter('sortname');
            $this->session->removeParameter('sortorder');
            $this->session->removeParameter('rp');
        }
        return "success";
    }

    //setter
    function setSession($session) {
        $this->session = $session;
    }
    function setList_back_flg($list_back_flg) {
        $this->list_back_flg = $list_back_flg;
    }
}
?>
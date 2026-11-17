<?php
  /************************************************************
   * last update :
   ************************************************************/
require_once( MODULE_DIR . "/common/common.php");

class Admin_Superior_Import {
    var $session;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Superior_Importが実行されました","Admin_Superior_Import#execute");

        return "success";
    }
    // setter
    function setSession($session) {
        $this->session = $session;
    }

    function getMaxSuperior() {
       return ( defined('MAX_SUPERIOR') ? MAX_SUPERIOR : 5 );
    }
}
?>
<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Archive {
    var $session;
    var $MdbArchive;
    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Archiveが実行されました","Admin_Archive#execute");

        return "success";
    }

    // 一番古いデータの情報
    function getMin_ArchiveLog_Date() {
        $min   = $this->MdbArchive->getSearchStartYear("1");
        $min_s = strtotime($min);
        return $min_s;
    }

    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setMdbArchive($MdbArchive) {
        $this->MdbArchive = $MdbArchive;
    }

    // getter
    function getSearchStartYear() {
        return $this->MdbArchive->getSearchStartYear();
    }
    function getSearchEndYear() {
        return $this->MdbArchive->getSearchEndYear();
    }
    function getSearchStartDate() {
        if ($this->getMin_ArchiveLog_Date()==strtotime(date("Y-m-d"))) {
            return date("Y-m-d");
        }
        return date("Y-m-d",mktime(0,0,0,date("m"),date("d")-1,date("Y"))); // １日前
    }
    function getSearchEndDate() {
        $min_d = $this->getMin_ArchiveLog_Date();
        if ($min_d==strtotime(date("Y-m-d"))) {
            return date("Y-m-d");
        } elseif ($min_d > (mktime(0,0,0,date("m"),date("d")-31,date("Y")))) {
            return date("Y-m-d",$min_d);
        }
        return date('Y-m-d',mktime(0,0,0,date("m"),date("d")-31,date("Y"))); // 31日前
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
<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Share_Upenddt {
    var $session;
    var $end_dt;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Share_Upenddtが実行されました","Admin_Config_Share_Upenddt#execute");

        //日付チェック
        if (!preg_match("|\d{4}-\d{2}-\d{2}|", $this->end_dt)) {
            echo "error";
            exit;
        }

        if(SHARE_FOLDER_TERM_DEFAULT_SPAN === 0) {
            echo $this->end_dt;
        } else {
            //groupsの有効期限が切れているかどうか
            $now_dt = date("Y-m-d");
            $time = (SHARE_FOLDER_TERM_DEFAULT_SPAN) * 24 * 60 * 60;
            $moto = $this->session->getParameter("log_end_dt");
            if(!is_null($moto)) {
                if($now_dt < $moto) {
                    echo date("Y-m-d" , strtotime($moto) + $time);
                } else {
                    echo date("Y-m-d" , strtotime($now_dt) + $time);
                }
            } else {
                echo date("Y-m-d" , strtotime($this->end_dt) + $time);
            }
        }
        exit;
    }

    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setEnd_dt($end_dt) {
        $this->end_dt = $end_dt;
    }
}
?>
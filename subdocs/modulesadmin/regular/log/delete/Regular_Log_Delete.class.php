<?php
require_once( MODULE_DIR . "/common/common.php");

// WebFile Lite:履歴ログの自動削除
class Regular_Log_Delete {
    var $MdbLog;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Regular_Log_Deleteが実行されました。", "Regular_Log_Delete#execute");

        if (is_numeric(AUTO_DEL_LOG_TERM) && AUTO_DEL_LOG_TERM > 0) {
            $this->MdbLog->deleteLogInterval(AUTO_DEL_LOG_TERM . " DAY");
        }
        exit;
    }

    function setMdbLog($MdbLog){
        $this->MdbLog =& $MdbLog;
    }
}

?>
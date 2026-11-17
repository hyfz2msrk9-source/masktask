<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Log_Regular_Del {
    var $span_value;
    var $DtoLog;
    var $MdbLog;
    
    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Log_Regular_Delが実行されました。", "Admin_Log_Regular_Dl#execute");

        $container =& DIContainerFactory::getContainer();
        $actionChain =& $container->getComponent("ActionChain");
        $errorList =& $actionChain->getCurErrorList();

        // 不正文字はNG
        if( preg_match("/[^0-9]/",$this->span_value )) {
            $errorList->add('Admin_Log_Regular_Del::error', '不正なファイルが指定されました。');
            return "error";
        }

        //ログファイルの取得
        $year = substr($this->span_value,0,4);
        $month = substr($this->span_value,4,2);

        $filename1 = $year."-".$month."-admin-log.csv";
        $filename2 = $year."-".$month."-user-log.csv";

        // ファイルがない場合は念のためにNG
        if( ! is_file(BASE_DIR."/htdocs".HISTORY_PATH."/".$filename1) &&
            ! is_file(BASE_DIR."/htdocs".HISTORY_PATH."/".$filename2) ) {
            $errorList->add('Admin_Log_Regular_Del::error', '不正なファイルが指定されました。');
            return "error";
        }

        return "success";

    }

    function setSpan_value($span_value){
        $this->span_value =& $span_value;
    }
    function setDtoLog(&$DtoLog) {
        $this->DtoLog =& $DtoLog;
    }
    function setMdbLog(&$MdbLog) {
        $this->MdbLog =& $MdbLog;
    }

    function getSpan_value() {
        return $this->span_value;
    }
}

?>
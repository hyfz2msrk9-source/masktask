<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Log_Regular_Del_Done {
    var $span_value;
    var $DtoLog;
    var $MdbLog;
    
    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Log_Regular_Del_Doneが実行されました。", "Admin_Log_Regular_Del_Done#execute");

        $container =& DIContainerFactory::getContainer();
        $actionChain =& $container->getComponent("ActionChain");
        $errorList =& $actionChain->getCurErrorList();

        // 不正文字はNG
        if( preg_match("/[^0-9]/",$this->span_value )) {
            $errorList->add('Admin_Log_Regular_Del_Done::error', '不正なファイルが指定されました。');
            return "error";
        }

        //ログファイルの削除
        $year = substr($this->span_value,0,4);
        $month = substr($this->span_value,4,2);

        $date = $year."-".$month;
        $filename1 = $date."-admin-log.csv";
        $filename2 = $date."-user-log.csv";
        $filename3 = $date."-group-log.csv";

        // ファイルがない場合は念のためにNG
        if (is_file(BASE_DIR."/htdocs".HISTORY_PATH."/".$filename1)) {
            unlink(BASE_DIR."/htdocs".HISTORY_PATH."/".$filename1);
        }
        if (is_file(BASE_DIR."/htdocs".HISTORY_PATH."/".$filename2)) {
            unlink(BASE_DIR."/htdocs".HISTORY_PATH."/".$filename2);
        }
        // ゴミが残ってしまう可能性があるので、ファイルがあった場合は、グループ共有機能を問わずに削除する
        if (is_file(BASE_DIR."/htdocs".HISTORY_PATH."/".$filename3)) {
            unlink(BASE_DIR."/htdocs".HISTORY_PATH."/".$filename3);
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
}

?>
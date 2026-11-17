<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Log_Regular_Dl {
    var $session;
    var $MdbLog;
    var $DtoLog;

    var $span_value;
    var $dl_flg;
    
    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Log_Regular_Dlが実行されました。", "Admin_Log_Regular_Dl#execute");

        $container =& DIContainerFactory::getContainer();
        $actionChain =& $container->getComponent("ActionChain");
        $errorList =& $actionChain->getCurErrorList();

        // 不正文字はNG
        if( preg_match("/[^0-9]/",$this->span_value )) {
            $errorList->add('Admin_Log_Reqular_Dl::error', '不正なファイルが指定されました。');
            return "error";
        }

        //ログファイルのDL
        $filename = "";
        $year = substr($this->span_value,0,4);
        $month = substr($this->span_value,4,2);

        if ($this->dl_flg == 1) {
            $filename = $year."-".$month."-admin-log.csv";
        } else if ($this->dl_flg == 2) {
            $filename = $year."-".$month."-user-log.csv";
        // グループのダウンロードは共有機能が有効時のみ
        } else if ($this->dl_flg == 3 && (defined("SHARE_GROUP_FLG") && SHARE_GROUP_FLG) ) {
            $filename = $year."-".$month."-group-log.csv";
        // 上記に該当しない場合はエラー
        } else {
            return "error";
        }

        // ファイルがない場合は念のためにNG
        if( ! is_file(BASE_DIR."/htdocs".HISTORY_PATH."/".$filename)) {
            $errorList->add('Admin_Log_Reqular_Dl::error', '不正なファイルが指定されました。');
            return "error";
        }

        $browser = new CrossBrowser();
        $size = filesize(BASE_DIR."/htdocs".HISTORY_PATH."/".$filename);
        $type = filetype(BASE_DIR."/htdocs".HISTORY_PATH."/".$filename);
        $browser->DownloadHeader($filename,$size,$type);
        echo file_get_contents(BASE_DIR."/htdocs".HISTORY_PATH."/".$filename);

        // Log
        $history_id = 107; // 107:ログ管理＞ログダウンロード
        $memo_parts = "";
        if ($this->dl_flg == 1) {
            $memo_parts = "管理者";
        } else if ($this->dl_flg == 2) {
            $memo_parts = "ユーザ";
        } else if ($this->dl_flg == 3) {
            $memo_parts = "グループ";
        }
        $memo  = "";
        $memo .= "指定年月：".$year."年".$month."月\n";
        $memo .= "対象：".$memo_parts."\n";
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        exit;
    }

    function setSession($session) {
        $this->session =& $session;
    }
    function setSpan_value($span_value){
        $this->span_value =& $span_value;
    }
    function setDl_flg($dl_flg) {
        $this->dl_flg =& $dl_flg;
    }
    function setMdbLog($MdbLog){
    	$this->MdbLog =& $MdbLog;
    }
    function setDtoLog($DtoLog){
    	$this->DtoLog =& $DtoLog;
    }
}

?>
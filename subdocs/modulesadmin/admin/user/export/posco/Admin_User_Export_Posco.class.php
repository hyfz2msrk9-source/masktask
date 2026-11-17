<?php
require_once(MODULE_DIR . '/common/common.php');

class Admin_User_Export_Posco {
    var $session;
    var $DtoLog;
    var $MdbLog;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_User_Exportが実行されました", "Admin_User_Export#execute");

        if(defined("POSCO_USER_CSV_FLG") && POSCO_USER_CSV_FLG == 1) {
            //ファイル存在チェック
            $filename = POSCO_USER_EXPORT_DIR."/".POSCO_USER_EXPORT_FILE;
            if(file_exists($filename)) {
                $browser = new CrossBrowser();
                $name = POSCO_USER_EXPORT_FILE;
                $file_name = $browser->FilenameExchange($name);
                $size = filesize($filename);
                $type = filetype($filename);
                 $browser->DownloadHeader($file_name,$size,$type);
                 @readfile($filename);
            } else {
                return "error";
            }
        } else {
            return "error";
        }

        // Log
         $history_id = 116; // 116:ユーザ管理＞ユーザ一覧のエクスポート
         $memo = "WebPoscoユーザCSVダウンロード";
         $this->DtoLog->setUser_id("");
         $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
         $this->DtoLog->setFile_id("");
         $this->DtoLog->setHistory_id($history_id);
         $this->DtoLog->setMemo($memo);
         $this->MdbLog->Logging($this->DtoLog);
         exit;
    }

    //************************************************************
    // setter
    //************************************************************
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoLog($DtoLog){
    	$this->DtoLog = $DtoLog;
    }    
    function setMdbLog($MdbLog){
    	$this->MdbLog = $MdbLog;
    }
}
?>

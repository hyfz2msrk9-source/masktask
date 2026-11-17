<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Log_Dl {

    var $session;
    var $span;
    var $DtoUser;
    var $UserLogic;
    var $MdbUser;
    var $MdbLog;
    var $MdbUpload;
    var $MdbCollect;
    var $MdbDownload;
    var $MdbDownloadMailer;
    var $MdbMchoice;
    var $MdbAdminuser;
    var $DtoLog;
    var $DtoUpload;
    var $DtoCollect;
    var $loguser_flg;
    var $MdbFolder;
    var $DtoFolder;
    var $ListLogic;
    var $MdbDeliveryto;
    var $MdbDeliveryfile;

    function execute() {
        // limitを600へ
        set_time_limit(600);

        $log =& LogFactory::getLog();
        $log->debug("Admin_Log_Dlが実行されました。", "Admin_Log_Dl#execute");

        // spanを整形
        /*
        $year = substr($this->span,0,4);
        $month = substr($this->span,4,2);
        $start = $year."-".$month."-01";
        $end   = date("Y-m-d",mktime(0,0,0, (int)$month+1, 1, (int)$year));
        */

        // 直近24時間以内のログ
        $end = date("Y-m-d H:i:s");
        $stime = strtotime($end) - 86400;
        $start = date("Y-m-d H:i:s",$stime);
        // ダウンロードタイプのチェック
        if (!array_key_exists($this->loguser_flg, $this->ListLogic->getLoguser())) {
            return "error";
        }
        if ($this->loguser_flg == 1) {
            $this->MdbLog->getSpanLogList($this->DtoLog,$start,$end);
        } else if ($this->loguser_flg == 2) {
            $this->MdbLog->getSpanLogList2($this->DtoLog,$start,$end);
        } else if ($this->loguser_flg == 3) {
            $this->MdbLog->getSpanLogList3($this->DtoLog,$start,$end);
        } else if ($this->loguser_flg == 4) {
            $this->MdbLog->getSpanLogList4($this->DtoLog,$start,$end);
        }
        if ($this->DtoLog->getLogList()) {
            $this->UserLogic->showlistall($this->DtoLog,
                                          $this->MdbUpload,$this->DtoUpload,
                                          $this->MdbCollect,$this->DtoCollect,
                                          $this->MdbMchoice,$this->MdbDownload,
                                          $this->MdbUser,$this->MdbAdminuser,
                                          $this->MdbFolder,$this->DtoFolder,
                                          $this->MdbDownloadMailer,$this->MdbDeliveryto,$this->MdbDeliveryfile);
        }
        $this->UserLogic->logAllCsv($this->DtoLog,date("YmdHi",strtotime($end)),$this->loguser_flg);

        // Log
        $history_id = 107; // 107:ログ管理＞ログダウンロード
        $memo_parts = "";
        if ($this->loguser_flg == 1) {
            $memo_parts = "全て";
        } else if ($this->loguser_flg == 2) {
            $memo_parts = "管理者";
        } else if ($this->loguser_flg == 3) {
            $memo_parts = "ユーザ";
        } else if ($this->loguser_flg == 4) {
            $memo_parts = "グループ";
        }
        $memo  = "";
        $memo .= "対象期間：".$start."〜".$end."\n";
        $memo .= "対象：".$memo_parts."\n";
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        exit;
        //return "success";
    }
    function setSession($session) {
        $this->session =& $session;
    }
    function setDtoUser($DtoUser) {
        $this->DtoUser =& $DtoUser;
    }
    function setUserLogic($UserLogic) {
        $this->UserLogic =& $UserLogic;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser =& $MdbUser;
    }
    function setMdbLog($MdbLog){
    	$this->MdbLog =& $MdbLog;
    }
    function setMdbUpload($MdbUpload){
    	$this->MdbUpload =& $MdbUpload;
    }
    function setMdbCollect($MdbCollect){
    	$this->MdbCollect =& $MdbCollect;
    }
    function setMdbDownload($MdbDownload){
    	$this->MdbDownload =& $MdbDownload;
    }
    function setMdbDownloadMailer($MdbDownloadMailer) {
        $this->MdbDownloadMailer = $MdbDownloadMailer;
    }
    function setMdbMchoice($MdbMchoice){
    	$this->MdbMchoice =& $MdbMchoice;
    }
    function setMdbAdminuser($MdbAdminuser){
    	$this->MdbAdminuser =& $MdbAdminuser;
    }
    function setDtoLog($DtoLog){
    	$this->DtoLog =& $DtoLog;
    }
    function setDtoUpload($DtoUpload){
    	$this->DtoUpload =& $DtoUpload;
    }
    function setDtoCollect($DtoCollect){
    	$this->DtoCollect =& $DtoCollect;
    }
    function setSpan($span) {
        $this->span =& $span;
    }
    function setLoguser_flg($loguser_flg){
        $this->loguser_flg =& $loguser_flg;
    }
    function setMdbFolder($MdbFolder){
    	$this->MdbFolder =& $MdbFolder;
    }
    function setDtoFolder($DtoFolder){
    	$this->DtoFolder =& $DtoFolder;
    }
    function setListLogic(&$ListLogic){
        $this->ListLogic =& $ListLogic;
    }
    function setMdbDeliveryto(&$MdbDeliveryto) {
        $this->MdbDeliveryto =& $MdbDeliveryto;
    }
    function setMdbDeliveryfile(&$MdbDeliveryfile) {
        $this->MdbDeliveryfile =& $MdbDeliveryfile;
    }
    function getSpan() {
        return $this->span;
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

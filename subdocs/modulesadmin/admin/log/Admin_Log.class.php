<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Log {

    var $session;
    var $DtoLog;
    var $MdbLog;
    var $ListLogic;
    var $spanlist;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Logが実行されました。", "Admin_Log#execute");

        $this->MdbLog->getLogSpan($this->DtoLog);

        return "success";
    }

    function setSession($session) {
        $this->session =& $session;
    }
    function setDtoLog(&$DtoLog) {
        $this->DtoLog =& $DtoLog;
    }
    function setMdbLog(&$MdbLog) {
        $this->MdbLog =& $MdbLog;
    }
    function setLoguser(&$loguser){
        $this->loguser =& $loguser;
    }
    function setListLogic(&$ListLogic){
        $this->ListLogic =& $ListLogic;
    }

    function getLogSpan() {
        return $this->DtoLog->getLogspan();
    }
    function getSpanlist() {
        foreach($this->DtoLog->getLogspan() as $val => $key){
            foreach($key as $val2) {
                $value = array_keys($key);
                $year = substr($value[0],0,4);
                $month = substr($value[0],4,2);
                $filename = $year."-".$month;
                $result1 = file_exists("history/".$filename."-admin-log.csv");
                $result2 = file_exists("history/".$filename."-user-log.csv");
                if ($result1 || $result2) {
                    $list['value'] = $value[0];
                    $list['span'] = $val2;
                    $list['user'] =0; $list['admin'] =0;
                    if ($result1) { $list['admin'] = 1; }
                    if ($result2) { $list['user'] = 1; }
                    $this->spanlist[] = $list;
                }
                $result = false;
            }
        }
        return $this->spanlist;
    }
    function getLoguser(){
        return $this->ListLogic->getLoguser();
    }
    function getLoguser_flg(){
        return 1;
    }
    // 最古ログからyear取り出す
    function getLogStartYear() {
        // ログdataの確認
        if ($this->DtoLog->getLogSpan()) {
            // 多重配列なので配列形式に変換
            $list = array();
            foreach($this->DtoLog->getLogspan() as $key){
                foreach($key as $key2 => $val2) {
                    $list[$key2] = $val2;
                }
            }
            // keyに日付(Ym形式)があるので、keyから最古のデータを取得
            $min = min(array_keys($list));
            // 日付を [Ym] → [Y]に
            return substr($min,0,4);
        }
        // なければ現在の年を取得
        return date("Y");
    }
    function getSMSLogCountList() {
        return $this->MdbLog->getSMSLogCountList();
    }
}
?>
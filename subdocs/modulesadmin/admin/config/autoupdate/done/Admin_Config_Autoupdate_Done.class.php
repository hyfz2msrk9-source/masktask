<?php

require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Autoupdate_Done {
    var $session;
    var $mailaddr;
    var $DtoConfigs;
    var $MdbConfigs;

    var $DtoLog;
    var $MdbLog;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Autoupdate_Doneが実行されました","Admin_Config_Autoupdate_Done#execute");

        if(!defined("AUTO_UPDATE_FLG") || (defined("AUTO_UPDATE_FLG") && AUTO_UPDATE_FLG == '0')) {
            return "error";
        }

        // メールアドレス更新
        $this->MdbConfigs->setValue("mail_addr", $this->mailaddr);

        // ログ
        $history_id = 112; // 各種設定
        $memo = "";
        $memo .= "[その他>自動更新用メールアドレスの設定]\n";
        $memo .= "自動更新用送信元メールアドレスの設定";
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id($this->session->getParameter('admin_id'));
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        return "success";
    }

    /***************************************************
     * setter
     **************************************************/
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoConfigs($DtoConfigs) {
        $this->DtoConfigs = $DtoConfigs;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setDtoLog($DtoLog) {
        $this->DtoLog = $DtoLog;
    }
    function setMdbLog($MdbLog) {
        $this->MdbLog = $MdbLog;
    }
    function setMailaddr($mailaddr) {
        $this->mailaddr = $mailaddr;
    }

    /***************************************************
     * getter
     **************************************************/
    function getValue() {
        return $this->mailaddr;
    }

}

?>

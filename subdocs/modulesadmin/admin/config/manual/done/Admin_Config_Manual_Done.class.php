<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Manual_Done {
    var $session;
    var $MdbConfigs;

    var $user_manual_dl_flg;
    var $admin_manual_dl_flg;
    var $DtoLog;
    var $MdbLog;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Manual_Doneが実行されました","Admin_Config_Manual_Done#execute");

        $log->debug($this->user_manual_dl_flg, "Admin_Config_Manual_Done#execute");
        $log->debug($this->admin_manual_dl_flg, "Admin_Config_Manual_Done#execute");
        if (!array_key_exists($this->user_manual_dl_flg, GlobalArray::getManual_Dl_Options())) return "error";
        if (!array_key_exists($this->admin_manual_dl_flg, GlobalArray::getManual_Dl_Options())) return "error";

        // 更新
        $this->MdbConfigs->setValue('user_manual_dl_flg', $this->user_manual_dl_flg);
        $this->MdbConfigs->setValue('admin_manual_dl_flg', $this->admin_manual_dl_flg);

        // LOG
        $memo = "[表示設定＞マニュアルに関する設定]"."\n";
        if ($this->user_manual_dl_flg != $this->session->getParameter("user_manual_dl_flg")) {
            if ($this->user_manual_dl_flg == "1") {
                $flg = "表示する";
            } else {
                $flg = "表示しない";
            }
            $memo .= "ユーザ：マニュアルとヘルプの表示可否：" . $flg . "\n";
        }
        if ($this->admin_manual_dl_flg != $this->session->getParameter("admin_manual_dl_flg")) {
            if ($this->admin_manual_dl_flg == "1") {
                $flg = "表示する";
            } else {
                $flg = "表示しない";
            }
            $memo .= "管理者：マニュアルとヘルプの表示可否：" . $flg . "\n";
        }

        $history_id = "112";
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        //sessionを削除
        $this->session->removeParameter("user_manual_dl_flg");
        $this->session->removeParameter("admin_manual_dl_flg");
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success'
        ]);
        exit;
    }

    // setter
    function setSession($session) {
        $this->session = $session;
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
    function setUser_manual_dl_flg($user_manual_dl_flg) {
        $this->user_manual_dl_flg = $user_manual_dl_flg;
    }
    function setAdmin_manual_dl_flg($admin_manual_dl_flg) {
        $this->admin_manual_dl_flg = $admin_manual_dl_flg;
    }
}
?>

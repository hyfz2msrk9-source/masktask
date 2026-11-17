<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Capacity_Alert_Done {
    var $session;
    var $MdbConfigs;

    var $DtoLog;
    var $MdbLog;

    // request
    var $capacity_alert_notice;
    var $capacity_alert_notice_addr;
    var $notify_personal_folder_usage;
    var $notify_share_folder_usage;
    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Capacity_Alert_Doneが実行されました","Admin_Config_Capacity_Alert_Done#execute");

        // エラー発生
        $container =& DIContainerFactory::getContainer();
        $actionChain =& $container->getComponent("ActionChain");
        $errorList =& $actionChain->getCurErrorList();
        if (EXTRA_SPACE_FLG) {
            if (!$this->capacity_alert_notice && $this->capacity_alert_notice !== "0") {
                $errorList->add(__CLASS__ . "::error", "警告メールを送信する容量設定が正しく入力されていません。");
                return 'input';
            }
            if (! preg_match("/^[0]$|^[1-9][0-9]*$/", $this->capacity_alert_notice)) {
                $errorList->add(__CLASS__ . "::error", "警告メールを送信する容量設定は整数で入力してください。");
                return 'input';
            }
            if ($this->capacity_alert_notice < 0 || $this->capacity_alert_notice > 100) {
                $errorList->add(__CLASS__ . "::error", "警告メールを送信する容量設定は0〜100の範囲で登録してください。");
                return 'input';
            }
        } else {
            //拡張容量がオフなら警告メールを送信しない
            $this->capacity_alert_notice = '0';
        }
        if (($this->notify_personal_folder_usage && $this->notify_personal_folder_usage !== '1') || ($this->notify_share_folder_usage && $this->notify_share_folder_usage !== '1')) {
            $errorList->add(__CLASS__ . "::error", "不正なパラメータを検知しました。");
            return 'input';
        }

        // メールアドレス重複チェック
        $update_data = "";
        $update_memo = "";
        if ($this->capacity_alert_notice_addr) {
            $addrs2 = explode(",", $this->capacity_alert_notice_addr);
            $addrs = array_unique($addrs2);
            foreach ($addrs as $val) {
                if (!$update_data) { $update_data = $val; } else { $update_data.= ",".$val; }
                $update_memo.= "　　".$val."\n";
            }
            $this->capacity_alert_notice_addr = $update_data;
        }

        // 更新
        $this->MdbConfigs->setValue('capacity_alert_notice',   $this->capacity_alert_notice);
        $this->MdbConfigs->setValue('capacity_alert_notice_addr',     $this->capacity_alert_notice_addr);
        $this->MdbConfigs->setValue('notify_personal_folder_usage',     $this->notify_personal_folder_usage);
        if (SHARE_GROUP_FLG) {
            $this->MdbConfigs->setValue('notify_share_folder_usage',     $this->notify_share_folder_usage);
        } else {
            $this->MdbConfigs->setValue('notify_share_folder_usage',     0);
        }

        // Log
        $history_id = 112; // 112:各種設定＞機能設定
        $memo = "";
        if (EXTRA_SPACE_FLG) {
            if ($this->capacity_alert_notice != $this->session->getParameter("capacity_alert_notice")) {
                $aft = $this->capacity_alert_notice;
                $bef = $this->session->getParameter("capacity_alert_notice");
                $memo .= "警告メールを送信する容量設定：" . $bef . "% → " . $aft . "%\n";
            } else {
                $memo .= "警告メールを送信する容量設定：" . $this->capacity_alert_notice . "%\n";
            }
        }
        if ($this->capacity_alert_notice_addr != $this->session->getParameter("capacity_alert_notice_addr")){
            $memo .= "使用状況の通知先：\n";
            $memo .= $update_memo ?: "未設定";
        }
        if (SHARE_GROUP_FLG) {
            $beforeShareNotifySetting = $this->session->getParameter("notify_share_folder_usage") ? 'する' : 'しない';
            $doShareNotifySetting = $this->notify_share_folder_usage ? 'する' : 'しない';
            if ($doShareNotifySetting !== $beforeShareNotifySetting) {
                $notifyShareFolderUsageMessage = "共有フォルダ使用状況の推移を通知：";
                $memo .= $notifyShareFolderUsageMessage . $beforeShareNotifySetting . " → " . $doShareNotifySetting . "\n";
            }
        }
        $beforePersonalNotifySetting = $this->session->getParameter("notify_personal_folder_usage") ? 'する' : 'しない';
        $doPersonalNotifySetting = $this->notify_personal_folder_usage ? 'する' : 'しない';
        if ($doPersonalNotifySetting !== $beforePersonalNotifySetting) {
            $notifyPersonalFolderUsageMessage = "個人フォルダ使用状況の推移を通知：";
            $memo .= $notifyPersonalFolderUsageMessage .  $beforePersonalNotifySetting . " → " . $doPersonalNotifySetting . "\n";
        }
        
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);

        // セッションクリア
        $this->session->removeParameter("capacity_alert_notice");
        $this->session->removeParameter("capacity_alert_notice_addr");
        $this->session->removeParameter("notify_personal_folder_usage");
        $this->session->removeParameter("notify_share_folder_usage");
        if (!$memo) return "success"; //変更がなければLogに残さない
        $this->MdbLog->Logging($this->DtoLog);
        return "success";
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
    function setCapacity_alert_notice($capacity_alert_notice)
    {
        $this->capacity_alert_notice = $capacity_alert_notice;
    }
    function setCapacity_alert_notice_addr($capacity_alert_notice_addr)
    {
        $this->capacity_alert_notice_addr = $capacity_alert_notice_addr;
    }
    function setNotify_share_folder_usage($notify_share_folder_usage)
    {
        $this->notify_share_folder_usage = $notify_share_folder_usage;
    }
    function setNotify_personal_folder_usage($notify_personal_folder_usage)
    {
        $this->notify_personal_folder_usage = $notify_personal_folder_usage;
    }

    // getter
    function getCapacity_alert_notice()
    {
        return $this->capacity_alert_notice;
    }
    function getCapacity_alert_notice_addr()
    {
        return $this->capacity_alert_notice_addr;
    }
    function getNotify_share_folder_usage()
    {
        return $this->notify_share_folder_usage;
    }
    function getNotify_personal_folder_usage()
    {
        return $this->notify_personal_folder_usage;
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

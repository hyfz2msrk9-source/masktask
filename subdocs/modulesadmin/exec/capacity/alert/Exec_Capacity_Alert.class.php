<?php
require_once( MODULE_DIR . "/common/common.php");

// 容量圧迫時のアラート機能
class Exec_Capacity_Alert {
    var $MdbUpload;
    var $MdbCollect;
    var $MdbConfigs;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Exec_Capacity_Alertが実行されました。", "Exec_Capacity_Alert#execute");

        $capacity_limit = $this->getCapacity_alert_notice();
        $notice_addr = $this->getCapacity_alert_notice_addr();

        if (!EXTRA_SPACE_FLG
            || !$capacity_limit
            || empty($notice_addr)) {
            // 拡張容量機能がOFF
            // 容量設定値が0
            // メールアドレス設定なしなら終了
            exit;
        }

        $mail = new MailSend();

        $all_size = 0;

        $all_size = $this->MdbUpload->getAllUserCapacity()+$this->MdbCollect->getUpMaxSize_all();
        $sys_size = SYS_MAX_SIZE * 1024 * 1024 * 1024;

        $size_parcentage = ($all_size/$sys_size) * 100;
        $size_parcentage = number_format($size_parcentage, 2);

        if($size_parcentage > (float)$capacity_limit){
            $all_size = StrChange::CapacityUnit($all_size);
            $sys_size = StrChange::CapacityUnit($sys_size);

            $memo = "";
            $memo .= "システム容量の使用率が".$capacity_limit."%を超えています。\n\n";
            $memo .= "[現在のご利用容量]\n";
            $memo .= $all_size."B / ".$sys_size."B（".$size_parcentage."%）\n";

            $mailaddrs = explode(",", $notice_addr);
            foreach ($mailaddrs as $mailaddr) {
                $mail->CapacityAlertMail(trim($mailaddr), $memo);
            }
        }

        exit;
    }

    function setMdbUpload($MdbUpload){
        $this->MdbUpload =& $MdbUpload;
    }
    function setMdbCollect($MdbCollect){
        $this->MdbCollect =& $MdbCollect;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }

    // getter
    function getCapacity_alert_notice()
    {
        return $this->MdbConfigs->getDirectValue("capacity_alert_notice");
    }
    function getCapacity_alert_notice_addr()
    {
        return $this->MdbConfigs->getDirectValue("capacity_alert_notice_addr");
    }
}

?>

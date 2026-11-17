<?php
require_once( MODULE_DIR . "/common/common.php");

/***************************************************************
 *　アーカイブファイルの定期削除
 * （update: 2010-03-12 kp-sawada）
 ***************************************************************/
class Admin_Archive_Regular_Delete {
    var $ArchiveLogic;
    var $MdbConfigs;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Archive_Regular_Deleteが実行されました","Admin_Archive_Regular_Delete#execute");

        $exec_flg = 0; // 必須。消さないで。

        // cron外から呼ばれたときの引数処理
        $arg = array();
        if (isset($_SERVER['argv'])) {
            $arg = $_SERVER['argv'];
            if (isset($arg[1])) {
                $exec_flg = 1;
                $term_start = $arg[1]; // 削除スタート日
                $term_end   = $arg[2]; // 削除エンド日
            }
        }

        // 警告メール送信準備
        $mail = new MailSend();
        $mailaddr = $this->ArchiveLogic->getArchive_notice_addr(); // 宛先

        // プロセス監視開始
        $filepath = realpath(EVI_PID_PATH."/archive_del_pid");
        if ($filepath === false) {
            $filepath = EVI_PID_PATH."/archive_del_pid";
        }
        $starttime = date("Y-m-d H:i:s");
        $proc_res = $this->ArchiveLogic->startProcLock($filepath);
        if ($proc_res == "notice") {
            if ($mailaddr) {
                foreach($mailaddr as $mailaddress) {
                    $mail->Evi_Timeout_Mail($mailaddress,"アーカイブファイルの定期削除",$starttime);
                }
            }
        }

        /*
        // プロセスが複数立ち上がるのを阻止 ------------------------------------
        $starttime = date("Y-m-d H:i:s");

        $pid = getmypid();
        $filename = realpath(EVI_PID_PATH)."/archive_del.pid";
        $lockname = $filename."_lock";
        $timeout = EVI_PROC_TIMEOUT;
        $sleeptime = 0.1;

        // lockファイルが24時間以上前に作ったファイルなら消す
        if (file_exists($lockname)) {
            if (date("Y-m-d H:i:s", filemtime($lockname)) <= date("Y-m-d H:i:s", strtotime('-1day'))) {
                @unlink($lockname);
            }
        }

        // ロック中のファイルも、ロックされてないファイルも、両方なければ作る
        if (!file_exists($filename) && !file_exists($lockname)) {
            $fp = fopen($filename, "w");
            fwrite($fp,$pid);
            fclose($fp);
        }

        // ロックファイルが見つかるまで回る
        $start = microtime(true);
        while(!file_exists($filename)){
            if(microtime(true) - $start > $timeout){
                //タイムアウト時間を過ぎたので失敗メールを飛ばす
                if ($mailaddr) {
                    foreach($mailaddr as $mailaddress) {
                        $mail->Evi_Timeout_Mail($mailaddress,"アーカイブファイルの定期削除",$starttime);
                    }
                }
                exit();
            }
            usleep($sleeptime * 1000 * 1000);
        }

        // エラーにならなければファイルをリネーム（ロック）
        rename($filename,$lockname);
        $fp = fopen($lockname, "w");
        fwrite($fp,$pid);
        fclose($fp);
        // ---------------------------------------------------------------------
        */

        // 定期削除のとき
        if (!$exec_flg) {

            // アーカイブの削除を始めます。 ------------------------------------
            // 保管期限が指定されてるかどうか。
            $save_days = 0;
            $save_days = $this->MdbConfigs->getDirectValue("archive_storage_span");

            // 保管期限を過ぎてるものを消す
            if ($save_days) {
                $this->ArchiveLogic->deleteArchive($save_days);
            }

            // 削除率が設定されてるかどうか
            $delete = $this->MdbConfigs->getDirectValue("evi_archive_autodelete");
            if ($delete) {
                // 容量チェック
                $result = $this->ArchiveLogic->checkArchiveCapacity(0);
                // 容量が削除容量を超えてる
                if ($result[0] <> "true" && $result[0] <> "notice") {
                    $this->ArchiveLogic->deleteOverArchive();
                }
            }

            // もう一回容量チェック
            $result = null;
            $result = $this->ArchiveLogic->checkArchiveCapacity(0);
            // 容量が警告容量を超えてる
            if ($result[0] == "notice") {
                // メールを飛ばす
                if ($mailaddr) {
                    foreach($mailaddr as $mailaddress) {
                        $mail->Evi_Autonotice_Mail($mailaddress,$result[1]);
                    }
                }
            }

            // エクスポートの削除を始めます。 ------------------------------------
            // 保管期限が指定されてるかどうか。
            $save_days2 = 0;
            $save_days2 = $this->MdbConfigs->getDirectValue("export_storage_span");

            // 保管期限を過ぎてるものを消す
            if ($save_days2) {
                $this->ArchiveLogic->deleteExport($save_days2);
            }

            $this->ArchiveLogic->checkExportCapacity(0);

            // 削除率が設定されてるかどうか
            $ex_delete = $this->MdbConfigs->getDirectValue("evi_archive_export_autodelete");
            if ($ex_delete) {
                // 容量チェック
                $result = $this->ArchiveLogic->checkExportCapacity(0);
                // 容量が削除容量を超えてる
                if ($result[0] <> "true" && $result[0] <> "notice") {
                    $this->ArchiveLogic->deleteOverExport();
                }
            }

        // 期間指定削除のとき
        } else {
            if ($term_start && $term_end) {
                $this->ArchiveLogic->deleteTermExport($term_start,$term_end);
            } else {
                $this->ArchiveLogic->endProcLock($filepath);
                //unlink($lockname);
                exit();
            }
        }

        // もう一度容量チェック
        $result = null;
        $result = $this->ArchiveLogic->checkExportCapacity(0);
        // 容量が警告容量を超えてる
        if ($result[0] == "notice") {
            // メールを飛ばす
            if ($mailaddr) {
                foreach($mailaddr as $mailaddress) {
                    $mail->Evi_Autonotice_Mail($mailaddress,$result[1],"export");
                }
            }
        }

        // ロックファイルを消す（ロック解除）
        $this->ArchiveLogic->endProcLock($filepath);
        exit();
    }

    // setter
    function setArchiveLogic($ArchiveLogic) {
        $this->ArchiveLogic = $ArchiveLogic;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }


}
?>

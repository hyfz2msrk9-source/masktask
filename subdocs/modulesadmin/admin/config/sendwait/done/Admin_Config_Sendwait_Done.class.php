<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Sendwait_Done {
    var $session;
    var $DtoConfigs;
    var $MdbConfigs;
    var $DtoLog;
    var $MdbLog;
    var $MdbMchoice;
    var $DtoGroups;
    var $MdbGroups;

    var $send_wait_flg;
    var $dl_start_time_flg;
    var $dl_start_time_value;
    var $send_collect_start_time_value;
    var $send_collect_start_time_flg;
    var $up_start_time_flg;
    var $up_start_time_value;
    var $token;

    function execute() {

        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Sendwait_Doneが実行されました","Admin_Config_Sendwait_Done#execute");

        $sendwait_error_response = function ($message = "不正な値が入力されました。") {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => $message,
                'token_name' => 'SendwaitEditToken',
                'token_value' => $this->token->getValue('SendwaitEditToken')
            ]);
            exit;
        };


        //入力チェック
        // 送信待機が有効時のみ
        if (SEND_WAIT_FLG) {
            if (!array_key_exists($this->send_wait_flg, GlobalArray::getSend_Wait_Options())) $sendwait_error_response("不正な値が入力されました。");
        }

        // 送信待機が有効時のみ
        if (SEND_WAIT_FLG) {
            //送信待機の利用有無
            $this->MdbConfigs->setValue('send_wait_flg',$this->send_wait_flg);
            if($this->send_wait_flg == 0){
                $this->MdbConfigs->setValue('up_start_time_flg','0');
                $this->MdbConfigs->setValue('up_start_time_value','0');
                $this->MdbConfigs->setValue('dl_start_time_flg','0');
                $this->MdbConfigs->setValue('dl_start_time_value','0');
                $this->MdbConfigs->setValue('send_collect_start_time_flg','0');
                $this->MdbConfigs->setValue('send_collect_start_time_value','0');
            } else {
                if (is_numeric($this->dl_start_time_flg) && $this->dl_start_time_flg < 3 && $this->dl_start_time_flg > 0) {
                    if ($this->dl_start_time_value > 999 || $this->dl_start_time_value < 1) {
                        $sendwait_error_response("配布メール待機時間は1～999分の範囲で登録してください。");
                    } elseif (!preg_match("/^[1-9][0-9]*$/", $this->dl_start_time_value)) {
                        $sendwait_error_response("配布メール待機時間は整数で入力してください。");
                    }
                } elseif ($this->dl_start_time_flg != 0) {
                    $log->debug($this->dl_start_time_flg, "Admin_Config_Sendwait_Done#execute");
                    $sendwait_error_response("不正な値が入力されました");
                }
                if (is_numeric($this->send_collect_start_time_flg) && $this->send_collect_start_time_flg < 3 && $this->send_collect_start_time_flg > 0) {
                    if ($this->send_collect_start_time_value > 999 || $this->send_collect_start_time_value < 1) {
                        $sendwait_error_response("引取り（収集）同時メール待機時間は1～999分の範囲で登録してください。");
                    } elseif (!preg_match("/^[1-9][0-9]*$/", $this->send_collect_start_time_value)) {
                        $sendwait_error_response("引取り（収集）同時メール待機時間は整数で入力してください。");
                    }
                } elseif ($this->send_collect_start_time_flg != 0) {
                    $sendwait_error_response("不正な値が入力されました");
                }

                //UP開始待機設定
                if (is_numeric($this->up_start_time_flg) && $this->up_start_time_flg < 3 && $this->up_start_time_flg >= 0) {
                    if ($this->up_start_time_flg > 0) {
                        if ($this->up_start_time_value > 999 || $this->up_start_time_value < 1) {
                            $sendwait_error_response("引取りメール待機時間は1～999分の範囲で登録してください。");
                        } elseif (!preg_match("/^[1-9][0-9]*$/", $this->up_start_time_value)) {
                            $sendwait_error_response("引取りメール待機時間は整数で入力してください。");
                        } 
                        $this->MdbConfigs->setValue('up_start_time_flg', $this->up_start_time_flg);
                        $this->MdbConfigs->setValue('up_start_time_value', $this->up_start_time_value);
                    } else {
                        $this->MdbConfigs->setValue('up_start_time_flg', '0');
                        $this->MdbConfigs->setValue('up_start_time_value', '0');
                    }
                } else {
                    $sendwait_error_response("不正な値が入力されました");
                }
                if ((int)$this->MdbConfigs->getDirectValue("sends_flg") !== 1 || $this->dl_start_time_flg == 0) {
                    $this->MdbConfigs->setValue('dl_start_time_flg', '0');
                    $this->MdbConfigs->setValue('dl_start_time_value', '0');
                } else {
                    $this->MdbConfigs->setValue('dl_start_time_flg', $this->dl_start_time_flg);
                    $this->MdbConfigs->setValue('dl_start_time_value', $this->dl_start_time_value);
                }
                if ((int)$this->MdbConfigs->getDirectValue("sendcollect_flg") !== 1 || $this->send_collect_start_time_flg == 0) {
                    $this->MdbConfigs->setValue('send_collect_start_time_flg', '0');
                    $this->MdbConfigs->setValue('send_collect_start_time_value', '0');
                } else {
                    $this->MdbConfigs->setValue('send_collect_start_time_flg', $this->send_collect_start_time_flg);
                    $this->MdbConfigs->setValue('send_collect_start_time_value', $this->send_collect_start_time_value);
                }
            }
        }

        // Log
        $history_id = 112; // 112:各種設定＞機能設定
        $memo  = "";

        // 送信待機が有効時のみ
        if (SEND_WAIT_FLG) {
            if ($this->send_wait_flg != $this->session->getParameter("send_wait_flg") && $this->send_wait_flg == 0){
                $memo .= "送信待機機能の利用可否：利用しない";
            } elseif ($this->send_wait_flg == 1) {
                $items_memo = "";
                $list = GlobalArray::getStart_time_Options();
                $time_config_items = [
                    ['label' => '配布メール待機', 'flg_key' => 'dl_start_time_flg', 'val_key' => 'dl_start_time_value'],
                    ['label' => '引取り（収集）同時メール待機', 'flg_key' => 'send_collect_start_time_flg', 'val_key' => 'send_collect_start_time_value'],
                    ['label' => 'UP待機', 'flg_key' => 'up_start_time_flg', 'val_key' => 'up_start_time_value']
                ];
                foreach ($time_config_items as $item) {
                    if ($this->{$item['flg_key']} != $this->session->getParameter($item['flg_key']) && isset($list[$this->{$item['flg_key']}])) {
                        $items_memo .= $item['label'] . "設定：" . $list[$this->{$item['flg_key']}] . "\n";
                    }
                    if ($this->{$item['flg_key']} > 0 && $this->{$item['val_key']} != $this->session->getParameter($item['val_key'])) {
                        $items_memo .= $item['label'] . "時間：" . $this->{$item['val_key']} . "分\n";
                    }
                }
                if ($this->send_wait_flg != $this->session->getParameter("send_wait_flg") || !empty($items_memo)){
                    $memo .= "送信待機機能の利用可否：利用する\n";
                    $memo .= (!empty($items_memo) ? $items_memo : '');
                }
            }
        }
	
        if ($memo) {
            $memo = "[送信機能＞送信待機に関する設定]\n".$memo;
            $this->DtoLog->setUser_id("");
            $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
            $this->DtoLog->setFile_id("");
            $this->DtoLog->setHistory_id($history_id);
            $this->DtoLog->setMemo($memo);
            $this->MdbLog->Logging($this->DtoLog);
        }

        //sessionを削除
        $this->session->removeParameter("send_wait_flg");
        $this->session->removeParameter("dl_start_time_flg");
        $this->session->removeParameter("dl_start_time_value");
        $this->session->removeParameter("send_collect_start_time_flg");
        $this->session->removeParameter("send_collect_start_time_value");
        $this->session->removeParameter("up_start_time_flg");
        $this->session->removeParameter("up_start_time_value");
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
    function setDtoConfigs($DtoConfigs){
        $this->DtoConfigs = $DtoConfigs;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setDtoLog ( &$DtoLog ) {
        $this->DtoLog =& $DtoLog;
    }
    function setMdbLog ( &$MdbLog ) {
        $this->MdbLog =& $MdbLog;
    }
    function setMdbMchoice($MdbMchoice) {
        $this->MdbMchoice =& $MdbMchoice;
    }
    function setDtoGroups($DtoGroups){
        $this->DtoGroups = $DtoGroups;
    }
    function setMdbGroups($MdbGroups) {
        $this->MdbGroups = $MdbGroups;
    }
    function setSend_wait_flg(&$send_wait_flg)
    {
        $this->send_wait_flg =& $send_wait_flg;
    }
    function setDl_start_time_flg(&$dl_start_time_flg) {
        $this->dl_start_time_flg =& $dl_start_time_flg;
    }
    function setDl_start_time_value(&$dl_start_time_value) {
        $this->dl_start_time_value =& $dl_start_time_value;
    }
    function setSend_collect_start_time_flg(&$send_collect_start_time_flg) {
        $this->send_collect_start_time_flg =& $send_collect_start_time_flg;
    }
    function setSend_collect_start_time_value(&$send_collect_start_time_value) {
        $this->send_collect_start_time_value =& $send_collect_start_time_value;
    }
    function setUp_start_time_flg(&$up_start_time_flg) {
        $this->up_start_time_flg =& $up_start_time_flg;
    }
    function setUp_start_time_value(&$up_start_time_value) {
        $this->up_start_time_value =& $up_start_time_value;
    }
    function setToken($token) {
        $this->token = $token;
    }
}
?>

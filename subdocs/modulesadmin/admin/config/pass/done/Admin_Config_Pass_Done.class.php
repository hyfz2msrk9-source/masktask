<?php
/**
 * [機能説明] 各種設定 - セキュリティ設定 - パスワードに関する設定(完了)
 * @updater     Hiroko Yanai（2018/09/28）passmust_span -> user_passmust_span
 * @updater     Hiroko Yanai（2018/10/01）admin_passmust_span の設定
 *
 * @package     WebFile
 * @version     3.16.0
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Pass_Done {
    var $session;
    var $DtoConfigs;
    var $MdbConfigs;
    var $DtoLog;
    var $MdbLog;
    var $MdbMchoice;
    var $DtoGroups;
    var $MdbGroups;

    var $reissue_flg;
    var $reissue_check;
    var $pass_min_num;
    var $pass_max_num;
    var $pass_type_id;
    var $smspass_min_num;
    var $smspass_max_num;
    var $smspass_type_id;
    var $pass_differ_flg;
    var $pass_account_ng_flg;
    var $user_passmust_span;
    var $admin_passmust_span;
    var $CommonList;
    var $token;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Pass_Doneが実行されました","Admin_Config_Pass_Done#execute");

        $pass_error_response = function ($message) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => $message,
                'token_name' => 'PassEditToken',
                'token_value' => $this->token->getValue('PassEditToken')
            ]);
            exit;
        };

        if (!DUPLICATE_EMAIL_ENABLE_FLG) {
            if (!array_key_exists($this->reissue_flg, GlobalArray::getReissue_Flg_Options()) ||
                !array_key_exists($this->reissue_check, GlobalArray::getReissue_Check_Options()))
                $pass_error_response("不正な値が入力されました");
        }
        if (!array_key_exists($this->pass_type_id, $this->MdbMchoice->getOptions("pass_type_id")) ||
                (defined('SMS_PASS_FLG') && SMS_PASS_FLG == "2" && !array_key_exists($this->smspass_type_id, $this->MdbMchoice->getOptions("pass_type_id"))) ||
                !array_key_exists($this->pass_differ_flg, GlobalArray::getPass_Differ_Flg_Options()) ||
                !array_key_exists($this->pass_account_ng_flg, GlobalArray::getPass_Account_NG_Flg_Options()))
                $pass_error_response("不正な値が入力されました");


        if ($this->pass_min_num == '' || $this->pass_max_num == '' || $this->smspass_min_num == '' || $this->smspass_max_num == '' || $this->user_passmust_span == '' || $this->admin_passmust_span == '') {
            $require_message = ($this->pass_min_num == '' ? "パスワード文字制限の始まり文字数が入力されていません。\n" : '');
            $require_message .= ($this->pass_max_num == '' ? "パスワード文字制限の終わり文字数が入力されていません。\n" : '');
            $require_message .= ($this->smspass_min_num == '' ? "ワンタイムパスワード(SMS)文字制限の始まり文字数が入力されていません。\n" : '');
            $require_message .= ($this->smspass_max_num == '' ? "ワンタイムパスワード(SMS)文字制限の終わり文字数が入力されていません。\n" : '');
            $require_message .= ($this->user_passmust_span == '' ? "ユーザ側のパスワード強制変更日数が入力されていません。\n" : '');
            $require_message .= ($this->admin_passmust_span == '' ? "管理者側のパスワード強制変更日数が入力されていません。\n" : '');
            $pass_error_response($require_message);
        }
        if (!preg_match("/^[1-9][0-9]*$/",$this->pass_min_num) || !preg_match("/^[1-9][0-9]*$/",$this->pass_max_num)) {
            $pass_error_response("パスワード文字制限は、整数で入力してください。");
        }
        if (!preg_match("/^[1-9][0-9]*$/", $this->smspass_min_num) || !preg_match("/^[1-9][0-9]*$/", $this->smspass_max_num)) {
            $pass_error_response("ワンタイムパスワード(SMS)文字制限は、整数で入力してください。");
        }
        if ($this->pass_min_num < 3 || $this->pass_max_num < 3) {
            $pass_error_response("パスワード文字制限は、3文字以上を入力してください。");
        }
        if ($this->pass_min_num > 100 || $this->pass_max_num > 100) {
            $pass_error_response("パスワード文字制限は、100文字以内で入力してください。");
        }
        if ($this->pass_min_num > $this->pass_max_num) {
            $pass_error_response("パスワード文字制限の入力が正しくありません。");
        }
        if ($this->smspass_min_num < 3 || $this->smspass_max_num < 3) {
            $pass_error_response("ワンタイムパスワード(SMS)文字制限は、3文字以上を入力してください。");
        }
        if ($this->smspass_min_num > 10 || $this->smspass_max_num > 10) {
            $pass_error_response("ワンタイムパスワード(SMS)文字制限は、10文字以内で入力してください。");
        }
        if ($this->smspass_min_num > $this->smspass_max_num) {
            $pass_error_response("ワンタイムパスワード(SMS)文字制限の入力が正しくありません。");
        }
        if (!preg_match("/^[0-9]*$/",$this->user_passmust_span) || !preg_match("/^[0-9]*$/",$this->admin_passmust_span)) {
                $pass_error_response("パスワード強制変更日数は整数で入力してください。");
        }
        if ($this->user_passmust_span < 0 || $this->admin_passmust_span < 0) {
            $pass_error_response("パスワード強制変更日数は0日以上を入力してください。");
        }
        if (strlen($this->user_passmust_span) > 4 || strlen($this->admin_passmust_span) > 4) {
            $pass_error_response("パスワード強制変更日数は4桁以内で入力してください。");
        }

        // パスワード再発行
        if (!DUPLICATE_EMAIL_ENABLE_FLG) {
            $this->MdbConfigs->setValue('reissue_flg',$this->reissue_flg);
            $this->MdbConfigs->setValue('reissue_check',$this->reissue_check);
        }

        // パスワード文字制限機能
        $this->MdbConfigs->setValue('pass_min_num',$this->pass_min_num);
        $this->MdbConfigs->setValue('pass_max_num',$this->pass_max_num);
        $this->MdbConfigs->setValue('pass_type_id',$this->pass_type_id);
        $this->MdbConfigs->setValue('pass_differ_flg',$this->pass_differ_flg);
        $this->MdbConfigs->setValue('pass_account_ng_flg',$this->pass_account_ng_flg);

        if(defined('SMS_PASS_FLG') && SMS_PASS_FLG == "2"){
            // SMSパスワード文字制限機能
            $this->MdbConfigs->setValue('smspass_min_num',$this->smspass_min_num);
            $this->MdbConfigs->setValue('smspass_max_num',$this->smspass_max_num);
            $this->MdbConfigs->setValue('smspass_type_id',$this->smspass_type_id);
        }
        
        // パスワード強制変更期間
        $this->MdbConfigs->setValue('user_passmust_span',$this->user_passmust_span);
        $admin_passmust_dt_before = $this->MdbConfigs->getDirectValue('admin_passmust_span');
        $this->MdbConfigs->setValue('admin_passmust_span',$this->admin_passmust_span);

        // Log
        $history_id = 112; // 112:各種設定＞機能設定
        $memo  = "";
        if ( $this->reissue_flg != $this->session->getParameter("reissue_flg")) {
            if ($this->reissue_flg == "1") {
                $flg = "有効";
            } else {
                $flg = "無効";
            }
            $memo .= "パスワード再発行：".$flg."\n";
        }

        if ( $this->reissue_check != $this->session->getParameter("reissue_check")) {
            if ($this->reissue_check == "1") {
                $check = "画面で通知";
            } elseif ($this->reissue_check == "2") {
                $check = "メールで通知";
            } else {
                $check = "通知なし";
            }
            $memo .= "未登録メールのパスワード再発行：".$check."\n";
        }

        if ( ($this->pass_min_num != $this->session->getParameter("pass_min_num"))
            OR ($this->pass_max_num != $this->session->getParameter("pass_max_num"))
            OR ($this->pass_type_id != $this->session->getParameter("pass_type_id"))
            OR ($this->pass_differ_flg != $this->session->getParameter("pass_differ_flg")) ) {
            $aft_list= $this->MdbMchoice->getInfo( "pass_type_id",$this->pass_type_id );
            $aft = $this->pass_min_num . "〜" . $this->pass_max_num."文字で";
            $aft.= $aft_list["content"];
            $aft_flg = "";
            if ($this->pass_differ_flg == "1") {
                $aft_flg = "前回と同じパスワードを不可にする";
            } else {
                $aft_flg = "前回と同じパスワードを許可する";
            }
            $aft_ng_flg = "";
            if ($this->pass_account_ng_flg == "1") {
                $aft_ng_flg = "アカウントと同じパスワードを不可にする";
            } else {
                $aft_ng_flg = "アカウントと同じパスワードを許可する";
            }
            $bef_list = $this->MdbMchoice->getInfo( "pass_type_id",$this->session->getParameter("pass_type_id") );
            $bef = $this->session->getParameter("pass_min_num")."〜".$this->session->getParameter("pass_max_num")."文字で";
            $bef.= $bef_list["content"];
            $brf_flg = "";
            if ($this->session->getParameter("pass_differ_flg") == "1") {
                $brf_flg = "前回と同じパスワードを不可にする";
            } else {
                $brf_flg = "前回と同じパスワードを許可する";
            }
            $bef_ng_flg = "";
            if ($this->session->getParameter("pass_account_ng_flg") == "1") {
                $bef_ng_flg = "アカウントと同じパスワードを不可にする";
            } else {
                $bef_ng_flg = "アカウントと同じパスワードを許可する";
            }
            $memo .= "パスワード文字制限機能：\n　".$bef."\n　".$brf_flg."\n　".$bef_ng_flg."\n　↓\n　".$aft."\n　".$aft_flg."\n　".$aft_ng_flg."\n";
        }
        if(defined('SMS_PASS_FLG') && SMS_PASS_FLG == "2"){
            if ( ($this->smspass_min_num != $this->session->getParameter("smspass_min_num"))
                OR ($this->smspass_max_num != $this->session->getParameter("smspass_max_num"))
                OR ($this->smspass_type_id != $this->session->getParameter("smspass_type_id")) ) {
                    $aft_list= $this->MdbMchoice->getInfo( "pass_type_id",$this->smspass_type_id );
                    $aft = $this->smspass_min_num . "〜" . $this->smspass_max_num."文字で";
                    $aft.= $aft_list["content"];
           
                    $bef_list = $this->MdbMchoice->getInfo( "pass_type_id",$this->session->getParameter("smspass_type_id") );
                    $bef = $this->session->getParameter("smspass_min_num")."〜".$this->session->getParameter("smspass_max_num")."文字で";
                    $bef.= $bef_list["content"];
                    $memo .= "ワンタイムパスワード(SMS)文字制限機能：\n　".$bef."\n　↓\n　".$aft."\n";
            }
        }
        
        if ( $this->user_passmust_span != $this->session->getParameter("user_passmust_span")) {
            $aft = $this->user_passmust_span;
            $bef = $this->session->getParameter("user_passmust_span");
            $memo .= "ユーザ側のパスワード強制変更日数：".$bef."→".$aft."\n";
        }

        if ( $this->admin_passmust_span != $admin_passmust_dt_before) {
            $aft = $this->admin_passmust_span;
            $memo .= "管理者側のパスワード強制変更日数：". $admin_passmust_dt_before."→".$aft."\n";
        }

        if ($memo) {
            $memo = "[セキュリティ＞パスワードに関する設定]\n".$memo;
            $this->DtoLog->setUser_id("");
            $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
            $this->DtoLog->setFile_id("");
            $this->DtoLog->setHistory_id($history_id);
            $this->DtoLog->setMemo($memo);
            $this->MdbLog->Logging($this->DtoLog);
        }

        //sessionを削除
        $this->session->removeParameter("reissue_flg");
        $this->session->removeParameter("reissue_check");
        // $this->session->removeParameter("admin_passmust_span"); //ログイン中は強制変更を発火させないので、あえてそのまま
        $this->session->removeParameter("pass_min_num");
        $this->session->removeParameter("pass_max_num");
        $this->session->removeParameter("pass_type_id");
        $this->session->removeParameter("pass_differ_flg");
        $this->session->removeParameter("pass_account_ng_flg");
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
    function setDtoConfigs($DtoConfigs) {
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
    function setDtoGroups($DtoGroups) {
        $this->DtoGroups = $DtoGroups;
    }
    function setMdbGroups($MdbGroups) {
        $this->MdbGroups = $MdbGroups;
    }
    function setReissue_flg(&$reissue_flg) {
        $this->reissue_flg =& $reissue_flg;
    }
    function setReissue_Check(&$reissue_check) {
        $this->reissue_check =& $reissue_check;
    }
    function setPass_min_num(&$pass_min_num) {
        $this->pass_min_num =& $pass_min_num;
    }
    function setPass_max_num(&$pass_max_num) {
        $this->pass_max_num =& $pass_max_num;
    }
    function setPass_type_id(&$pass_type_id) {
        $this->pass_type_id =& $pass_type_id;
    }
    function setSMSPass_min_num(&$smspass_min_num) {
        $this->smspass_min_num =& $smspass_min_num;
    }
    function setSMSPass_max_num(&$smspass_max_num) {
        $this->smspass_max_num =& $smspass_max_num;
    }
    function setSMSPass_type_id(&$smspass_type_id) {
        $this->smspass_type_id =& $smspass_type_id;
    }
    function setPass_differ_flg(&$pass_differ_flg) {
        $this->pass_differ_flg =& $pass_differ_flg;
    }
    function setPass_account_ng_flg(&$pass_account_ng_flg) {
        $this->pass_account_ng_flg =& $pass_account_ng_flg;
    }
    function setUser_Passmust_span(&$user_passmust_span) {
        $this->user_passmust_span =& $user_passmust_span;
    }
    function setAdmin_Passmust_span(&$admin_passmust_span) {
        $this->admin_passmust_span =& $admin_passmust_span;
    }
    function setCommonList($CommonList) {
        $this->CommonList =& $CommonList;
    }
    function setToken($token) {
        $this->token =& $token;
    }
}
?>

<?php
require_once( MODULE_DIR . "/common/common.php");

/**
 * Admin_Config_Continuouslogin_Done module
 * セキュリティ設定 (連続ログインに関する設定)：完了
 * @updater     Hiroko Yanai（2018/01/29）通知メールの編集
 *
 * @package     WebFile
 * @author      Hiroko Yanai（2017/12/04）
 * @copyright   KEYPOINT.INC
 */

class Admin_Config_Continuouslogin_Done {
    var $session;
    var $MdbConfigs;
    var $DtoLog;
    var $MdbLog;
    var $AdminUserLogic;
    var $TemplateLogic;
    var $token;

    // request
    var $continuous_login_notice_flg;
    var $continuous_login_notice_each_time_flg;
    var $continuous_login_notice_address;
    var $continuous_login_span_minute;
    var $continuous_login_num;
    var $continuous_login_mail_body_user;
    var $continuous_login_mail_body_admin;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Continuouslogin_Doneが実行されました","Admin_Config_Continuouslogin_Done#execute");

        $continuouslogin_error_response = function ($message) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => $message,
                'token_name' => 'ContinuousloginEditToken',
                'token_value' => $this->token->getValue('ContinuousloginEditToken')
            ]);
            exit;
        };
        // 入力チェック
        // array2つ
        if (!array_key_exists($this->continuous_login_notice_flg, $this->getContinuous_Login_Notice_Flg_Options()) ||
            !array_key_exists($this->continuous_login_notice_each_time_flg, $this->getContinuous_Login_Notice_Each_Time_Flg_Options())) {
            $continuouslogin_error_response("不正な値が入力されました");
        }

        if ($this->continuous_login_notice_flg) {
            if ($this->continuous_login_span_minute == "") {
                $continuouslogin_error_response("連続ログイン計測時間が入力されていません。");
            }
            if ($this->continuous_login_num == "") {
                $continuouslogin_error_response("通知を行う連続ログイン回数が入力されていません。");
            }

            if ($this->continuous_login_span_minute !== '0') {
                if (!preg_match("/^[0-9]+$/", $this->continuous_login_span_minute)) {
                    $continuouslogin_error_response("連続ログイン計測時間は整数で入力してください。");
                }
            } else {
                $continuouslogin_error_response("連続ログイン計測時間は1以上で入力してください。");
            }
            if ($this->continuous_login_num !== '0') {
                if (!preg_match("/^[0-9]+$/", $this->continuous_login_num)) {
                    $continuouslogin_error_response("通知を行う連続ログイン回数は整数で入力してください。");
                }
            } else {
                $continuouslogin_error_response("通知を行う連続ログイン回数は1以上で入力してください。");
            }

            if ($this->continuous_login_notice_address == "") {
                $continuouslogin_error_response("通知先メールアドレスを入力してください。");
            }

            if (mb_strlen($this->continuous_login_notice_address, 'UTF-8') > 255) {
                $continuouslogin_error_response("通知先メールアドレスは255文字以内で入力してください。");
            }
            // 重複のチェック
            $address_array = explode(",", $this->continuous_login_notice_address);
            $mail_valid = false;
            foreach ($address_array as $key => $value) {
                if ($value === '' || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $mail_valid = true;
                    break;
                }
                $address_array[$key] = trim($value);
            }
            if ($mail_valid) {
                $continuouslogin_error_response("通知先メールアドレスを正しく入力してください。");
            }
            $duplicate = array_count_values($address_array);
            $duplicate_flg = false;
            $error = "";
            foreach ($duplicate as $key => $value) {
                if ($value !== 1) {
                    $error .= "通知先メールアドレスが重複しています。：" . $key . "\n";
                    $duplicate_flg = true;
                }
            }
            if ($duplicate_flg) {
                $continuouslogin_error_response($error);
            }
            // 重複なしなら、trimした後の値を設定に使用
            $this->continuous_login_notice_address = implode(",", $address_array);

            // メール本文
            $bodies_key = array('_user', '_admin');
            $error_lang = array('日本語', '管理者');
            foreach ($bodies_key as $key => $value) {
                $check_value_name = 'continuous_login_mail_body' . $value;

                // 本文必須チェック
                if ($this->$check_value_name == '') {
                    $continuouslogin_error_response($error_lang[$key] . "のメール本文を入力してください。");
                }

                // 本文中にURLが入力されていないかチェック
                if ($this->AdminUserLogic->is_url($this->$check_value_name)) {
                    $continuouslogin_error_response($error_lang[$key] . "のメール本文中にURLを入力することはできません。");
                }
            }

        } else {
            // OFFの場合は更新しない
            $this->continuous_login_span_minute = $this->session->getParameter("continuous_login_span_minute");
            $this->continuous_login_num = $this->session->getParameter("continuous_login_num");
            $this->continuous_login_notice_address = $this->session->getParameter("continuous_login_notice_address");
            $this->continuous_login_notice_each_time_flg = $this->session->getParameter("continuous_login_notice_each_time_flg");
        }

        // 更新
        $this->MdbConfigs->setValue('continuous_login_notice_flg', $this->continuous_login_notice_flg);
        $this->MdbConfigs->setValue('continuous_login_span_minute', $this->continuous_login_span_minute);
        $this->MdbConfigs->setValue('continuous_login_num', $this->continuous_login_num);
        $this->MdbConfigs->setValue('continuous_login_notice_address', $this->continuous_login_notice_address);
        $this->MdbConfigs->setValue('continuous_login_notice_each_time_flg', $this->continuous_login_notice_each_time_flg);
        if ($this->continuous_login_notice_flg) {
            $this->TemplateLogic->put_template_file_contents("", "continuouslogin_mail_body_user.txt", $this->continuous_login_mail_body_user);
            $this->TemplateLogic->put_template_file_contents("", "continuouslogin_mail_body_admin.txt", $this->continuous_login_mail_body_admin);
        }

        // Log
        $history_id = 112; // 112:各種設定＞機能設定
        $memo  = "";

        if ( $this->continuous_login_notice_flg != $this->session->getParameter("continuous_login_notice_flg")) {
            if ($this->continuous_login_notice_flg == "1") {
                $flg = "無効→有効";
            } else {
                $flg = "有効→無効";
            }
            $memo .= "メール通知設定：".$flg."\n";
        }
        if ($this->continuous_login_notice_flg) {
            if ($this->continuous_login_span_minute != $this->session->getParameter("continuous_login_span_minute")){
                $memo .= "連続ログイン計測時間："
                    .$this->session->getParameter("continuous_login_span_minute")
                    ."→".$this->continuous_login_span_minute."分\n";
            }

            if ($this->continuous_login_num != $this->session->getParameter("continuous_login_num")){
                $memo .= "通知を行う連続ログイン数："
                    .$this->session->getParameter("continuous_login_num")
                    ."→".$this->continuous_login_num."回\n";
            }

            if ($this->continuous_login_notice_address != $this->session->getParameter("continuous_login_notice_address")){
                $memo .= "通知先メールアドレス："
                    .$this->session->getParameter("continuous_login_notice_address")
                    ."→".$this->continuous_login_notice_address."\n";
            }

            if ($this->continuous_login_notice_each_time_flg != $this->session->getParameter("continuous_login_notice_each_time_flg")){
                $options = $this->getContinuous_Login_Notice_Each_Time_Flg_Options();
                $memo .= "通知方法："
                    .$options[$this->session->getParameter("continuous_login_notice_each_time_flg")]
                    ."→".$options[$this->continuous_login_notice_each_time_flg]."\n";
            }
        }

        if($memo) {
            $memo = "[セキュリティ＞連続ログインに関する設定]\n".$memo;
            $this->DtoLog->setUser_id("");
            $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
            $this->DtoLog->setFile_id("");
            $this->DtoLog->setHistory_id($history_id);
            $this->DtoLog->setMemo($memo);
            $this->MdbLog->Logging($this->DtoLog);
        }

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
    function setDtoLog ( &$DtoLog ) {
        $this->DtoLog =& $DtoLog;
    }
    function setMdbLog ( &$MdbLog ) {
        $this->MdbLog =& $MdbLog;
    }
    function setAdminUserLogic($AdminUserLogic) {
        $this->AdminUserLogic = $AdminUserLogic;
    }
    function setTemplateLogic($TemplateLogic) {
        $this->TemplateLogic = $TemplateLogic;
    }

    function setContinuous_login_notice_flg($continuous_login_notice_flg) {
        $this->continuous_login_notice_flg = $continuous_login_notice_flg;
    }
    function setContinuous_login_notice_each_time_flg($continuous_login_notice_each_time_flg) {
        $this->continuous_login_notice_each_time_flg = $continuous_login_notice_each_time_flg;
    }
    function setContinuous_login_notice_address($continuous_login_notice_address) {
        $this->continuous_login_notice_address = $continuous_login_notice_address;
    }
    function setContinuous_login_span_minute($continuous_login_span_minute) {
        $this->continuous_login_span_minute = $continuous_login_span_minute;
    }
    function setContinuous_login_num($continuous_login_num) {
        $this->continuous_login_num = $continuous_login_num;
    }
    function setContinuous_login_mail_body_user($continuous_login_mail_body_user)
    {
      $this->continuous_login_mail_body_user = $continuous_login_mail_body_user;
    }
    function setContinuous_login_mail_body_admin($continuous_login_mail_body_admin)
    {
      $this->continuous_login_mail_body_admin = $continuous_login_mail_body_admin;
    }
    function setToken($token){
        $this->token = $token;
    }

    // getter
    function getContinuous_Login_Notice_Flg_Options() {
        return GlobalArray::getContinuous_Login_Notice_Flg_Options();
    }
    function getContinuous_login_notice_flg() {
        return $this->continuous_login_notice_flg;
    }

    function getContinuous_Login_Notice_Each_Time_Flg_Options() {
        return GlobalArray::getContinuous_Login_Notice_Each_Time_Flg_Options();
    }
    function getContinuous_login_notice_each_time_flg() {
        return $this->continuous_login_notice_each_time_flg;
    }
    function getContinuous_login_notice_address() {
        return $this->continuous_login_notice_address;
    }
    function getContinuous_login_span_minute() {
        return $this->MdbConfigs->getDirectValue("continuous_login_span_minute");
    }
    function getContinuous_login_num() {
        return $this->MdbConfigs->getDirectValue("continuous_login_num");
    }
    function getContinuous_login_mail_body_user() {
        return $this->continuous_login_mail_body_user;
    }
    function getContinuous_login_mail_body_admin() {
        return $this->continuous_login_mail_body_admin;
    }
}
?>

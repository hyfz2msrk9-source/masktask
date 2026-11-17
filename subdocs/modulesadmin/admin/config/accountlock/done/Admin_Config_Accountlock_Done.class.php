<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] 各種設定 - セキュリティ設定 - アカウントロック通知に関する設定(完了)
 * @updater     Hiroko Yanai（2018/04/13）SAML認証(OneLogin)に対応。SSOやSAMLでも、設定は更新可能とする
 *
 * @package     WebFile
 * @version     3.15.2
 * @author      Hiroko Yanai（2017/12/??）
 * @copyright   KEY POINT
 * @access      public
 */

class Admin_Config_Accountlock_Done {
    var $session;
    var $MdbConfigs;
    var $DtoLog;
    var $MdbLog;

    // request
    var $accountlock_mail_flg;
    var $accountlock_mail_type;
    var $accountlock_mail_addr;
    var $accountlock_mail_body;
    var $accountlock_mail_body_en;
    var $accountlock_mail_body_cn;
    var $accountlock_mail_body_tcn;
    var $admin_account_lock_cnt;
    var $admin_account_lock_time;
    var $user_account_lock_cnt;
    var $user_account_lock_time;

    var $CommonList;
    var $TemplateLogic;
    var $token;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Accountlock_Doneが実行されました","Admin_Config_Accountlock_Done#execute");

        $accountlock_error_response = function ($message) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => $message,
                'token_name' => 'AccountlockEditToken',
                'token_value' => $this->token->getValue('AccountlockEditToken')
            ]);
            exit;
        };
        // 入力チェック
        if (!array_key_exists($this->accountlock_mail_flg, $this->getAccountlock_Mail_Flg_Options())) {
            $accountlock_error_response("不正な値が入力されました");
        }
        if (is_array($this->accountlock_mail_type)) {
            if (array_diff($this->accountlock_mail_type, array_keys($this->getAccountlock_Mail_Type_Options()))) {
                $accountlock_error_response("不正な値が入力されました");
            }
            $this->accountlock_mail_type = implode(",", $this->accountlock_mail_type);
        } else {
            $this->accountlock_mail_type = "";
        }

        if ($this->getLock_edit_flg()) {
            if ($this->admin_account_lock_cnt == "") {
                $accountlock_error_response("管理者アカウントがロックとなる失敗回数が入力されていません。");
            }
            if ($this->admin_account_lock_time == "") {
                $accountlock_error_response("管理者アカウントのロック自動解除時間が入力されていません。");
            }

            if ($this->admin_account_lock_cnt !== '0') {
                if (!preg_match("/^[1-9][0-9]*$/", $this->admin_account_lock_cnt)) {
                    $accountlock_error_response("管理者アカウントがロックとなる失敗回数は整数で入力してください。");
                }
            }
            if ($this->admin_account_lock_time !== '0') {
                if (!preg_match("/^[1-9][0-9]*$/", $this->admin_account_lock_time)) {
                    $accountlock_error_response("管理者アカウントのロック自動解除時間は整数で入力してください。");
                }
            }

            if (!is_numeric($this->user_account_lock_cnt)) {
                $accountlock_error_response("ユーザーアカウントがロックとなる失敗回数は数値のみを入力してください。");
            }
            if (!is_numeric($this->user_account_lock_time)) {
                $accountlock_error_response("ユーザーアカウントのロック自動解除時間は数値のみを入力してください。");
            }
            if (($this->user_account_lock_cnt < 0) && ($this->user_account_lock_cnt > 999)) {
                $accountlock_error_response("ユーザーアカウントがロックとなる失敗回数は0〜999の範囲で登録してください。");
            }
            if (($this->user_account_lock_time < 0) && ($this->user_account_lock_time > 999)) {
                $accountlock_error_response("ユーザーアカウントのロック自動解除時間は0〜999の範囲で登録してください。");
            }

            if ($this->user_account_lock_cnt == "") {
                $accountlock_error_response("ユーザーアカウントがロックとなる失敗回数が入力されていません。");
            }
            if ($this->user_account_lock_time == "") {
                $accountlock_error_response("ユーザーアカウントのロック自動解除時間が入力されていません。");
            }

            if ($this->user_account_lock_cnt !== '0') {
                if (!preg_match("/^[1-9][0-9]*$/", $this->user_account_lock_cnt)) {
                    $accountlock_error_response("ユーザーアカウントがロックとなる失敗回数は整数で入力してください。");
                }
            }
            if ($this->user_account_lock_time !== '0') {
                if (!preg_match("/^[1-9][0-9]*$/", $this->user_account_lock_time)) {
                    $accountlock_error_response("ユーザーアカウントのロック自動解除時間は整数で入力してください。");
                }
            }
        }

        // 通知先設定チェック
        if ($this->accountlock_mail_flg) {
            if (!$this->accountlock_mail_type) {
                $accountlock_error_response("通知先を選択してください。");
            }
            // 管理者メアド必須チェック
            if (in_array('2',explode(',', $this->accountlock_mail_type))) {
                if ($this->accountlock_mail_addr == "") {
                    $this->accountlock_mail_type = explode(',', $this->accountlock_mail_type);
                    $accountlock_error_response("管理者のメールアドレスを入力してください。");
                } elseif (mb_strlen($this->accountlock_mail_addr, 'UTF-8') > 255) {
                    $accountlock_error_response("管理者のメールアドレスは255文字以内で入力してください。");
                } elseif (!filter_var($this->accountlock_mail_addr, FILTER_VALIDATE_EMAIL)) {
                    $accountlock_error_response("管理者のメールアドレスを正しく入力してください。");
                }
            }
        }

        // 更新
        $this->MdbConfigs->setValue('accountlock_mail_flg',  $this->accountlock_mail_flg);
        $this->MdbConfigs->setValue('accountlock_mail_type', $this->accountlock_mail_type);
        $this->MdbConfigs->setValue('accountlock_mail_addr', $this->accountlock_mail_addr);
        $this->TemplateLogic->put_template_file_contents("", "accountlock_mail_body.txt", $this->accountlock_mail_body);
        $this->TemplateLogic->put_template_file_contents("en", "accountlock_mail_body.txt", $this->accountlock_mail_body_en);
        if ( defined("CHINESE_FLG")  &&  CHINESE_FLG == 1 ) {
            $this->TemplateLogic->put_template_file_contents("cn", "accountlock_mail_body.txt", $this->accountlock_mail_body_cn);
            $this->TemplateLogic->put_template_file_contents("tcn", "accountlock_mail_body.txt", $this->accountlock_mail_body_tcn);
        }

        if ($this->getLock_edit_flg()) {
            //管理者アカウントがロックとなる失敗回数
            $this->MdbConfigs->setValue('admin_account_lock_cnt',$this->admin_account_lock_cnt);
            //管理者アカウントのロック自動解除時間
            $this->MdbConfigs->setValue('admin_account_lock_time',$this->admin_account_lock_time);
            //ユーザーアカウントがロックとなる失敗回数
            $this->MdbConfigs->setValue('user_account_lock_cnt',$this->user_account_lock_cnt);
            //ユーザーアカウントのロック自動解除時間
            $this->MdbConfigs->setValue('user_account_lock_time',$this->user_account_lock_time);
        }

        // Log
        $history_id = 112; // 112:各種設定＞機能設定
        $memo  = "";
        if ($this->getLock_edit_flg()) {
            if ($this->admin_account_lock_cnt != $this->session->getParameter("admin_account_lock_cnt")) {
                $memo .= "管理者アカウントがロックとなる失敗回数：".$this->admin_account_lock_cnt."回\n";
            }

            if ($this->admin_account_lock_time != $this->session->getParameter("admin_account_lock_time")) {
                $memo .= "管理者アカウントのロック自動解除時間：".$this->admin_account_lock_time."分\n";
            }
            if ($this->user_account_lock_cnt != $this->session->getParameter("user_account_lock_cnt")) {
                $memo .= "ユーザーアカウントがロックとなる失敗回数：".$this->user_account_lock_cnt."回\n";
            }

            if ($this->user_account_lock_time != $this->session->getParameter("user_account_lock_time")) {
                $memo .= "ユーザーアカウントのロック自動解除時間：".$this->user_account_lock_time."分\n";
            }
        }
        if ( $this->accountlock_mail_flg != $this->session->getParameter("accountlock_mail_flg")) {
            if ($this->accountlock_mail_flg == "1") {
                $flg = "有効";
            } else {
                $flg = "無効";
            }
            $memo .= "メール通知設定：".$flg."\n";
        }
        if ($this->accountlock_mail_type != $this->session->getParameter("accountlock_mail_type")) {
            $type = "";
            $this->accountlock_mail_type = explode(",", $this->accountlock_mail_type);
            $options = $this->getAccountlock_Mail_Type_Options();
            if (is_array($this->accountlock_mail_type)) {
                foreach ($this->accountlock_mail_type as $val) {
                    if (isset($options[$val])) {
                        $type.= "　".$options[$val]."\n";
                    }
                }
            }
            $memo .= "通知先：\n".$type."\n";
        }
        if ($memo) {
            $memo = "[セキュリティ＞アカウントロック通知に関する設定]\n".$memo;
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
    function setAccountlock_mail_flg($accountlock_mail_flg)
    {
      $this->accountlock_mail_flg = $accountlock_mail_flg;
    }
    function setAccountlock_mail_type($accountlock_mail_type)
    {
      $this->accountlock_mail_type = $accountlock_mail_type;
    }
    function setAccountlock_mail_addr($accountlock_mail_addr)
    {
      $this->accountlock_mail_addr = $accountlock_mail_addr;
    }
    function setAccountlock_mail_body($accountlock_mail_body)
    {
      $this->accountlock_mail_body = $accountlock_mail_body;
    }
    function setAccountlock_mail_body_en($accountlock_mail_body_en)
    {
      $this->accountlock_mail_body_en = $accountlock_mail_body_en;
    }
    function setAccountlock_mail_body_cn($accountlock_mail_body_cn)
    {
      $this->accountlock_mail_body_cn = $accountlock_mail_body_cn;
    }
    function setAccountlock_mail_body_tcn($accountlock_mail_body_tcn)
    {
      $this->accountlock_mail_body_tcn = $accountlock_mail_body_tcn;
    }
    function setAdmin_account_lock_cnt(&$admin_account_lock_cnt)
    {
        $this->admin_account_lock_cnt =& $admin_account_lock_cnt;
    }
    function setAdmin_account_lock_time(&$admin_account_lock_time)
    {
        $this->admin_account_lock_time =& $admin_account_lock_time;
    }
    function setUser_account_lock_cnt(&$user_account_lock_cnt)
    {
        $this->user_account_lock_cnt =& $user_account_lock_cnt;
    }
    function setUser_account_lock_time(&$user_account_lock_time)
    {
        $this->user_account_lock_time =& $user_account_lock_time;
    }
    function setCommonList($CommonList) {
        $this->CommonList =& $CommonList;
    }
    function setTemplateLogic($TemplateLogic) {
        $this->TemplateLogic = $TemplateLogic;
    }
    function setToken($token) {
        $this->token = $token;
    }

    // getter
    function getAccountlock_Mail_Flg_Options()
    {
        return GlobalArray::getAccountlock_Mail_Flg_Options();
    }
    function getAccountlock_Mail_Type_Options()
    {
        return GlobalArray::getAccountlock_Mail_Type_Options();
    }
    function getAccountlock_mail_flg() {
        return $this->accountlock_mail_flg;
    }
    function getAccountlock_mail_type() {
        return $this->accountlock_mail_type;
    }
    function getAccountlock_mail_addr() {
        return $this->accountlock_mail_addr;
    }
    function getAccountlock_mail_body() {
        return $this->accountlock_mail_body;
    }
    function getAccountlock_mail_body_en() {
        return $this->accountlock_mail_body_en;
    }
    function getAccountlock_mail_body_cn() {
        return $this->accountlock_mail_body_cn;
    }
    function getAccountlock_mail_body_tcn() {
        return $this->accountlock_mail_body_tcn;
    }
    function getLock_edit_flg() {
        if ($this->session->getParameter("login_admin_level") == 10) {
            return true;
        }else{
            return false;
        }
    }
}
?>

<?php
require_once(MODULE_DIR . '/common/common.php');
/**
 * [機能説明] ユーザ管理 - ユーザ情報通知(完了)
 * @updater     Hiroko Yanai（2018/04/11）SAML認証(OneLogin)に対応
 *
 * @package     WebFile
 * @version     3.15.2
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_User_Inform_Done {

    var $session;
    var $DtoUser;
    var $MdbUser;
    var $DtoLog;
    var $MdbLog;
    var $MdbConfigs;
    var $AdminUserLogic;
    var $count;
    var $body;
    var $CommonList;
    var $TemplateLogic;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_User_Inform_Doneが実行されました", "Admin_User_Inform_Done#execute");

        // エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        // パスワード認証を使わない場合
        $usage_auth_types = $this->CommonList->getUsageAuthTypes();
        $inform_invalid = false;
        if (!isset($usage_auth_types[0])) {
            $inform_invalid = true;
        }
        // AD連携時、パスワード認証を使わない場合は利用できない
        if (AD_FLG || PASSWORD_REQUIRED_FLG <> 1 || $inform_invalid) {
            $errorList->add("Admin_User_Inform_Done::error","ユーザ情報通知機能はご利用頂けません。");
            return "error";
        }

        // メールアドレス重複登録が許可されている場合は利用できない
        if (DUPLICATE_EMAIL_ENABLE_FLG) {
            $errorList->add("Admin_User_Inform_Done::error","ユーザ情報通知機能はご利用頂けません。");
            return "error";
        }

        // 未ログインユーザを一覧を取得
        $sql = "password = '' AND (last_login_dt IS NULL OR last_login_dt <= 0)";
        // AD連携、Google SSO認証、SAML認証(OneLogin)は除外
        $sql.=" AND ad_flg = '0' AND sso_flg = '0' AND saml_flg = '0' AND stop_flg = '0' AND del_flg='0'";
        $where = array($sql);

        // ユーザ管理者は他の管理者が追加したアカウントに通知できない
        if ( $this->session->getParameter("login_admin_level") == 3 ) {
            $where[] = "owner_admin_id=".Db_Common::escape_string( $this->session->getParameter("admin_id") );
        }

        $list = $this->MdbUser->getUserListWhere($where);
        $this->count = count($list);

        // 入力チェック
        if ($this->body == '') {
            $errorList->add("Admin_User_Inform_Done::error","本文を入力してください。");
            return "input";
        }

        // パスワード入力チェック
        if (!preg_match("/\[##password##]/", $this->body)) {
            $errorList->add("Admin_User_Inform_Done::error","[##password##]を入力してください。");
            return "input";
        }

        // 本文中にURLが入力されていないかチェック
        if ($this->AdminUserLogic->is_url($this->body)) {
            $errorList->add("Admin_User_Inform_Done::error","本文中にURLを入力することはできません。");
            return "input";
        }

        if ($this->count > 0) {
            $done_count= 0;
            $error_count= 0;
            $error_log = "";
            $done_log = "";
            foreach($list as $key => $val) {
                $this->DtoUser->setUserInfo($val);

                //パスワード生成
                $password = Password::resetPassword($this->MdbConfigs->getDirectValue('pass_min_num'),$this->MdbConfigs->getDirectValue('pass_max_num'),$this->MdbConfigs->getDirectValue('pass_type_id'));
                $this->DtoUser->setPassword($password);

                //メールを送る
                $mail = new MailSend();
                $ret = $mail->User_Inform_Mail($this->DtoUser, $this->body);

                if (PEAR::isError($ret)) {
                    $reissue_check = 1;
                    $container   = &DIContainerFactory::getContainer();
                    $actionChain = &$container->getComponent("ActionChain");
                    $errorList   = &$actionChain->getCurErrorList();
                    $errorList->add("Admin_User_Inform_Done::error", $this->DtoUser->getAccount()." : メールの送信に失敗しました。しばらく時間をおいてから、再度お試しください。");
                    $error_log .= "　".$this->DtoUser->getAccount()."(".$this->DtoUser->getUser_name().")\n";
                } else {
                    $data = array();
                    //$data['password'] = sha1($password);
                    $data['password'] = Password::hash($password);
                    $data['password_change_dt'] = date('Y-m-d');

                    // アカウントロックを解除する
                    if ($this->DtoUser->getAccount_lock_flg()) {
                        $data['account_lock_flg'] = 0;
                        $data['login_failuer_num'] = 0;
                        $data['login_failuer_dt'] = null;
                    }
                    $this->MdbUser->updateByData($this->DtoUser,$data);
                    $done_log .= "　".$this->DtoUser->getAccount()."(".$this->DtoUser->getUser_name().")\n";
                    $done_count++;
                }

                // エラーあり
                if ($errorList->isExists()) {
                    return "error";
                }
            }

            // Log
            $history_id = 161; // 161:ユーザ管理＞ユーザ情報通知
            $memo = "";
            $memo.= "通知ユーザ：" . $done_count . "件\n";
            $memo .= $done_log;
            if ($error_count > 0) {
                $memo.= "\nエラー　　：" . $error_count . "件\n";
                $memo .= $error_log;
            }

            $this->DtoLog->setUser_id("");
            $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
            $this->DtoLog->setFile_id("");
            $this->DtoLog->setHistory_id($history_id);
            $this->DtoLog->setMemo($memo);
            $this->MdbLog->Logging($this->DtoLog);

        } else {
            $errorList->add("Admin_User_Inform::error","未ログインかつパスワード未設定のユーザが存在しません。");
            return "error";
        }

        return "success";
    }

    //************************************************************
    // setter
    //************************************************************
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoUser($DtoUser) {
        $this->DtoUser = $DtoUser;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = $MdbUser;
    }
    function setDtoLog($DtoLog) {
        $this->DtoLog = $DtoLog;
    }
    function setMdbLog($MdbLog) {
        $this->MdbLog = $MdbLog;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setBody($body) {
        $this->body = $body;
    }
    function setPassword_flg($password_flg) {
        $this->password_flg = $password_flg;
    }
    function setAdminUserLogic($AdminUserLogic) {
        $this->AdminUserLogic = $AdminUserLogic;
    }
    function setCommonList($CommonList) {
        $this->CommonList =& $CommonList;
    }
    function setTemplateLogic($TemplateLogic) {
        $this->TemplateLogic = $TemplateLogic;
    }

    //************************************************************
    // getter
    //************************************************************
    function getUserCount() {
        return $this->count;
    }
    function getBody() {
        $body = $this->TemplateLogic->get_template_file_contents("", "user_inform_body.txt");
        if ($body) {
            return $body;
        }
        return $this->body;
    }
    function getTemplateToken() {
        $token =  md5(uniqid(rand(),1));
        $this->session->setParameter('templateToken',$token);
        return $token;
    }

}
?>

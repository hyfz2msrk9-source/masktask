<?php

/**
 * 管理者側：二要素認証でコードを送信後の処理
 *
 * @version 5.4~
 * @author
 **/
class Admin_Twostepauth_Login_Done
{

    var $session;
    var $TwostepauthLogic;
    var $TwostepauthOpenssl;
    var $confirm_code;
    var $backup_code;
    var $MdbAdminTwostepauth;
    var $admin_id;
    var $email;
    var $DtoLog;
    var $MdbBackupcode;
    var $is_save_admin_confirm_in_browser;
    var $MdbAdminTwostepauthBrowser;

    function execute()
    {
        $log = &LogFactory::getLog();
        $log->debug(__CLASS__ . "が実行されました", __CLASS__ . "#execute");

        $this->session->removeParameter('two_factor_authenticated');

        $two_step_auth_type = $this->TwostepauthLogic->getAdmin_two_step_auth_type();
        $admin_id = $this->session->getParameter('login_admin_id') ?: $this->admin_id;
        $this->setAdmin_id($admin_id);
        /**
         * エラーの設定
         */
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent('ActionChain');
        $errorList   = &$actionChain->getCurErrorList();
        if ($errorMessage = $this->TwostepauthLogic->getAuthLoginErrorMessage($admin_id)) {
            $errorList->add(__CLASS__ . '::error', $errorMessage);
            return 'error';
        }

        $accountTwostepauthInfo = $this->MdbAdminTwostepauth->getAdminTwostepauthInfo($admin_id);

        /********************************
         * バックアップコードでの認証
         ********************************/
        if ($this->backup_code) {
            if (isset($accountTwostepauthInfo['email'])) $this->setEmail($accountTwostepauthInfo['email']);
            // バックアップコードでログイン
            if ($this->verifyBackupCode($this->backup_code, $admin_id)) {
                $this->TwostepauthLogic->addTwostepauthLog($this->DtoLog, history_id: '202', memo: '【管理者側：二要素認証】バックアップコードでログイン', admin_id: $admin_id); // ログへ記録
                // バックアップコードでの認証に成功
                $this->session->setParameter('two_factor_authenticated', '1');
                $this->session->removeParameter("for_check_admin_id");
                //ブラウザの保存
                if (!$this->saveBrowser($admin_id)) $log->debug("ブラウザの保存に失敗しました");
                return 'success';
            } else {
                $errorList->add(__CLASS__ . '::error', '利用できるバックアップコードではありません。',);
                if ($two_step_auth_type === '1') {
                    return 'input_app';
                } elseif ($two_step_auth_type === '2') {
                    return 'input_mail';
                } else {
                    return 'error';
                }
            }
        }
        /**********************************************************************************************************************************
         * アプリ認証（app_secret_keyをチェック）
         **********************************************************************************************************************************/
        if ($two_step_auth_type === '1') {
            if (!$this->confirm_code) {
                $errorList->add(__CLASS__ . '::error', '確認コードを入力してください。',);
                return 'input_app';
            }
            // ユーザの秘密鍵を取得
            $decryptedSaveKey = $this->TwostepauthOpenssl->decryptSecretKey($accountTwostepauthInfo['app_secret_key']);
            // 秘密鍵の復号化に失敗した場合
            if (!$decryptedSaveKey) {
                $errorList->add(__CLASS__ . '::error', 'エラーが発生しました。二要素認証の解除について問い合わせてください。',);
                return 'error';
            }
            if ($this->TwostepauthLogic->verifyCode($decryptedSaveKey, $this->confirm_code)) {
                $this->TwostepauthLogic->addTwostepauthLog($this->DtoLog, history_id: '202', memo: '【管理者側：二要素認証】アプリ認証でログイン', admin_id: $admin_id); // ログへ記録
                //ブラウザの保存
                if (!$this->saveBrowser($admin_id)) $log->debug("ブラウザの保存に失敗しました");
            } else {
                $errorList->add(__CLASS__ . '::error', '確認コードが一致しません。再度入力してください。',);
                return 'input_app';
            }
            $this->session->setParameter('two_factor_authenticated', '1');
            $this->session->removeParameter("for_check_admin_id");
            return 'success';
        }
        /**********************************************************************************************************************************
         * メール認証（confirmation_codeをチェック）
         **********************************************************************************************************************************/
        if ($two_step_auth_type === '2' && isset($accountTwostepauthInfo['confirmation_code'])) {
            $this->setEmail($accountTwostepauthInfo['email']);
            if (!$this->confirm_code) {
                $errorList->add(__CLASS__ . '::error', '確認コードを入力してください。',);
                return 'input_mail';
            }
            // 入力された確認コードの有効期限と一致を確認
            if (
                Password::isOneTimePasswordWithinLimit($accountTwostepauthInfo['confirmation_code_issued_at'])
                && Password::verify(Password::normalizeOneTimePassword($this->confirm_code), $accountTwostepauthInfo['confirmation_code'])
            ) {
                // 認証に成功した確認コードは再利用できないようにする
                $this->MdbAdminTwostepauth->invalidateConfirmationCode($accountTwostepauthInfo['admin_two_step_auth_id']);
                $this->TwostepauthLogic->addTwostepauthLog($this->DtoLog, history_id: '202', memo: '【管理者側：二要素認証】メール認証でログイン', admin_id: $admin_id); // ログへ記録
                $this->session->setParameter('two_factor_authenticated', '1');
                $this->session->removeParameter("two_step_mail_sended");
                $this->session->removeParameter("for_check_admin_id");
                //ブラウザの保存
                if (!$this->saveBrowser($admin_id)) $log->debug("ブラウザの保存に失敗しました");
                return 'success';
            } else {
                $errorList->add(__CLASS__ . '::error', '確認コードが一致しません。再度入力してください。',);
                return 'input_mail';
            }
        }
        return 'error';
    }
    /**
     * setter
     */
    function setTwostepauthLogic($TwostepauthLogic)
    {
        $this->TwostepauthLogic = $TwostepauthLogic;
    }
    function setTwostepauthOpenssl(&$TwostepauthOpenssl)
    {
        $this->TwostepauthOpenssl = &$TwostepauthOpenssl;
    }
    function setConfirm_code($confirm_code)
    {
        $this->confirm_code = $confirm_code;
    }
    function setMdbAdminTwostepauth($MdbAdminTwostepauth)
    {
        $this->MdbAdminTwostepauth = $MdbAdminTwostepauth;
    }
    function setSession(&$session)
    {
        $this->session = &$session;
    }
    function setAdmin_id(&$admin_id)
    {
        $this->admin_id = &$admin_id;
    }
    function setEmail(&$email)
    {
        $this->email = &$email;
    }
    function setBackup_code(&$backup_code)
    {
        $this->backup_code = &$backup_code;
    }
    function setMdbBackupcode(&$MdbBackupcode)
    {
        $this->MdbBackupcode = &$MdbBackupcode;
    }
    function setDtoLog(&$DtoLog)
    {
        $this->DtoLog = &$DtoLog;
    }
    /**
     * バックアップコードで認証して正しければtrueを返す
     *
     * @param string $inputtedBackupCode
     * @param string $admin_id
     * @return boolean 一致すればtrue
     */
    function verifyBackupCode($inputtedBackupCode, $admin_id)
    {
        // データベースから未使用のバックアップコードを取得
        $backupCodes = $this->MdbBackupcode->getUnusedCodeInfo($admin_id);
        foreach ($backupCodes as $backupCode) {
            if (Password::verify($inputtedBackupCode, $backupCode['code'])) {
                // 一致した場合、使用済みに更新
                $this->MdbBackupcode->updateToUsed($backupCode['backupcode_id']);
                return true;
            }
        }
        return false; // 一致しない場合
    }

    function setIs_save_admin_confirm_in_browser(&$is_save_admin_confirm_in_browser)
    {
        $this->is_save_admin_confirm_in_browser = &$is_save_admin_confirm_in_browser;
    }
    function setMdbAdminTwostepauthBrowser(&$MdbAdminTwostepauthBrowser)
    {
        $this->MdbAdminTwostepauthBrowser = &$MdbAdminTwostepauthBrowser;
    }
    //エラー画面用
    function getPreviousAction()
    {
        //戻るボタンのリンク先
        return "admin_user";
    }
    function getFunctionName()
    {
        //エラー画面のタイトル
        return '二要素認証';
    }
    function getAdmin_id()
    {
        return $this->admin_id;
    }
    function getEmail()
    {
        return $this->email;
    }
    function getAdmin_two_step_span()
    {
        return $this->TwostepauthLogic->getAdmin_two_step_auth_type();;
    }
    /**
     * ブラウザコードと期限をDBに保存
     *
     * @param string $admin_id
     * @return boolean
     */
    function saveBrowser($admin_id)
    {
        //ブラウザの保存のチェックボックスをチェックしていない
        if (!$this->is_save_admin_confirm_in_browser) return false;
        //管理者の各種設定で毎回認証する設定になっているならDBに保存しない
        $admin_two_step_auth_span = $this->TwostepauthLogic->getAdmin_two_step_auth_span();
        if ($admin_two_step_auth_span === '0') return false;
        //保存するデータ
        $expirationDateTime = $this->TwostepauthLogic->getExpirationDateTime($admin_two_step_auth_span);
        $formattedExpirationDateTime = $expirationDateTime->format('Y-m-d H:i:s');
        //ブラウザコードの生成
        $existingBrowserCode = array_key_exists('ADMIN_TWO_STEP_AUTH_BROWSER_CODE', $_COOKIE) ?  $_COOKIE['ADMIN_TWO_STEP_AUTH_BROWSER_CODE'] : '';
        $browserCode = $existingBrowserCode ? $existingBrowserCode : $this->MdbAdminTwostepauthBrowser->createBrowserCode();
        if (!$browserCode) return false;
        //Cookieに保存
        $this->setCode($browserCode, $expirationDateTime);
        $saveData = [
            'expiration_dt' => $formattedExpirationDateTime,
            'browser_code' => $browserCode,
            'admin_id' => $admin_id,
            'user_id' => '0',
        ];
        // 期限とブラウザコードをブラウザへ登録
        return $this->MdbAdminTwostepauthBrowser->add($saveData);
    }
    /**
     * クッキーへブラウザコードを保存する
     * @param  string   $browserCode
     * @param  DateTime $expirationDateTime
     * @return boolean
     */
    function setCode($browserCode, DateTime $expire)
    {
        return setcookie(
            'ADMIN_TWO_STEP_AUTH_BROWSER_CODE',
            $browserCode,
            $expire->getTimestamp()
        );
    }
    /**
     * getter
     */
    function getAdmin_two_step_auth_span()
    {
        return $this->TwostepauthLogic->getAdmin_two_step_auth_span();
    }
}

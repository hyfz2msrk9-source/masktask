<?php

/**
 * 管理者側：二要素認証(完了)
 *
 * @version 5.4~
 * @author
 **/
class Admin_Twostepauth_On_Done 
{

    var $TwostepauthLogic;
    var $TwostepauthOpenssl;
    var $confirm_code;
    var $MdbAdminTwostepauth;
    var $session;
    var $DtoLog;
    var $MdbBackupcode;
    var $backupCodes;

    function execute()
    {
        $log = &LogFactory::getLog();
        $log->debug(__CLASS__ . "が実行されました", __CLASS__ . "#execute");

        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent('ActionChain');
        $errorList   = &$actionChain->getCurErrorList();
        $admin_id = $this->session->getParameter("login_admin_id");

        if (!ADMIN_TWO_STEP_AUTH_FLG || $this->TwostepauthLogic->getAdmin_two_step_auth_type() !== '1') {
            $errorList->add(__CLASS__ . '::error', '不正なアクセスです。');
            return 'error';
        }

        // コードが一致しなければエラー
        if (!$this->TwostepauthLogic->verifyCodeFromTemporarySecretKey($this->confirm_code)) {
            $errorList->add(__CLASS__ . '::error', 'コードが一致しません。再度入力してください。',);
            return 'input';
        }

        // ユーザの秘密鍵を保存
        $temporarySecretKey = $this->TwostepauthLogic->getTemporarySecretKey();
        $secretKey = $this->TwostepauthOpenssl->encryptSecretKey($temporarySecretKey);
        $beforeSavingInfo = $this->MdbAdminTwostepauth->getAdminTwostepauthInfo($admin_id);
        if (isset($beforeSavingInfo['admin_two_step_auth_id'])) {
            //DBにあれば更新
            $this->MdbAdminTwostepauth->update(['app_secret_key' => $secretKey], $beforeSavingInfo['admin_two_step_auth_id']);
        } else {
            //DBになければ新しく保存
            $this->MdbAdminTwostepauth->add(['admin_id' => $admin_id, 'app_secret_key' => $secretKey]);
        }
        /**
         * バックアップコードの作成
         */
        $admin_id = $this->session->getParameter("login_admin_id");
        //今までのバックアップコードは削除
        $this->MdbBackupcode->remove($admin_id);
        for ($createNum = 0; $createNum < 10; $createNum++) {
            $backupCode = (Password::resetPassword(12, 12, '3'));
            $data = ['admin_id' => $admin_id, 'code' => Password::hash($backupCode), 'is_used' => 0];
            if ($this->MdbBackupcode->add($data)) {
                $this->backupCodes[] = $backupCode;
            }
        }

        // セッションの秘密鍵を削除
        $this->TwostepauthLogic->removeTemporarySecretKey();
        // ２要素認証済みフラグを立てる
        $this->session->setParameter('two_factor_authenticated', '1');
        $this->TwostepauthLogic->addTwostepauthLog($this->DtoLog, history_id: '201', memo: '認証アプリの設定：有効'); // ログへ記録
        return 'success';
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
    function setDtoLog(&$DtoLog)
    {
        $this->DtoLog = &$DtoLog;
    }
    function setMdbBackupcode(&$MdbBackupcode)
    {
        $this->MdbBackupcode = &$MdbBackupcode;
    }
    function setBackupCode($backupCodes)
    {
        $this->backupCodes = $backupCodes;
    }

    function getSecretKeyQRCode()
    {
        $admin_account = $this->session->getParameter("login_admin_account");
        return $this->TwostepauthLogic->getTemporarySecretKeyQRCodeGoogleUrl($admin_account);
    }
    function getSecretKey()
    {
        return $this->TwostepauthLogic->getTemporarySecretKey();
    }
    //エラー画面用
    function getPreviousAction()
    {
        //戻るボタンのリンク先
        return "admin_twostepauth";
    }
    function getFunctionName()
    {
        //エラー画面のタイトル
        return '二要素認証の設定';
    }
    function getIsEnabled()
    {
        return '有効';
    }
    
    function getBackupCode()
    {
        return $this->backupCodes;
    }
}

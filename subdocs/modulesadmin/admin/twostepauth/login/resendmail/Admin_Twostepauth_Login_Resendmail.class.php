<?php

/**
 * 管理者側：二要素認証ログイン：メールの再送
 *
 * @version 5.4~
 * @author
 **/
class Admin_Twostepauth_Login_Resendmail 
{

    var $session;
    var $TwostepauthLogic;
    var $admin_id;

    function execute()
    {
        $log = &LogFactory::getLog();
        $log->debug(__CLASS__ . "が実行されました", __CLASS__ . "#execute");

        //権限系のチェック
        if ($errorMessage = $this->TwostepauthLogic->getAuthLoginErrorMessage($this->admin_id)) {
            echo json_encode([
                'token' => $this->getNewTokenValue(),
                'message' => $errorMessage
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        // メール送信
        $sendResult = $this->TwostepauthLogic->Confirmation_Code_Mail($this->admin_id, true);
        if (! $sendResult) {
            echo json_encode([
                'token' => $this->getNewTokenValue(),
                'message' => 'メールの再送が失敗しました。メールを再送するボタンを再度押してください。'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $this->session->removeParameter("two_step_mail_sended");
        echo json_encode([
            'token' => $this->getNewTokenValue(),
            'message' => 'メールを再送しました。'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    /**
     * setter
     */
    function setTwostepauthLogic($TwostepauthLogic)
    {
        $this->TwostepauthLogic = $TwostepauthLogic;
    }
    function setSession(&$session)
    {
        $this->session = &$session;
    }
    function setAdmin_id(&$admin_id)
    {
        $this->admin_id = &$admin_id;
    }
    function getPreviousAction()
    {
        //戻るボタンのリンク先
        return "admin_login";
    }
    function getFunctionName()
    {
        //エラー画面のタイトル
        return '二要素認証';
    }
    /**
     * 新しくなったトークンを取得
     */
    function getNewTokenValue()
    {
        $container = &DIContainerFactory::getContainer();
        $session = &$container->getComponent("Session");
        $token = new Token;
        $container->register($token, "Token");
        $token->setSession($session);
        $token->setName('AdminLoginTwoStepAuthToken');
        return $token->getValue();
    }
}

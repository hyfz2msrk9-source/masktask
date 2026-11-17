<?php

/**
 *管理者側二要素認証
 *
 * @version 5.4
 * @author
 **/
class Admin_Twostepauth_On 
{
    var $TwostepauthLogic;
    var $session;

    function execute()
    {
        $log = &LogFactory::getLog();
        $log->debug(__CLASS__ . "が実行されました", __CLASS__ . "#execute");

        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent('ActionChain');
        $errorList   = &$actionChain->getCurErrorList();

        if (!ADMIN_TWO_STEP_AUTH_FLG || $this->TwostepauthLogic->getAdmin_two_step_auth_type() !== '1') {
            $errorList->add(__CLASS__ . '::error',  '不正なアクセスです。');
            return 'error';
        }
        return 'input';
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
    /**
     * getter
     */
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
}

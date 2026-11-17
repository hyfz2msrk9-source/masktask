<?php


/**
 * 二要素認証の設定
 *
 * @package     WebFile
 * @version     5.4.?
 * @author
 * @copyright   KEYPOINT.INC
 * @access      public
 **/
require_once(MODULE_DIR . "/common/common.php");

class Admin_Twostepauth 
{
    var $MdbConfigs;
    var $MdbAdminTwostepauth;
    var $TwostepauthLogic;
    var $session;

    function execute()
    {
        $log = &LogFactory::getLog();
        $log->debug(__CLASS__ . "が実行されました", __CLASS__);
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent('ActionChain');
        $errorList   = &$actionChain->getCurErrorList();
        $two_step_auth_type = $this->TwostepauthLogic->getAdmin_two_step_auth_type();
        if (!$two_step_auth_type || !ADMIN_TWO_STEP_AUTH_FLG) {
            $errorList->add(__CLASS__ . '::error', '二要素認証機能は、現在ご利用いただけません。');
            return 'error';
        }

        return "success";
    }
    /**
     * setter
     */
    function setMdbAdminTwostepauth($MdbAdminTwostepauth)
    {
        $this->MdbAdminTwostepauth = $MdbAdminTwostepauth;
    }
    function setSession($session)
    {
        $this->session = $session;
    }
    function setMdbConfigs($MdbConfigs)
    {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setTwostepauthLogic($TwostepauthLogic)
    {
        $this->TwostepauthLogic = $TwostepauthLogic;
    }
    /**
     * getter
     */
    function getAdmin_id()
    {
        return $this->session->getParameter("admin_id");
    }
    function getAdminTwostepauthInfo()
    {
        $admin_id = $this->session->getParameter("admin_id");
        return $this->MdbAdminTwostepauth->getAdminTwostepauthInfo($admin_id);
    }
    function getIsEnabledAppTwoStepAuth()
    {
        $admin_id = $this->session->getParameter("admin_id");
        return $this->TwostepauthLogic->isEnabledAppAuthAccount($admin_id);
    }
    function getAdmin_two_step_auth_type()
    {
        return $this->MdbConfigs->getDirectValue("admin_two_step_auth_type");
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
        return '二要素認証の設定';
    }
    function getAdmin_Two_step_auth_flg_options()
    {
        return GlobalArray::getTwo_Step_Auth_Flg_Options();
    }
    function getAdmin_two_step_auth_span()
    {
        return $this->MdbConfigs->getDirectValue("admin_two_step_auth_span");
    }
    function getBackupCodeReissueErrorMessage()
    {
        //二要素認証の情報を取得
        $twoStepAuthType = $this->TwostepauthLogic->getAdmin_two_step_auth_type();
        $isEnabledAppTwoStepAuth = $this->getIsEnabledAppTwoStepAuth();
        if (!$twoStepAuthType || !ADMIN_TWO_STEP_AUTH_FLG) {
            return '二要素認証機能は、現在ご利用いただけません。';
        }
        if ($twoStepAuthType === '1' && !$isEnabledAppTwoStepAuth) {
            return '認証アプリが無効になっています。認証アプリを設定後バックアップコードを発行できます。';
        }
        $info = $this->getAdminTwostepauthInfo();
        $isSetMailAddr = boolval($info['email'] ?? '');
        if ($twoStepAuthType === '2' && !$isSetMailAddr) {
            return 'メールアドレスを設定後バックアップコードを発行できます。';
        }
        return '';
    }

}

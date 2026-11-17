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

class Admin_Twostepauth_Mailaddr 
{
    var $MdbConfigs;
    var $MdbAdminTwostepauth;
    var $session;
    var $TwostepauthLogic;

    function execute()
    {
        $log = &LogFactory::getLog();
        $log->debug(__CLASS__ . "が実行されました", __CLASS__);
        
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent('ActionChain');
        $errorList   = &$actionChain->getCurErrorList();

        //権限系のチェック
        if (!ADMIN_TWO_STEP_AUTH_FLG || $this->TwostepauthLogic->getAdmin_two_step_auth_type() !== '2') {
            $errorList->add(__CLASS__ . '::error',  '不正なアクセスです。');
            return 'error';
        }
        if (!ADMIN_TWO_STEP_AUTH_FLG) return "error";

        return "success";
    }
    function setMdbAdminTwostepauth($MdbAdminTwostepauth)
    {
        $this->MdbAdminTwostepauth = $MdbAdminTwostepauth;
    }
    function setSession($session)
    {
        $this->session = $session;
    }
    function getAdmin_id()
    {
        return $this->session->getParameter("admin_id");
    }
    function getAdminTwostepauthInfo()
    {
        $admin_id = $this->session->getParameter("admin_id");
        return $this->MdbAdminTwostepauth->getAdminTwostepauthInfo($admin_id);
    }
    //エラー画面用
    function getPreviousAction()
    {
        //戻るボタンのリンク先
        return '';
    }
    function getFunctionName()
    {
        //エラー画面のタイトル
        return '二要素認証の設定';
    }
    function setTwostepauthLogic($TwostepauthLogic)
    {
        $this->TwostepauthLogic = $TwostepauthLogic;
    }
    
}

<?php

/**
 * 二要素認証(完了)
 *
 * @version 5.4
 * @author
 **/
class Admin_Twostepauth_Off_Done 
{
    var $TwostepauthLogic;
    var $MdbAdminTwostepauth;
    var $session;
    var $MdbBackupcode;
    var $DtoLog;
    var $MdbAdminTwostepauthBrowser;

    function execute()
    {

        $log = &LogFactory::getLog();
        $log->debug(__CLASS__ . "が実行されました", __CLASS__ . "#execute");

        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent('ActionChain');
        $errorList   = &$actionChain->getCurErrorList();

        if (!ADMIN_TWO_STEP_AUTH_FLG || $this->TwostepauthLogic->getAdmin_two_step_auth_type() !== '1') {
            $errorList->add(__CLASS__ . '::error', '不正なアクセスです。');
            return 'error';
        }

        $admin_id = $this->session->getParameter("login_admin_id");
        $beforeSavingInfo = $this->MdbAdminTwostepauth->getAdminTwostepauthInfo($admin_id);
        // ユーザの秘密鍵を削除
        $this->MdbAdminTwostepauth->update(['app_secret_key' => NULL], $beforeSavingInfo['admin_two_step_auth_id']);
        //バックアップコードを削除
        $this->MdbBackupcode->remove($admin_id);
        // ユーザのブラウザごとの二要素認証の有効期限を削除
        $this->MdbAdminTwostepauthBrowser->delete($admin_id);
        $this->TwostepauthLogic->addTwostepauthLog($this->DtoLog, history_id: '201', memo: '認証アプリの設定：無効'); // ログへ記録

        return 'success';
    }

    function setTwostepauthLogic($TwostepauthLogic)
    {
        $this->TwostepauthLogic = $TwostepauthLogic;
    }
    function setMdbAdminTwostepauth($MdbAdminTwostepauth)
    {
        $this->MdbAdminTwostepauth = $MdbAdminTwostepauth;
    }
    function setSession($session)
    {
        $this->session = $session;
    }
    function setMdbBackupcode(&$MdbBackupcode)
    {
        $this->MdbBackupcode = &$MdbBackupcode;
    }
    function setDtoLog(&$DtoLog)
    {
        $this->DtoLog = &$DtoLog;
    }
    function setMdbAdminTwostepauthBrowser($MdbAdminTwostepauthBrowser)
    {
        $this->MdbAdminTwostepauthBrowser = $MdbAdminTwostepauthBrowser;
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
        return '無効';
    }

}

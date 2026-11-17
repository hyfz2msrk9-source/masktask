<?php

/**
 * 管理者側：二要素認証のバックアップコードの作成
 *
 * @version 5.4~
 * @author
 **/
class Admin_Twostepauth_Backupcode
{

    var $TwostepauthLogic;
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

        if (!ADMIN_TWO_STEP_AUTH_FLG || $this->TwostepauthLogic->getAdmin_two_step_auth_type() === '0') {
            $errorList->add(__CLASS__ . '::error', '不正なアクセスです。');
            return 'error';
        }
        //今までのバックアップコードは削除
        $this->MdbBackupcode->remove($admin_id);
        // バックアップコードの作成
        for ($createNum = 0; $createNum < 10; $createNum++) {
            $backupCode = (Password::resetPassword(12, 12, '3'));
            $data = ['admin_id' => $admin_id, 'code' => Password::hash($backupCode), 'is_used' => 0];
            if ($this->MdbBackupcode->add($data)) {
                $this->backupCodes[] = $backupCode;
            }
        }

        $this->TwostepauthLogic->addTwostepauthLog($this->DtoLog, history_id: '203', memo: '', admin_id: $admin_id); // ログへ記録
        return 'success';
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
    function getBackupCode()
    {
        return $this->backupCodes;
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
        return '';
    }
}

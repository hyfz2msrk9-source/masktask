<?php
require_once(MODULE_DIR . "/common/common.php");
/**
 * [機能説明] 管理者管理 - 管理者の削除(完了)
 * @updater
 *
 * @package     WebFile
 * @version     3.?.?
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_Admin_Del_Done
{
    var $session;
    var $MdbAdminUser;
    var $DtoAdminUser;
    var $DtoLog;
    var $MdbLog;
    var $MdbUser;
    var $MdbGroups;
    var $MdbSection;
    var $MdbAdminTwostepauth;
    var $MdbBackupcode;
    var $MdbAdminTwostepauthBrowser;

    function execute()
    {
        $log = &LogFactory::getLog();
        $log->debug("Admin_Admin_Del_Done", "Admin_Admin_Del_Done#execute");

        //エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        if (!ADMIN_MANAGER_FLG) {
            $errorList->add("Admin_Admin_Del_Done::error", "管理者の削除機能は現在、使用できません。");
            $this->removeSession();
            return "error";
        }
        // 削除ユーザの確認
        if (!$this->session->getParameter('del_admin_id')) {
            $errorList->add("Admin_Admin_Del_Done::error", "管理者の削除に失敗しました。");
            // session削除
            $this->removeSession();
            return "error";
        }
        // データセット
        $this->DtoAdminUser->setAdmin_id($this->session->getParameter('del_admin_id'));
        $this->DtoAdminUser->setAccount($this->session->getParameter('account'));
        $this->DtoAdminUser->setDel_flg('1');

        // 更新
        if (!$this->MdbAdminUser->updateAdmin($this->DtoAdminUser)) {
            $errorList->add("Admin_Admin_Del_Done::error", "管理者の削除に失敗しました。");
            // session削除
            $this->removeSession();
            return "error";
        }

        // USER・section・groupテーブルのowner_admin_idを1にする
        if (!$this->MdbUser->updateAdmin($this->DtoAdminUser->getAdmin_id())) {
            $errorList->add("Admin_Admin_Del_Done::error", "ユーザ情報の更新に失敗しました。");
            // session削除
            $this->removeSession();
            return "error";
        }
        if (!$this->MdbSection->updateAdmin($this->DtoAdminUser->getAdmin_id())) {
            $errorList->add("Admin_Admin_Del_Done::error", "部署情報の更新に失敗しました。");
            // session削除
            $this->removeSession();
            return "error";
        }
        if (!$this->MdbGroups->updateAdmin($this->DtoAdminUser->getAdmin_id())) {
            $errorList->add("Admin_Admin_Del_Done::error", "グループ情報の更新に失敗しました。");
            // session削除
            $this->removeSession();
            return "error";
        }
        if (!$this->MdbAdminTwostepauth->remove($this->DtoAdminUser->getAdmin_id())) {
            $errorList->add("Admin_Admin_Del_Done::error", "このアカウントの二要素認証の削除に失敗しました。");
            // session削除
            $this->removeSession();
        }
        //バックアップコードの削除
        $this->MdbBackupcode->remove($this->DtoAdminUser->getAdmin_id());
        if (!$this->MdbAdminTwostepauthBrowser->delete($this->DtoAdminUser->getAdmin_id())) {
            $errorList->add("Admin_Admin_Del_Done::error", "二要素認証のブラウザ情報の削除に失敗しました。");
            // session削除
            $this->removeSession();
        }

        // Log
        $history_id = 127; // 127:ユーザ管理＞管理者管理＞管理者の削除
        $memo  = "削除管理者\n";
        $memo .= "　アカウント：" . $this->DtoAdminUser->getAccount() . "\n";

        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id($this->session->getParameter('admin_id'));
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        // session削除
        $this->removeSession();
        return "success";
    }

    function removeSession()
    {
        $this->session->removeParameter("del_admin_id");
        $this->session->removeParameter("account");
    }

    /************************************
     * setter
     *************************************/
    function setSession($session)
    {
        $this->session = $session;
    }
    function setMdbAdminUser(&$MdbAdminUser)
    {
        $this->MdbAdminUser = &$MdbAdminUser;
    }
    function setDtoAdminUser($DtoAdminUser)
    {
        $this->DtoAdminUser = &$DtoAdminUser;
    }
    function setDtoLog($DtoLog)
    {
        $this->DtoLog = $DtoLog;
    }
    function setMdbLog($MdbLog)
    {
        $this->MdbLog = $MdbLog;
    }
    function setMdbUser($MdbUser)
    {
        $this->MdbUser = $MdbUser;
    }
    function setMdbSection($MdbSection)
    {
        $this->MdbSection = $MdbSection;
    }
    function setMdbGroups($MdbGroups)
    {
        $this->MdbGroups = $MdbGroups;
    }
    function setMdbAdminTwostepauth($MdbAdminTwostepauth)
    {
        $this->MdbAdminTwostepauth = $MdbAdminTwostepauth;
    }
    function setMdbBackupcode($MdbBackupcode)
    {
        $this->MdbBackupcode = $MdbBackupcode;
    }
    function setMdbAdminTwostepauthBrowser($MdbAdminTwostepauthBrowser)
    {
        $this->MdbAdminTwostepauthBrowser = $MdbAdminTwostepauthBrowser;
    }
}

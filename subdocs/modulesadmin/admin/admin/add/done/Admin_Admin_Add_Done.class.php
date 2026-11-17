<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] 管理者管理 - 管理者の追加(完了)
 * @updater
 *
 * @package     WebFile
 * @version     3.?.?
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_Admin_Add_Done {
    var $session;
    var $MdbAdminUser;
    var $DtoAdminUser;
    var $DtoLog;
    var $MdbLog;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Admin_Add_Done","Admin_Admin_Add_Done#execute");

        //エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        if(!ADMIN_MANAGER_FLG) {
            $errorList->add("Admin_Admin_Add_Done::error", "管理者の登録機能は現在、使用できません。");
            // session削除
            $this->removeSession();
            return "error";
        }
        // パスワード暗号化
        //$this->DtoAdminUser->setPassword(sha1($this->DtoAdminUser->getPassword()));
        $this->DtoAdminUser->setPassword(Password::hash($this->DtoAdminUser->getPassword()));
        // 登録
        if(!$this->MdbAdminUser->insertAdmin($this->DtoAdminUser)) {
            $errorList->add("Admin_Admin_Add_Done::error", "管理者の登録に失敗しました。");
            // session削除
            $this->removeSession();
            return "error";
        }

        // Log
        $history_id = 125; // 125:ユーザ管理＞管理者管理＞管理者の登録
        $memo  = "";
        $memo  = "追加管理者\n";
        $memo .= "　アカウント：".$this->DtoAdminUser->getAccount()."\n";
        $memo_new =  $this->DtoAdminUser->getMemo();
        $memo .= "　備考：" . ($memo_new === null || $memo_new === "" ? "(なし)" : $memo_new);
        $memo .= "\n";
        if( $this->DtoAdminUser->getLevel() == "5" ) {
            $memo .= "　権限：システム管理者\n";
        } else if( $this->DtoAdminUser->getLevel() == "4" ) {
            $memo .= "　権限：スーパーユーザ管理者\n";
        } else {
            $memo .= "　権限：ユーザ管理者\n";
        }
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        // session削除
        $this->removeSession();

        return "success";
    }

    function removeSession() {
        $this->session->removeParameter("account");
        $this->session->removeParameter("password");
        $this->session->removeParameter("memo");
        $this->session->removeParameter("level");
    }

    /************************************
    * setter
    *************************************/
    function setSession($session) {
        $this->session = $session;
    }
    function setMdbAdminUser(&$MdbAdminUser) {
        $this->MdbAdminUser = & $MdbAdminUser;
    }
    function setDtoAdminUser($DtoAdminUser) {
        $this->DtoAdminUser = & $DtoAdminUser;
    }
    function setDtoLog($DtoLog) {
        $this->DtoLog = $DtoLog;
    }
    function setMdbLog($MdbLog) {
        $this->MdbLog = $MdbLog;
    }

}
?>

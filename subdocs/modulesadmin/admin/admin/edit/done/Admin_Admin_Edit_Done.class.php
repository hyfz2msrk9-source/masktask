<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] 管理者管理 - 管理者の編集(完了)
 * @updater
 *
 * @package     WebFile
 * @version     3.?.?
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_Admin_Edit_Done {
    var $session;
    var $MdbAdminUser;
    var $DtoAdminUser;
    var $DtoLog;
    var $MdbLog;

    // 権限移行
    var $AdminuserDto;
    var $DtoGroups;
    var $MdbGroups;
    var $DtoSection;
    var $MdbSection;
    var $DtoUser;
    var $MdbUser;
    var $AdminLogic;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Admin_Edit_Done","Admin_Admin_Edit_Done#execute");

        //エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        if(!ADMIN_MANAGER_FLG) {
            $errorList->add("Admin_Admin_Edit_Done::error", "管理者の編集機能は、現在、使用できません。");
            $this->removeSession();
            return "error";
        }
        // 編集ユーザの確認
        if(!$this->session->getParameter('edit_admin_id')) {
            $errorList->add("Admin_Admin_Edit_Done::error", "管理者の編集に失敗しました。");
            // session削除
            $this->removeSession();
            return "error";
        } else {
            $this->DtoAdminUser->setAdmin_id($this->session->getParameter('edit_admin_id'));
        }
        // パスワード暗号化
        if($this->DtoAdminUser->getPassword()) {
            //$this->DtoAdminUser->setPassword(sha1($this->DtoAdminUser->getPassword()));
            $this->DtoAdminUser->setPassword(Password::hash($this->DtoAdminUser->getPassword()));
        }
        // 権限譲渡チェック
        $owner_log = "";
        if ($this->AdminuserDto->getOwner_admin_id() != $this->DtoAdminUser->getAdmin_id()) {
            $owner_info = $this->MdbAdminUser->getAdminuserInfo2($this->AdminuserDto->getOwner_admin_id());
            if (!$owner_info || $owner_info["del_flg"]==1) {
                $errorList->add("Admin_Admin_Edit_Done::error", "権限を移行する管理者は既に削除されています。");
                $this->removeSession();
                return "error";
            }
            // 権限移行
            $res = $this->AdminLogic->OwnerAdminUpdate($this->DtoAdminUser->getAdmin_id(),
                                                       $this->AdminuserDto->getOwner_admin_id(),
                                                       $this->DtoGroups,$this->MdbGroups,
                                                       $this->DtoSection,$this->MdbSection,
                                                       $this->DtoUser,$this->MdbUser,
                                                       $this->MdbAdminUser);
            if (key($res) == "error") {
                $errorList->add("Admin_Admin_Edit_Done::error", "管理者の編集に失敗しました。");
                $this->removeSession();
                return "error";
            } elseif (key($res) == "success") {
                if ($res["success"]) {
                    $owner_log = $res["success"];
                    $new_owner = $owner_info["account"];
                }
            }
        }

        // 更新
        if(!$this->MdbAdminUser->updateAdmin($this->DtoAdminUser)) {
            $errorList->add("Admin_Admin_Edit_Done::error", "管理者の編集に失敗しました。");
            // session削除
            $this->removeSession();
            return "error";
        }

        // Log
        $history_id = 126; // 126:ユーザ管理＞管理者管理＞管理者の編集
        $memo  = "";
        $memo  = "編集管理者";
        if($this->session->getParameter('account_org') != $this->DtoAdminUser->getAccount()) {
            $memo .= "\n";
            $memo .= "　アカウント：".$this->session->getParameter('account_org');
            $memo .= "→".$this->DtoAdminUser->getAccount();
        } else {
            $memo .= "\n";
            $memo .= "　アカウント：".$this->session->getParameter('account_org');
        }
        if($this->DtoAdminUser->getPassword() != "") {
            $memo .= "\n";
            if($this->DtoAdminUser->getPassword() != $this->session->getParameter("password_org")) {
                $memo .= "　パスワード：******→******";
            }else{
                $memo .= "　パスワード：******";
            }
        }
        $memo .= "\n　備考：";
        $memo_org = $this->session->getParameter('memo_org');
        $memo .= ($memo_org === null || $memo_org === "") ? "(なし)" : $memo_org;
        $memo_new =  $this->DtoAdminUser->getMemo();
        if($memo_new != $this->session->getParameter("memo_org")) {
            $memo .= "→";
            $memo .= ($memo_new === null || $memo_new === "") ? "(なし)" : $memo_new;
        }
        if($this->DtoAdminUser->getAccount_lock_flg() != $this->session->getParameter("account_lock_flg_org")) {
            $memo.= "\n";
            $memo.= "　アカウントロック：".($this->session->getParameter('account_lock_flg_org') ? "ロック中" : "解除中");
            $memo.= "→".($this->DtoAdminUser->getAccount_lock_flg() ? "ロック" : "解除");
        }
        if ($owner_log) {
            $memo.= "\n";
            $memo.= "　権限移行：".$this->DtoAdminUser->getAccount()."→".$new_owner."\n";
            $memo.= $owner_log;
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
        $this->session->removeParameter("memo_org");
        $this->session->removeParameter("account_lock_flg");
        $this->session->removeParameter("account_org");
        $this->session->removeParameter("password_org");
        $this->session->removeParameter("account_lock_flg_org");
        $this->session->removeParameter("edit_admin_id");
        $this->session->removeParameter("level");
        $this->session->removeParameter("owner_admin_id");
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
    // 権限移行
    function setAdminuserDto($AdminuserDto) {
        $this->AdminuserDto = $AdminuserDto;
    }
    function setDtoGroups($DtoGroups) {
        $this->DtoGroups = $DtoGroups;
    }
    function setMdbGroups($MdbGroups) {
        $this->MdbGroups = $MdbGroups;
    }
    function setDtoSection($DtoSection) {
        $this->DtoSection = $DtoSection;
    }
    function setMdbSection($MdbSection) {
        $this->MdbSection = $MdbSection;
    }
    function setDtoUser($DtoUser) {
        $this->DtoUser = $DtoUser;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = $MdbUser;
    }
    function setAdminLogic($AdminLogic) {
        $this->AdminLogic = $AdminLogic;
    }

}
?>

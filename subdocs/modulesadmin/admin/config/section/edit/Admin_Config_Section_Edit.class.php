<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * [機能説明] 部署グループ設定 - 部署グループ編集(Ajaxレスポンス)
 *
 * @package     WebFile
 * @author      Yusuke Numata
 * @copyright   KeyPoint
 * @access      public
 */

require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Section_Edit {
    var $session;
    var $DtoUser;
    var $MdbUser;
    var $DtoSection;
    var $MdbSection;
    var $DtoSectionusers;
    var $MdbSectionusers;
    var $DtoGroupsusers;
    var $MdbGroupsusers;
    var $SectionLogic;
    var $GroupsusersLogic;
    var $DtoLog;
    var $MdbLog;
    var $MdbUpload;
    var $MdbAdminuser;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Section_Editが実行されました","Admin_Config_Section_Edit#execute");

        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        // IDチェック
        if(!is_numeric($this->DtoSection->getSection_id())) {
            exit("ALERT,不正なアクセスを検知しました。");
        }

        // サブ管理者編集権限設定チェック
        if( $this->session->getParameter("login_admin_level") >= 5 && defined("ADMIN_MANAGER_FLG") && ADMIN_MANAGER_FLG) {
            if ($this->DtoSection->getSubadmin_editable_flg() !== "0"  &&  $this->DtoSection->getSubadmin_editable_flg() !== "1") {
                $errorList->add("Admin_Config_Section_Edit::error", "サブ管理者編集権限設定を選択してください。");
                return "input";
            }
        }

        // 部署アドレス帳 設定チェック
        if( $this->session->getParameter("login_admin_level") >= 5 && defined("ADMIN_MANAGER_FLG") && ADMIN_MANAGER_FLG) {
            if ($this->DtoSection->getView_dept_address_book_flg() !== "0"  &&  $this->DtoSection->getView_dept_address_book_flg() !== "1") {
                $errorList->add("Admin_Config_Section_Edit::error", "部署別アドレス帳設定を選択してください。");
                return "input";
            }
        }

        // 部署グループ名存在チェック
        $where = array();
        $where[] = "section_id <> " . Db_Common::escape_string($this->DtoSection->getSection_id()) . "";
        $where[] = "section_name = '" . Db_Common::escape_string($this->DtoSection->getSection_name()) . "'";
        if ($this->MdbSection->getExistCheck($where)) {
            $errorList->add("Admin_Config_Section_Edit::error", "入力した部署グループ名は、すでに使用されています。");
            return "input";
        }

        // サブ管理者編集許可チェック
        if( $this->session->getParameter("login_admin_level") < 5 ) {
            $where = array();
            $where[] = "section_id = " . Db_Common::escape_string($this->DtoSection->getSection_id()) . "";
            $where[] = "subadmin_editable_flg = '1'";
            if (!$this->MdbSection->getExistCheck($where)) {
                $errorList->add("Admin_Config_Section_Edit::error", "この部署グループの編集を許可されていません。");
                return "input";
            }
        }

        // ユーザIDの整合性をチェック
        $sectionusers = $this->session->getParameter("sectionusers");
        if (is_array($sectionusers) && $sectionusers) {
            $user_ids = $this->SectionLogic->getUserIdList($this->MdbUser, $this->DtoUser);
            if (array_diff(array_keys($sectionusers), array_values($user_ids))) {
                exit("ALERT,一部のユーザが見つかりませんでした。\nすでに削除されている可能性があります。");
            }
        } else {
            $sectionusers = array();
        }

        // ログ記録用に現部署グループ情報を取得
        $memo_section_name_old = "";
        $memo_section_name = $this->DtoSection->getSection_name();
        $info = $this->MdbSection->getInfo2($this->DtoSection->getSection_id());
        if (isset($info['section_name'])) {
            $memo_section_name_old.= $info['section_name'];
        }
 
        // 管理者チェック（ログ記録用に情報を取得）
        $memo_admin_account_old = "";
        $memo_admin_account = "";
        if (defined("ADMIN_MANAGER_FLG") && ADMIN_MANAGER_FLG) {
            if( $this->session->getParameter("login_admin_level") >= 5) {
                if (!$this->DtoSection->getOwner_admin_id()) {
                    exit("ALERT,管理者を選択してください。");
                }
                if (!is_numeric($this->DtoSection->getOwner_admin_id())) {
                    exit("ALERT,管理者の選択で不正な値が選択されました。");
                }
            }
            // 旧owner_admin_idが空なら1を入れておく
            if (!isset($info['owner_admin_id']) || !$info["owner_admin_id"]) {
                $info['owner_admin_id'] = 1;
            }
            // 現時点での管理者アカウントを取得
            $admin_info2 = $this->MdbAdminuser->getAdminuserInfo2($info["owner_admin_id"]);
            if ($admin_info2) {
                $memo_admin_account_old = $admin_info2["account"]; // ログ用
            }
            if ($this->session->getParameter("login_admin_level")  < 5) {
                // サブ管理者なら新しい管理者idは今のidのままにしておく
                $this->DtoSection->setOwner_admin_id($info['owner_admin_id']);
            } else {
                // システム管理者なら新しい管理者の存在チェック
                $admin_info = $this->MdbAdminuser->getAdminuserInfo2($this->DtoSection->getOwner_admin_id());
                if (!$admin_info || $admin_info["del_flg"]==1) {
                    exit("ALERT,選択した管理者は、既に削除されている可能性があります。");
                }
                if ($info['owner_admin_id'] != $this->DtoSection->getOwner_admin_id()) {
                    $memo_admin_account = $admin_info["account"]; // ログ用
                }
            }
        } else {
            $this->DtoSection->setOwner_admin_id($this->session->getParameter("admin_id"));
        }

        // 部署グループ編集
        $this->DtoSection->setParent_section_id(0);
        if (!$this->MdbSection->update($this->DtoSection)) {
            exit("ALERT,部署グループの編集に失敗しました。");
        }

        // ログ記録用に現部署グループユーザ情報を取得
        $memo_section_user_old = "";
        $memo_section_user = "";
        $userkeylist = $this->MdbUser->getUserKeyList();
        $sectionusers_old = $this->SectionLogic->getSectionusersList($this->MdbSectionusers, $this->DtoSectionusers);
        $lock_users = array(); //ロック解除するユーザリスト
        foreach ($sectionusers_old as $val) {
            $memo_section_user_old.= "　" . $userkeylist[$val['user_id']]['user_name'] . "（" . $userkeylist[$val['user_id']]['mailaddr'] . "）　履歴公開：".($val['history_open_flg'] ? "する" : "しない")."\n";
            //追加リストに元ユーザがいなかったらロックを解除する
//            if(!in_array($val['user_id'],$sectionusers)) {
            if(!array_key_exists($val['user_id'],$sectionusers)) { 
                $lock_users[] = $val['user_id'];
            }
        }

        //ロック解除
        if(FILE_LOCK_OPTION_FLG && $lock_users) {
            //部署が所属しているグループを抽出して
            $this->GroupsusersLogic->updateFileLockSectionUser(
                                                               $this->DtoSection->getSection_id(), $lock_users,
                                                               $this->MdbGroupsusers, $this->MdbUpload
                                                               );
        }
        /*
        $sectionusers_old = $this->SectionLogic->getSectionUserIdList($this->MdbSectionusers, $this->DtoSectionusers);
        foreach ($sectionusers_old as $user_id) {
            $memo_section_user_old.= "　" . $userkeylist[$user_id]['user_name'] . "（" . $userkeylist[$user_id]['mailaddr'] . "）\n";
        }
        */

        // 部署グループユーザ全削除
        $this->MdbSectionusers->delete($this->DtoSectionusers);
        // 部署グループユーザ登録
        $error = 0;
        $this->DtoSectionusers->setLeader_flg(0);
        foreach ($sectionusers as $user_id => $history_open_flg) {
            $this->DtoSectionusers->setUser_id($user_id);
            $this->DtoSectionusers->setHistory_open_flg($history_open_flg);
            if (!$this->MdbSectionusers->insert($this->DtoSectionusers)) {
                $error++;
            } else {
                $memo_section_user.= "　" . $userkeylist[$user_id]['user_name'] . "（" . $userkeylist[$user_id]['mailaddr'] . "）　履歴公開：".($history_open_flg ? "する" : "しない")."\n";
            }
        }

        // 部署グループのユーザの排他
        $this->GroupsusersLogic->excluseSectionUesr($this->MdbGroupsusers,$this->DtoSection->getSection_id());

        // ログ記録
        $history_id = 130;
        $memo = "";
        if ($memo_section_name_old !== $memo_section_name) {
            $memo.= "部署グループ名：" . $memo_section_name_old . "→" . $memo_section_name  . "\n";
        } else {
            $memo.= "部署グループ名：" . $memo_section_name . "\n";
        }
        if ($memo_admin_account_old && $memo_admin_account) {
            $memo.= "編集管理者：" . $memo_admin_account_old . "→" . $memo_admin_account . "\n";
        }
        if ($memo_section_user_old !== $memo_section_user) {
            $memo.= "部署グループ所属ユーザ：\n" . $memo_section_user_old . "→\n" . $memo_section_user;
        } else {
            $memo.= "部署グループ所属ユーザ：\n" . $memo_section_user;
        }
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        if ($error === 0) {
            exit("SUCCESS,部署グループの編集に成功しました。");
        } else {
            exit("SUCCESS,部署グループの編集に成功しましたが、\n一部ユーザの部署追加に失敗しました。");
        }
    }

    /***************************************************
     * setter
     **************************************************/
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoUser($DtoUser){
        $this->DtoUser = $DtoUser;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = $MdbUser;
    }
    function setDtoSection($DtoSection){
        $this->DtoSection = $DtoSection;
    }
    function setMdbSection($MdbSection) {
        $this->MdbSection = $MdbSection;
    }
    function setDtoSectionusers($DtoSectionusers){
        $this->DtoSectionusers = $DtoSectionusers;
    }
    function setMdbSectionusers($MdbSectionusers) {
        $this->MdbSectionusers = $MdbSectionusers;
    }
    function setSectionLogic($SectionLogic) {
        $this->SectionLogic = $SectionLogic;
    }
    function setDtoGroupsusers($DtoGroupsusers){
        $this->DtoGroupsusers = $DtoGroupsusers;
    }
    function setMdbGroupsusers($MdbGroupsusers) {
        $this->MdbGroupsusers = $MdbGroupsusers;
    }
    function setDtoLog($DtoLog) {
        $this->DtoLog = $DtoLog;
    }
    function setMdbLog($MdbLog) {
        $this->MdbLog = $MdbLog;
    }
    function setGroupsusersLogic(&$GroupsusersLogic) {
        $this->GroupsusersLogic = &$GroupsusersLogic;
    }
    function setMdbUpload($MdbUpload) {
        $this->MdbUpload = $MdbUpload;
    }
    function setGroupLogic($GroupLogic) {
        $this->GroupLogic = $GroupLogic;
    }
    function setMdbAdminuser($MdbAdminuser) {
        $this->MdbAdminuser = $MdbAdminuser;
    }

    /***************************************************
     * getter
     **************************************************/
    // 部署グループID
    function getSection_id() {
        return $this->DtoSection->getSection_id();
    }
    // 部署グループ名
    function getSection_name() {
        return $this->DtoSection->getSection_name();
    }
}

/*
 * Local Variables:
 * mode: php
 * coding: utf-8-unix
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * indent-tabs-mode: nil
 * End:
 */

?>

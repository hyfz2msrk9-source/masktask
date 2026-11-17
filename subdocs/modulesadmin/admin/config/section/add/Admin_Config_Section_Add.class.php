<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * [機能説明] 部署グループ設定 - 部署グループ追加(Ajaxレスポンス)
 *
 * @package     WebFile
 * @author      Yusuke Numata
 * @copyright   KeyPoint
 * @access      public
 */

require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Section_Add {
    var $session;

    var $DtoUser;
    var $MdbUser;

    var $DtoSection;
    var $MdbSection;

    var $DtoSectionusers;
    var $MdbSectionusers;

    var $MdbGroupsusers;

    var $SectionLogic;
    var $GroupsusersLogic;

    var $DtoLog;
    var $MdbLog;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Section_Addが実行されました","Admin_Config_Section_Add#execute");

        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        // サブ管理者編集権限設定チェック
        if( $this->session->getParameter("login_admin_level") >= 5 ) {
            if (defined("ADMIN_MANAGER_FLG") && ADMIN_MANAGER_FLG) {
                if ($this->DtoSection->getSubadmin_editable_flg() !== "0"  &&  $this->DtoSection->getSubadmin_editable_flg() !== "1") {
                    $errorList->add("Admin_Config_Section_Add::error", "サブ管理者編集権限設定を選択してください。");
                    return "input";
                }
            } else {
                $this->DtoSection->setSubadmin_editable_flg( "0" );
            }
        } else {
            $this->DtoSection->setSubadmin_editable_flg( "1" );
        }

        // 部署アドレス帳 設定チェック
        if( $this->session->getParameter("login_admin_level") >= 5 ) {
            if (defined("ADMIN_MANAGER_FLG") && ADMIN_MANAGER_FLG) {
                if ($this->DtoSection->getView_dept_address_book_flg() !== "0"  &&  $this->DtoSection->getView_dept_address_book_flg() !== "1") {
                    $errorList->add("Admin_Config_Section_Add::error", "部署別アドレス帳設定を選択してください。");
                    return "input";
                }
            } else {
                $this->DtoSection->setView_dept_address_book_flg("1");
            }
        } else {
            $this->DtoSection->setView_dept_address_book_flg("1");
        }

        // 部署グループ名存在チェック
        $where = array();
        $where[] = "section_name = '" . Db_Common::escape_string($this->DtoSection->getSection_name()) . "'";
        if ($this->MdbSection->getExistCheck($where)) {
            $errorList->add("Admin_Config_Section_Add::error", "入力した部署グループ名は、すでに使用されています。");
            return "input";
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

        // 部署グループ登録
        $this->DtoSection->setParent_section_id(0);
        $this->DtoSection->setOwner_admin_id( $this->session->getParameter("admin_id") );
        if (!$section_id = $this->MdbSection->insert($this->DtoSection)) {
            exit("ALERT,部署グループの追加に失敗しました。");
        }

        // 部署グループユーザ登録
        $memo_section_user = "";
        $error = 0;
        $userkeylist = $this->MdbUser->getUserKeyList();
        $this->DtoSectionusers->setSection_id($section_id);
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

        // 部署に所属したユーザの排他
        $this->GroupsusersLogic->excluseSectionUesr($this->MdbGroupsusers,$section_id);

        // ログ記録
        $history_id = 129;
        $memo = "";
        $memo.= "部署グループ名：" . $this->DtoSection->getSection_name() . "\n";
        if ($memo_section_user !== "") {
            $memo.= "部署グループ所属ユーザ：\n" . $memo_section_user;
        }
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        if ($error === 0) {
            exit("SUCCESS,部署グループの追加に成功しました。");
        } else {
            exit("SUCCESS,部署グループの追加に成功しましたが、\n一部ユーザの部署追加に失敗しました。");
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
    function setDtoLog($DtoLog) {
        $this->DtoLog = $DtoLog;
    }
    function setMdbLog($MdbLog) {
        $this->MdbLog = $MdbLog;
    }
    function setGroupsusersLogic(&$GroupsusersLogic) {
        $this->GroupsusersLogic = &$GroupsusersLogic;
    }
    function setMdbGroupsusers(&$MdbGroupsusers) {
        $this->MdbGroupsusers = &$MdbGroupsusers;
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
    // サブ管理者編集可能フラグ
    function getSubadmin_editable_flg() {
        return $this->DtoSection->getSubadmin_editable_flg();
    }
    // 部署アドレス帳フラグ
    function getView_dept_address_book_flg() {
        return $this->DtoSection->getView_dept_address_book_flg();
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
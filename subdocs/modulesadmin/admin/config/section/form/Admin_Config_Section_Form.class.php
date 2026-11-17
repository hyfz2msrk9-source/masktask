<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * [機能説明] 部署グループ設定 - 入力フォーム(Ajaxレスポンス)
 *
 * @package     WebFile
 * @author      Yusuke Numata
 * @copyright   KeyPoint
 * @access      public
 */

require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Section_Form {
    var $session;
    var $DtoSection;
    var $MdbSection;
    var $DtoSectionusers;
    var $MdbSectionusers;
    var $SectionLogic;
    var $MdbAdminuser;
    var $DtoAdminuser;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Section_Formが実行されました","Admin_Config_Section_Form#execute");

        $section_id = $this->getSection_id();
        if (!$section_id) {
            // 登録
            // セッションクリア
            $this->session->removeParameter("sectionusers");
        } else {
            // 編集
            // 数値チェック
            if( !is_numeric($section_id)) {
                exit("ALERT,不正なアクセスが検出されました。");
            }
            // 存在チェック
            if (!$this->MdbSection->getInfo($this->DtoSection)) {
                exit("ALERT,部署グループ情報の取得に失敗しました。");
            }
            // アクセス権チェック
            if( $this->session->getParameter("login_admin_level") < 5  &&  $this->DtoSection->getSubadmin_editable_flg() == "0" ) {
                exit("ALERT,不正なアクセスが検出されました。");
            }
            // 部署グループユーザIDをセッションにセット
            //$sectionusers = $this->SectionLogic->getSectionUserIdList($this->MdbSectionusers, $this->DtoSectionusers);
            $list = $this->SectionLogic->getSectionusersList($this->MdbSectionusers, $this->DtoSectionusers);
            $sectionusers = array();
            foreach ($list as $val) {
                $sectionusers[$val['user_id']] = $val['history_open_flg'];
            }
            $this->session->setParameter("sectionusers", $sectionusers);
        }

        // 部署グループIDをセッションにセット
        $this->session->setParameter("section_id", $section_id);

        return "success";
    }

    /***************************************************
     * setter
     **************************************************/
    function setSession($session) {
        $this->session = $session;
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
    function setMdbAdminuser($MdbAdminuser) {
        $this->MdbAdminuser = $MdbAdminuser;
    }
    function setDtoAdminuser($DtoAdminuser) {
        $this->DtoAdminuser = $DtoAdminuser;
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
        if (!$this->getSection_id()) {
            return 1;
        }
        return $this->DtoSection->getSubadmin_editable_flg();
    }
    // 部署アドレス帳表示フラグ
    function getView_dept_address_book_flg() {
        if (!$this->getSection_id()) {
            return 1;   // 初期値：表示する
        }
        return $this->DtoSection->getView_dept_address_book_flg();
    }
    // 編集管理者ID
    function getAdmin_id() {
        if (!$this->DtoSection->getOwner_admin_id()) {
            $where = array("level=10","del_flg=0");
            $this->MdbAdminuser->getAdminuserInfoByWhere($where, $this->DtoAdminuser);
            return $this->DtoAdminuser->getAdmin_id();            
        }
        return $this->DtoSection->getOwner_admin_id();
    }
    // 編集管理者リスト
    function getAdmin_list() {
        $select_list = array();
        $order = array("account");
        $list = $this->MdbAdminuser->getAdminUserSelect();
        foreach ($list as $key => $val) {
            $select_list[$val["admin_id"]] = $val["account"];
        }
        return $select_list;
    }
    function getView_Dept_Address_Book_flg_Options() {
        return GlobalArray::getView_Dept_Address_Book_Flg_Options();
    }
    function getSection_address_num() {
        return count($this->MdbSection->getSectionAddress($this->DtoSection->getSection_id()));
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
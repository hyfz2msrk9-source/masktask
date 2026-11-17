<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * [機能説明] 部署グループ設定 - FlexiGrid用 部署グループ無所属ユーザリスト(jsonレスポンス)
 *
 * @package     WebFile
 * @author      Yusuke Numata
 * @copyright   KeyPoint
 * @access      public
 */

require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Section_Grid_Unbelong {
    var $session;

    var $DtoFlexi;
    var $DtoUser;
    var $MdbUser;
    var $SectionLogic;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Section_Grid_Unbelongが実行されました","Admin_Config_Section_Grid_Unbelong#execute");

        // セッションから部署所属ユーザを取得
        $sectionusers = $this->session->getParameter("sectionusers");

        // 無所属ユーザ一覧取得
        $where = array();
        $where[] = "del_flg=0";
        if (is_array($sectionusers) && $sectionusers) {
            $user_id = implode(",", array_keys($sectionusers));
            $where[] = "user_id NOT IN(" . Db_Common::escape_string($user_id) . ")";
        }
        $this->MdbUser->getFlexiUserList3($this->DtoUser, $this->DtoFlexi, $where);
        exit($this->SectionLogic->getUserListJSON($this->DtoUser, $this->DtoFlexi));
    }

    /***************************************************
     * setter
     **************************************************/
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoFlexi($DtoFlexi) {
        $this->DtoFlexi = $DtoFlexi;
    }
    function setDtoUser($DtoUser){
        $this->DtoUser = $DtoUser;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = $MdbUser;
    }
    function setSectionLogic($SectionLogic) {
        $this->SectionLogic = $SectionLogic;
    }

    /***************************************************
     * getter
     **************************************************/
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
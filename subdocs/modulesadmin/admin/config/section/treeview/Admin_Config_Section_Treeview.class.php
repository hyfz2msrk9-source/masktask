<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * [機能説明] 部署グループ設定 - 部署グループツリービュー(Ajaxレスポンス)
 *
 * @package     WebFile
 * @author      Yusuke Numata
 * @copyright   KeyPoint
 * @access      public
 */

require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Section_Treeview {
    var $session;

    var $DtoSection;
    var $MdbSection;
    var $SectionLogic;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Section_Treeviewが実行されました","Admin_Config_Section_Treeview#execute");

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
    function setSectionLogic($SectionLogic) {
        $this->SectionLogic = $SectionLogic;
    }

    /***************************************************
     * getter
     **************************************************/
    // 部署グループツリー
    function getTreeView() {
        return $this->SectionLogic->getTreeView($this->MdbSection,0,$this->session->getParameter("login_admin_level"));
    }
    function getSection_id() {
        return $this->DtoSection->getSection_id();
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
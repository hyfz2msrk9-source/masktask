<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * [機能説明] 部署グループ設定 - FlexiGrid用 ログ公開ユーザチェック
 *
 * @package     WebFile
 * @author      Yusuke Numata
 * @copyright   KeyPoint
 * @access      public
 */

require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Section_Grid_Checkbox {
    var $session;

    var $DtoFlexi;
    var $DtoUser;
    var $MdbUser;
    var $SectionLogic;

    var $user_id;
    var $checked;
    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Section_Grid_Unbelongが実行されました","Admin_Config_Section_Grid_Unbelong#execute");

        // セッションから部署所属ユーザを取得
        $sectionusers = $this->session->getParameter("sectionusers");
        if (is_array($sectionusers) && isset($sectionusers[$this->user_id])){
            if ($this->checked == "true") {
                $sectionusers[$this->user_id] = "1";
            } else {
                $sectionusers[$this->user_id] = "0";
            }
        }
        $this->session->setParameter("sectionusers", $sectionusers);
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

    function setUser_id($user_id) {
        $this->user_id = $user_id;
    }
    function setChecked($checked) {
        $this->checked = $checked;
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
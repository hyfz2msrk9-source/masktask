<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * [機能説明] 部署グループ設定 - 部署グループユーザドラッグドロップ(ajaxレスポンス)
 *
 * @package     WebFile
 * @author      Yusuke Numata
 * @copyright   KeyPoint
 * @access      public
 */

require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Section_Grid_Dragdrop {
    var $session;

    var $DtoFlexi;
    var $DtoUser;
    var $MdbUser;
    var $SectionLogic;

    // request
    var $belong_flg;
    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Section_Grid_Dragdropが実行されました","Admin_Config_Section_Grid_Dragdrop#execute");

        
        // リクエストパラメータの整合性をチェック
        $request = $this->DtoUser->getUser_id();
        if (!is_array($request) || !$request) {
            exit("ALERT,不正な処理が実行されました。");
        }

        $session = $this->session->getParameter("sectionusers");
        
        // リクエストのユーザIDのチェック
        $ok_flg = true;
        $sectionusers = array();
        foreach($request as $val) {
            if(!is_numeric($val)) {
                $ok_flg = false;
                break;
            }
            if (isset($session[$val])) {
                $sectionusers[$val] = $session[$val];
            } else {
                $sectionusers[$val] = "0";
            }
        }
        if(!$ok_flg) {
            exit("ALERT,不正な処理が実行されました。");
        }

        // リクエストパラメータの存在をチェック
        $user_ids = $this->SectionLogic->getUserIdList($this->MdbUser, $this->DtoUser);
        if ( array_diff($request, $user_ids)) {
            exit("ALERT,該当ユーザが見つかりませんでした。\nすでに削除されている可能性があります。");
        }

        if (!$this->belong_flg) {
            /* 無所属から所属へドラッグ */
            if (is_array($session) && $session) {
                $sectionusers = $session + $sectionusers;
            }
        } else {
            /* 所属から無所属へドラッグ */
            $sectionusers = array_diff_assoc($session, $sectionusers);
        }
        // 部署グループユーザをセッションにセット
        $this->session->setParameter("sectionusers", $sectionusers);
        exit;
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
    function setBelong_flg($belong_flg) {
        $this->belong_flg = $belong_flg;
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
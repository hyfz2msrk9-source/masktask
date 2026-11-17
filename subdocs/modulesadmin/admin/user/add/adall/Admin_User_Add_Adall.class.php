<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] ユーザ管理 - ADユーザの一括登録
 * @updater     Hiroko Yanai（2018/04/12）SAML認証(OneLogin)に対応
 *
 * @package     WebFile
 * @version     3.15.2
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_User_Add_Adall {
    var $session;
    var $MdbUser;

    var $user_capacity;

    var $CommonList;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_User_Add","Admin_User_Add#execute");

        if (AD_FLG != 1 ||  AD_PACKAGE != 1 ) {
            return "error";
        }

        // パスワード認証を使わない場合
        $usage_auth_types = $this->CommonList->getUsageAuthTypes();
        if (!isset($usage_auth_types[0])) {
            return "error";
        }

        if ( !defined("MAX_USER") || MAX_USER < 0 ) {
            return "error";
        }

        // MAX_USERが0(無制限)
        if ( MAX_USER == 0 ) {
            $this->user_capacity = -1;
        } else {
            $this->user_capacity = MAX_USER - $this->MdbUser->getActiveUsers();
            if ( $this->user_capacity < 0 ) {
                $this->user_capacity = 0;
            }
        }


        return "success";
    }
    /************************************
    * setter
    *************************************/
    function setSession($session) {
        $this->session = $session;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = $MdbUser;
    }
    function setCommonList($CommonList) {
        $this->CommonList =& $CommonList;
    }
    /************************************
    * getter
    *************************************/
    function getUser_capacity() {
        return $this->user_capacity;
    }
}
?>

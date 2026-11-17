<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] グループ共有機能 - グループ共有機能に関する設定 - グループ追加
 * @updater     Hiroko Yanai（2018/04/13）SAML認証(OneLogin)に対応。SSOやSAMLでも、設定は更新可能とする
 *
 * @package     WebFile
 * @version     3.15.2
 * @author
 * @copyright   KEY POINT
 * @access      public
 */
class Admin_Config_Share_Add {
    var $session;
    var $DtoGroups;
    var $MdbGroups;
    var $MdbGroups_users;
    var $MdbUpload;
    var $MdbConfigs;
    var $DtoConfigs;
    var $groupDto;
    var $MdbMchoice;

    var $limit_capacity;
    var $del_span_init;
    var $back_flg;
    var $readonly_flgs;
    var $section_readonly_flgs;
    var $leader_array;
    var $user_array;
    var $section_array;

    var $CommonList;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Share_Addが実行されました","Admin_Config_Share_Add#execute");

        // 2008/12/16@yano 割当てがなしモードはチェックしない
        if (!defined('SHARE_SPACE_FLG') || SHARE_SPACE_FLG == 0) {
            $this->use_capacity = $this->MdbGroups->getUseCapacity();
            if ((SHARE_MAX_CAPACITY*1024*1024*1024 - $this->use_capacity)<1) {
                //上限に達しているので、追加出来ない
                return "limit";
            }
        } else {
            $this->use_capacity = $this->MdbUpload->getAllUserShareCapacity();
        }
        $this->limit_capacity = SHARE_MAX_CAPACITY*1024*1024*1024 - $this->use_capacity;

        // 2013/04/30 ユーザ設定から戻ってきた場合、セッションから入力を復元
        if ( $this->back_flg == "1" ) {
            $this->groupDto->setAdmin_edit_flg( $this->session->getParameter( "admin_edit_flg" ) );
            $this->groupDto->setName( $this->session->getParameter( "name" ) );
            $this->groupDto->setFolder_name( $this->session->getParameter( "folder_name" ) );
            $this->groupDto->setDel_span( $this->session->getParameter( "del_span" ) );
            $this->groupDto->setCapacity( $this->session->getParameter( "capacity" ) );
            $this->groupDto->setLock_flg( $this->session->getParameter( "lock_flg" ) );
            $this->groupDto->setLock_dl_flg( $this->session->getParameter( "lock_dl_flg" ) );
            $this->groupDto->setTerm_flg( $this->session->getParameter( "term_flg" ) );
            $this->groupDto->setTerm_type_id( $this->session->getParameter( "term_type_id" ) );
            $this->groupDto->setStart_year( $this->session->getParameter( "start_year" ) );
            $this->groupDto->setStart_month( $this->session->getParameter( "start_month" ) );
            $this->groupDto->setStart_day( $this->session->getParameter( "start_day" ) );
            $this->groupDto->setEnd_year( $this->session->getParameter( "end_year" ) );
            $this->groupDto->setEnd_month( $this->session->getParameter( "end_month" ) );
            $this->groupDto->setEnd_day( $this->session->getParameter( "end_day" ) );
            $this->groupDto->setCancel_span( $this->session->getParameter( "cancel_span" ) );
            $this->groupDto->setSend_flg( $this->session->getParameter( "send_flg" ) );
            $this->groupDto->setLog_share_flg( $this->session->getParameter( "log_share_flg" ) );
            $this->groupDto->setMail_show_flg( $this->session->getParameter( "mail_show_flg" ) );
            $this->groupDto->setApi_flg($this->session->getParameter( "api_flg" ));

            $this->del_span_init = $this->session->getParameter("del_span_init");
            $this->alledit_flg = $this->session->getParameter("alledit_flg");
            $this->session->setParameter( "member_edit", "1");
            $this->session->setParameter( "users", $this->groupDto->getUsers() );
            $this->session->setParameter( "leaders", $this->groupDto->getLeaders() );
            $this->session->setParameter( "sections", $this->groupDto->getSections() );
            $this->session->setParameter( "readonly_flgs", $this->readonly_flgs );
            $this->session->setParameter( "section_readonly_flgs", $this->section_readonly_flgs );
            $this->session->setParameter( "group_admin_operate_flg", $this->groupDto->getGroup_admin_operate_flg() );
        } else {
            $this->session->removeParameter( "admin_edit_flg" );
            $this->session->removeParameter( "name" );
            $this->session->removeParameter( "folder_name" );
            $this->session->removeParameter( "del_span" );
            $this->session->removeParameter( "capacity" );
            $this->session->removeParameter( "lock_flg" );
            $this->session->removeParameter( "lock_dl_flg" );
            $this->session->removeParameter( "term_flg" );
            $this->session->removeParameter( "term_type_id" );
            $this->session->removeParameter( "start_year" );
            $this->session->removeParameter( "start_month" );
            $this->session->removeParameter( "start_day" );
            $this->session->removeParameter( "end_year" );
            $this->session->removeParameter( "end_month" );
            $this->session->removeParameter( "end_day" );
            $this->session->removeParameter( "cancel_span" );
            $this->session->removeParameter( "send_flg" );
            $this->session->removeParameter( "log_share_flg" );
            $this->session->removeParameter( "mail_show_flg" );
            $this->session->removeParameter( "del_span_init" );
            $this->session->removeParameter( "alledit_flg" );
            $this->session->removeParameter( "users" );
            $this->session->removeParameter( "leaders" );
            $this->session->removeParameter( "sections" );
            $this->session->removeParameter( "member_edit" );
            $this->session->removeParameter( "readonly_flgs" );
            $this->session->removeParameter( "section_readonly_flgs" );
            $this->session->removeParameter( "group_admin_operate_flg" );
            $this->session->removeParameter( "api_flg" );
        }

        return "success";
    }

    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoGroups($DtoGroups) {
        $this->DtoGroups = $DtoGroups;
    }
    function setMdbGroups($MdbGroups) {
        $this->MdbGroups = $MdbGroups;
    }
    function setMdbGroups_users($MdbGroups_users) {
        $this->MdbGroups_users = $MdbGroups_users;
    }
    function setMdbUpload($MdbUpload) {
        $this->MdbUpload = $MdbUpload;
    }
    function setDel_span_init($del_span_init) {
        $this->del_span_init = $del_span_init;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setDtoConfigs($DtoConfigs) {
        $this->DtoConfigs = $DtoConfigs;
    }
    function setGroupDto($groupDto) {
        $this->groupDto = $groupDto;
    }
    function setMdbMchoice($MdbMchoice) {
        $this->MdbMchoice = $MdbMchoice;
    }
    function setBack_flg($back_flg) {
        $this->back_flg = $back_flg;
    }
    function setCommonList($CommonList) {
        $this->CommonList =& $CommonList;
    }

    // getter
    function getGroupList() {
        return $this->group_list;
    }
    function getShareMaxCapacity()
    {
          return SHARE_MAX_CAPACITY."G";
    }
    function getUseCapacity()
    {
        return StrChange::CapacityUnit($this->use_capacity);
    }
    function getGroupCount()
    {
        return $this->group_count;
    }
    function getLimitCapacity()
    {
        //$this->limit_capacity = SHARE_MAX_CAPACITY*1024*1024*1024 - $this->use_capacity;
        return StrChange::CapacityUnit($this->limit_capacity);
    }
    function getDel_span()
    {
        if (is_null($this->del_span_init) && (string)$this->del_span_init !== '0') {
            if ($this->MdbConfigs->getValCnt("del_span") >= 1) {
                $this->MdbConfigs->getInfo($this->DtoConfigs,"del_span");
                return $this->DtoConfigs->getValue();
            } else {
                return 0;
            }
        } else {
            return $this->del_span_init;
        }
    }
    function getLockOptions()
    {
        return GlobalArray::getFile_Lock_Options();
    }
    function getLockDlOptions()
    {
        return GlobalArray::getFile_Lock_Download_Options();
    }
    function getLockFlg() {
        return $this->groupDto->getLock_flg();
    }
    function getLockDlFlg() {
        return $this->groupDto->getLock_dl_flg();
    }
    function getCancel_span() {
        if ($this->groupDto->getCancel_span() != "") {
            return ($this->groupDto->getCancel_span() != '0') ? $this->groupDto->getCancel_span() : '0';
        }
        return 0;
    }

    function getTerm_flg() {
        if (SHARE_FOLDER_TERM_REQUIRED_FLG) {
            return SHARE_FOLDER_TERM_REQUIRED_FLG;
        }
        return $this->groupDto->getTerm_flg();
    }
    function getStart_year() {
        if ( $this->groupDto->getStart_year() ) {
            return $this->groupDto->getStart_year();
        } else {
            return date("Y");
        }
    }
    function getStart_month() {
        if ( $this->groupDto->getStart_month() ) {
            return $this->groupDto->getStart_month();
        } else {
            return date("m");
        }
    }
    function getStart_day() {
        if ( $this->groupDto->getStart_day() ) {
            return $this->groupDto->getStart_day();
        } else {
            return date("d");
        }
    }
    function getEnd_year() {
        if ( $this->groupDto->getEnd_year() ) {
            return $this->groupDto->getEnd_year();
        } else {
            $span_time = SHARE_FOLDER_TERM_DEFAULT_SPAN  * 24 * 60 * 60;
            return date("Y" , time() + $span_time);
        }
    }
    function getEnd_month() {
        if ( $this->groupDto->getEnd_month() ) {
            return $this->groupDto->getEnd_month();
        } else {
            $span_time = SHARE_FOLDER_TERM_DEFAULT_SPAN  * 24 * 60 * 60;
            return date("m" , time() + $span_time);
        }
    }
    function getEnd_day() {
        if ( $this->groupDto->getEnd_day() ) {
            return $this->groupDto->getEnd_day();
        } else {
            $span_time = SHARE_FOLDER_TERM_DEFAULT_SPAN  * 24 * 60 * 60;
            return date("d" , time() + $span_time);
        }
    }
    function getTerm_type_options() {
        return $this->MdbMchoice->getOptions("share_term_type_id");
    }
    function getTerm_type_id() {
        return $this->groupDto->getTerm_type_id();
    }
    function getSend_flg() {
        return $this->groupDto->getSend_flg();
    }
    function getLog_share_flg() {
        return $this->groupDto->getLog_share_flg();
    }
    function getAdmin_edit_flg() {
        if ( $this->back_flg == 1 ) {
            return $this->groupDto->getAdmin_edit_flg();
        } else {
            //デフォルト許可をセット
            return 1;
        }
    }
    function getGroup_admin_operate_flg() {
        return $this->groupDto->getGroup_admin_operate_flg();
    }
    function getMail_show_flg() {
        return $this->groupDto->getMail_show_flg();
    }
    function getApi_Flg_Options() {
        return GlobalArray::getApi_Flg_Options();
    }
    function getApi_flg() {
        if ($this->groupDto->getApi_flg()) {
            return 1;
        } else {
            return 0;
        }
    }
    function getName()
    {
        return $this->groupDto->name;
    }
    function getFolder_name()
    {
        return $this->groupDto->folder_name;
    }
    function getCapacity()
    {
        return $this->groupDto->getCapacity();
    }
    function getAuth_type_notice() {
        $str = '';
        $usage_auth_types = $this->CommonList->getUsageAuthTypes();
        if (isset($usage_auth_types[1]) OR isset($usage_auth_types[2])) {
            unset($usage_auth_types[0]);
            $str = implode('、', $usage_auth_types);
            $str = '<br />※' . $str . 'のユーザには適用されません。';
        }
        return $str;
    }

}
?>

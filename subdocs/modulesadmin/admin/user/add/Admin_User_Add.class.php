<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] ユーザ管理 - ユーザ登録
 * @updater
 *
 * @package     WebFile
 * @version
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_User_Add {
    var $session;
    var $DtoUser;
    var $userLogic;
    var $MdbConfigs;
    var $DtoConfigs;
    var $user_add_back;
    var $MdbUser;
    var $MdbGroups;
    var $DtoGroups;
    var $DtoGroupsusers;
    var $MdbGroupsusers;
    var $DtoSection;
    var $MdbSection;
    var $adminuserLogic;
    var $GroupsusersLogic;
    var $MdbGroupsadminusers;
    var $subfolder;

    var $start_at_year;
    var $start_at_month;
    var $start_at_day;

    var $end_at_year;
    var $end_at_month;
    var $end_at_day;

    var $all_readonly_flg;

    var $CommonList;
    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_User_Add","Admin_User_Add#execute");

        $cnt = $this->userLogic->getUserActiveCont($this->DtoUser, $this->MdbUser);
        if (MAX_USER != 0) {
            if (MAX_USER <= $cnt) {
                return "error";
            }
        }

        if (!$this->user_add_back) {
            $this->adminuserLogic->setInit($this->DtoUser); // 全部null
            $this->session->removeParameter("auth_type");
            $this->session->removeParameter("account");
            $this->session->removeParameter("google_mail_address");
            $this->session->removeParameter("saml_mail_address");
            $this->session->removeParameter("password");
            $this->session->removeParameter("user_name");
            $this->session->removeParameter("user_kana");
            $this->session->removeParameter("mailaddr");
            $this->session->removeParameter("max_file_size");
            $this->session->removeParameter("ext_file_size");
            $this->session->removeParameter("ext_unlimited_flg");
            $this->session->removeParameter("max_up_size");
            $this->session->removeParameter("start_dt");
            $this->session->removeParameter("end_dt");
            $this->session->removeParameter("memo");
            $this->session->removeParameter("readonly_flg");
            $this->session->removeParameter("stop_flg");
            $this->session->removeParameter("ad_flg");
            $this->session->removeParameter("section_id");
            $this->session->removeParameter("subfolder");
            $this->session->removeParameter("password_change_flg");
            $this->session->removeParameter("api_flg");
            $this->session->removeParameter("file_delete_limit_dt");
            $this->session->removeParameter("send_flg");
            $this->session->removeParameter("collect_flg");
            $this->session->removeParameter("open_folder_flg");
            $this->session->removeParameter("delivery_flg");
            $this->session->removeParameter("gathering_flg");

            $this->DtoGroupsusers->setSection_id("");

            // 認証方式
            $usage_auth_types = $this->CommonList->getUsageAuthTypes();
            $this->DtoUser->setAuth_type($this->userLogic->get_first_key($usage_auth_types));
            if ($this->DtoUser->getAuth_type() == 1) {
                $this->DtoUser->setSso_flg(1);
            } elseif ($this->DtoUser->getAuth_type() == 2) {
                $this->DtoUser->setSaml_flg(1);
            }
        } else {
            if (TERM_OF_VALIDITY) {
                $dt = $this->session->getParameter("start_dt");
                if ($dt != NULL) {
                    list($this->start_at_year,$this->start_at_month,$this->start_at_day) = explode("-",$dt);
                    $this->start_at_month = abs($this->start_at_month);
                    $this->start_at_day = abs($this->start_at_day);
                }
                $dt = $this->session->getParameter("end_dt");
                if ($dt != NULL) {
                    list($this->end_at_year,$this->end_at_month,$this->end_at_day) = explode("-",$dt);
                    $this->end_at_month = abs($this->end_at_month);
                    $this->end_at_day = abs($this->end_at_day);
                }
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
    function setDtoUser($DtoUser) {
        $this->DtoUser = $DtoUser;
    }
    function setUserLogic($UserLogic) {
        $this->userLogic = $UserLogic;
    }
    function setUser_add_back($user_add_back) {
        $this->user_add_back = $user_add_back;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setDtoConfigs($DtoConfigs) {
        $this->DtoConfigs = $DtoConfigs;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = $MdbUser;
    }
    function setMdbGroups($MdbGroups) {
        $this->MdbGroups = $MdbGroups;
    }
    function setDtoGroups($DtoGroups) {
        $this->DtoGroups = $DtoGroups;
    }
    function setDtoGroupsusers($DtoGroupsusers) {
        $this->DtoGroupsusers = $DtoGroupsusers;
    }
    function setMdbGroupsusers($MdbGroupsusers) {
        $this->MdbGroupsusers = $MdbGroupsusers;
    }
    function setDtoSection($DtoSection) {
        $this->DtoSection = $DtoSection;
    }
    function setMdbSection($MdbSection) {
        $this->MdbSection = $MdbSection;
    }
    function setAll_readonly_flg($all_readonly_flg) {
        $this->all_readonly_flg = intval($all_readonly_flg);
    }
    function setAdminuserLogic($adminuserLogic) {
        $this->adminuserLogic = $adminuserLogic;
    }
    function setGroupsusersLogic(&$GroupsusersLogic) {
        $this->GroupsusersLogic = & $GroupsusersLogic;
    }
    function setMdbGroupsadminusers(&$MdbGroupsadminusers) {
        $this->MdbGroupsadminusers =&$MdbGroupsadminusers;
    }
    function setSubfolder($subfolder) {
        $this->subfolder = $subfolder;
    }
    function setCommonList($CommonList) {
        $this->CommonList =& $CommonList;
    }
    /************************************
    * getter
    *************************************/
    function getAccount() {
        return $this->DtoUser->getAccount();
    }
    function getPassword_change_flg() {
        if (!$this->user_add_back) {
            return 1;
        }
        return $this->DtoUser->getPassword_change_flg();
    }
    function getUser_name() {
        return $this->DtoUser->getUser_name();
    }
    function getUser_kana() {
        return $this->DtoUser->getUser_kana();
    }
    function getMailaddr() {
        return $this->DtoUser->getMailaddr();
    }
    function getMax_file_size() {
        return $this->DtoUser->getMax_file_size();
    }
    function getExt_file_size() {
        return $this->DtoUser->getExt_file_size();
    }
    function getExt_unlimited_flg() {
        return $this->DtoUser->getExt_unlimited_flg();
    }
    function getMax_up_size() {
        if (!$this->DtoUser->getMax_up_size()) {
            return "0";
        } else {
            return $this->DtoUser->getMax_up_size();
        }
    }
    function getStart_at_year() {
      return $this->start_at_year;
    }
    function getStart_at_month() {
      return $this->start_at_month;
    }
    function getStart_at_day() {
      return $this->start_at_day;
    }
    function getEnd_at_year() {
      return $this->end_at_year;
    }
    function getEnd_at_month() {
      return $this->end_at_month;
    }
    function getEnd_at_day() {
      return $this->end_at_day;
    }
    function getMemo() {
        return $this->DtoUser->getMemo();
    }
    function getPassmin() {
        $this->MdbConfigs->getInfo($this->DtoConfigs,"pass_min_num");
        return $this->DtoConfigs->getValue();
    }
    function getPassmax() {
        $this->MdbConfigs->getInfo($this->DtoConfigs,"pass_max_num");
        return $this->DtoConfigs->getValue();
    }
    function getSection_id() {
        return $this->DtoGroupsusers->getSection_id();
    }
    function getSectionKeyList () {
        if ( $this->session->getParameter("login_admin_level") < 5 ) {
            return $this->MdbSection->getKeyList("",array("subadmin_editable_flg='1'"));
        } else {
            return $this->MdbSection->getKeyList();
        }
    }
    function getGroupsList() {
        return $this->adminuserLogic->getGroupsList(
            $this->DtoGroups, $this->DtoGroupsusers,
            $this->MdbGroups, $this->MdbGroupsusers, $this->MdbSection, $this->MdbGroupsadminusers,
            $this->session->getParameter("login_admin_level")
        );
    }
    function getType_List() {
        return array(
            "" => '<img src="images/ico-authority-none.gif" title="なし"/>なし',
            "1" => '<img src="images/ico-authority-r.gif" title="読み出しのみ"/>読み出しのみ',
            "2" => '<img src="images/ico-authority-rw.gif" title="読み／書き"/>読み／書き',
            "0" => '<img src="images/ico-authority-rwx.gif" title="読み／書き／フォルダ操作"/>読み／書き／フォルダ操作',
        );
    }
    function getType_ListImg() {
        return array(
            "" => '<img src="images/ico-authority-none.gif" title="なし"/>',
            "1" => '<img src="images/ico-authority-r.gif" title="読み出しのみ"/>',
            "2" => '<img src="images/ico-authority-rw.gif" title="読み／書き"/>',
            "0" => '<img src="images/ico-authority-rwx.gif" title="読み／書き／フォルダ操作"/>',
        );
    }
    function getAll_readonly_flg() {
        return $this->all_readonly_flg;
    }
    function getStop_flg() {
        return is_null( $this->DtoUser->getStop_flg() ) ?
            0 : $this->DtoUser->getStop_flg();
    }
    function getAd_flg() {
        return $this->DtoUser->getAd_flg();
    }
    function getSubfolder() {
        return $this->subfolder;
    }
    function getApi_flg() {
        return is_null( $this->DtoUser->getApi_flg() ) ?
            0 : $this->DtoUser->getApi_flg();
    }
    function getFile_delete_limit_dt() {
        if (!$this->DtoUser->getFile_delete_limit_dt()) {
            return "0";
        } else {
            return $this->DtoUser->getFile_delete_limit_dt();
        }
    }
    function getSend_flg() {
        if (!$this->user_add_back) {
            return 1;
        }
        return $this->DtoUser->getSend_flg();
    }
    function getCollect_flg() {
        if (!$this->user_add_back) {
            return 1;
        }
        return $this->DtoUser->getCollect_flg();
    }
    function getOpen_folder_flg() {
        if (!$this->user_add_back) {
            return 1;
        }
        return $this->DtoUser->getOpen_folder_flg();
    }
    function getDelivery_flg() {
        return is_null( $this->DtoUser->getDelivery_flg() ) ?
            0 : $this->DtoUser->getDelivery_flg();
    }
    function getGathering_flg() {
        return is_null( $this->DtoUser->getGathering_flg() ) ?
            0 : $this->DtoUser->getGathering_flg();
    }

    function getAll_auth_types() {
        return $this->CommonList->getAllAuthTypes();
    }
    function getUsage_auth_types() {
        return $this->CommonList->getUsageAuthTypes();
    }
    function getUsage_auth_types_space() {
        $usage_auth_types = $this->CommonList->getUsageAuthTypes();
        foreach ($usage_auth_types as $key => $value) {
            $usage_auth_types_space[$key] = " " . $value;
        }
        return $usage_auth_types_space;
    }
    function getAuth_type() {
        return $this->DtoUser->getAuth_type();
    }
    function getGoogle_mail_address() {
        return $this->DtoUser->getGoogle_mail_address();
    }
    function getSaml_mail_address() {
        return $this->DtoUser->getSaml_mail_address();
    }
    function getDel_span() {
        $this->MdbConfigs->getInfo($this->DtoConfigs, "del_span");
        return $this->DtoConfigs->getValue();
    }
}
?>

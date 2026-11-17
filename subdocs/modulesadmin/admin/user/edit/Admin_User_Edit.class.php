<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] ユーザ管理 - ユーザ編集
 * @updater     Hiroko Yanai（2018/09/27）認証方式について修正
 *
 * @package     WebFile
 * @version     3.16.0
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_User_Edit {
    var $session;

    var $userDto;
    var $DtoUser;
    var $MdbUser;
    var $adminuserLogic;

    var $DtoConfigs;
    var $MdbConfigs;

    var $DtoGroups;
    var $MdbGroups;

    var $DtoGroupsusers;
    var $MdbGroupsusers;
    var $GroupsusersLogic;

    var $DtoSection;
    var $MdbSection;

    var $DtoSectionusers;
    var $MdbSectionusers;

    var $MdbGroupsadminusers;

    var $MdbAdminuser;
    var $DtoAdminuser;
    var $AdminuserDto;
    var $AdminadminLogic;

    var $all_readonly_flg;
    var $user_edit_back;
    var $subfolder;

    //Log
    var $log_auth_type;
    var $log_account;
    var $log_google_mail_address;
    var $log_saml_mail_address;
    var $log_mailaddr;
    var $log_user_name;
    var $log_user_kana;
    var $log_max_file_size;
    var $log_ext_file_size;
    var $log_ext_unlimited_flg;
    var $log_memo;
    var $log_stop_flg;
    var $log_ad_flg;
    var $log_account_lock_flg;
    var $log_section;
    var $log_start_dt;
    var $log_end_dt;
    var $log_owner_admin_id;
    var $log_password_change_flg;
    var $log_api_flg;
    var $log_file_delete_limit_dt;
    var $log_send_flg;
    var $log_collect_flg;
    var $log_open_folder_flg;
    var $log_delivery_flg;
    var $log_gathering_flg;
    var $start_at_year;
    var $start_at_month;
    var $start_at_day;
    var $end_at_year;
    var $end_at_month;
    var $end_at_day;

    var $CommonList;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_User_Edit","Admin_User_Edit#execute");

        if (!$this->user_edit_back) {
            // ユーザ情報取得
            $this->MdbUser->getUserInfo($this->DtoUser);
            if ($this->DtoUser->getUser_id() == "") {
                //存在しないID
                return "input";
            }
            // 削除されているユーザ
            if ($this->DtoUser->getDel_flg() == '1') {
                return "input";
            }
            $this->DtoUser->setMax_file_size(round($this->DtoUser->getMax_file_size()/1024/1024,2));
            $this->DtoUser->setExt_file_size(round($this->DtoUser->getExt_file_size()/1024/1024,2));
            $this->DtoUser->setMax_up_size(round($this->DtoUser->getMax_up_size()/1024/1024,2));

            //開始日・有効期限
            if (TERM_OF_VALIDITY) {
                $dt = $this->DtoUser->getStart_dt();
                if ($dt != NULL) {
                    list($this->start_at_year,$this->start_at_month,$this->start_at_day) = explode("-",$dt);
                    $this->start_at_month = abs($this->start_at_month);
                    $this->start_at_day = abs($this->start_at_day);
                }
                $dt = $this->DtoUser->getEnd_dt();
                if ($dt != NULL) {
                    list($this->end_at_year,$this->end_at_month,$this->end_at_day) = explode("-",$this->DtoUser->getEnd_dt());
                    $this->end_at_month = abs($this->end_at_month);
                    $this->end_at_day = abs($this->end_at_day);
                }
            }

            // 認証方式
            if ($this->DtoUser->getSso_flg()) {
                $edit_user_auth_type = 1;
            } elseif ($this->DtoUser->getSaml_flg()) {
                $edit_user_auth_type = 2;
            } else {
                $edit_user_auth_type = 0;
            }
            $usage_auth_types = $this->CommonList->getUsageAuthTypes();
            // 存在しない場合は、使えるやつをセット
            if (!array_key_exists($edit_user_auth_type, $usage_auth_types)) {
                $this->DtoUser->setAuth_type($this->adminuserLogic->get_first_key($usage_auth_types));
            } else {
                $this->DtoUser->setAuth_type($edit_user_auth_type);
                if ($edit_user_auth_type == 1) {
                    $this->DtoUser->setGoogle_mail_address($this->DtoUser->getAccount());
                    //$this->DtoUser->setAccount('');
                } elseif ($edit_user_auth_type == 2) {
                    $this->DtoUser->setSaml_mail_address($this->DtoUser->getAccount());
                    //$this->DtoUser->setAccount('');
                }
            }

            // Log用
            $this->log_auth_type            = $this->DtoUser->getAuth_type();
            $this->log_account              = $this->DtoUser->getAccount();
            $this->log_google_mail_address  = $this->DtoUser->getGoogle_mail_address();
            $this->log_saml_mail_address    = $this->DtoUser->getSaml_mail_address();
            $this->log_mailaddr             = $this->DtoUser->getMailaddr();
            $this->log_user_name            = $this->DtoUser->getUser_name();
            $this->log_user_kana            = $this->DtoUser->getUser_kana();
            $this->log_max_file_size        = $this->DtoUser->getMax_file_size();
            $this->log_ext_file_size        = $this->DtoUser->getExt_file_size();
            $this->log_ext_unlimited_flg    = $this->DtoUser->getExt_unlimited_flg();
            $this->log_max_up_size          = $this->DtoUser->getMax_up_size();
            $this->log_memo                 = $this->DtoUser->getMemo();
            $this->log_stop_flg             = $this->DtoUser->getStop_flg();
            $this->log_ad_flg               = $this->DtoUser->getAd_flg();
            $this->log_account_lock_flg     = $this->DtoUser->getAccount_lock_flg();
            $this->log_start_dt             = $this->DtoUser->getStart_dt();
            $this->log_end_dt               = $this->DtoUser->getEnd_dt();
            $this->log_owner_admin_id       = $this->DtoUser->getOwner_admin_id();
            $this->log_password_change_flg  = $this->DtoUser->getPassword_change_flg();
            $this->log_api_flg              = $this->DtoUser->getApi_flg();
            $this->log_file_delete_limit_dt = $this->DtoUser->getFile_delete_limit_dt();
            $this->log_send_flg             = $this->DtoUser->getSend_flg();
            $this->log_collect_flg          = $this->DtoUser->getCollect_flg();
            $this->log_open_folder_flg      = $this->DtoUser->getOpen_folder_flg();
            $this->log_delivery_flg         = $this->DtoUser->getDelivery_flg();
            $this->log_gathering_flg        = $this->DtoUser->getGathering_flg();

            // ユーザの所属部署を取得
            $this->DtoGroupsusers->section_id=array();
            $sectionkeylist = $this->getSectionKeyList();
            $where = array();
            $where[] = "user_id = " . Db_Common::escape_string($this->DtoUser->getUser_id());
            if ( $this->session->getParameter("login_admin_level") < 5 ) {
                $section_ids = "";
                foreach( $sectionkeylist as $key => $val ) {
                    if ( $section_ids != "" ) {
                        $section_ids .= ",";
                    }
                    $section_ids .= $key;
                }
                $where[] = "section_id IN(".$section_ids.")";
            }
            $list = $this->MdbSectionusers->getSectionusersList($where);
            if ($list) {
                foreach ($list as $val) {
                    $this->DtoGroupsusers->section_id[] = $val['section_id'];
                    $this->log_section.= "　　".$sectionkeylist[$val['section_id']]."\n";
                }
            }

            // ユーザの共有グループに対する権限を取得
            $list = $this->MdbGroups->getJoinList2($this->DtoUser->getUser_id());
            $type_list = $this->getType_List();
            foreach ($list as $val) {
                $this->DtoGroupsusers->readonly_flg[$val['id']] = $val['readonly_flg'];
            }

            $this->session->removeParameter("auth_type");
            $this->session->removeParameter("account");
            $this->session->removeParameter("google_mail_address");
            $this->session->removeParameter("saml_mail_address");
            $this->session->removeParameter("mailaddr");
            $this->session->removeParameter("user_name");
            $this->session->removeParameter("user_kana");
            $this->session->removeParameter("password");
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
            $this->session->removeParameter("account_lock_flg");
            $this->session->removeParameter("owner_admin_id");
            $this->session->removeParameter("section_id");
            $this->session->removeParameter("owner_admin_id");
            $this->session->removeParameter("password_change_flg");
            $this->session->removeParameter("reinfo");
            $this->session->removeParameter("api_flg");
            $this->session->removeParameter("file_delete_limit_dt");
            $this->session->removeParameter("send_flg");
            $this->session->removeParameter("collect_flg");
            $this->session->removeParameter("open_folder_flg");
            $this->session->removeParameter("delivery_flg");
            $this->session->removeParameter("gathering_flg");
            $this->session->removeParameter("two_step_auth_disable_flg");

            $this->session->removeParameter("log_auth_type");
            $this->session->removeParameter("log_account");
            $this->session->removeParameter("log_google_mail_address");
            $this->session->removeParameter("log_saml_mail_address");
            $this->session->removeParameter("log_mailaddr");
            $this->session->removeParameter("log_user_name");
            $this->session->removeParameter("log_user_kana");
            $this->session->removeParameter("log_max_file_size");
            $this->session->removeParameter("log_ext_file_size");
            $this->session->removeParameter("log_ext_unlimited_flg");
            $this->session->removeParameter("log_max_up_size");
            $this->session->removeParameter("log_start_dt");
            $this->session->removeParameter("log_end_dt");
            $this->session->removeParameter("log_memo");
            $this->session->removeParameter("log_shere_groups");
            $this->session->removeParameter("log_stop_flg");
            $this->session->removeParameter("log_ad_flg");
            $this->session->removeParameter("log_account_lock_flg");
            $this->session->removeParameter("log_section");
            $this->session->removeParameter("log_owner_admin_id");
            $this->session->removeParameter("log_password_change_flg");
            $this->session->removeParameter("log_api_flg");
            $this->session->removeParameter("log_file_delete_limit_dt");
            $this->session->removeParameter("log_send_flg");
            $this->session->removeParameter("log_collect_flg");
            $this->session->removeParameter("log_open_folder_flg");
            $this->session->removeParameter("log_delivery_flg");
            $this->session->removeParameter("log_gathering_flg");

        } else {
            //開始日・有効期限
            if (TERM_OF_VALIDITY) {
                $dt = $this->session->getParameter("start_dt");
                if ($dt != NULL) {
                    list($this->start_at_year,$this->start_at_month,$this->start_at_day)=explode("-",$dt);
                    $this->start_at_month = abs($this->start_at_month);
                    $this->start_at_day = abs($this->start_at_day);
                }
                $dt = $this->session->getParameter("end_dt");
                if ($dt != NULL) {
                    list($this->end_at_year,$this->end_at_month,$this->end_at_day)=explode("-",$dt);
                    $this->end_at_month = abs($this->end_at_month);
                    $this->end_at_day = abs($this->end_at_day);
                }
            }
        }

        // ユーザ管理者の場合のアクセス制限
        if ($this->session->getParameter("login_admin_level") == "3"  &&  $this->DtoUser->getOwner_admin_id() != $this->session->getParameter("admin_id")) {
            // エラー初期化
            $container   = &DIContainerFactory::getContainer();
            $actionChain = &$container->getComponent("ActionChain");
            $errorList   = &$actionChain->getCurErrorList();
            $errorList->add("Admin_User_Edit::error","このアカウントで追加したユーザではないため、編集できません。");
            return "input";
        }

        return "success";
    }
    /************************************
    * setter
    *************************************/
    function setSession($session) {
        $this->session = $session;
    }
    function setUserDto($userDto) {
        $this->userDto = $userDto;
    }
    function setDtoUser($DtoUser) {
        $this->DtoUser = $DtoUser;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = $MdbUser;
    }
    function setAdminuserLogic($adminuserLogic) {
        $this->adminuserLogic = $adminuserLogic;
    }

    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setDtoConfigs($DtoConfigs) {
        $this->DtoConfigs = $DtoConfigs;
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
    function setGroupsusersLogic(&$GroupsusersLogic) {
        $this->GroupsusersLogic = & $GroupsusersLogic;
    }

    function setDtoSection($DtoSection) {
        $this->DtoSection = $DtoSection;
    }
    function setMdbSection($MdbSection) {
        $this->MdbSection = $MdbSection;
    }

    function setDtoSectionusers($DtoSectionusers) {
        $this->DtoSectionusers = $DtoSectionusers;
    }
    function setMdbSectionusers($MdbSectionusers) {
        $this->MdbSectionusers = $MdbSectionusers;
    }

    function setAll_readonly_flg($all_readonly_flg) {
        $this->all_readonly_flg = intval($all_readonly_flg);
    }
    function setUser_edit_back($user_edit_back) {
        $this->user_edit_back = $user_edit_back;
    }

    // Log
    function setLog_auth_type($log_auth_type) {
        return $this->log_auth_type = $log_auth_type;
    }
    function setLog_account($log_account) {
        return $this->log_account = $log_account;
    }
    function setLog_google_mail_address($log_google_mail_address) {
        return $this->log_google_mail_address = $log_google_mail_address;
    }
    function setLog_saml_mail_address($log_saml_mail_address) {
        return $this->log_saml_mail_address = $log_saml_mail_address;
    }
    function setLog_user_name($log_user_name) {
        return $this->log_user_name = $log_user_name;
    }
    function setLog_user_kana($log_user_kana) {
        return $this->log_user_kana = $log_user_kana;
    }
    function setLog_mailaddr($log_mailaddr) {
        return $this->log_mailaddr = $log_mailaddr;
    }
    function setLog_max_file_size($log_max_file_size) {
        return $this->log_max_file_size = $log_max_file_size;
    }
    function setLog_ext_file_size($log_ext_file_size) {
        return $this->log_ext_file_size = $log_ext_file_size;
    }
    function setLog_ext_unlimited_flg($log_ext_unlimited_flg) {
        return $this->log_ext_unlimited_flg = $log_ext_unlimited_flg;
    }
    function setUser_max_up_size($log_max_up_size) {
        return $this->log_max_up_size = $log_max_up_size;
    }
    function setLog_start_dt($log_start_dt) {
        return $this->log_start_dt = $log_start_dt;
    }
    function setLog_end_dt($log_end_dt) {
        return $this->log_end_dt = $log_end_dt;
    }

    function setLog_memo($log_memo) {
        return $this->log_memo = $log_memo;
    }
    function setLog_stop_flg($log_stop_flg) {
        return $this->log_stop_flg = $log_stop_flg;
    }
    function setLog_ad_flg($log_ad_flg) {
        return $this->log_ad_flg = $log_ad_flg;
    }
    function setLog_account_lock_flg($log_account_lock_flg) {
        return $this->log_account_lock_flg = $log_account_lock_flg;
    }
    function setLog_section($log_section) {
        return $this->log_section = $log_section;
    }
    function setLog_owner_admin_id($log_owner_admin_id) {
        return $this->log_owner_admin_id = $log_owner_admin_id;
    }
    function setLog_password_change_flg($log_password_change_flg) {
        return $this->log_password_change_flg = $log_password_change_flg;
    }
    function setLog_api_flg($log_api_flg) {
        return $this->log_api_flg = $log_api_flg;
    }
    function setLog_file_delete_limit_dt($log_file_delete_limit_dt) {
        return $this->log_file_delete_limit_dt = $log_file_delete_limit_dt;
    }
    function setLog_send_flg($log_send_flg) {
        return $this->log_send_flg = $log_send_flg;
    }
    function setLog_collect_flg($log_collect_flg) {
        return $this->log_collect_flg = $log_collect_flg;
    }
    function setLog_open_folder_flg($log_open_folder_flg) {
        return $this->log_open_folder_flg = $log_open_folder_flg;
    }
    function setLog_delivery_flg($log_delivery_flg) {
        return $this->log_delivery_flg = $log_delivery_flg;
    }
    function setLog_gathering_flg($log_gathering_flg) {
        return $this->log_gathering_flg = $log_gathering_flg;
    }
    function setMdbGroupsadminusers(&$MdbGroupsadminusers) {
        $this->MdbGroupsadminusers = & $MdbGroupsadminusers;
    }
    function setSubfolder($subfolder) {
        $this->subfolder = $subfolder;
    }
    function setMdbAdminuser(&$MdbAdminuser) {
        $this->MdbAdminuser = & $MdbAdminuser;
    }
    function setDtoAdminuser($DtoAdminuser) {
        $this->DtoAdminuser = & $DtoAdminuser;
    }
    function setAdminuserDto($AdminUserDto) {
        $this->AdminuserDto = & $AdminUserDto;
    }
    function setAdminadminLogic($AdminadminLogic) {
        $this->AdminadminLogic = & $AdminadminLogic;
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
        return $this->DtoUser->getPassword_change_flg();
    }
    function getReinfo() {
        return $this->DtoUser->getReinfo();
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
    function getUser_id() {
        return $this->DtoUser->getUser_id();
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
    function getStop_flg() {
        return $this->DtoUser->getStop_flg();
    }
    function getAd_flg() {
        return $this->DtoUser->getAd_flg();
    }
    function getOwner_admin_id() {
        return $this->DtoUser->getOwner_admin_id();
    }
    function getOwner_admin_account() {
        // 管理者情報取得
        $this->DtoAdminuser->setAdmin_id($this->DtoUser->getOwner_admin_id());
        $this->MdbAdminuser->getAdminuserInfo($this->DtoAdminuser);
        return $this->DtoAdminuser->getAccount();
    }
    function getOwner_admin_list() {
        // 管理者リストの取得
        $list = $this->AdminadminLogic->getSelectList($this->MdbAdminuser);
        return $list;
    }
    function getFile_delete_limit_dt() {
        if (!$this->DtoUser->getFile_delete_limit_dt()) {
            return "0";
        } else {
            return $this->DtoUser->getFile_delete_limit_dt();
        }
    }

    function getLog_auth_type() {
        return $this->log_auth_type;
    }
    function getLog_google_mail_address() {
        return $this->log_google_mail_address;
    }
    function getLog_saml_mail_address() {
        return $this->log_saml_mail_address;
    }
    function getLog_account() {
        return $this->log_account;
    }
    function getLog_user_name() {
        return $this->log_user_name;
    }
    function getLog_user_kana() {
        return $this->log_user_kana;
    }
    function getLog_mailaddr() {
        return $this->log_mailaddr;
    }
    function getLog_max_file_size() {
        return $this->log_max_file_size;
    }
    function getLog_ext_file_size() {
        return $this->log_ext_file_size;
    }
    function getLog_ext_unlimited_flg() {
        return $this->log_ext_unlimited_flg;
    }
    function getLog_max_up_size() {
        return $this->log_max_up_size;
    }
    function getLog_start_dt() {
        return $this->log_start_dt;
    }
    function getLog_end_dt() {
        return $this->log_end_dt;
    }
    function getLog_memo() {
        return $this->log_memo;
    }
    function getLog_stop_flg() {
        return $this->log_stop_flg;
    }
    function getLog_ad_flg() {
        return $this->log_ad_flg;
    }
    function getLog_account_lock_flg() {
        return $this->log_account_lock_flg;
    }
    function getLog_owner_admin_id() {
        return $this->log_owner_admin_id;
    }
    function getLog_password_change_flg() {
        return $this->log_password_change_flg;
    }
    function getPassmin() {
        $this->MdbConfigs->getInfo($this->DtoConfigs,'pass_min_num');
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
    function getAccount_lock_flg() {
        return $this->DtoUser->getAccount_lock_flg();
    }
    function getLog_section() {
        return $this->log_section;
    }
    function getSubfolder() {
        return $this->subfolder;
    }
    function getUser_account_lock_cnt() {
        return $this->MdbConfigs->getDirectValue("user_account_lock_cnt");
    }
    function getApi_flg() {
        return $this->DtoUser->getApi_flg();
    }
    function getLog_api_flg() {
        return $this->log_api_flg;
    }
    function getLog_file_delete_limit_dt() {
        return $this->log_file_delete_limit_dt;
    }
    function getSend_flg() {
        return $this->DtoUser->getSend_flg();
    }
    function getCollect_flg() {
        return $this->DtoUser->getCollect_flg();
    }
    function getOpen_folder_flg() {
        return $this->DtoUser->getOpen_folder_flg();
    }
    function getDelivery_flg() {
        return $this->DtoUser->getDelivery_flg();
    }
    function getGathering_flg() {
        return $this->DtoUser->getGathering_flg();
    }
    function getLog_send_flg() {
        return $this->log_send_flg;
    }
    function getLog_collect_flg() {
        return $this->log_collect_flg;
    }
    function getLog_open_folder_flg() {
        return $this->log_open_folder_flg;
    }
    function getLog_delivery_flg() {
        return $this->log_delivery_flg;
    }
    function getLog_gathering_flg() {
        return $this->log_gathering_flg;
    }
    function getTwo_step_auth_disable_flg() {
        return $this->DtoUser->getTwo_step_auth_disable_flg() === '1';
    }
    function getTwo_step_auth_is_enabled() {
        return !is_null( $this->getUserInfoValue( 'two_step_auth_secret_key' ) );
    }
    function getTwo_step_auth_flg() {
        $this->MdbConfigs->getInfo($this->DtoConfigs,"two_step_auth_flg");
        return $this->DtoConfigs->getValue();
    }

    /**
     * ユーザ情報を取得する
     * @return array
     */
    function getUserInfo() {
        // 既に取得していればそれを返す
        if ( !isset( $this->userInfo ) ) {
            $this->userInfo =
                $this->MdbUser->getInfo( $this->DtoUser->getUser_id() );
        }
        return $this->userInfo;
    }

    /**
     * キーからユーザ情報の値を取得する
     * @param  string $key
     * @return mixed
     */
    function getUserInfoValue( $key ) {
        $info = $this->getUserInfo();
        return $info[$key];
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
    function getPassword_required_flg() {
        return PASSWORD_REQUIRED_FLG;
    }
}
?>

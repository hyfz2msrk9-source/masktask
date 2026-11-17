<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] ユーザ管理 - ユーザ削除
 * @updater     Hiroko Yanai（2019/07/26）上長を削除したら、承認待ちは全て却下する
 *
 * @package     WebFile
 * @version     3.17.0
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_User_Del {
    var $session;

    var $userDto;
    var $DtoUser;
    var $MdbUser;
    var $adminuserLogic;

    var $MdbConfigs;
    var $DtoConfigs;

    var $MdbGroups;
    var $DtoGroups;

    var $DtoGroupsusers;
    var $MdbGroupsusers;
    var $GroupsusersLogic;
    var $MdbGroupsadminusers;

    var $DtoSection;
    var $MdbSection;

    var $DtoSectionusers;
    var $MdbSectionusers;

    var $MdbUserSuperior;
    var $DtoUserSuperior;
    var $MdbSuperiorMulti;

    var $CommonList;
    var $MdbSuperiorSend;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_User_Del","Admin_User_Del#execute");

        // エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        // ユーザ情報取得
        $this->MdbUser->getUserInfo($this->DtoUser);
        if ($this->DtoUser->getUser_id()=="") {
            // 存在しないID
            return "input";
        }
        // 既に削除されている
        if ($this->DtoUser->getDel_flg() == '1') {
            return "input";
        }
        // 権限なし
        if ( $this->session->getParameter("login_admin_level") == "3"  &&  $this->DtoUser->getOwner_admin_id() != $this->session->getParameter("admin_id") ) {
            $errorList->add("Admin_User_Del::error", "このアカウントで追加したユーザではないため、削除できません。");
            return "input";
        }
        $this->DtoUser->setMax_file_size(round($this->DtoUser->getMax_file_size()/1024/1024,2));
        $this->DtoUser->setExt_file_size(round($this->DtoUser->getExt_file_size()/1024/1024,2));
        $this->DtoUser->setMax_up_size(round($this->DtoUser->getMax_up_size()/1024/1024,2));

        // ユーザの所属部署を取得
        $sectionkeylist = $this->getSectionKeyList();
        $where = array();
        $where[] = "user_id = " . Db_Common::escape_string($this->DtoUser->getUser_id());
        $list = $this->MdbSectionusers->getSectionusersList($where);
        foreach ($list as $val) {
            $this->DtoGroupsusers->section_id[] = $val['section_id'];
        }

        // ユーザの共有グループに対する権限を取得
        $list = $this->MdbGroups->getJoinList2($this->DtoUser->getUser_id());
        foreach ($list as $key => $val) {
            $this->DtoGroupsusers->readonly_flg[$val['id']] = $val['readonly_flg'];
        }
        // 上長になっている場合は、承認待ちが無いかをチェック
        if (SUPERIOR_OPTION_FLG) {
            $unapproved_ids = $this->MdbSuperiorMulti-> getUnapprovedIdsBySuperiorId($this->DtoUser->getUser_id());
            $latest_superior_send_info = $this->MdbSuperiorSend->getLatestInfoBySuperiorId($unapproved_ids);
            if (count($latest_superior_send_info) === 1) {
                $errorList->add("Admin_User_Del::error", "このユーザは上長に設定されており、承認待ちの依頼があります。");
                $errorList->add("Admin_User_Del::error", "依頼は全て却下されます。ご注意ください。");
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

    function setMdbUserSuperior($MdbUserSuperior) {
        $this->MdbUserSuperior = & $MdbUserSuperior;
    }
    function setDtoUserSuperior($DtoUserSuperior) {
        $this->DtoUserSuperior = & $DtoUserSuperior;
    }
    function setMdbGroupsadminusers(&$MdbGroupsadminusers) {
        $this->MdbGroupsadminusers =&$MdbGroupsadminusers;
    }
    function setCommonList($CommonList) {
        $this->CommonList =& $CommonList;
    }
    function setMdbSuperiorSend($MdbSuperiorSend) {
        $this->MdbSuperiorSend = $MdbSuperiorSend;
    }
    function setMdbSuperiorMulti($MdbSuperiorMulti) {
        $this->MdbSuperiorMulti = & $MdbSuperiorMulti;
    }

    /************************************
    * getter
    *************************************/
    function getAccount() {
        return $this->DtoUser->getAccount();
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
    function getTotal_file_size() {
        return $this->DtoUser->getMax_file_size() + $this->DtoUser->getExt_file_size();
    }
    function getMax_up_size() {
        return $this->DtoUser->getMax_up_size();
    }
    function getUser_id() {
        return $this->DtoUser->getUser_id();
    }
    function getStart_dt() {
        $start_dt = $this->DtoUser->getStart_dt();
        if(isset($start_dt)) {
            list($year,$month,$day) = explode("-",$start_dt);
            return $year."年".abs($month)."月".abs($day)."日";
        }
    }
    function getEnd_dt() {
        $end_dt = $this->DtoUser->getEnd_dt();
        if(isset($end_dt)) {
            list($year,$month,$day) = explode("-",$end_dt);
            return $year."年".abs($month)."月".abs($day)."日";
        }
    }
    function getMemo() {
        return $this->DtoUser->getMemo();
    }
    function getSection_id() {
        return $this->DtoGroupsusers->getSection_id();
    }
    function getIsSection($param) {
        $data = $param['data'];
        if (!is_array($data)) return false;
        $sec = $this->DtoGroupsusers->getSection_id();
        $flg = "";
        $cmp = array(""=>0,1=>1,2=>2,0=>3);
        if (is_array($sec)) foreach( $sec as $val ) {
            if ( $cmp[$data['section_readonly_flg'][$val]] > $cmp[$flg]) $flg=$data['section_readonly_flg'][$val];
        }
        return $flg;
    }
    function getSectionKeyList () {
        return $this->MdbSection->getKeyList();
    }
    function getGroupsList() {
        return $this->adminuserLogic->getGroupsList(
            $this->DtoGroups, $this->DtoGroupsusers,
            $this->MdbGroups, $this->MdbGroupsusers, $this->MdbSection, $this->MdbGroupsadminusers
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
    function getStop_flg() {
        return $this->DtoUser->getStop_flg();
    }
    function getAd_flg() {
        return $this->DtoUser->getAd_flg();
    }
    function getAccount_lock_flg() {
        return $this->DtoUser->getAccount_lock_flg();
    }
    function getUser_account_lock_cnt() {
        return $this->MdbConfigs->getDirectValue("user_account_lock_cnt");
    }
    function getApi_flg() {
        return $this->DtoUser->getApi_flg();
    }
    function getFile_delete_limit_dt() {
        if (!$this->DtoUser->getFile_delete_limit_dt()) {
            return "0";
        } else {
            return $this->DtoUser->getFile_delete_limit_dt();
        }
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
    function getAuth_type_name() {
        $usage_auth_types = $this->CommonList->getUsageAuthTypes();
        if (count($usage_auth_types) < 2) {
            return "";
        }

        if ($this->DtoUser->getSso_flg()) {
            $auth_type = 1;
        } elseif ($this->DtoUser->getSaml_flg()) {
            $auth_type = 2;
        } else {
            $auth_type = 0;
        }
        return $usage_auth_types[$auth_type];
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
}

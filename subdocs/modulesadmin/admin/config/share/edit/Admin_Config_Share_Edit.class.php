<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] グループ共有機能 - グループ共有機能に関する設定 - グループ編集
 * @updater     Hiroko Yanai（2018/04/13）SAML認証(OneLogin)に対応。SSOやSAMLでも、設定は更新可能とする
 *
 * @package     WebFile
 * @version     3.15.2
 * @author
 * @copyright   KEY POINT
 * @access      public
 */
class Admin_Config_Share_Edit {
    var $session;
    var $DtoGroups;
    var $MdbGroups;
    var $MdbGroups_users;
    var $DtoFolder;
    var $MdbFolder;
    var $groupDto;
    var $DtoUser;
    var $MdbUser;
    var $MdbUpload;
    var $DtoSection;
    var $MdbSection;
    var $SectionLogic;
    var $DtoGroupsadminusers;
    var $MdbGroupsadminusers;
    var $MdbMchoice;
    var $DtoAdminUser;
    var $AdminUserLogic;
    var $MdbAdminuser;
    var $AdminUserDto;
    var $AdminadminLogic;
    var $MdbConfigs;

    var $limit_capacity;
    var $leader_array;
    var $user_array;
    var $section_array;
    var $back_flg;
    var $readonly_flgs;
    var $section_readonly_flgs;
    var $alledit_flg;

    var $CommonList;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Share_Editが実行されました","Admin_Config_Share_Edit#execute");

        //エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        // パラメータチェック
        if (!is_numeric($this->DtoGroups->getId())) {
            $errorList->add("Admin_Config_Share_Edit::error", "不正なパラメーターを検知しました。再度、一覧から編集するグループを選択してください。");
            return "error";
        }

        // 容量情報の取得
        if(!defined('SHARE_SPACE_FLG') || SHARE_SPACE_FLG == 0) {
            //割当てあり
            $this->use_capacity = $this->MdbGroups->getUseCapacity();
        } else {
            //割当てなし
            $this->use_capacity = $this->MdbUpload->getAllUserShareCapacity();
        }
        // 2008/12/16@yano 割当て容量から使用容量に変更
        $this->limit_capacity = SHARE_MAX_CAPACITY*1024*1024*1024 - $this->use_capacity;

        // グループ情報の取得
        $this->MdbGroups->getInfo($this->DtoGroups);
        if (!$this->DtoGroups->id) {
            // 存在しないグループの選択
            return "error";
        }

        //管理者チェック
        if(!$this->AdminUserLogic->checkAdminGroup($this->DtoAdminUser, $this->DtoGroups)) {
            $errorList->add("Admin_Config_Share_Edit::error", "権限がありません。再度、一覧からグループを選択してください。");
            return "error";
        }

        // フォルダ名の取得
        $this->DtoFolder->setFolder_id($this->DtoGroups->folder_id);
        $this->MdbFolder->setFolderInfo($this->DtoFolder);

        // リーダーの一覧を取得
        $this->DtoGroupsadminusers->setGroup_id($this->DtoGroups->id);
        $this->MdbGroupsadminusers->getList($this->DtoGroupsadminusers);
        $this->leader_array = array();
        $old_leader = array();
        if ($this->DtoGroupsadminusers->getGroupsAdminList()) {
            foreach($this->DtoGroupsadminusers->getGroupsAdminList() as $key => $val) {
                $this->DtoUser->setUser_id($val["user_id"]);
                $this->MdbUser->getUserInfo($this->DtoUser);
                $old_leader[] = $val['user_id'];
                array_push($this->leader_array, array("user_id"  =>$this->DtoUser->getUser_id(),
                                                      "user_name"=>$this->DtoUser->getUser_name(),
                                                      "mailaddr" =>$this->DtoUser->getMailaddr()));
            }
        }

        if( $this->back_flg ) {
            $this->groupDto->setAdmin_edit_flg( $this->session->getParameter( "admin_edit_flg" ) );
            $this->groupDto->setId( $this->session->getParameter( "id" ) );
            $this->groupDto->setName( $this->session->getParameter( "name" ) );
            $this->groupDto->setFolder_id( $this->session->getParameter( "folder_id" ) );
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
            $this->groupDto->setLeader_id( $this->session->getParameter( "leader_id" ) );

            // Google SSO認証のみのときは使えない
            if (LOGIN_FLG || (!LOGIN_FLG && !SSO_FLG)) {
                $this->groupDto->setApi_flg($this->session->getParameter( "api_flg" ));
            }

            $this->session->setParameter( "member_edit", "1");
            $this->session->setParameter( "users", $this->groupDto->getUsers() );
            $this->session->setParameter( "leaders", $this->groupDto->getLeaders() );
            $this->session->setParameter( "sections", $this->groupDto->getSections() );
            $this->session->setParameter( "readonly_flgs", $this->readonly_flgs );
            $this->session->setParameter( "section_readonly_flgs", $this->section_readonly_flgs );
            $this->session->setParameter( "alledit_flg", $this->alledit_flg);
            $this->session->setParameter( "group_admin_operate_flg", $this->groupDto->getGroup_admin_operate_flg() );
        } else {
            // DTOへセット
            $this->groupDto->setId($this->DtoGroups->id);
            $this->groupDto->setName($this->DtoGroups->name);
            $this->groupDto->setFolder_id($this->DtoGroups->folder_id);
            $this->groupDto->setFolder_name($this->DtoFolder->folder_name);
            $this->groupDto->setCapacity($this->DtoGroups->capacity);
            $this->groupDto->setDel_span($this->DtoGroups->del_span);
            $this->groupDto->setLock_flg($this->DtoGroups->lock_flg);
            $this->groupDto->setLock_dl_flg($this->DtoGroups->lock_dl_flg);
            $this->groupDto->setCancel_span($this->DtoGroups->cancel_span);
            $this->groupDto->setTerm_flg($this->DtoGroups->term_flg);
            $this->groupDto->setTerm_type_id($this->DtoGroups->term_type_id);
            $this->groupDto->setApi_flg($this->DtoGroups->getApi_flg());

            if(!is_null($this->DtoGroups->start_dt)) {
                $this->groupDto->setStart_year(date("Y",  strtotime($this->DtoGroups->start_dt)));
                $this->groupDto->setStart_month(date("m", strtotime($this->DtoGroups->start_dt)));
                $this->groupDto->setStart_day(date("d",   strtotime($this->DtoGroups->start_dt)));
            }
            if(!is_null($this->DtoGroups->end_dt)) {
                $this->groupDto->setEnd_year(date("Y",    strtotime($this->DtoGroups->end_dt)));
                $this->groupDto->setEnd_month(date("m",   strtotime($this->DtoGroups->end_dt)));
                $this->groupDto->setEnd_day(date("d",     strtotime($this->DtoGroups->end_dt)));
            }
            //$this->groupDto->setLeader_id($this->DtoGroupsadminusers->getUser_id());
            $this->groupDto->setSend_flg($this->DtoGroups->getSend_flg());
            $this->groupDto->setLog_share_flg($this->DtoGroups->getLog_share_flg());
            $this->groupDto->setMail_show_flg($this->DtoGroups->getMail_show_flg());
            $this->groupDto->setAdmin_edit_flg($this->DtoGroups->getAdmin_edit_flg());
            //$this->groupDto->setGroup_admin_operate_flg($this->DtoGroups->getGroup_admin_operate_flg());
            $this->session->removeParameter("users");
            $this->session->removeParameter("leaders");
            $this->session->removeParameter("sections");
            $this->session->removeParameter("member_edit");
            $this->session->removeParameter("readonly_flgs");
            $this->session->removeParameter("section_readonly_flgs");
            $this->session->removeParameter("alledit_flg");
            $this->session->removeParameter("group_admin_operate_flg");
            $this->session->removeParameter("leader_open_flg");
            $this->session->removeParameter("user_open_flg");
            $this->session->removeParameter("section_open_flg");
            $this->session->removeParameter("groupDto");
            $this->session->removeParameter("sub_folder_id"); //サブフォルダ権限設定で利用したfolder_id
            $this->session->removeParameter("folder_id");
            $this->session->removeParameter("term_flg");
            $this->session->removeParameter("old_leaders");

            // Google SSO認証のみの時は使えない
            if (LOGIN_FLG || (!LOGIN_FLG && !SSO_FLG)) {
                $this->session->removeParameter( "api_flg" );
            }

            // 初期のグループ管理者情報をセットしておく
            if(SHARE_ADMIN_MUST_FLG==1) {
                $this->session->setParameter("old_leaders",$old_leader);
            }
        }
        $this->groupDto->setApi_flg($this->DtoGroups->getApi_flg());

        // メンバーの一覧を取得
        $where = array("user_id>0",
                       "folder_id=".$this->DtoGroups->folder_id);
        $userlist = $this->MdbGroups_users->getUserlist($this->DtoGroups->id,$where);
        $this->user_array = array();

        foreach ($userlist as $val){
            $this->DtoUser->setUser_id($val["user_id"]);
            $this->MdbUser->getUserInfo($this->DtoUser);
            array_push($this->user_array, array(
                                          "user_id"   => $this->DtoUser->user_id,
                                          "user_name" => $this->DtoUser->user_name,
                                          "mailaddr"  => $this->DtoUser->mailaddr,
                                          "readonly_flg" => $val["readonly_flg"]
                                          ));
        }

        // 部署の一覧を取得
        $where = array("section_id>0",
                       "folder_id=".$this->DtoGroups->folder_id);
        $sectionlist = $this->MdbGroups_users->getUserlist($this->DtoGroups->id,$where);
        $this->section_array = array();
        foreach ($sectionlist as $val) {
            $this->DtoSection->setSection_id($val["section_id"]);
            $this->MdbSection->getInfo($this->DtoSection);
            array_push($this->section_array,array(
                                                  "section_id" => $this->DtoSection->getSection_id(),
                                                  "section_name" => $this->DtoSection->getSection_name(),
                                                  "readonly_flg" => $val["readonly_flg"]
                                                  ));
        }

        //sessionに入れておく
        $this->session->setParameter("id",$this->groupDto->getId());
        $this->session->setParameter("folder_id",$this->groupDto->getFolder_id());
        $this->session->setParameter("old_leader_id",$this->groupDto->getLeader_id());
        $this->session->setParameter("log_g_name",$this->groupDto->getName());
        $this->session->setParameter("log_f_name",$this->groupDto->getFolder_name());
        $this->session->setParameter("log_del_span",$this->groupDto->getDel_span());
        $this->session->setParameter("log_capa",$this->groupDto->getCapacity());
        $this->session->setParameter("log_lock_flg",$this->groupDto->lock_flg);
        $this->session->setParameter("log_lock_dl_flg",$this->groupDto->lock_dl_flg);
        $this->session->setParameter("log_cancel_span",$this->groupDto->cancel_span);
        $this->session->setParameter("log_term_flg",$this->groupDto->term_flg);
        $this->session->setParameter("log_term_type_id",$this->groupDto->term_type_id);
        $this->session->setParameter("log_send_flg",$this->groupDto->getSend_flg());
        $this->session->setParameter("log_log_share_flg",$this->groupDto->getLog_share_flg());
        $this->session->setParameter("log_mail_show_flg",$this->groupDto->getMail_show_flg());
        $this->session->setParameter("log_admin_edit_flg",$this->groupDto->getAdmin_edit_flg());
        $this->session->setParameter("log_group_admin_operate_flg",$this->groupDto->getGroup_admin_operate_flg());
        $this->session->setParameter("log_start_dt",$this->groupDto->getStart_dt());
        $this->session->setParameter("log_end_dt",$this->groupDto->getEnd_dt());

        // Google SSO認証のみのときは使えない
        if (LOGIN_FLG || (!LOGIN_FLG && !SSO_FLG)) {
            $this->session->setParameter("log_api_flg",$this->groupDto->getApi_flg());
        }

        return "success";
    }
    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoGroups($DtoGroups){
        $this->DtoGroups = $DtoGroups;
    }
    function setMdbGroups($MdbGroups) {
        $this->MdbGroups = $MdbGroups;
    }
    function setDtoFolder($DtoFolder) {
        $this->DtoFolder = $DtoFolder;
    }
    function setMdbFolder($MdbFolder) {
        $this->MdbFolder = $MdbFolder;
    }
    function setGroupDto($groupDto) {
        $this->groupDto = $groupDto;
    }
    function setDtoUser($DtoUser) {
        $this->DtoUser = $DtoUser;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = $MdbUser;
    }
    function setMdbGroups_users($MdbGroups_users) {
        $this->MdbGroups_users = $MdbGroups_users;
    }
    function setMdbUpload($MdbUpload) {
        $this->MdbUpload = $MdbUpload;
    }
    function setDtoSection($DtoSection) {
        $this->DtoSection = $DtoSection;
    }
    function setMdbSection($MdbSection) {
        $this->MdbSection = $MdbSection;
    }
    function setSectionLogic($SectionLogic) {
        $this->SectionLogic = $SectionLogic;
    }
    function setDtoGroupsadminusers($DtoGroupsadminusers) {
        $this->DtoGroupsadminusers = $DtoGroupsadminusers;
    }
    function setMdbGroupsadminusers($MdbGroupsadminusers) {
        $this->MdbGroupsadminusers = $MdbGroupsadminusers;
    }
    function setMdbMchoice($MdbMchoice) {
        $this->MdbMchoice = $MdbMchoice;
    }
    function setDtoAdminUser($DtoAdminUser) {
        $this->DtoAdminUser = $DtoAdminUser;
    }
    function setAdminUserLogic($AdminUserLogic) {
        $this->AdminUserLogic = $AdminUserLogic;
    }
    function setMdbAdminuser(&$MdbAdminuser) {
        $this->MdbAdminuser = & $MdbAdminuser;
    }
    function setAdminUserDto($AdminUserDto) {
        $this->AdminUserDto = & $AdminUserDto;
    }
    function setAdminadminLogic($AdminadminLogic) {
        $this->AdminadminLogic = & $AdminadminLogic;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = & $MdbConfigs;
    }
    function setBack_flg($back_flg) {
        $this->back_flg = $back_flg;
    }
    function setReadonly_flgs($readonly_flgs) {
        $this->readonly_flgs = $readonly_flgs;
    }
    function setSection_readonly_flgs($section_readonly_flgs) {
        $this->section_readonly_flgs = $section_readonly_flgs;
    }
    function setAlledit_flg($alledit_flg) {
        $this->alledit_flg = $alledit_flg;
    }
    function setCommonList($CommonList) {
        $this->CommonList =& $CommonList;
    }

    // getter
    function getGroupInfo(){
        return $this->groupDto;
    }
    function getMode()
    {
        return "編集";
    }
    function getShareMaxCapacity()
    {
        return SHARE_MAX_CAPACITY."G";
    }
    function getUseCapacity()
    {
        return StrChange::CapacityUnit($this->use_capacity);
    }
    function getLimitCapacity()
    {
        return StrChange::CapacityUnit($this->limit_capacity);
    }
    function getChangeCapacity()
    {
        return StrChange::CapacityUnit($this->limit_capacity+$this->DtoGroups->capacity);
    }
    function getName()
    {
        return $this->groupDto->getName();
    }
    function getFolder_name()
    {
        return $this->groupDto->getFolder_name();
    }
    function getDel_span()
    {
        return $this->groupDto->getDel_span();
    }
    function getCapacity()
    {
        //return $this->groupDto->getCapacity();
        return round($this->groupDto->getCapacity()/1024/1024,2);
    }
    function getFolder_id()
    {
        return $this->groupDto->getFolder_id();
    }
    function getId()
    {
        return $this->DtoGroups->id;
    }
    function getLeader_array()
    {
        return $this->leader_array;
    }
    function getUser_array()
    {
        return $this->user_array;
    }
    function getSection_array()
    {
        return $this->section_array;
    }
    function getTreeView() {
        return $this->SectionLogic->getTreeView($this->MdbSection);
    }
    function getLeader_id() {
        return $this->groupDto->getLeader_id();
    }
    function getLeader_name()
    {
        if ($this->groupDto->getLeader_id()) {
            $info = "";
            $info = $this->MdbUser->getInfo($this->groupDto->getLeader_id());
            if ($info) {
                return $info["user_name"];
            }
        }
    }
    function getLeader_mail()
    {
        if ($this->groupDto->getLeader_id()) {
            $info = "";
            $info = $this->MdbUser->getInfo($this->groupDto->getLeader_id());
            if ($info) {
                return $info["mailaddr"];
            }
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
        if($this->groupDto->getCancel_span() != "") {
            return ($this->groupDto->getCancel_span() != '0') ? $this->groupDto->getCancel_span() : '0';
        }
        return 0;
    }
    function getTerm_flg() {
        if(SHARE_FOLDER_TERM_REQUIRED_FLG) {
            return SHARE_FOLDER_TERM_REQUIRED_FLG;
        }
        return $this->groupDto->getTerm_flg();
    }
    function getStart_year() {
        if(is_null($this->groupDto->getStart_dt())) {
            return date("Y", strtotime($this->DtoGroups->getCreated()));
        }
        return $this->groupDto->getStart_year();
    }
    function getStart_month() {
        if(is_null($this->groupDto->getStart_dt())) {
            return date("m", strtotime($this->DtoGroups->getCreated()));
        }
        return $this->groupDto->getStart_month();
    }
    function getStart_day() {
        if(is_null($this->groupDto->getStart_dt())) {
            return date("d", strtotime($this->DtoGroups->getCreated()));
        }
        return $this->groupDto->getStart_day();
    }
    function getEnd_year() {
        if(is_null($this->groupDto->getEnd_dt())) {
            if(SHARE_FOLDER_TERM_DEFAULT_SPAN > 0) {
                $span_time = SHARE_FOLDER_TERM_DEFAULT_SPAN  * 24 * 60 * 60;
                return date("Y", strtotime($this->DtoGroups->getCreated()) + $span_time);
            } else {
                return date("Y");
            }
        }
        return $this->groupDto->getEnd_year();
    }
    function getEnd_month() {
        if(is_null($this->groupDto->getEnd_dt())) {
            if(SHARE_FOLDER_TERM_DEFAULT_SPAN > 0) {
                $span_time = SHARE_FOLDER_TERM_DEFAULT_SPAN  * 24 * 60 * 60;
                return date("m", strtotime($this->DtoGroups->getCreated()) + $span_time);
            } else {
                return date("m");
            }
        }
        return $this->groupDto->getEnd_month();
    }
    function getEnd_day() {
        if(is_null($this->groupDto->getEnd_dt())) {
            if(SHARE_FOLDER_TERM_DEFAULT_SPAN > 0) {
                $span_time = SHARE_FOLDER_TERM_DEFAULT_SPAN  * 24 * 60 * 60;
                return date("d", strtotime($this->DtoGroups->getCreated()) + $span_time);
            } else {
                return date("d");
            }
        }
        return $this->groupDto->getEnd_day();
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
    function getMail_show_flg() {
        return $this->groupDto->getMail_show_flg();
    }
    function getAdmin_edit_flg() {
        return $this->groupDto->getAdmin_edit_flg();
    }
    function getGroup_admin_operate_flg() {
        return $this->groupDto->getGroup_admin_operate_flg();
    }
    function getAdmin_id() {
        if (!$this->DtoGroups->getAdmin_id()) {
            $where = array("level=10","del_flg=0");
            $this->MdbAdminuser->getAdminuserInfoByWhere($where, $this->DtoAdminUser);
            return $this->DtoAdminUser->getAdmin_id();
        }
        return $this->DtoGroups->getAdmin_id();
    }
    function getAdmin_account() {
        // 管理者情報取得
        $this->DtoAdminUser->setAdmin_id($this->groupDto->getAdmin_id());
        $this->MdbAdminuser->getAdminuserInfo($this->DtoAdminUser);
        return $this->DtoAdminUser->getAccount();
    }
    function getAdmin_list()
    {
        $list = $this->AdminUserLogic->getSelectList($this->MdbAdminuser);
        return $list;
    }
    function getApi_Flg_Options() {
        return GlobalArray::getApi_Flg_Options();
    }
    function getApi_flg() {
        return $this->groupDto->getApi_flg();
    }
    function getAlledit_flg() {
        return $this->alledit_flg;
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

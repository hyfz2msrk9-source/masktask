<?php
/************************************************************
 * last update :
 ************************************************************/
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Share_Del {
    var $session;
    var $DtoGroups;
    var $MdbGroups;
    var $MdbGroups_users;
    var $DtoFolder;
    var $MdbFolder;
    var $groupDto;
    var $DtoUser;
    var $MdbUser;
    var $DtoSection;
    var $MdbSection;
    var $SectionLogic;
    var $DtoGroupsadminusers;
    var $MdbGroupsadminusers;
    var $DtoAdminUser;
    var $AdminUserLogic;

    var $limit_capacity;
    var $user_array;
    var $section_array;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Share_Delが実行されました","Admin_Config_Share_Del#execute");

        //エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        // パラメータチェック
        if (!is_numeric($this->DtoGroups->getId())) {
            $errorList->add("Admin_Config_Share_Del::error", "不正なパラメーターを検知しました。再度、一覧から削除するグループを選択してください。");
            return "error";
        }

        // グループ情報の取得
        $this->MdbGroups->getInfo($this->DtoGroups);

        if (!$this->DtoGroups->id) {
            // 存在しないグループの選択
            return "error";
        }

        //管理者チェック
        if(!$this->AdminUserLogic->checkAdminGroup($this->DtoAdminUser, $this->DtoGroups)) {
            $errorList->add("Admin_Config_Share_Del::error", "権限がありません。再度、一覧からグループを選択してください。");
            return "error";
        }

        // フォルダ名の取得
        $this->DtoFolder->setFolder_id($this->DtoGroups->folder_id);
        $this->MdbFolder->setFolderInfo($this->DtoFolder);

        // リーダー情報の取得
        $this->DtoGroupsadminusers->setGroup_id($this->DtoGroups->id);
        $this->MdbGroupsadminusers->getList($this->DtoGroupsadminusers);

        // DTOへセット
        $this->groupDto->setId($this->DtoGroups->id);
        $this->groupDto->setName($this->DtoGroups->name);
        $this->groupDto->setFolder_id($this->DtoGroups->folder_id);
        $this->groupDto->setFolder_name($this->DtoFolder->folder_name);
        $this->groupDto->setCapacity(StrChange::CapacityUnit($this->DtoGroups->capacity));
        $this->groupDto->setDel_span($this->DtoGroups->getDel_span());
        $this->groupDto->setLeader_id($this->DtoGroupsadminusers->getUser_id());
        $this->groupDto->setAdmin_edit_flg($this->DtoGroups->admin_edit_flg);
        $this->groupDto->setGroup_admin_operate_flg($this->DtoGroups->getGroup_admin_operate_flg());

        if(FILE_LOCK_OPTION_FLG) {
            $this->groupDto->setLock_flg($this->DtoGroups->lock_flg);
            $this->groupDto->setLock_dl_flg($this->DtoGroups->lock_dl_flg);
            $this->groupDto->setCancel_span($this->DtoGroups->cancel_span);
        }

        //sessionに入れておく
        $this->session->setParameter("id",$this->groupDto->getId());
        $this->session->setParameter("log_g_name",$this->groupDto->getName());
        $this->session->setParameter("log_f_name",$this->groupDto->getFolder_name());
        $this->session->setParameter("log_del_span",$this->groupDto->getDel_span());

        // メンバーの一覧を取得
        $where = array("user_id>0",
                       "folder_id=".Db_Common::escape_string($this->DtoGroups->getFolder_id()));
        $userlist = $this->MdbGroups_users->getUserlist($this->DtoGroups->id,$where);
        $this->user_array = array();
        foreach ($userlist as $val){
            $this->DtoUser->setUser_id($val["user_id"]);
            $this->MdbUser->getUserInfo($this->DtoUser);
            array_push($this->user_array, array(
                                          "user_id"   => $this->DtoUser->user_id,
                                          "user_name" => $this->DtoUser->user_name,
                                          "mailaddr"  => $this->DtoUser->mailaddr,
                                          ));
        }

        // 部署の一覧を取得
        $where = array("section_id>0",
                       "folder_id=".Db_Common::escape_string($this->DtoGroups->getFolder_id()));
        $sectionlist = $this->MdbGroups_users->getUserlist($this->DtoGroups->id,$where);
        $this->section_array = array();
        foreach ($sectionlist as $val) {
            $this->DtoSection->setSection_id($val["section_id"]);
            $this->MdbSection->getInfo($this->DtoSection);
            array_push($this->section_array,array(
                                                  "section_id" => $this->DtoSection->getSection_id(),
                                                  "section_name" => $this->DtoSection->getSection_name(),
                                                  ));
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
    function setMdbGroups_users($MdbGroups_users) {
        $this->MdbGroups_users = $MdbGroups_users;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = $MdbUser;
    }
    function setDtoUser($DtoUser) {
        $this->DtoUser = $DtoUser;
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
    function setDtoAdminUser($DtoAdminUser) {
        $this->DtoAdminUser = $DtoAdminUser;
    }
    function setAdminUserLogic($AdminUserLogic) {
        $this->AdminUserLogic = $AdminUserLogic;
    }

    // getter
    function getGroupInfo(){
        return $this->groupDto;
    }
    function getMode()
    {
        return "削除";
    }
    function getUser_array()
    {
        return $this->user_array;
    }
    function getSection_array()
    {
        return $this->section_array;
    }
    function getLeader_array() {
        $leader_array = array();
        if ($this->DtoGroupsadminusers->getGroupsAdminList()) {
            foreach($this->DtoGroupsadminusers->getGroupsAdminList() as $key => $val) {
                $this->DtoUser->setUser_id($val["user_id"]);
                $this->MdbUser->getUserInfo($this->DtoUser);
                array_push($leader_array, array("user_id"  =>$this->DtoUser->getUser_id(),
                                                "user_name"=>$this->DtoUser->getUser_name(),
                                                "mailaddr" =>$this->DtoUser->getMailaddr()));
            }
            return $leader_array;
        }
        return $leader_array;
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
}
?>

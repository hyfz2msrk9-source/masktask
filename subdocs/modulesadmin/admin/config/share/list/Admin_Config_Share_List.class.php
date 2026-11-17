<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Share_List {
    var $session;
    var $DtoGroups;
    var $MdbGroups;
    var $MdbGroups_users;
    var $MdbUpload;
    var $GroupDto;
    var $GroupLogic;
    var $DtoFlexi;
    var $DtoAdminUser;
    var $MdbAdminuser;
    var $AdminuserDto;
    var $AdminadminLogic;

    var $use_capacity;
    var $group_count;
    var $group_list;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Share_Listが実行されました","Admin_Config_Share_List#execute");

        // セッションセット
        $this->session->setParameter("page", $this->DtoFlexi->getPage());
        $this->session->setParameter("query", $this->DtoFlexi->getQuery());
        $this->session->setParameter("qtype", $this->DtoFlexi->getQtype());
        $this->session->setParameter("sortname", $this->DtoFlexi->getSortname());
        $this->session->setParameter("sortorder", $this->DtoFlexi->getSortorder());
        $this->session->setParameter("rp", $this->DtoFlexi->getRp());

        // グループ一覧の取得
        $this->GroupLogic->getFlexiList(
                                        $this->GroupDto,
                                        $this->MdbGroups,
                                        $this->DtoFlexi,
                                        $this->MdbGroups_users,
                                        $this->MdbUpload,
                                        $this->DtoAdminUser,
                                        $this->MdbAdminuser, 
                                        $this->AdminuserDto, 
                                        $this->AdminadminLogic
                                        );
        
        echo $this->GroupLogic->getFlexiList2($this->GroupDto,
                                              $this->DtoFlexi);

        // グループ一覧に、所属ユーザ数を追加
        /*
        $i=0;
        foreach ($list as $val) {            
            $count = 0;
            $count = $this->MdbGroups_users->getUserCount($val['id']);

            //グループ毎の使用容量を追加
            $capacity =0;
            $capacity = $this->MdbUpload->getUseFolderCapacity($val['folder_id']);

            $this->group_list[$i] = array(
                                          "id"           => $val['id'],
                                          "name"         => $val['name'],
                                          "folder_name"  => $val['_FOLDER_folder_name'],
                                          "capacity"     => StrChange::CapacityUnit($val['capacity']),
                                          "del_span"     => $val['del_span'],
                                          "use_capacity"     => StrChange::CapacityUnit($capacity),
                                          "member_num"   => $count
                                          );
            ++$i;
        }
        */

        //2008/12/16@yano 割当て容量を使用容量に変更
        if(defined('SHARE_SPACE_FLG') && SHARE_SPACE_FLG == 1) {
            $this->use_capacity = $this->MdbUpload->getAllUserShareCapacity();
        } else {
            $this->use_capacity = $this->MdbGroups->getUseCapacity();
        }

        //$this->group_count = $this->MdbGroups->getCount();

       exit ();
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
    function setMdbGroups_users($MdbGroups_users) {
        $this->MdbGroups_users = $MdbGroups_users;
    }
    function setMdbUpload($MdbUpload) {
        $this->MdbUpload = $MdbUpload;
    }
    function setGroupDto($GroupDto) {
        $this->GroupDto = $GroupDto;
    }
    function setGroupLogic($GroupLogic) {
        $this->GroupLogic = $GroupLogic;
    }
    function setDtoFlexi($DtoFlexi) {
        $this->DtoFlexi = & $DtoFlexi;
    }
    function setDtoAdminUser($DtoAdminUser) {
        $this->DtoAdminUser = $DtoAdminUser;
    }
    function setMdbAdminuser($MdbAdminuser) {
        $this->MdbAdminuser = $MdbAdminuser;
    }
    function setAdminuserDto($AdminuserDto) {
        $this->AdminuserDto = $AdminuserDto;
    }
    function setAdminadminLogic($AdminadminLogic) {
        $this->AdminadminLogic = & $AdminadminLogic;
    }
}
?>
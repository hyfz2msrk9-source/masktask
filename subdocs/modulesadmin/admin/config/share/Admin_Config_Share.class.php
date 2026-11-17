<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Share {
    var $session;
    var $DtoGroups;
    var $MdbGroups;
    var $MdbGroups_users;
    var $MdbUpload;
    var $GroupDto;
    var $DtoAdminUser;

    var $use_capacity;
    var $group_count;
    var $group_list;
    var $group_list_back;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Shareが実行されました","Admin_Config_Share#execute");

        if (!$this->group_list_back) {
            $this->session->removeParameter('query');
            $this->session->removeParameter('qtype');
            $this->session->removeParameter('page');
            $this->session->removeParameter('sortname');
            $this->session->removeParameter('sortorder');
            $this->session->removeParameter('rp');
            $this->GroupDto->setPage(0);
        }
        /* grid移行で不要なはず　2012-05-01 @yanoコメントアウト
        // グループ一覧の取得
        $list = $this->MdbGroups->getJoinList();

        // グループ一覧に、所属ユーザ数を追加
        $i=0;
        foreach ($list as $val) {
            // $val['id']
            // $val['name']
            // $val['_FOLEDER_folder_name']
            // $val['capacity']
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

        $this->group_count = $this->MdbGroups->getCount($this->DtoAdminUser);

        if (defined('SHARE_SPACE_FLG') && SHARE_SPACE_FLG == 1) {
            return "success";
        } else {
            return "success2";
        }
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
    function setGroup_list_back($group_list_back) {
        $this->group_list_back = $group_list_back;
    }
    function setGroupDto($GroupDto) {
        $this->GroupDto = $GroupDto;
    }
    function setDtoAdminUser($DtoAdminUser) {
        $this->DtoAdminUser = $DtoAdminUser;
    }


    // getter
    function getGroupList(){
        return $this->group_list;
    }
    function getShareMaxCapacity()
    {
        if (defined('SHARE_MAX_CAPACITY')) {
            return SHARE_MAX_CAPACITY."G";
        } else {
            return "SHARE_MAX_CAPACITYを設定してください。";
        }
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
        return StrChange::CapacityUnit(SHARE_MAX_CAPACITY*1024 - $this->use_capacity);
    }

}
?>
<?php
require_once( MODULE_DIR . "/common/common.php");

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * [機能説明] 共有フォルダのロックファイルの解除
 */
class Admin_Config_Share_Lockfile {
    var $session;
    var $DtoGroups;
    var $MdbGroups;
    var $MdbGroups_users;
    var $MdbUpload;
    var $GroupDto;

    var $group_list_back;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Share_Lockfileが実行されました","Admin_Config_Share_Lockfile#execute");

        return "success";

        if (!$this->group_list_back) {
            $this->session->removeParameter('query');
            $this->session->removeParameter('qtype');
            $this->session->removeParameter('page');
            $this->session->removeParameter('sortname');
            $this->session->removeParameter('sortorder');
            $this->session->removeParameter('rp');
            $this->GroupDto->setPage(0);
        }

        // グループ一覧の取得
        $list = $this->MdbGroups->getJoinList();

        // グループ一覧に、所属ユーザ数を追加
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
        //2008/12/16@yano 割当て容量を使用容量に変更
        if(defined('SHARE_SPACE_FLG') && SHARE_SPACE_FLG == 1) {
            $this->use_capacity = $this->MdbUpload->getAllUserShareCapacity();
        } else {
            $this->use_capacity = $this->MdbGroups->getUseCapacity();
        }

       $this->group_count = $this->MdbGroups->getCount();

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


    // getter
    function getGroupList(){
        return $this->group_list;
    }
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
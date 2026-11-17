<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * [機能説明] 部署グループ設定 - 部署グループ削除(Ajaxレスポンス)
 *
 * @package     WebFile
 * @author      Yusuke Numata
 * @copyright   KeyPoint
 * @access      public
 */

require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Section_Del {
    var $session;

    var $DtoUser;
    var $MdbUser;

    var $DtoSection;
    var $MdbSection;

    var $DtoSectionusers;
    var $MdbSectionusers;

    var $DtoGroupsusers;
    var $MdbGroupsusers;
    var $GroupsusersLogic;

    var $DtoLog;
    var $MdbLog;

    var $MdbUpload;

    var $MdbAddr;
    var $MdbAddrGroupUsers;
    var $MdbAddrGroup;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Section_Delが実行されました","Admin_Config_Section_Del#execute");

        //IDチェック
        if(!is_numeric($this->DtoSection->getSection_id())) {
            exit("ALERT,不正なアクセスを検知しました。");
        }

        // ログ記録用に現部署グループ情報を取得
        $memo_section_name = "";
        $info = $this->MdbSection->getInfo2($this->DtoSection->getSection_id());
        if (isset($info['section_name'])) {
            $memo_section_name.= $info['section_name'];
        }

        // サブ管理者編集権限設定チェック
        if( $this->session->getParameter("login_admin_level") < 5 ) {
            if ($info["subadmin_editable_flg"] == "0") {
                exit("ALERT,この部署グループの編集を許可されていません。");
            }
        }

        // この部署のアドレス帳id
        $section_address_ids_array = $this->MdbSection->getSectionAddress($this->DtoSection->getSection_id());
        if (count($section_address_ids_array)) {
            foreach ($section_address_ids_array as $key => $value) {
                $tmp[] = $value['addr_id'];
            }
            $section_address_ids = implode(",", $tmp);
            // 部署のアドレス帳を削除
            if (!$this->MdbAddrGroupUsers->deleteSectionAddrGroupUsersById($section_address_ids)) {
                exit("ALERT,部署グループのアドレス帳削除に失敗しました。");
            }
            // 部署のアドレス帳の紐付けを削除
            if (!$this->MdbAddrGroup->deleteSectionAddrGroupById($this->DtoSection->getSection_id())) {
                exit("ALERT,部署グループのアドレス帳削除に失敗しました。");
            }
            // 部署のアドレス帳を削除
            if (!$this->MdbAddr->deleteSectionAddrById($section_address_ids)) {
                exit("ALERT,部署グループのアドレス帳削除に失敗しました。");
            }
        }

        // 部署グループ削除
        if (!$this->MdbSection->delete($this->DtoSection)) {
            exit("ALERT,部署グループの削除に失敗しました。");
        }

        //ロック解除
        if(FILE_LOCK_OPTION_FLG) {
            $where = array("section_id=".Db_Common::escape_string(intval($this->DtoSection->getSection_id())));
            $users = $this->MdbSectionusers->getSectionusersList($where);
            $lock_users = array();
            foreach($users as $val) {
                $lock_users = $val['user_id'];
            }
            //ロック解除
            if($lock_users) {
                //部署が所属しているグループを抽出して
                $this->GroupsusersLogic->updateFileLockSectionUser(
                                                                   $this->DtoSection->getSection_id(), $lock_users,
                                                                   $this->MdbGroupsusers, $this->MdbUpload
                                                                   );
            }
        }

        // 部署グループユーザ全削除
        $error = 0;
        if (!$this->MdbSectionusers->delete($this->DtoSectionusers)) {
            $error++;
        }

        // 共有グループユーザ削除
        $section_id = Db_Common::escape_string($this->getSection_id());
        $where = array();
        $where[] = "section_id=" . $section_id . " OR user_section_id=" . $section_id;
        $this->MdbGroupsusers->deleteWhere($where);


        // ログ記録
        $history_id = 131;
        $memo = "";
        $memo.= "部署グループ名：" . $memo_section_name . "\n";
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        if ($error === 0) {
            exit("SUCCESS,部署グループの削除に成功しました。");
        } else {
            exit("SUCCESS,部署グループの削除に成功しましたが、\n部署グループユーザ情報を削除できませんでした。");
        }
    }

    /***************************************************
     * setter
     **************************************************/
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoUser($DtoUser){
        $this->DtoUser = $DtoUser;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = $MdbUser;
    }
    function setDtoSection($DtoSection){
        $this->DtoSection = $DtoSection;
    }
    function setMdbSection($MdbSection) {
        $this->MdbSection = $MdbSection;
    }
    function setDtoSectionusers($DtoSectionusers){
        $this->DtoSectionusers = $DtoSectionusers;
    }
    function setMdbSectionusers($MdbSectionusers) {
        $this->MdbSectionusers = $MdbSectionusers;
    }
    function setDtoGroupsusers($DtoGroupsusers){
        $this->DtoGroupsusers = $DtoGroupsusers;
    }
    function setMdbGroupsusers($MdbGroupsusers) {
        $this->MdbGroupsusers = $MdbGroupsusers;
    }
    function setGroupsusersLogic(&$GroupsusersLogic) {
        $this->GroupsusersLogic = &$GroupsusersLogic;
    }
    function setDtoLog($DtoLog) {
        $this->DtoLog = $DtoLog;
    }
    function setMdbLog($MdbLog) {
        $this->MdbLog = $MdbLog;
    }
    function setMdbUpload($MdbUpload) {
        $this->MdbUpload = $MdbUpload;
    }
    function setMdbAddr($MdbAddr) {
        $this->MdbAddr = $MdbAddr;
    }
    function setMdbAddrGroupUsers($MdbAddrGroupUsers) {
        $this->MdbAddrGroupUsers = $MdbAddrGroupUsers;
    }
    function setMdbAddrGroup($MdbAddrGroup) {
        $this->MdbAddrGroup = $MdbAddrGroup;
    }
    /***************************************************
     * getter
     **************************************************/
    // 部署グループID
    function getSection_id() {
        return $this->DtoSection->getSection_id();
    }
    // 部署グループ名
    function getSection_name() {
        return $this->DtoSection->getSection_name();
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
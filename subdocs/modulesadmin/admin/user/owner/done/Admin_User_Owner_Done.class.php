<?php
require_once(MODULE_DIR . '/common/common.php');
/**
 * [機能説明] ユーザ管理 - 管理者の変更(完了)
 * @updater
 *
 * @package     WebFile
 * @version     3.?.?
 * @author
 * @copyright   KeyPoint
 * @access      public
 */

class Admin_User_Owner_Done {
    var $session;
    var $DtoUser;
    var $MdbUser;
    var $DtoAdminUser;
    var $MdbAdminUser;
    var $MdbSection;
    var $MdbSectionusers;
    var $MdbGroups;
    var $MdbGroupsadminusers;
    var $MdbGroupsUsers;
    var $DtoUserSuperior;
    var $MdbUserSuperior;
    var $MdbUpload;
    var $DtoLog;
    var $MdbLog;
    var $AdminUserLogic;

    var $new_owner_name;
    var $old_owner_name;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_User_Owner_Doneが実行されました", "Admin_User_Owner_Done#execute");

        // エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        // adminレベルが低い
        if($this->session->getParameter("login_admin_level") < 4) {
            $errorList->add("Admin_User_Owner_Done::error","権限がないため管理者を変更できません。");
            $this->removeParameter();
            return "error";
        }

        // 変なid
        if (!is_numeric($this->DtoUser->getOwner_admin_id()) ||
            !$this->DtoUser->getOwner_admin_id()) {
            $errorList->add("Admin_User_Owner_Done::error","不正なIDが選択されました。");
            $this->removeParameter();
            return "error";
        }

        // 今の管理者の情報（ログ用）
        $log_owner_admin_id = $this->session->getParameter("log_owner_admin_id");
        $this->DtoAdminUser->setAccount(null);
        $this->DtoAdminUser->setAdmin_id($log_owner_admin_id);
        $this->MdbAdminUser->getAdminuserInfo($this->DtoAdminUser);
        $this->old_owner_name = $this->DtoAdminUser->getAccount(); // ログ用

        // 変更にならないならここで終わり
        if ($log_owner_admin_id == $this->DtoUser->getOwner_admin_id()) {
            $this->removeParameter();
            return "success";
        }

        // 削除されてる
        $this->DtoAdminUser->setAccount(null);
        $this->DtoAdminUser->setAdmin_id($this->DtoUser->getOwner_admin_id());
        $this->MdbAdminUser->getAdminuserInfo($this->DtoAdminUser);
        if (!$this->DtoAdminUser->getAccount()) {
            $errorList->add("Admin_User_Owner_Done::error","選択されたアカウントは既に削除されています。");
            $this->removeParameter();
            return "input";
        }
        $this->new_owner_name = $this->DtoAdminUser->getAccount(); // ログ用

        /*
        // ログのための情報をゲット
        $log_superior = "";
        if(SUPERIOR_OPTION_FLG) {
            $where = array();
            $where[] = "user_id=".Db_Common::escape_string($this->DtoUser->getUser_id());
            $this->MdbUserSuperior->getUserSuperiorListWhere($this->DtoUserSuperior,$where);
            if ($this->DtoUserSuperior->getUsersuperiorList()) {
                foreach($this->DtoUserSuperior->getUsersuperiorList() as $key => $val) {
                    $info = null;
                    $info = $this->MdbUser->getInfo($val["superior_id"]);
                    $log_superior.= "　　".$info["account"]."(".$info["user_name"].")"."\n";
                }
            }
        }

        $log_section = "";
        $sectionkeylist = $this->MdbSection->getKeyList();
        $where = array();
        $where[] = "user_id = " . Db_Common::escape_string($this->DtoUser->getUser_id());
        $list = $this->MdbSectionusers->getSectionusersList($where);
        if ($list) {
            foreach ($list as $val) {
                $log_section.= "　　".$sectionkeylist[$val['section_id']]."\n";
            }
        }

        $log_share_groups = ""; $list = null;
        $list = $this->MdbGroups->getJoinList2($this->DtoUser->getUser_id());
        $type_list = array("" => "権限なし", "1" => "RO", "2" => "RW", "0" => "+RW");
        if ($list) {
            foreach($list as $key => $val) {
                if($val['readonly_flg'] !== ""){
                    if ($val['readonly_flg'] == "1") {
                        $log_read_type = "読み";
                    } elseif ($val['readonly_flg'] == "2") {
                        $log_read_type = "読み/書き";
                    } else {
                        $log_read_type = "読み/書き/フォルダ操作";
                    }

                    // Logのため、グループ名を確保
                    $log_share_groups.= "　　".$val['name'].'：'.$log_read_type."\n";
                }
            }
        }

        // 部署から外す
        $where = array();
        $where[] = "user_id = " . Db_Common::escape_string($this->DtoUser->getUser_id());
        $this->MdbSectionusers->deleteWhere($where);

        // グループから外す
        // 管理者
        $where = array();
        $where[] = "user_id = " . Db_Common::escape_string($this->DtoUser->getUser_id());
        $this->MdbGroupsadminusers->deleteWhere($where);
        // 管理者以外
        $where = array();
        $where[] = "user_id = " . Db_Common::escape_string($this->DtoUser->getUser_id());
        $this->MdbGroupsUsers->deleteWhere($where);

        // 上長を外す
        if(SUPERIOR_OPTION_FLG) {
            //自分の上長設定削除
            $where = array("0"=>"user_id='".Db_Common::escape_string($this->DtoUser->getUser_id())."'");
            $this->MdbUserSuperior->deleteWhere($where);
        }

        //ロックファイル解除
        if(FILE_LOCK_OPTION_FLG) {
            $data = array();
            $data['lock_user_id'] = '0';
            $data['lock_flg'] = '0';

            $where = array(
                           "lock_user_id='".Db_Common::escape_string($this->DtoUser->getUser_id())."'",
                           "lock_flg =1"
                           );
            $this->MdbUpload->update($data, $where);
        }
        */

        // 初期化
        $memo2 = $this->AdminUserLogic->change_user_admin(
            $this->DtoUser,$this->MdbUser,
            $this->DtoUserSuperior,$this->MdbUserSuperior,
            $this->MdbSection,$this->MdbSectionusers,
            $this->MdbGroups,$this->MdbGroupsadminusers,$this->MdbGroupsUsers,
            $this->MdbUpload
        );

        // 管理者を変える
        $owner_admin_id = $this->DtoUser->getOwner_admin_id();
        $this->MdbUser->getUserInfo($this->DtoUser);
        $this->DtoUser->setOwner_admin_id($owner_admin_id);
        $this->MdbUser->editUserByAdmin($this->DtoUser);

        // ログを残す
        $history_id = 103; // 103:ユーザ管理＞ユーザ情報の編集
        $memo = "編集ユーザ\n";
        $memo.= "　アカウント：".$this->DtoUser->getAccount()."\n";
        $memo.= "　管理者：".$this->old_owner_name."→".$this->new_owner_name."\n";
        if ($memo2) {
            $memo = $memo.$memo2;
        }
        /*
        $memo2 = "";
        if(SUPERIOR_OPTION_FLG && $log_superior) {
            $memo2.= "　上長"."\n";
            $memo2.= $log_superior;
        }
        if ($log_share_groups) {
            $memo2.= "　共有グループ"."\n";
            $memo2.= $log_share_groups;
        }
        if ($log_section) {
            $memo2.= "　部署グループ"."\n";
            $memo2.= $log_section;
        }
        if ($memo2) {
            $memo.= "　（以下が初期化されました）"."\n";
            $memo.= $memo2;
        }
        */

        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        $this->removeParameter();
        return "success";
    }

    function removeParameter() {
        $this->session->removeParameter("user_id");
        $this->session->removeParameter("log_owner_admin_id");
    }

    //************************************************************
    // setter
    //************************************************************
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoUser($DtoUser) {
        $this->DtoUser = $DtoUser;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = $MdbUser;
    }
    function setDtoAdminUser($DtoAdminUser) {
        $this->DtoAdminUser = $DtoAdminUser;
    }
    function setMdbAdminUser($MdbAdminUser) {
        $this->MdbAdminUser = $MdbAdminUser;
    }
    function setMdbSection($MdbSection) {
        $this->MdbSection = $MdbSection;
    }
    function setMdbSectionusers($MdbSectionusers) {
        $this->MdbSectionusers = $MdbSectionusers;
    }
    function setMdbGroups($MdbGroups) {
        $this->MdbGroups = $MdbGroups;
    }
    function setMdbGroupsadminusers($MdbGroupsadminusers) {
        $this->MdbGroupsadminusers = $MdbGroupsadminusers;
    }
    function setMdbGroupsUsers($MdbGroupsUsers) {
        $this->MdbGroupsUsers = $MdbGroupsUsers;
    }
    function setDtoUserSuperior($DtoUserSuperior) {
        $this->DtoUserSuperior = $DtoUserSuperior;
    }
    function setMdbUserSuperior($MdbUserSuperior) {
        $this->MdbUserSuperior = $MdbUserSuperior;
    }
    function setMdbUpload($MdbUpload) {
        $this->MdbUpload = $MdbUpload;
    }
    function setDtoLog($DtoLog) {
        $this->DtoLog = $DtoLog;
    }
    function setMdbLog($MdbLog) {
        $this->MdbLog = $MdbLog;
    }
    function setAdminUserLogic($AdminUserLogic) {
        $this->AdminUserLogic = $AdminUserLogic;
    }
    //************************************************************
    // getter
    //************************************************************
    // 管理者リスト
    function getOwner_admin_list() {
        $select_list = array();
        $order = array("account");
        $list = $this->MdbAdminUser->getAdminUserSelect();
        foreach ($list as $key => $val) {
            $select_list[$val["admin_id"]] = $val["account"];
        }
        return $select_list;
    }
    function getOwner_admin_id() {
        return $this->DtoUser->getOwner_admin_id();
    }
    function getUser_id() {
        return $this->DtoUser->getUser_id();
    }
    function getOwner_name() {
        if ($this->new_owner_name) {
            return $this->new_owner_name;
        }
        return $this->old_owner_name;
    }
    function getUser_name() {
        $info = $this->MdbUser->getInfo($this->DtoUser->getUser_id());
        return $info["user_name"];
    }
    function getMailaddr() {
        $info = $this->MdbUser->getInfo($this->DtoUser->getUser_id());
        return $info["mailaddr"];
    }
}
?>

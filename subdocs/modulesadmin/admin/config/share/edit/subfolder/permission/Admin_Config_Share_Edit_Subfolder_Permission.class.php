<?php

require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Share_Edit_Subfolder_Permission {

    // maple:action
    var $session;
    var $DtoUser;
    var $MdbUser;

    var $DtoFolder;
    var $MdbFolder;
    var $DtoGroups;
    var $MdbGroups;
    var $ShareLogic;
    var $MdbGroupsaccesses;
    var $MdbGroupsadminusers;
    var $MdbSection;
    var $DtoSection;
    var $MdbGroupsusers;
    var $FolderDto;
    var $FolderLogic;
    var $MdbAdminuser;
    var $groupDto;

    // inner
    var $username;
    var $group_id;
    var $folderPower;
    var $Powerlist;
    var $displaylist;
    var $top_flg;
    var $folder_tree;
    var $leader_open_flg;
    var $user_open_flg;
    var $section_open_flg;
    var $group_admin_operate_flg;

    var $alledit_flg;
    var $readonly_flgs;
    var $section_readonly_flgs;

    var $back_flg;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Share_Edit_Subfolder_Permissionが実行されました","Admin_Config_Share_Edit_Subfolder_Permission#execute");

        //エラーオブジェクト
        $container   =& DIContainerFactory::getContainer();
        $actionChain =& $container->getComponent('ActionChain');
        $errorList   =& $actionChain->getCurErrorList();

        if(!preg_match("/^[0-9]+$/",$this->DtoFolder->getFolder_id())) {
            $errorList->add('Admin_Config_Share_Edit_Subfolder_Permission::error', ' 不正なアクセスがありました。');
            return "error";
        }

        // 権限画面から遷移した場合
        if( $this->back_flg ) {
            $this->session->setParameter( "member_edit", "1");
            $this->session->setParameter( "users", $this->groupDto->getUsers() );
            $this->session->setParameter( "leaders", $this->groupDto->getLeaders() );
            $this->session->setParameter( "sections", $this->groupDto->getSections() );
            $this->session->setParameter( "readonly_flgs", $this->readonly_flgs );
            $this->session->setParameter( "section_readonly_flgs", $this->section_readonly_flgs );
            $this->session->setParameter( "alledit_flg", $this->alledit_flg);

            //2014/07/11@yano injectRequestをinjectSessionで上書きされてるので別変数でもつ
            $this->session->setParameter( "group_admin_operate_flg", $this->group_admin_operate_flg);
        }

        //フォルダチェック
        /*if(!$this->MdbGroups->getExistCheck(array("folder_id='".$this->DtoFolder->getFolder_id()."'" ))) {
            //権限なし
            $errorList->add('Admin_Config_Share_Edit_Subfolder_Permission::error', ' このフォルダは編集できません。');
            return "error";
        }*/


        //グループ名セット
        $this->group_id = $this->DtoGroups->getId();
        if(!$this->group_id) {
            $this->group_id = $this->session->getParameter("id");
        }
        $this->DtoGroups->setId($this->group_id);
        $this->MdbGroups->getInfo($this->DtoGroups);

        //DtoFolderセット
        $info = $this->MdbFolder->getFolderInfo($this->DtoFolder->getFolder_id());

        //グループのフォルダIDと一致している場合はトップディレクトリ
        $this->top_flg = ( $this->DtoGroups->getFolder_id()==$info[0]["folder_id"] ? 1 : 0 );

        // 管理者ユーザIDを取得
        /*
        $admin = $this->MdbGroupsadminusers->getDirectList( array("group_id='".$this->group_id."'","user_id<>0");
        if($admin === false) {
        }*/

        //共有フォルダリスト
        //$share_dir = $this->FolderLogic->getShareDir($this->MdbGroupsusers, $this->MdbGroupsaccesses, $this->DtoUser->getUser_id());
        //$share_dir = $this->MdbGroupsusers->getHasGroupsEx(0);//$user_id);
        //var_dump($share_dir);
        // 読み出し以上のフォルダリスト
        //$this->FolderLogic->folderShareSelect($this->FolderDto,$this->MdbFolder,$this->DtoUser->getUser_id(),$share_dir,0);


        // フォルダツリー作成
        $this->FolderDto->sharefolder_select = array();
        $info = $this->MdbFolder->getFolderInfo($this->DtoGroups->getFolder_id());
        if( $info ) {
            $this->FolderDto->sharefolder_select[] = array($info[0]["folder_id"] => $info[0]["folder_name"]);
            $this->digTree($this->MdbFolder->childrenList(0,$info[0]["folder_id"]));
        } else {
            $this->digTree($this->MdbFolder->childrenList(0,$this->DtoFolder->getFolder_id()));
        }


        //管理者権限
        //セッション設定
        $this->session->setParameter("sub_folder_id",$this->DtoFolder->getFolder_id());

        // フォルダ変更なしの場合のみ一度だけグループ容量を再計算
        // 2018/06/06 @yano $_GETではなくrequestオブジェクトを利用するよう修正
        $request = $container->getComponent("Request");
        if (is_null($request->getParameter("folder_id"))) {
            // sesssion
            $this->session->setParameter("capacity", round($this->groupDto->capacity/1024/1024,2));
            $this->session->setParameter("leader_open_flg", $this->leader_open_flg);
            $this->session->setParameter("user_open_flg", $this->user_open_flg);
            $this->session->setParameter("section_open_flg", $this->section_open_flg);
            $this->session->setParameter("groupDto", $this->groupDto);
            $this->session->setParameter("term_flg", $this->groupDto->getTerm_flg());
        }
        return "success";
    }

    function digTree($tree,$depth=0) {
        $spacer = "&nbsp;&nbsp;";
        for($i=0;$i<$depth;$i++) {
            $spacer .= '&nbsp;&nbsp;';
        }
        foreach($tree as $val) {
            $this->FolderDto->sharefolder_select[] = array($val["folder_id"] => $spacer.$val["folder_name"]);
            $check = $this->MdbFolder->childrenList(0,$val["folder_id"]);
            if(count($check)) {
                $this->digTree($check,$depth+1);
            }
        }
    }

    // Setter
    function setSession(&$session) {
        $this->session = & $session;
    }
    function setDtoUser(&$DtoUser) {
        $this->DtoUser = & $DtoUser;
    }
    function setMdbUser(&$MdbUser) {
        $this->MdbUser = & $MdbUser;
    }
    function setDtoFolder(&$DtoFolder) {
        $this->DtoFolder = & $DtoFolder;
    }
    function setMdbFolder(&$MdbFolder) {
        $this->MdbFolder = & $MdbFolder;
    }
    function setDtoGroups(&$DtoGroups) {
        $this->DtoGroups = & $DtoGroups;
    }
    function setMdbGroups(&$MdbGroups) {
        $this->MdbGroups = & $MdbGroups;
    }
    function setShareLogic(&$ShareLogic) {
        $this->ShareLogic = & $ShareLogic;
    }
    function setMdbGroupsaccesses(&$MdbGroupsaccesses) {
        $this->MdbGroupsaccesses =& $MdbGroupsaccesses;
    }
    function setMdbGroupsadminusers(&$MdbGroupsadminusers) {
        $this->MdbGroupsadminusers = & $MdbGroupsadminusers;
    }
    function setMdbSection(&$MdbSection) {
        $this->MdbSection = & $MdbSection;
    }
    function setDtoSection(&$DtoSection) {
        $this->DtoSection = & $DtoSection;
    }
    function setMdbGroupsusers(&$MdbGroupsusers) {
        $this->MdbGroupsusers = & $MdbGroupsusers;
    }
    function setFolderDto(&$FolderDto) {
        $this->FolderDto = & $FolderDto;
    }
    function setFolderLogic(&$FolderLogic) {
        $this->FolderLogic =& $FolderLogic;
    }
    function setMdbAdminuser($MdbAdminuser) {
        $this->MdbAdminuser = $MdbAdminuser;
    }
    function setLeader_open_flg($leader_open_flg) {
        $this->leader_open_flg = $leader_open_flg;
    }
    function setUser_open_flg($user_open_flg) {
        $this->user_open_flg = $user_open_flg;
    }
    function setSection_open_flg($section_open_flg) {
        $this->section_open_flg = $section_open_flg;
    }
    function setGroupDto($groupDto) {
        $this->groupDto = $groupDto;
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
    function setGroup_admin_operate_flg($group_admin_operate_flg)
    {
        $this->group_admin_operate_flg = $group_admin_operate_flg;
    }
    //utility

    //共通:グループ名取得
    function getGroupName() {
        return $this->DtoGroups->getName();
    }
    //共通:folder_name
    function getFolderName() {
        return $this->DtoFolder->getFolder_name();
    }
    //フォルダID
    function getFolderId() {
        return $this->DtoFolder->getFolder_id();
    }
    //グループID
    function getGroup_id() {
        return $this->DtoGroups->getId();
    }
    //index.html:共有フォルダ or サブフォルダ
    function getShareTopFolder() {
        return $this->top_flg;
    }
    //info.html:管理者情報を取得
    function getAdminuser() {
        $ret = $this->MdbGroupsadminusers->getAdminuserByGroup($this->group_id);
        //var_dump($var);
        return $ret;
    }
    //info.html:表示リスト
    function getDisplayList() {
        return $this->displaylist;
    }
    //info.html:所属文言取得
    function getSectionName($param) {
        $section_id=$param['id'];
        if($section_id==0) return "  ";
        if($section_id>=1) {
            $this->DtoSection->setSection_id($section_id);
            $this->MdbSection->getInfo($this->DtoSection);
            return $this->DtoSection->getSection_name();
        }
    }
    function getPowerImgName($param) {
        $power_flg = $param['id'];
        switch($power_flg) {
            case '0': return "ico-authority-r.gif";
            case '1': return "ico-authority-rw.gif";
            case '2': return "ico-authority-rwx.gif";
            case '3': return "ico-authority-rwx.gif";
            default: return "ico-authority-none.gif";
        }
    }
    //権限文言取得
    function getPowerShortName($param) {
        $power_flg = $param['id'];
        if(!ENTRY_USE) {
            switch($power_flg) {
                case '0': return "読み出しのみ";
                case '1': return "読み／書き";
                case '2': return "読み／書き／フォルダ操作";
                case '3': return "管理者";
                default: return "なし";
            }
        }
        else {
            switch($power_flg) {
                case '0': return "Read only";
                case '1': return "Read/Write";
                case '2': return "Read/Write/Folder";
                case '3': return "Admin";
                default: return "None";
            }
        }
    }
    //共有フォルダリスト
    function getShareFolderSelect() {
        if ($this->FolderDto->sharefolder_select) {
            return $this->FolderDto->sharefolder_select;
        } else {
            return array();
        }
    }

    function getUser_open_flg() {
        return $this->session->getParameter('user_open_flg');
    }
    function getSection_open_flg() {
        return $this->session->getParameter('section_open_flg');
    }
    function getLeader_open_flg() {
        return $this->session->getParameter('leader_open_flg');
    }
    function getTerm_flg() {
        return $this->groupDto->getTerm_flg();
    }
}
?>

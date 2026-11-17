<?php
  /************************************************************
   * last update : 2009/09/03 yano
   ************************************************************/
require_once( MODULE_DIR . "/common/common.php");

class Admin_Superior_Edit_Done {
    var $session;
    var $MdbUserSuperior;
    var $DtoUserSuperior;
    var $DtoUser;
    var $MdbUser;
    var $DtoLog;
    var $MdbLog;
    var $MdbSuperiorMulti;
    var $adminuserLogic;
    var $UserLogic;
    var $MdbSuperiorSend;

    var $superior_ids; //in
    var $sorts; //in
    var $require_all_approval_flg;//in
    var $can_login_without_superior;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Superior_Edit_Doneが実行されました","Admin_Superior_Edit_Done#execute");

        // エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        // 上長承認オプションチェック
        if(!SUPERIOR_OPTION_FLG) {
            $errorList->add("Admin_Superior_Edit_Done::error", "この機能をご利用頂くためには、上長承認スキャンオプションが必要です。");
            return "error";
        }
        // ユーザの存在チェック
        $where = array('0'=>"user_id='".Db_Common::escape_string($this->DtoUser->getUser_id())."'", '1'=>"del_flg='0'");
        if(!$this->MdbUser->getExistCheck($where)) {
            $errorList->add("Admin_Superior_Edit_Done::error", "ユーザ情報を取得できませんでした。");
            $errorList->add("Admin_Superior_Edit_Done::error", "既に削除されている可能性があります。");
            return "error";
        }

        // ユーザ情報取得
        $this->MdbUser->getUserInfo($this->DtoUser);
        // ユーザ管理者の場合 選択したユーザが自分が追加したユーザかを確認
        if( $this->session->getParameter("login_admin_level") == "3" ) {
            if($this->DtoUser->getOwner_admin_id() != $this->session->getParameter("login_admin_id")){
                $errorList->add("Admin_Superior_Edit::error", "選択したユーザは、他の管理者が追加したユーザのため編集できません。");
                return "error";
            }
        }

        //重複除去
        if($this->superior_ids && is_array($this->superior_ids)) {
            $this->superior_ids = array_unique($this->superior_ids);
        }

        $where = array("0"=>"del_flg='0'","1"=>"stop_flg='0'");
        $users = $this->MdbUser->getUserKeyList($where); // ユーザ一覧取得
        $this->DtoUser->setUserList($users); //DTOに保持
        $user_id = $this->DtoUser->getUser_id();

        if($this->superior_ids && is_array($this->superior_ids) && $users && is_array($users)) {
            foreach($this->superior_ids as $key => $val) {
                // 上長存在チェック
                if(!array_key_exists($val, $users)) {
                    $errorList->add("Admin_Superior_Edit_Done::error", "選択された上長は、既に削除されている可能性があります。");
                    return "input";
                }
                // 編集ユーザが上長に指定されていないかどうか
                if($user_id== $val) {
                    $errorList->add("Admin_Superior_Edit_Done::error", "選択された上長に、編集中のユーザが含まれています。");
                    $errorList->add("Admin_Superior_Edit_Done::error", "編集中のユーザに上長は指定できません。");
                    return "input";
                }
            }
        }
        if ($this->can_login_without_superior === null) {
            $this->can_login_without_superior = 0; //checkboxなので、チェックしない = null
        } else if ($this->can_login_without_superior == 1) {
            $this->can_login_without_superior = 1;
        } else {
            $errorList->add("Admin_Superior_Edit_Done::error", "上長設定無しでのログイン設定チェックボックスに不正な入力が見つかりました。");
            return "input";
        }

        // ユーザの上長が設定されているかどうか
        $where = array('0' => "user_id='".Db_Common::escape_string($user_id)."'");
        $this->MdbUserSuperior->getUserSuperiorListWhere($this->DtoUserSuperior,$where);
        $superior_list_org = $this->DtoUserSuperior->getUsersuperiorList();
        $rejected_flg = false;
        foreach($superior_list_org as $val){
            if($this->superior_ids === null || !in_array($val['superior_id'],$this->superior_ids)){
                //上長が外された場合、承認待ちを却下
                $unapproved_ids = $this->MdbSuperiorMulti->getUnapprovedIdsBySuperiorAndUserId($val['superior_id'],$user_id);
                $this->DtoUser->setUser_id($val['superior_id']);
                $this->MdbUser->getUserInfo($this->DtoUser);
                $result = $this->adminuserLogic->rejectAllApprovalRequest(
                    $this->DtoUser, $this->MdbUser, $this->DtoUserSuperior,
                    $this->MdbUserSuperior, $this->MdbSuperiorSend, $this->UserLogic,$unapproved_ids
                );
                if($result === true){
                    $rejected_flg = true;
                }
            }
        }

        if($rejected_flg === true){
            $errorList->add("Admin_Superior_Edit_Done::unapproved", "未承認の承認依頼が却下されました。");
        }
        $this->DtoUser->setUser_id($user_id);
        $this->MdbUser->getUserInfo($this->DtoUser);

        //設定されている場合は全て削除する
        $this->MdbUserSuperior->deleteWhere($where);

        //ログ準備
        $memo = "編集ユーザ\n";
        $memo .= "　アカウント : ".$this->DtoUser->getAccount()."\n";
        $memo .= "　氏名 : ".$this->DtoUser->getAccount();
        $memo .= ($this->DtoUser->getUser_kana()) ? "(".$this->DtoUser->getUser_kana().")\n" : "\n";
        $memo .= "　メールアドレス : ".$this->DtoUser->getMailaddr()."\n";
        $memo .= "上長設定\n";

        // 上長を設定
        if($this->superior_ids && is_array($this->superior_ids) && $users && is_array($users)) {
            $this->DtoUserSuperior->setUser_id($this->DtoUser->getUser_id());
            foreach($this->superior_ids as $key => $val) {
                $this->DtoUserSuperior->setSuperior_id($val);
                $this->DtoUserSuperior->setSort($key + 1);
                $this->MdbUserSuperior->insert($this->DtoUserSuperior);
                $memo.= "　アカウント:".$users[$val]['account'] . " (氏名:".$users[$val]['user_name'].")\n";
            }
        }

        if ($this->session->getParameter("login_admin_level") >= 5) {
            // 上長承認無しでログイン許可のフラグ
            $this->DtoUser->setCan_login_without_superior($this->can_login_without_superior);
            $memo .= "上長が未設定の場合のログイン\n";
            $memo .= ($this->can_login_without_superior) ? "　許可" : "　禁止";
        }
        //全上長の承認が必要かのフラグ変更
        $this->DtoUser->setRequire_all_approval_flg($this->require_all_approval_flg);
        $this->MdbUser->updateUser($this->DtoUser);

        // Log
        $history_id = 124; // 124:上長承認設定＞上長設定
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id($this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        //セッション消す
        $this->removeSession();

        return "success";
    }
    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setMdbUserSuperior($MdbUserSuperior) {
        $this->MdbUserSuperior = & $MdbUserSuperior;
    }
    function setDtoUserSuperior($DtoUserSuperior) {
        $this->DtoUserSuperior = & $DtoUserSuperior;
    }
    function setMdbSuperiorSend($MdbSuperiorSend) {
        $this->MdbSuperiorSend = & $MdbSuperiorSend;
    }
    function setMdbSuperiorMulti($MdbSuperiorMulti) {
        $this->MdbSuperiorMulti = & $MdbSuperiorMulti;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = & $MdbUser;
    }
    function setDtoUser($DtoUser) {
        $this->DtoUser = & $DtoUser;
    }
    function setUserLogic($UserLogic) {
        $this->UserLogic = & $UserLogic;
    }
    function setAdminuserLogic($adminuserLogic) {
        $this->adminuserLogic = $adminuserLogic;
    }
    function setMdbLog($MdbLog) {
        $this->MdbLog = & $MdbLog;
    }
    function setDtoLog($DtoLog) {
        $this->DtoLog = & $DtoLog;
    }
    function setSuperior_ids($superior_ids) {
        $this->superior_ids = $superior_ids;
    }
    function setSorts($sorts) {
        $this->sorts = $sorts;
    }
    function setRequire_all_approval_flg($require_all_approval_flg) {
        $this->require_all_approval_flg = $require_all_approval_flg;
    }

    function setCan_login_without_superior(&$can_login_without_superior)
    {
        $this->can_login_without_superior =& $can_login_without_superior;
    }


    function removeSession() {
        $this->session->removeParameter('user_id');
        $this->session->removeParameter('account');
        $this->session->removeParameter('user_name');
        $this->session->removeParameter('user_kana');
        $this->session->removeParameter('mailaddr');
    }
    // getter
    // getter
    function getUserInfo() {
        $info = array(
                      'user_id' => $this->DtoUser->getUser_id(),
                      'account' => $this->DtoUser->getAccount(),
                      'user_name' => $this->DtoUser->getUser_name(),
                      'user_kana' => $this->DtoUser->getUser_kana(),
                      'mailaddr' => $this->DtoUser->getMailaddr(),
                      'stop_flg' => $this->DtoUser->getStop_flg(),
                      'can_login_without_superior' => $this->DtoUser->getCan_login_without_superior()
                      );
        return $info;
    }
    function getSuperiorList() {
        // ユーザ一覧取得
        $users = $this->DtoUser->getUserList();
        $list = array();
        foreach($this->superior_ids as $key => $val) {
            if(array_key_exists($val, $users)) {
                $list[$key]['superior_id'] = $val;
                $list[$key]['superior_account'] = $users[$val]['account'];
                $list[$key]['superior_name'] = $users[$val]['user_name'];
            }
        }
        return $list;
    }

}
?>

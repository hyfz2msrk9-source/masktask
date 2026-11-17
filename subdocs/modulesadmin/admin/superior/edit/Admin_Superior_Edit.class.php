<?php
  /************************************************************
   * last update : 2009.09.03
   ************************************************************/
require_once( MODULE_DIR . "/common/common.php");

class Admin_Superior_Edit {
    var $session;
    var $MdbUserSuperior;
    var $DtoUserSuperior;
    var $DtoUser;
    var $MdbUser;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Superior_Editが実行されました","Admin_Superior_Edit#execute");

        // エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        // ユーザの存在チェック
        $where = array('0'=>"user_id='".Db_Common::escape_string($this->DtoUser->getUser_id())."'", '1'=>"del_flg='0'");
        if(!$this->MdbUser->getExistCheck($where)) {
            $errorList->add("Admin_Superior_Edit::error", "ユーザ情報を取得できませんでした。");
            $errorList->add("Admin_Superior_Edit::error", "既に削除されている可能性があります。");
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
        // 上長情報取得
        $where = array('0' => "user_id='".Db_Common::escape_string($this->DtoUser->getUser_id())."'");
        $this->MdbUserSuperior->getUserSuperiorListWhere($this->DtoUserSuperior, $where);

        // セッションに保存
        $this->session->setParameter('user_id',$this->DtoUser->getUser_id());
        $this->session->setParameter('account',$this->DtoUser->getAccount());
        $this->session->setParameter('user_name',$this->DtoUser->getUser_name());
        $this->session->setParameter('user_kana',$this->DtoUser->getUser_kana());
        $this->session->setParameter('mailaddr',$this->DtoUser->getMailaddr());

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
    function setMdbUser($MdbUser) {
        $this->MdbUser = & $MdbUser;
    }
    function setDtoUser($DtoUser) {
        $this->DtoUser = & $DtoUser;
    }
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
        // 上長情報取得
        $info = $this->DtoUserSuperior->getUsersuperiorList();
        // ユーザ一覧取得
        $where = array("0"=>"del_flg='0'","1"=>"stop_flg='0'");
        $users = $this->MdbUser->getUserKeyList($where);
        $list = array();
        foreach($info as $key => $val) {
            if(!$users[$val['superior_id']]['stop_flg'] && !$users[$val['superior_id']]['del_flg'] && array_key_exists($val['superior_id'], $users)) {
                $list[$key]['superior_id'] = $val['superior_id'];
                $list[$key]['superior_account'] = $users[$val['superior_id']]['account'];
                $list[$key]['superior_name'] = $users[$val['superior_id']]['user_name'];
                $list[$key]['sort'] = $val['sort'];
            }
        }
        return $list;
    }
    function getRequire_all_approval_flg(){
        return $this->DtoUser->getRequire_all_approval_flg();
    }
    function getCan_login_without_superior() {
        return $this->DtoUser->getCan_login_without_superior();
    }
}
?>

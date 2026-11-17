<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Superior_List {
    var $session;
    var $DtoFlexi;
    var $MdbUserSuperior;
    var $DtoUserSuperior;
    var $SuperiorLogic;
    var $UserDto;
    var $MdbUser;
    var $DtoUser;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Virus executed.","Admin_Virus#execute");

        // セッションセット
        $this->session->setParameter("page", $this->DtoFlexi->getPage());
        $this->session->setParameter("query", $this->DtoFlexi->getQuery());
        $this->session->setParameter("qtype", $this->DtoFlexi->getQtype());
        $this->session->setParameter("sortname", $this->DtoFlexi->getSortname());
        $this->session->setParameter("sortorder", $this->DtoFlexi->getSortorder());
        $this->session->setParameter("rp", $this->DtoFlexi->getRp());

        // 自分の権限を確認 (ユーザ管理=3)であれば 自分の追加したユーザのみを表示するように設定する
        if( $this->session->getParameter("login_admin_level") == "3" ) {
            // 動作テストの為、コメント中
            $admin_id = $this->session->getParameter("admin_id");
        } else {
            $admin_id = 0;
        }

        // 一覧取得
        $this->SuperiorLogic->getFlexiList($this->MdbUserSuperior, $this->DtoUserSuperior,$this->DtoFlexi,$this->MdbUser,$this->DtoUser,$this->UserDto,$admin_id);
        echo $this->SuperiorLogic->getFlexiList2($this->DtoUserSuperior,$this->DtoFlexi, $this->UserDto, $this->DtoUser, $this->MdbUser);
        exit();
    }

    function setSession(&$session) {
        $this->session =& $session;
    }
    function setDtoFlexi($DtoFlexi) {
        $this->DtoFlexi = & $DtoFlexi;
    }
    function setMdbUserSuperior($MdbUserSuperior) {
        $this->MdbUserSuperior = & $MdbUserSuperior;
    }
    function setDtoUserSuperior($DtoUserSuperior) {
        $this->DtoUserSuperior = & $DtoUserSuperior;
    }
    function setSuperiorLogic($SuperiorLogic) {
        $this->SuperiorLogic = & $SuperiorLogic;
    }
    function setUserDto($UserDto) {
        $this->UserDto = & $UserDto;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = & $MdbUser;
    }
    function setDtoUser($DtoUser) {
        $this->DtoUser = & $DtoUser;
    }
}
?>

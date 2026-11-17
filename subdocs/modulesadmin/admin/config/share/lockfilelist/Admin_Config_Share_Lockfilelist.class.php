<?php
require_once( MODULE_DIR . "/common/common.php");

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * [機能説明] 共有フォルダのロックファイル一覧取得
 */
class Admin_Config_Share_Lockfilelist {
    var $session;
    var $DtoGroups;
    var $MdbGroups;
    var $MdbUser;
    var $MdbFolder;
    var $MdbUpload;
    var $GroupDto;
    var $GroupLogic;
    var $DtoFlexi;
    var $DtoAdminUser;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Share_Lockfilelistが実行されました","Admin_Config_Share_Lockfilelist#execute");

        // セッションセット
        $this->session->setParameter("page", $this->DtoFlexi->getPage());
        $this->session->setParameter("query", $this->DtoFlexi->getQuery());
        $this->session->setParameter("qtype", $this->DtoFlexi->getQtype());
        $this->session->setParameter("sortname", $this->DtoFlexi->getSortname());
        $this->session->setParameter("sortorder", $this->DtoFlexi->getSortorder());
        $this->session->setParameter("rp", $this->DtoFlexi->getRp());

        // グループ一覧の取得
        $this->GroupLogic->getFlexiListLockfile(
                                                $this->GroupDto,
                                                $this->MdbUpload,
                                                $this->DtoFlexi,
                                                $this->MdbUser,
                                                $this->MdbFolder,
                                                $this->MdbGroups,
                                                $this->DtoAdminUser
                                                );

        echo $this->GroupLogic->getFlexiList3($this->GroupDto,
                                              $this->DtoFlexi);

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
    function setMdbUser($MdbUser) {
        $this->MdbUser = $MdbUser;
    }
    function setMdbFolder($MdbFolder) {
        $this->MdbFolder = $MdbFolder;
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
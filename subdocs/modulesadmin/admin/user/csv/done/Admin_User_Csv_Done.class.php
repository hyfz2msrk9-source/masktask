<?php
require_once(MODULE_DIR . '/common/common.php');
/**
 * [機能説明] ユーザ管理 - CSVファイルで追加(完了)
 * @updater     Hiroko Yanai（2019/08/28）上長を削除したら、承認待ちは全て却下する
 *
 * @package     WebFile
 * @version     3.17.0
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_User_Csv_Done {
    var $DtoUser;
    var $session;
    var $fileUpload;
    var $UserLogic;     // admin_user_logic
    var $User2Logic;    // user_logic
    var $MdbUser;
    var $UserDto;
    var $DtoLog;
    var $MdbLog;
    var $MdbConfigs;
    var $DtoConfigs;
    var $MdbGroups;
    var $MdbGroupsUsers;
    var $MdbSection;
    var $MdbSectionusers;
    var $MdbGroupsadminusers;
    var $MdbUserSuperior;
    var $DtoUserSuperior;
    var $MdbUpload;
    var $DtoUpload;
    var $MdbCollect;
    var $DtoCollect;
    var $MdbFolder;
    var $DtoFolder;
    var $MdbTag;
    var $DtoTag;
    var $MdbAddr;
    var $DtoAddr;
    var $MdbAddrGroup;
    var $DtoAddrGroup;
    var $MdbAddrGroupUsers;
    var $MdbSign;
    var $DtoSign;
    var $MdbTemplate;
    var $DtoTemplate;
    var $MdbResendMail;
    var $DtoResendMail;
    var $CapacityLogic;
    var $MdbDomain;
    var $DtoDomain;
    var $MdbAdminuser;
    var $DtoAdminuser;
    var $MdbReportmail;
    var $inform_count;

    var $CommonList;
    var $MdbSuperiorSend;
    var $MdbSuperiorMulti;
    var $MdbDelivery;

    var $rejected_flg;

    function execute() {
        // ログ
        $log =& LogFactory::getLog();
        $log->debug("Admin_User_Csv_Doneが実行されました", "Admin_User_Csv_Done#execute");
        $this->rejected_flg = false;

        $filename = $this->fileUpload->getTmpName();
        $filesize = $this->fileUpload->getFilesize();

        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        if ($filesize <= 0) {
            $errorList->add("Admin_Config_Share_Export::error", "CSVファイルにエラーがありました。");
            $errorList->add("Admin_Config_Share_Export::error", "ファイルを確認してください。");
            return "upload";
        }
        if (!preg_match("/\.csv/i",$this->fileUpload->getOriginalName())) {
            $errorList->add("Admin_Config_Share_Export::error", "インポートファイルは、CSV形式で作成してください。");
            return "upload";
        }

        $this->rejected_flg = $this->UserLogic->addCsvUser(
            $filename, $filesize
            ,$this->DtoUser, $this->UserDto, $this->MdbUser
            ,$this->User2Logic // コベルコさんカスタマイズ用
            ,$this->MdbConfigs,$this->DtoConfigs
            ,$this->MdbGroupsUsers, $this->MdbGroups
            ,$this->MdbSectionusers, $this->MdbSection
            ,$this->MdbGroupsadminusers
            ,$this->MdbUserSuperior,$this->DtoUserSuperior
            ,$this->MdbUpload, $this->DtoUpload
            ,$this->MdbCollect, $this->DtoCollect
            ,$this->MdbFolder, $this->DtoFolder
            ,$this->MdbTag, $this->DtoTag
            ,$this->MdbAddr, $this->DtoAddr
            ,$this->MdbAddrGroup, $this->DtoAddrGroup
            ,$this->MdbAddrGroupUsers
            ,$this->MdbSign, $this->DtoSign
            ,$this->MdbTemplate, $this->DtoTemplate
            ,$this->MdbResendMail, $this->DtoResendMail
            ,$this->CapacityLogic
            ,$this->MdbDomain, $this->DtoDomain
            ,$this->DtoAdminuser, $this->MdbAdminuser
            ,$this->MdbReportmail
            ,0
            ,$this->session->getParameter("admin_id"), $this->session->getParameter("login_admin_level")
            , $this->CommonList
            , $this->MdbSuperiorSend
            , $this->MdbSuperiorMulti
            , $this->MdbDelivery
        );

        // Log
        $history_id = 102; // 102:ユーザ管理＞CSVファイルでの追加
        $memo = "";
        $memo.= "ファイル名：" . $this->fileUpload->getOriginalName() . "\n";
        $memo.= "追加ユーザ：" . $this->UserDto->getIncount() . "件\n";
        $memo.= "更新ユーザ：" . $this->UserDto->getAddcount() . "件\n";
        $memo.= "削除ユーザ：" . $this->UserDto->getDelcount() . "件\n";

        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        //未ログインユーザを一覧を取得
        $sql = "password = '' AND (last_login_dt IS NULL OR last_login_dt <= 0)";
        $sql.=" AND ad_flg = '0' AND stop_flg = '0' AND del_flg='0'"; //AD連携は除外
        $sql.=" AND sso_flg='0' AND saml_flg='0'"; // Google SSO認証とSAML認証は除外
        $where = array($sql);

        $num = $this->MdbUser->getUserListWhere($where,1);
        if ($num > 0) {
            $this->inform_count = $num;
        }
        return "success";

    }

    //************************************************************
    // setter
    //************************************************************
    function setSession($session) {
        $this->session = $session;
    }

    function setFileUpload(&$fileUpload) {
        $this->fileUpload =& $fileUpload;
    }
    function setDtoUser($DtoUser) {
        $this->DtoUser = $DtoUser;
    }
    function setUserLogic($UserLogic) {
        $this->UserLogic = $UserLogic;
    }
    function setUser2Logic($User2Logic) {
        $this->User2Logic = $User2Logic;
    }
    function setMdbUser($MdbUser) {
    	$this->MdbUser = $MdbUser;
    }
    function setUserDto($UserDto) {
    	$this->UserDto = $UserDto;
    }
    function setDtoLog ( &$DtoLog ) {
        $this->DtoLog =& $DtoLog;
    }
    function setMdbLog ( &$MdbLog ) {
        $this->MdbLog =& $MdbLog;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setDtoConfigs($DtoConfigs) {
        $this->DtoConfigs = $DtoConfigs;
    }
    function setMdbGroups($MdbGroups ) {
        $this->MdbGroups = $MdbGroups;
    }
    function setMdbGroupsUsers(&$MdbGroupsUsers ) {
        $this->MdbGroupsUsers = &$MdbGroupsUsers;
    }
    function setMdbSection($MdbSection ) {
        $this->MdbSection = $MdbSection;
    }
    function setMdbSectionusers(&$MdbSectionusers ) {
        $this->MdbSectionusers = &$MdbSectionusers;
    }
    function setMdbGroupsadminusers(&$MdbGroupsadminusers ) {
        $this->MdbGroupsadminusers = &$MdbGroupsadminusers;
    }
    function setMdbUserSuperior(&$MdbUserSuperior ) {
        $this->MdbUserSuperior = &$MdbUserSuperior;
    }
    function setDtoUserSuperior($DtoUserSuperior ) {
        $this->DtoUserSuperior = $DtoUserSuperior;
    }
    function setMdbUpload(&$MdbUpload ) {
        $this->MdbUpload = &$MdbUpload;
    }
    function setDtoUpload(&$DtoUpload ) {
        $this->DtoUpload = &$DtoUpload;
    }
    function setMdbCollect(&$MdbCollect ) {
        $this->MdbCollect = &$MdbCollect;
    }
    function setDtoCollect(&$DtoCollect ) {
        $this->DtoCollect = &$DtoCollect;
    }
    function setMdbFolder(&$MdbFolder) {
        $this->MdbFolder = &$MdbFolder;
    }
    function setDtoFolder(&$DtoFolder) {
        $this->DtoFolder = &$DtoFolder;
    }
    function setMdbTag(&$MdbTag ) {
        $this->MdbTag = &$MdbTag;
    }
    function setDtoTag(&$DtoTag ) {
        $this->DtoTag = &$DtoTag;
    }
    function setMdbAddr(&$MdbAddr ) {
        $this->MdbAddr = &$MdbAddr;
    }
    function setDtoAddr(&$DtoAddr ) {
        $this->DtoAddr = &$DtoAddr;
    }
    function setMdbAddrGroup(&$MdbAddrGroup ) {
        $this->MdbAddrGroup = &$MdbAddrGroup;
    }
    function setDtoAddrGroup(&$DtoAddrGroup ) {
        $this->DtoAddrGroup = &$DtoAddrGroup;
    }
    function setMdbAddrGroupUsers(&$MdbAddrGroupUsers ) {
        $this->MdbAddrGroupUsers = &$MdbAddrGroupUsers;
    }
    function setMdbSign(&$MdbSign ) {
        $this->MdbSign = &$MdbSign;
    }
    function setDtoSign(&$DtoSign ) {
        $this->DtoSign = &$DtoSign;
    }
    function setMdbTemplate(&$MdbTemplate ) {
        $this->MdbTemplate = &$MdbTemplate;
    }
    function setDtoTemplate(&$DtoTemplate ) {
        $this->DtoTemplate = &$DtoTemplate;
    }
    function setMdbResendMail(&$MdbResendMail ) {
        $this->MdbResendMail = &$MdbResendMail;
    }
    function setDtoResendMail(&$DtoResendMail ) {
        $this->DtoResendMail = &$DtoResendMail;
    }
    function setCapacityLogic($CapacityLogic) {
        $this->CapacityLogic = $CapacityLogic;
    }
    function setMdbDomain(&$MdbDomain ) {
        $this->MdbDomain = &$MdbDomain;
    }
    function setDtoDomain(&$DtoDomain ) {
        $this->DtoDomain = &$DtoDomain;
    }
    function setMdbAdminuser(&$MdbAdminuser ) {
        $this->MdbAdminuser = &$MdbAdminuser;
    }
    function setDtoAdminuser(&$DtoAdminuser ) {
        $this->DtoAdminuser = &$DtoAdminuser;
    }
    function setMdbReportmail(&$MdbReportmail ) {
        $this->MdbReportmail = &$MdbReportmail;
    }
    function setCommonList($CommonList) {
        $this->CommonList =& $CommonList;
    }
    function setMdbSuperiorSend($MdbSuperiorSend) {
        $this->MdbSuperiorSend = $MdbSuperiorSend;
    }
    function setMdbSuperiorMulti($MdbSuperiorMulti) {
        $this->MdbSuperiorMulti = $MdbSuperiorMulti;
    }
    function setMdbDelivery($MdbDelivery) {
        $this->MdbDelivery = $MdbDelivery;
    }

    //************************************************************
    // getter
    //************************************************************
    // エラーメッセージ
    function getErr_msg() {
        return $this->UserDto->getErr_msg();
    }
    // 何件追加したか
    function getIncount() {
        return $this->UserDto->getIncount();
    }
    function getAddcount() {
        return $this->UserDto->getAddcount();
    }
    function getDelcount() {
        return $this->UserDto->getDelcount();
    }
    function getInformCount() {
        return $this->inform_count;
    }
    function getRejected_flg() {
        return $this->rejected_flg;
    }
}
?>

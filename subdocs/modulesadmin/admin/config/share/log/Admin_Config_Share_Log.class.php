<?php
require_once( MODULE_DIR . "/common/common.php");

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * [機能説明] WebFile 共有ログ
 *
 * @package     WebFile
 * @author      Chihiro Yano
 * @updater
 * @copyright   KEY POINT
 * @access      public
 */

class Admin_Config_Share_Log {
    var $session;
    var $DtoGroups;
    var $MdbGroups;
    var $DtoFolder;
    var $MdbFolder;
    var $DtoLog;
    var $MdbLog;
    var $GroupLogic;
    var $MdbUpload;
    var $DtoUpload;
    var $MdbCollect;
    var $DtoCollect;
    var $MdbMchoice;
    var $MdbDownload;
    var $MdbDownloadMailer;
    var $MdbDeliveryto;
    var $MdbDeliveryfile;

    var $DtoAdminUser;
    var $AdminUserLogic;

    var $list_back_flg;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Share_Logが実行されました","Admin_Config_Share_Log#execute");

        //エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        // パラメータチェック
        if (!is_numeric($this->DtoGroups->getId())) {
            $errorList->add("Admin_Config_Share_Log::error", "不正なパラメーターを検知しました。再度、一覧から編集するグループを選択してください。");
            return "error";
        }

        // グループ情報の取得
        $this->MdbGroups->getInfo($this->DtoGroups);
        if (!$this->DtoGroups->id) {
            // 存在しないグループの選択
            $errorList->add("Admin_Config_Share_Log::error", "グループ情報が取得できませんでした。再度、一覧から編集するグループを選択してください。");
            return "error";
        }

        // フォルダ情報取得
        $this->DtoFolder->setFolder_id($this->DtoGroups->getFolder_id());
        $this->MdbFolder->setFolderInfo($this->DtoFolder);

        // 管理者チェック
        if (!$this->AdminUserLogic->checkAdminGroup($this->DtoAdminUser, $this->DtoGroups)) {
            $errorList->add("Admin_Config_Share_Edit::error", "権限がありません。再度、一覧からグループを選択してください。");
            return "error";
        }

        // ログ取得
        $this->MdbLog->getShareLogList($this->DtoLog,$this->DtoGroups);

        // FlexyGrid 初期化
        if (!$this->list_back_flg) {
            $this->session->removeParameter('query');
            $this->session->removeParameter('qtype');
            $this->session->removeParameter('page');
            $this->session->removeParameter('sortname');
            $this->session->removeParameter('sortorder');
            $this->session->removeParameter('rp');
        }

        $this->DtoLog->setShare_id($this->DtoGroups->getId());
        $this->MdbLog->getGroupLogList($this->DtoLog, NULL);

        if ($this->DtoLog->getLogList()) {
            $this->GroupLogic->showlist($this->DtoLog,
                                        $this->MdbUpload,$this->DtoUpload,
                                        $this->MdbCollect,$this->DtoCollect,
                                        $this->MdbMchoice,$this->MdbDownload,
                                        $this->MdbFolder,$this->DtoFolder,
                                        $this->MdbDownloadMailer,
                                        $this->MdbDeliveryto,$this->MdbDeliveryfile);
        }

        return "success";
    }

    // setter
    function setSession($session)
    {
        $this->session = $session;
    }
    function setDtoGroups($DtoGroups)
    {
        $this->DtoGroups = $DtoGroups;
    }
    function setMdbGroups($MdbGroups)
    {
        $this->MdbGroups = $MdbGroups;
    }
    function setDtoFolder($DtoFolder)
    {
        $this->DtoFolder = $DtoFolder;
    }
    function setMdbFolder($MdbFolder)
    {
        $this->MdbFolder = $MdbFolder;
    }
    function setDtoAdminUser($DtoAdminUser)
    {
        $this->DtoAdminUser = $DtoAdminUser;
    }
    function setAdminUserLogic($AdminUserLogic)
    {
        $this->AdminUserLogic = $AdminUserLogic;
    }
    function setDtoLog($DtoLog)
    {
        $this->DtoLog = $DtoLog;
    }
    function setMdbLog($MdbLog)
    {
        $this->MdbLog = $MdbLog;
    }
    function setList_back_flg($list_back_flg)
    {
        $this->list_back_flg = $list_back_flg;
    }
    function setGroupLogic($GroupLogic) {
        $this->GroupLogic = $GroupLogic;
    }
    function setMdbUpload($MdbUpload) {
        $this->MdbUpload = $MdbUpload;
    }
    function setDtoUpload($DtoUpload) {
        $this->DtoUpload = $DtoUpload;
    }
    function setMdbCollect($MdbCollect) {
        $this->MdbCollect = $MdbCollect;
    }
    function setDtoCollect($DtoCollect) {
        $this->DtoCollect = $DtoCollect;
    }
    function setMdbMchoice($MdbMchoice) {
        $this->MdbMchoice = $MdbMchoice;
    }
    function setMdbDownload($MdbDownload) {
        $this->MdbDownload = $MdbDownload;
    }
    function setMdbDownloadMailer($MdbDownloadMailer) {
        $this->MdbDownloadMailer = $MdbDownloadMailer;
    }
    function setMdbDeliveryto(&$MdbDeliveryto) {
        $this->MdbDeliveryto =& $MdbDeliveryto;
    }
    function setMdbDeliveryfile(&$MdbDeliveryfile) {
        $this->MdbDeliveryfile =& $MdbDeliveryfile;
    }

    // getter
    function getGroupId()
    {
        return $this->DtoGroups->getId();
    }
    function getGroupName()
    {
        return $this->DtoGroups->getName();
    }
    function getFolderName()
    {
        return $this->DtoFolder->getFolder_name();
    }
    function getLogList() {
        return $this->DtoLog->getLogList();
    }
    function getLogSpan() {
        $log_span = $this->MdbLog->getLogSpan2(NULL,NULL,$this->DtoGroups->getId());
        $ret = array();
        foreach($log_span as $val){
            foreach($val as $key2 => $val2){
                $ret[$key2] = $val2;
            }
        }
        return $ret;
    }

}
?>

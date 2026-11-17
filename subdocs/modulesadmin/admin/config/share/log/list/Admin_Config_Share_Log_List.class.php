<?php
require_once( MODULE_DIR . "/common/common.php");

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * [機能説明] WebFile 共有ログ一覧取得
 *
 * @package     WebFile
 * @author      Chihiro Yano
 * @updater
 * @copyright   KEY POINT
 * @access      public
 */

class Admin_Config_Share_Log_List {
    var $session;
    var $DtoGroups;
    var $MdbGroups;
    var $GroupLogic;
    var $DtoLog;
    var $MdbLog;
    var $DtoUpload;
    var $MdbUpload;
    var $DtoCollect;
    var $MdbCollect;
    var $MdbDownload;
    var $MdbDownloadMailer;
    var $DtoFolder;
    var $MdbFolder;
    var $MdbDeliveryto;
    var $MdbDeliveryfile;
    var $MdbMchoice;

    var $DtoFlexi;
    var $DtoAdminUser;
    var $AdminUserLogic;

    var $list_back_flg;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Share_Log_Listが実行されました","Admin_Config_Share_Log_List#execute");

        //エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        // パラメータチェック
        if (!is_numeric($this->DtoLog->getShare_id())) {
            return "error";
        }

        // グループ情報の取得
        $this->DtoGroups->setId($this->DtoLog->getShare_id());
        $this->MdbGroups->getInfo($this->DtoGroups);
        if (!$this->DtoGroups->id) {
            // 存在しないグループの選択
            return "error";
        }

        // フォルダ情報取得
        $this->DtoFolder->setFolder_id($this->DtoGroups->getFolder_id());
        $this->MdbFolder->setFolderInfo($this->DtoFolder);

        // 管理者チェック
        if (!$this->AdminUserLogic->checkAdminGroup($this->DtoAdminUser, $this->DtoGroups)) {
            return "error";
        }

        // セッションセット
        $this->session->setParameter("page", $this->DtoFlexi->getPage());
        $this->session->setParameter("query", $this->DtoFlexi->getQuery());
        $this->session->setParameter("qtype", $this->DtoFlexi->getQtype());
        $this->session->setParameter("sortname", $this->DtoFlexi->getSortname());
        $this->session->setParameter("sortorder", $this->DtoFlexi->getSortorder());
        $this->session->setParameter("rp", $this->DtoFlexi->getRp());


        $this->MdbLog->setFlexiShareLogList($this->DtoLog, $this->DtoFlexi);

        echo $this->GroupLogic->getFlexiShareLogList(
                                                     $this->DtoLog, $this->DtoFlexi,
                                                     $this->DtoUpload, $this->MdbUpload,
                                                     $this->DtoCollect, $this->MdbCollect,
                                                     $this->MdbDownload, $this->MdbDownloadMailer,
                                                     $this->DtoFolder, $this->MdbFolder,
                                                     $this->MdbDeliveryto, $this->MdbDeliveryfile,
                                                     $this->MdbMchoice
                                                     );

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
    function setGroupLogic($GroupLogic)
    {
        $this->GroupLogic = $GroupLogic;
    }
    function setDtoLog($DtoLog)
    {
        $this->DtoLog = $DtoLog;
    }
    function setMdbLog($MdbLog)
    {
        $this->MdbLog = $MdbLog;
    }
    function setDtoUpload($DtoUpload)
    {
        $this->DtoUpload = $DtoUpload;
    }
    function setMdbUpload($MdbUpload)
    {
        $this->MdbUpload = $MdbUpload;
    }
    function setDtoCollect($DtoCollect)
    {
        $this->DtoCollect = $DtoCollect;
    }
    function setMdbCollect($MdbCollect)
    {
        $this->MdbCollect = $MdbCollect;
    }
    function setMdbDownload($MdbDownload)
    {
        $this->MdbDownload = $MdbDownload;
    }
    function setMdbDownloadMailer($MdbDownloadMailer)
    {
        $this->MdbDownloadMailer = $MdbDownloadMailer;
    }
    function setDtoFolder($DtoFolder)
    {
        $this->DtoFolder = $DtoFolder;
    }
    function setMdbFolder($MdbFolder)
    {
        $this->MdbFolder = $MdbFolder;
    }

    function setDtoFlexi($DtoFlexi) {
        $this->DtoFlexi = $DtoFlexi;
    }
    function setDtoAdminUser($DtoAdminUser)
    {
        $this->DtoAdminUser = $DtoAdminUser;
    }
    function setAdminUserLogic($AdminUserLogic)
    {
        $this->AdminUserLogic = $AdminUserLogic;
    }
    function setList_back_flg($list_back_flg)
    {
        $this->list_back_flg = $list_back_flg;
    }
    function setMdbDeliveryto(&$MdbDeliveryto) {
        $this->MdbDeliveryto =& $MdbDeliveryto;
    }
    function setMdbDeliveryfile(&$MdbDeliveryfile) {
        $this->MdbDeliveryfile =& $MdbDeliveryfile;
    }
    function setMdbMchoice(&$MdbMchoice)
    {
        $this->MdbMchoice = &$MdbMchoice;
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

}
?>

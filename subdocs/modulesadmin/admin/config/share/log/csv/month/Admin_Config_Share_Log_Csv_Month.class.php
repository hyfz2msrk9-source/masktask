<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Share_Log_Csv_Month {
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
    var $MdbMchoice;
    var $DtoAdminUser;
    var $AdminUserLogic;

    var $month;
    var $MdbDeliveryto;
    var $MdbDeliveryfile;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_User_Log_Csv_Month","Admin_User_Log_Csv_Month#execute");

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

        // 管理者チェック
        if (!$this->AdminUserLogic->checkAdminGroup($this->DtoAdminUser, $this->DtoGroups)) {
            $errorList->add("Admin_Config_Share_Edit::error", "権限がありません。再度、一覧からグループを選択してください。");
            return "error";
        }

        $this->DtoLog->setShare_id($this->DtoGroups->getId());
        $this->MdbLog->getGroupLogList($this->DtoLog, substr($this->month,0,4)."-".substr($this->month,4,2));
        if ($this->DtoLog->getLogList()) {
            $this->GroupLogic->showlist($this->DtoLog,
                                        $this->MdbUpload,$this->DtoUpload,
                                        $this->MdbCollect,$this->DtoCollect,
                                        $this->MdbMchoice,$this->MdbDownload,
                                        $this->MdbFolder,$this->DtoFolder,
                                        $this->MdbDownloadMailer,
                                        $this->MdbDeliveryto, $this->MdbDeliveryfile
                                    );
        }

        //var_dump($this->DtoLog);var_dump($this->DtoGroups);exit;
        $this->GroupLogic->log_output_group($this->DtoLog,$this->DtoGroups, $this->month);

        // Log
        $history_id = 195; // 195:グループ共有に関する設定>共有グループの操作履歴＞エクスポート(月指定)
        $memo = "対象グループ：". $this->DtoGroups->getName() . "\n";
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        exit;
    }
    /************************************
    * setter
    *************************************/
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
    function setMdbMchoice($MdbMchoice)
    {
        $this->MdbMchoice = $MdbMchoice;
    }
    function setDtoAdminUser($DtoAdminUser)
    {
        $this->DtoAdminUser = $DtoAdminUser;
    }
    function setAdminUserLogic($AdminUserLogic)
    {
        $this->AdminUserLogic = $AdminUserLogic;
    }
    function setMonth($month){
        $this->month = $month;
    }
    function setMdbDeliveryto(&$MdbDeliveryto) {
        $this->MdbDeliveryto =& $MdbDeliveryto;
    }
    function setMdbDeliveryfile(&$MdbDeliveryfile) {
        $this->MdbDeliveryfile =& $MdbDeliveryfile;
    }
}
?>

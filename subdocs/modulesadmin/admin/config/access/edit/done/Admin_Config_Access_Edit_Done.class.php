<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Access_Edit_Done {
    var $session;
    var $DtoAccess;
    var $MdbAccess;
    var $AccessDto;
    var $AccessLogic;
    var $DtoConfigs;
    var $MdbConfigs;
    var $DtoLog;
    var $MdbLog;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Access_Edit_Doneが実行されました","Admin_Config_Access_Edit_Done#execute");

        // エラー発生
        $container =& DIContainerFactory::getContainer();
        $actionChain =& $container->getComponent("ActionChain");
        $errorList =& $actionChain->getCurErrorList();
        $proxy = new Proxy();

        // API有効チェック
        if(API_FLG == 0 && $this->DtoAccess->getArea() == 4) {
            $this->session->removeParameter("AccessEditToken");
            return "error";
        }

        $IpAddress = $this->DtoAccess->getType() ? $this->DtoAccess->getInternet_ip_addr() : $this->DtoAccess->getIp_addr();

        if ($IpAddress == '') {
            $errorList->add("Admin_Config_Access_Edit::error", "IPアドレスが入力されていません。");
            return "input";
        }

        if (strlen($IpAddress) > 18) {
            $errorList->add("Admin_Config_Access_Edit::error", "IPアドレスは18文字以内で入力してください。");
            return "input";
        }

        //if(!$proxy->is_ipaddr($this->DtoAccess->getIp_addr())) {
        if(!$proxy->check_ipaddr($IpAddress)) {
            $errorList->add("Admin_Config_Access_Edit::error", "IPアドレスを正しく入力してください。");
            return "input";
        }

        //存在チェック
        if($this->AccessLogic->checkIpAddr($this->DtoAccess, $this->MdbAccess)) {
            $errorList->add("Admin_Config_Access_Edit::error", "既にこのIPアドレスは登録されています。");
            return "input";
        }

        //登録
        $this->AccessLogic->addAccess($this->DtoAccess, $this->MdbAccess);

        // Log
        $history_id = 117;
        $memo  = "";
        switch ($this->DtoAccess->getArea()) {
            case 1:
                $areaLabel = '管理者画面アクセス制限';
                break;
            case 2:
                $areaLabel = 'ユーザ画面アクセス制限';
                break;
            case 3:
                $areaLabel = '引取・ダウンロード画面アクセス制限';
                break;
            case 4:
                $areaLabel = 'APIアクセス制限';
                break;
        }
        if($IpAddress) {
            $memo .= "対象：" . $areaLabel . "\n";
            if (USE_ENV_SEPARATION_FLG) {
                if ($this->DtoAccess->getType() == 1) {
                    $envType = "インターネット";
                } else {
                    $envType = "イントラネット";
                }
                $memo .= "環境：". $envType . "\n";
            }
            $memo .= "IPアドレス：". $IpAddress . "\n";
        }

        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        return "success";
    }

    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoAccess($DtoAccess){
        $this->DtoAccess = $DtoAccess;
    }
    function setMdbAccess($MdbAccess) {
        $this->MdbAccess = $MdbAccess;
    }
    function setAccessDto($AccessDto) {
        $this->AccessDto = $AccessDto;
    }
    function setAccessLogic($AccessLogic) {
        $this->AccessLogic = $AccessLogic;
    }
    function setDtoConfigs($DtoConfigs){
        $this->DtoConfigs = $DtoConfigs;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setDtoLog ( &$DtoLog ) {
        $this->DtoLog =& $DtoLog;
    }
    function setMdbLog ( &$MdbLog ) {
        $this->MdbLog =& $MdbLog;
    }

    // getter
    function getConfig() {
        return $this->DtoConfigs->configslist;
    }
    function getArea()
    {
        return $this->DtoAccess->getArea();
    }
    // IPアドレス一覧取得
    function getAccessList()
    {
        return $this->AccessLogic->getAccessKeyList($this->MdbAccess, $this->DtoAccess->getArea());
    }
    function getInternetAccessList()
    {
        return $this->AccessLogic->getAccessKeyList($this->MdbAccess, $this->DtoAccess->getArea(), 1);
    }
    function getIp_addr() {
        return $this->DtoAccess->getIp_addr();
    }
    function getInternet_ip_addr() {
        return $this->DtoAccess->getInternet_ip_addr();
    }

}
?>

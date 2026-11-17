<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Access_Edit_Delete {
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
        $log->debug("Admin_Config_Access_Edit_Deleteが実行されました","Admin_Config_Access_Edit_Delete#execute");

        if(API_FLG == 0 && $this->DtoAccess->getArea() == 4) {
            $this->session->removeParameter('AccessEditToken');
            return "error";
        }

        // Log
        $history_id = 119; // 119:各種設定＞アクセス制限に関する設定＞IPアドレス削除
        if ($this->DtoAccess->getType() == 1) {
            $list = $this->getInternetAccessList();
        } else {
            $list = $this->getAccessList();
        }
        $IpAddress = "";
        if($this->DtoAccess->getAccess_id() && is_array($this->DtoAccess->getAccess_id())) {
            foreach($this->DtoAccess->getAccess_id() as $val){
                if(array_key_exists($val, $list)){
                    $IpAddress .= $list[$val] . "\n";
                }
            }
        }

        //削除
        $this->AccessLogic->deleteAccess($this->DtoAccess, $this->MdbAccess);

        // メモがあれば残す
        if($IpAddress){
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
            $memo = "対象：" . $areaLabel . "\n";
            if (USE_ENV_SEPARATION_FLG) {
                if ($this->DtoAccess->getType() == 1) {
                    $envType = "インターネット";
                } else {
                    $envType = "イントラネット";
                }
                $memo .= "環境：". $envType . "\n";
            }
            $memo .= "IPアドレス：".$IpAddress;
            $this->DtoLog->setUser_id("");
            $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
            $this->DtoLog->setFile_id("");
            $this->DtoLog->setHistory_id($history_id);
            $this->DtoLog->setMemo($memo);
            $this->MdbLog->Logging($this->DtoLog);
        }

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
    function getInternet_ip_addr()
    {
        return $this->DtoAccess->getInternet_ip_addr();
    }
}
?>

<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Access_Done {
    var $session;
    var $DtoConfigs;
    var $MdbConfigs;
    var $DtoLog;
    var $MdbLog;

    var $access_admin_flg;
    var $access_user_flg;
    var $access_other_flg;
    var $access_api_flg;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Access_Doneが実行されました","Admin_Config_Access_Done#execute");

        if( ($this->access_admin_flg !="0" && $this->access_admin_flg !="1") ||
            ($this->access_user_flg !="0" && $this->access_user_flg !="1") ||
            ($this->access_other_flg !="0" && $this->access_other_flg !="1")) {
            return "error";
        }

        //管理者制限
        $this->MdbConfigs->setValue('access_admin_flg', $this->access_admin_flg);
        //ユーザ制限
        $this->MdbConfigs->setValue('access_user_flg', $this->access_user_flg);
        //引取・ダウンロード制限
        $this->MdbConfigs->setValue('access_other_flg', $this->access_other_flg);
        //引取・ダウンロード制限
        if ( API_FLG == 1){
            $this->MdbConfigs->setValue('access_api_flg', $this->access_api_flg);
        }
        
        // Log
        $history_id = 112; // 112:各種設定＞機能設定
        $memo  = "";

        if ( $this->access_admin_flg != $this->session->getParameter("access_admin_flg")){
            if ($this->access_admin_flg == "1") {
                $flg = "登録IPのみ";
            } else {
                $flg = "全てのIP";
            }
            $memo .= "管理者画面アクセス制限：".$flg."\n";
        }
        if ( $this->access_user_flg != $this->session->getParameter("access_user_flg")){
            if ($this->access_user_flg == "1") {
                $flg = "登録IPのみ";
            } else {
                $flg = "全てのIP";
            }
            $memo .= "ユーザ画面アクセス制限：".$flg."\n";
        }
        if ( $this->access_other_flg != $this->session->getParameter("access_other_flg")){
            if ($this->access_other_flg == "1") {
                $flg = "登録IPのみ";
            } else {
                $flg = "全てのIP";
            }
            $memo .= "引取・ダウンロード画面アクセス制限：".$flg."\n";
        }
        if ( API_FLG == 1) {
            if ( $this->access_api_flg != $this->session->getParameter("access_api_flg")){
                if ($this->access_api_flg == "1") {
                    $flg = "登録IPのみ";
                } else {
                    $flg = "全てのIP";
                }
                $memo .= API_CLIENT_NAME."アクセス制限：".$flg."\n";
            }
        }

        // メモがあればログに残す
        if($memo){
            $memo = "[セキュリティ＞アクセス制限に関する設定]\n".$memo;
            $this->DtoLog->setUser_id("");
            $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
            $this->DtoLog->setFile_id("");
            $this->DtoLog->setHistory_id($history_id);
            $this->DtoLog->setMemo($memo);
            $this->MdbLog->Logging($this->DtoLog);
        }

        //sessionを削除
        $this->session->removeParameter("access_admin_flg");
        $this->session->removeParameter("access_user_flg");
        $this->session->removeParameter("access_other_flg");
        $this->session->removeParameter("access_api_flg");

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success'
        ]);
        exit;
    }

    // setter
    function setSession($session) {
        $this->session = $session;
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
    function setAccess_admin_flg(&$access_admin_flg)
    {
        $this->access_admin_flg =& $access_admin_flg;
    }
    function setAccess_user_flg(&$access_user_flg)
    {
        $this->access_user_flg =& $access_user_flg;
    }
    function setAccess_other_flg(&$access_other_flg)
    {
        $this->access_other_flg =& $access_other_flg;
    }
    function setAccess_api_flg(&$access_api_flg)
    {
        $this->access_api_flg =& $access_api_flg;
    }

    // getter
    function getConfig() {
        $this->MdbConfigs->getList($this->DtoConfigs);
        return $this->DtoConfigs->configslist;
    }
    function getAccess_Flg_Options()
    {
        return GlobalArray::getAccess_Flg_Options();
    }
}
?>
<?php
/**
 * [機能説明] 各種設定 - 操作設定 - 送信者(差出人)に関する設定
 * @updater     Hiroko Yanai（2018/09/28）passmust_span -> user_passmust_span
 *
 * @package     WebFile
 * @version     3.16.0
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Sender_Done {
    var $session;
    var $DtoConfigs;
    var $MdbConfigs;
    var $DtoLog;
    var $MdbLog;
    var $sender_setting_flg;
    var $token;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Sender_Doneが実行されました","Admin_Config_Sender_Done#execute");

        $sender_error_response = function ($message = "不正な値が入力されました。") {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => $message,
                'token_name' => 'SenderEditToken',
                'token_value' => $this->token->getValue('SenderEditToken')
            ]);
            exit;
        };
        // 引数チェック
        if( $this->sender_setting_flg!="0" && $this->sender_setting_flg!="1") {
            $sender_error_response("不正な値が入力されました。");
        }

        //引取り先フォルダ指定機能
        $this->MdbConfigs->setValue('sender_setting_flg',$this->sender_setting_flg);

        // Log
        $history_id = 112; // 112:各種設定＞機能設定
        $memo  = "";
        if ( $this->sender_setting_flg != $this->session->getParameter("sender_setting_flg")){
            $flg = "";
            switch ($this->sender_setting_flg) {
            case 1:
                $flg .= "有効にする";
                break;
            default:
                $flg .= "無効にする";
                break;
            }
            $memo .= "送信者（差出人）設定機能：".$flg."\n";
        }

        if ($memo) {
            $memo = "[送信機能＞送信者（差出人）に関する設定]\n".$memo;
            $this->DtoLog->setUser_id("");
            $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
            $this->DtoLog->setFile_id("");
            $this->DtoLog->setHistory_id($history_id);
            $this->DtoLog->setMemo($memo);
            $this->MdbLog->Logging($this->DtoLog);
        }

        //sessionを削除
        $this->session->removeParameter("sender_setting_flg");

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
    function setSender_setting_flg(&$sender_setting_flg)
    {
        $this->sender_setting_flg =& $sender_setting_flg;
    }
    function setToken($token) {
        $this->token = $token;
    }
}
?>

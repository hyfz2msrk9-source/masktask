<?php
/**
 * [機能説明] 各種設定 - セキュリティ設定 - ウイルスチェックに関する設定(完了)
 * @updater     Hiroko Yanai（2018/09/28）passmust_span -> user_passmust_span
 *
 * @package     WebFile
 * @version     3.16.0
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Virus_Done {
    var $session;
    var $DtoConfigs;
    var $MdbConfigs;
    var $sends_flg;
    var $file_move_flg;
    var $mailer_flg;
    var $DtoLog;
    var $MdbLog;
    var $MdbMchoice;
    var $DtoGroups;
    var $MdbGroups;
    var $token;

    var $virus_pass_protect_flg;
    var $virus_timeout_flg;
    var $virus_riskware_flg;
    var $virus_malice_flg;
    var $virus_cant_upload_when_scanner_error_flg;

    function execute() {
        $log =& LogFactory::getLog(); $log->debug("Admin_Config_Virus_Doneが実行されました","Admin_Config_Virus_Done#execute");

        $virus_error_response = function ($message) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => $message,
                'token_name' => 'VirusEditToken',
                'token_value' => $this->token->getValue('VirusEditToken')
            ]);
            exit;
        };

        //入力チェック
        $this->virus_pass_protect_flg = (FTF_FLG == 1 || FILE_SANITIZE_FLG == 1) ? 1 : $this->virus_pass_protect_flg;
        if(preg_match("/[^01]/",$this->virus_pass_protect_flg) ||
            preg_match("/[^01]/",$this->virus_timeout_flg) ||
            preg_match("/[^01]/",$this->virus_riskware_flg) ||
            preg_match("/[^01]/",$this->virus_malice_flg) ||
            preg_match("/[^01]/",$this->virus_cant_upload_when_scanner_error_flg)) $virus_error_response("不正な値が入力されました");

        //パスワード保護ファイルのアップロード
        $this->MdbConfigs->setValue('virus_pass_protect_flg',$this->virus_pass_protect_flg);

        //タイムアウト
        $this->MdbConfigs->setValue('virus_timeout_flg',$this->virus_timeout_flg);

        //リスクウェア
        $this->MdbConfigs->setValue('virus_riskware_flg',$this->virus_riskware_flg);

        //悪意があるファイル
        $this->MdbConfigs->setValue('virus_malice_flg',$this->virus_malice_flg);

        //ウイルススキャンに失敗したファイル
        $this->MdbConfigs->setValue('virus_cant_upload_when_scanner_error_flg',$this->virus_cant_upload_when_scanner_error_flg);

        // Log
        $history_id = 112; // 112:各種設定＞機能設定
        $memo  = "";
        if (FTF_FLG != 1 && FILE_SANITIZE_FLG != 1 && $this->virus_pass_protect_flg != $this->session->getParameter("virus_pass_protect_flg")){
            if ($this->virus_pass_protect_flg == 1) {
                $flg = "アップロードを拒否する";
            } else {
                $flg = "アップロードを許可する";
            }
            $memo .= "パスワード保護ファイルのアップロード：".$flg."\n";
        }
        if ( $this->virus_timeout_flg != $this->session->getParameter("virus_timeout_flg")){
            if ($this->virus_timeout_flg == "1") {
                $flg = "アップロードを拒否する";
            } else {
                $flg = "アップロードを許可する";
            }
            $memo .= "タイムアウトした場合のアップロード：".$flg."\n";
        }
        if ( $this->virus_riskware_flg != $this->session->getParameter("virus_riskware_flg")){
            if ($this->virus_riskware_flg == "1") {
                $flg = "アップロードを拒否する";
            } else {
                $flg = "アップロードを許可する";
            }
            $memo .= "リスクウェア判定されたファイルのアップロード：".$flg."\n";
        }
        if ( $this->virus_malice_flg != $this->session->getParameter("virus_malice_flg")){
            if ($this->virus_malice_flg == "1") {
                $flg = "アップロードを拒否する";
            } else {
                $flg = "アップロードを許可する";
            }
            $memo .= "悪意があるファイルのアップロード：".$flg."\n";
        }
        if ( $this->virus_cant_upload_when_scanner_error_flg != $this->session->getParameter("virus_cant_upload_when_scanner_error_flg")){
            if ($this->virus_cant_upload_when_scanner_error_flg == "1") {
                $flg = "アップロードを拒否する";
            } else {
                $flg = "アップロードを許可する";
            }
      $memo .= "ウイルススキャンに失敗したファイルのアップロード：" . $flg . "\n";
        }

        if($memo) {
      $memo = "[セキュリティ＞ウイルスチェックに関する設定]\n" . $memo;
            $this->DtoLog->setUser_id("");
            $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
            $this->DtoLog->setFile_id("");
            $this->DtoLog->setHistory_id($history_id);
            $this->DtoLog->setMemo($memo);
            $this->MdbLog->Logging($this->DtoLog);
        }

        //sessionを削除
        $this->session->removeParameter("virus_pass_protect_flg");
        $this->session->removeParameter("virus_timeout_flg");
        $this->session->removeParameter("virus_riskware_flg");
        $this->session->removeParameter("virus_malice_flg");
        $this->session->removeParameter("virus_cant_upload_when_scanner_error_flg");
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
    function setMdbMchoice($MdbMchoice) {
        $this->MdbMchoice =& $MdbMchoice;
    }
    function setDtoGroups($DtoGroups){
        $this->DtoGroups = $DtoGroups;
    }
    function setMdbGroups($MdbGroups) {
        $this->MdbGroups = $MdbGroups;
    }
    function setVirus_pass_protect_flg(&$virus_pass_protect_flg)
    {
        $this->virus_pass_protect_flg =& $virus_pass_protect_flg;
    }
    function setVirus_timeout_flg($virus_timeout_flg)
    {
        $this->virus_timeout_flg =& $virus_timeout_flg;
    }
    function setVirus_riskware_flg($virus_riskware_flg)
    {
        $this->virus_riskware_flg =& $virus_riskware_flg;
    }
    function setVirus_malice_flg($virus_malice_flg)
    {
        $this->virus_malice_flg =& $virus_malice_flg;
    }
    function setVirus_cant_upload_when_scanner_error_flg($virus_cant_upload_when_scanner_error_flg)
    {
        $this->virus_cant_upload_when_scanner_error_flg =& $virus_cant_upload_when_scanner_error_flg;
    }
    function setTokne($token) {
        $this->token = $token;
    }

    // getter
    function getConfig() {
        $this->MdbConfigs->getList($this->DtoConfigs);
        return $this->DtoConfigs->configslist;
    }
    function getVirus_check_size_limit() {
        return StrChange::CapacityUnit(VIRUS_CHECK_SIZE_LIMIT*1024*1024);
    }
}
?>

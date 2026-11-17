<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Archive_Export {
    var $session;
    var $DtoFlexi;
    var $MdbConfigs;
    var $DtoArchive;
    var $MdbArchive;
    var $DtoArchiveExport;
    var $MdbArchiveExport;
    var $ArchiveLogic;
    var $AdminArchiveLogic;

    var $DtoLog;
    var $MdbLog;
    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Archive_Exportが実行されました","Admin_Archive_Export#execute");

        // エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        // アーカイブシステムがOFF
        if (!EVI_ARCHIVE_FLG) {
            exit("ERROR,不正な処理が実行されました。");
        }
        // エクスポートディレクトリが存在しない
        if (!is_dir(EVI_ARCHIVE_EXPORT_PATH)) {
            exit("ERROR,エクスポートディレクトリが存在しませんでした。");
        }
        // エクスポートの保存領域の容量が0、設定されていない
        if(!defined("EVI_ARCHIVE_EXPORT_MAX_SIZE") || EVI_ARCHIVE_EXPORT_MAX_SIZE === 0) { 
            exit("ERROR,エクスポートの保存領域がないため、エクスポートできません。");
        }
        // プロセス監視
        $filename = EVI_PID_PATH."/archive_export.pid";
        $lockname = $filename."_lock";
        if (file_exists($filename) || file_exists($lockname)) {
            exit("ERROR,別のエクスポートが実行されています。\nしばらく時間を置いてから、再度お試しください。");
        }
        // エクスポートファイルの分割1ファイルあたりのサイズ
        if( !defined("EVI_FILE_SIZE_MB") ){
            exit("ERROR,Eviエクスポートファイルの分割ファイルサイズが設定されていません。");
        }

        // 日付入力チェック
        $attributes = array(
                            "startYear,startMonth,startDay.required" => "1,開始日付を選択してください。",
                            "endYear,endMonth,endDay.required" => "1,終了日付を選択してください。",
                            "startYear,startMonth,startDay.date" => "1,開始日付を正しく選択してください。",
                            "endYear,endMonth,endDay.date" => "1,終了日付を正しく選択してください。",
                            "search_account.maxlength" => "1,アカウントは100文字以内で入力してください。,100",
                            "search_user_name.maxlength" => "1,ユーザ名は100文字以内で入力してください。,100",
                            "search_mailaddr.maxlength" => "1,メールアドレスは100文字以内で入力してください。,100",
                            "search_file_name.maxlength" => "1,ファイル名は100文字以内で入力してください。,100",
                            "search_to_send.maxlength" => "1,送信先は100文字以内で入力してください。,100",
                            "search_to_collect.maxlength" => "1,収集先は100文字以内で入力してください。,100"
                            );
        $validatorManager = new ValidatorManager();
        $validatorManager->execute($attributes);
        if($errorList->getType() == VALIDATE_ERROR_TYPE) {
            echo "ERROR,";
            foreach ($errorList->getMessages() as $val) {
                echo $val . "\n";
            }
            exit;
        }

        // 日付チェック
        $file_name = $this->DtoArchiveExport->getStart() . '-' . $this->DtoArchiveExport->getEnd() . '.zip';
        $start     = date("Y-m-d 00:00:00", strtotime($this->DtoArchiveExport->getStart()));
        $end       = date("Y-m-d 23:59:59", strtotime($this->DtoArchiveExport->getEnd()));
        $last      = date('Y-m-d 23:59:59', strtotime('-1 day'));    // 昨日
        if ($end > $last) {
            exit("ERROR,アーカイブの終了日には、昨日以前を選択してください。");
        }

        // エクスポート存在確認
        $this->DtoArchiveExport->setFile_name($file_name);
        $this->DtoArchiveExport->setFile_size(0);
        $this->DtoArchiveExport->setStart($start);
        $this->DtoArchiveExport->setEnd($end);
        if ($this->MdbArchiveExport->checkExists($this->DtoArchiveExport)) {
            exit("ERROR,入力された条件と同じエクスポートファイルが、既にデータベースに存在しています。");
        }

        // アーカイブ件数が0以下
        $this->MdbArchive->getFlexiList($this->DtoFlexi, $this->DtoArchive, false);
        if ($this->DtoArchive->getRow_count() <= 0) {
            exit("ERROR,エクスポートを実行するアーカイブが存在しません。");
        }

        // DB登録
        $archive_export_id = $this->AdminArchiveLogic->insertArchiveExport($this->DtoArchiveExport, $this->MdbArchiveExport);
        
        @exec(COM_PHP_PATH." ".WF_SHELL_PATH."exec_archive_export.php " . escapeshellarg($archive_export_id) . " " . escapeshellarg($file_name) . " > /dev/null &");

        // Log
        $memo = '';
        $memo.= $this->DtoArchiveExport->getStart() . " 〜 " . $this->DtoArchiveExport->getEnd();
        $history_id = 143; // 143:アーカイブシステム＞エクスポート
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);
        
        exit("SUCCESS,エクスポートを実行しました。\nメールアドレスを指定している場合は、エクスポート完了時にメールで通知します。");
    }
    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoFlexi($DtoFlexi) {
        $this->DtoFlexi = $DtoFlexi;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setDtoArchive($DtoArchive) {
        $this->DtoArchive = $DtoArchive;
    }
    function setMdbArchive($MdbArchive) {
        $this->MdbArchive = $MdbArchive;
    }
    function setDtoArchiveExport($DtoArchiveExport) {
        $this->DtoArchiveExport = $DtoArchiveExport;
    }
    function setMdbArchiveExport($MdbArchiveExport) {
        $this->MdbArchiveExport = $MdbArchiveExport;
    }
    function setArchiveLogic($ArchiveLogic) {
        $this->ArchiveLogic = $ArchiveLogic;
    }
    function setAdminArchiveLogic($AdminArchiveLogic) {
        $this->AdminArchiveLogic = $AdminArchiveLogic;
    }
    function setDtoLog($DtoLog) {
        $this->DtoLog = $DtoLog;
    }
    function setMdbLog($MdbLog) {
        $this->MdbLog = $MdbLog;
    }
    // getter
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

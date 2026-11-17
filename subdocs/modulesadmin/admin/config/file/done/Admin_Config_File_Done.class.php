<?php
/**
 * [機能説明] 各種設定 - 操作設定 - アップロードに関する設定(完了)
 * @updater     Hiroko Yanai（2018/09/28）passmust_span -> user_passmust_span
 *
 * @package     WebFile
 * @version     3.16.0
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_File_Done {
    var $session;
    var $DtoConfigs;
    var $MdbConfigs;
    var $DtoLog;
    var $MdbLog;
    var $MdbMchoice;
    var $DtoGroups;
    var $MdbGroups;
    var $file_move_flg;
    var $folder_dl_flg;
    var $file_upload_limit;
    var $del_span;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_File_Doneが実行されました","Admin_Config_File_Done#execute");

        // エラー発生
        $container =& DIContainerFactory::getContainer();
        $actionChain =& $container->getComponent("ActionChain");
        $errorList =& $actionChain->getCurErrorList();

        // 引数チェック
        if(($this->file_move_flg != "0" && $this->file_move_flg != "1") ||
            (defined("ZIPDL_TYPE") && ZIPDL_TYPE == 1 && $this->folder_dl_flg != "0" && $this->folder_dl_flg != "1")
            ) {
            return "error";
        }


        $this->MdbConfigs->setValue('file_move_flg', $this->file_move_flg);
        $this->MdbConfigs->setValue('folder_dl_flg', $this->folder_dl_flg);
        //引取り先フォルダ指定機能
        //最大アップロード数
        $this->MdbConfigs->setValue('file_upload_limit',$this->file_upload_limit);

        // 定期削除機能が無効の時
        if (AUTO_DEL_FILE_STOP_FLG == 1) {
            // 削除日数を強制的に0にする
            $this->del_span = 0;
        }

        // 入力値チェック
        if (is_null($this->del_span)) {
            // $this->error_msg = "ファイルの定期削除日数が指定されていません。";
            return "input";
        }
        if (!preg_match("/^[0-9]+$/", $this->del_span)) {
            // $this->error_msg = "ファイルの定期削除日数に、不正な値が入力されています。";
            return "input";
        }
        if ($this->del_span > 99999) {
            // $this->error_msg = "ファイルの定期削除日数は、99999日以下を入力してください。";
            return "input";
        }


        $log->debug("ここきた", "Admin_Config_File_Done#execute");
        // 更新
        $this->MdbConfigs->setValue("del_span", $this->del_span);
        // ユーザ側に設定のキャッシュファイルを作る
        //$this->MdbConfigs->getList($this->DtoConfigs);
        //$configlist =  $this->DtoConfigs->getConfigsList();
        //$this->MdbConfigs->defineSet($configlist);

        // Log
        $history_id = 112; // 112:各種設定＞機能設定
        $memo  = "";

        if ($this->file_move_flg != $this->session->getParameter("file_move_flg")) {
            if ($this->file_move_flg == "1") {
                $flg = "有効";
            } else {
                $flg = "無効";
            }
            $memo .= "ファイル移動：" . $flg . "\n";
        }


        if ( $this->file_upload_limit != $this->session->getParameter("file_upload_limit")){
            $memo .= "最大アップロード数：" . $this->session->getParameter("file_upload_limit") . "日→" . $this->file_upload_limit . "日";
        } else {
            $memo .= "最大アップロード数：".$this->file_upload_limit."\n";
        }
        if ($this->folder_dl_flg != $this->session->getParameter("folder_dl_flg")) {
            if ($this->folder_dl_flg == "1") {
                $flg = "有効";
            } else {
                $flg = "無効";
            }
            $memo .= "ファイル一括ダウンロード：" . $flg . "\n";
        }
        if ($this->del_span != $this->session->getParameter("del_span")) {
            $memo .= "ファイルの定期削除日数：" . $this->session->getParameter("del_span") . "日→" . $this->del_span . "日";
        } else {
            $memo .= "ファイルの定期削除日数：" . $this->del_span . "日";
        }

        if ($memo) {
            $memo = "[操作＞ファイルに関する設定]\n".$memo;
            $this->DtoLog->setUser_id("");
            $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
            $this->DtoLog->setFile_id("");
            $this->DtoLog->setHistory_id($history_id);
            $this->DtoLog->setMemo($memo);
            $this->MdbLog->Logging($this->DtoLog);
        }

        //sessionを削除
        
        $this->session->removeParameter("file_move_flg");
        $this->session->removeParameter("folder_dl_flg");
        $this->session->removeParameter("file_upload_limit");
        $this->session->removeParameter("del_span");
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
    function setDel_span(&$del_span)
    {
        $this->del_span =& $del_span;
    }
    function setFile_upload_limit(&$file_upload_limit) {
        $this->file_upload_limit =& $file_upload_limit;
    }
    function setFolder_dl_flg($folder_dl_flg) {
        $this->folder_dl_flg = $folder_dl_flg;
    }
    function setFile_move_flg($file_move_flg) {
        $this->file_move_flg = $file_move_flg;
    }

    // getter
    function getConfig() {
        $this->MdbConfigs->getList($this->DtoConfigs);
        return $this->DtoConfigs->configslist;
    }
    function getUpload_Return_Flg_Options()
    {
        return GlobalArray::getUpload_Return_Flg_Options();
    }
    function getFileUploadLimit()
    {
        $this->MdbConfigs->getInfo($this->DtoConfigs,"file_upload_limit");
        return $this->DtoConfigs->getValue();
    }
}
?>

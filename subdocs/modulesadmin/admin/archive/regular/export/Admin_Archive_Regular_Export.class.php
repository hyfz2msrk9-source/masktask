<?php
/*
 * [機能説明] WebFile 分割エクスポート定期実行
 *
 * @package     WebFile
 * @copyright   KEYPOINT
 * @access      public
 * @author      ?
 * @create      ?
 * @change log  2013/04/03 Tomoko Uchida : ロックファイルを作らない方法に変更
 *              2014/07/03 Chihiro Yano  : ファイルチェックを次のファイルサイズでチェックするよう改良
 */
require_once( MODULE_DIR . "/common/common.php");

class Admin_Archive_Regular_Export {
    var $session;
    var $DtoArchiveExport;
    var $MdbArchiveExport;
    var $DtoArchive;
    var $MdbArchive;
    var $DtoFlexi;
    var $ArchiveLogic;
    var $MdbConfigs;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Archive_Regular_Exportが実行されました","Admin_Archive_Regular_Export#execute");

        $mode = 0;
        $mode = $this->MdbConfigs->getDirectValue("regular_export_flg");

        // 1ヶ月に1回モード
        if ($mode == "2") {

            // add 2013.04.23@uchida
            // テスト用
            if ( defined("TEST_EVI_REGULAR_EXPORT")  && TEST_EVI_REGULAR_EXPORT == 1 ){
                // 開始日と終了日が未設定なら、さようなら。
                if( !defined("TEST_EVI_REGULAR_EXPORT_START") || !defined("TEST_EVI_REGULAR_EXPORT_END") ){
                    exit();
                }
                // 日付計算
                $end_dt = date('Y-m-d 23:59:59', strtotime(TEST_EVI_REGULAR_EXPORT_END));      // 末日
                $start_dt = date('Y-m-01 00:00:00', strtotime(TEST_EVI_REGULAR_EXPORT_START)); // 1日
            // 本番用
            }else{

                // 今日が1日じゃなければさようなら。
                if (date("d") != "01") {
                    exit();
                }

                // 日付計算
                $end_dt = date('Y-m-d 23:59:59', strtotime('-1 day'));    // 昨日
                $start_dt = date('Y-m-01 00:00:00', strtotime('-1 day')); // 1日

            }
            // add 2013.04.23@uchida

        // 1週間に1回モード
        } else if ($mode == "1") {

            // add 2013.04.23@uchida
            // テスト用
            if ( defined("TEST_EVI_REGULAR_EXPORT")  && TEST_EVI_REGULAR_EXPORT == 1 ){
                // 開始日と終了日が未設定なら、さようなら。
                if( !defined("TEST_EVI_REGULAR_EXPORT_START") || !defined("TEST_EVI_REGULAR_EXPORT_END") ){
                    exit();
                }
                // 日付計算
                $end_dt = date('Y-m-d 23:59:59', strtotime(TEST_EVI_REGULAR_EXPORT_END));     // 終了日
                $start_dt = date('Y-m-d 00:00:00', strtotime(TEST_EVI_REGULAR_EXPORT_START)); // 開始日

            // 本番用
            }else{

                // 今日が月曜日じゃなければさようなら。
                if (date("D") != "Mon") {
                    exit();
                }

                // 日付計算
                $end_dt = date('Y-m-d 23:59:59', strtotime('-1 day'));    // 昨日
                $start_dt = date('Y-m-d 00:00:00', strtotime('-7 day'));  // 7日前

            }

        } else {
            //unlink($lockname); // ロック解除
            exit();
        }

        // 条件に合致するデータがあるかどうか
        $this->DtoArchive->setStart($start_dt);
        $this->DtoArchive->setEnd($end_dt);
        $this->MdbArchive->getFlexiList($this->DtoFlexi,$this->DtoArchive, false);
        // add 2013.04.22@uchida
        $file_list = $this->DtoArchive->getArchiveList();
        // add 2013.04.22@uchida
        $file_count = $this->DtoArchive->getRow_count();

        // 条件に合致するデータがなければ終わり
        if (!$this->DtoArchive->getArchiveList()) {
            //unlink($lockname); // ロック解除
            exit();
        }
        // add 2013.04.22@uchida
        // ファイルを分割して、zip圧縮する

        // エクスポートファイルの分割1ファイルあたりのサイズ
        if( !defined("EVI_FILE_SIZE_MB") ){
            exit();    // サイズ設定されていなければ、終わり
        }

        // 初期化
        $max_size     = EVI_FILE_SIZE_MB*1024*1024;
        $export_count = 0; // 分割エクスポート回数
        $export_size  = 0; // 分割エクスポートするファイルの合計サイズ
        $export_files = array(); //エクスポートするファイル一覧

        // 全体をzipにしたときの名前
        $sta     = explode("-",$start_dt);
        $end     = explode("-",$end_dt);
        $sta_d   = explode(" ",$sta[2]);
        $end_d   = explode(" ",$end[2]);

        // TODO:: 2014/07/03@yano 時間があれば、Admin_Archive_Export_Background共通化して欲しい
        foreach($file_list as $key => $val) {
            if (!$val["archive_file_id"]) { continue; }

            $copy_size = 0;
            $copy_size = $val['file_size'];
            
            // エクスポートするサイズを合計(チェックするサイズも)
            $check_file_size = 0;
            $check_file_size = $export_size = $export_size + $copy_size;
            
            // 次のファイルサイズを取得
            $next_file_size = 0;
            $i = $key;
            while ($i <= $file_count) {
                $i++;
                if (isset($file_list[$i])) {
                    if($file_list[$i]["archive_file_id"]) {
                        $next_file_size = $file_list[$i]['file_size'];
                        break;
                    }
                } else {
                    break;
                }
            }

            // 次のファイルがあれば、追加して上限に達するかどうかチェックする
            $check_file_size += $next_file_size;

            // エクスポートファイル一覧にセット
            $export_files[] = array(
                "archive_id"         => $val["archive_id"],
                "user_id"            => $val["user_id"],
                "archive_type"       => $val["archive_type"],
                "mailer_id"          => $val["mailer_id"],
                "folder_name"        => $val["folder_name"],
                "folder_flg"         => $val["folder_flg"],
                "group_id"           => $val["group_id"],
                "subject"            => $val["subject"],
                "body"               => $val["body"],
                "in_dt"              => $val["in_dt"],
                "checksum"           => $val["checksum"],
                "dl_time"            => $val["dl_time"],
                "max_dl_time"        => $val["max_dl_time"],
                "dl_dt"              => $val["dl_dt"],
                "dl_limit_dt"        => $val["dl_limit_dt"],
                "dl_pass_flg"        => $val["dl_pass_flg"],
                "dl_pass"            => $val["dl_pass"],
                "up_limit_dt"        => $val["up_limit_dt"],
                "collect_pass_flg"   => $val["collect_pass_flg"],
                "collect_pass"       => $val["collect_pass"],
                "zip_pass_flg"       => $val["zip_pass_flg"],
                "zip_pass"           => $val["zip_pass"],
                "open_flg"           => $val["open_flg"],
                "download_flg"       => $val["download_flg"],
                "stop_flg"           => $val["stop_flg"],
                "created"            => $val["created"],
                "modified"           => $val["modified"],
                "to_name"            => $val["to_name"],
                "to_mail"            => $val["to_mail"],
                "to_sms"            => $val["to_sms"],
                "mail_type_flg"      => $val["mail_type_flg"],
                "archive_file_id"    => $val["archive_file_id"],
                "file_name"          => $val["file_name"],
                "account"            => $val["account"],
                "user_name"          => $val["user_name"],
                "mailaddr"           => $val["mailaddr"]
            );


            // エクスポートファイルサイズが上限を超えていないかチェック
            // 1ファイルが上限を超えている場合もエクスポートを実行
            if ($max_size <= $check_file_size) {

                // 分割エクスポート回数カウントアップ
                $export_count++; 

                //zipファイル名
                $export_file_name = $sta[0].$sta[1].$sta_d[0]."-".$end[0].$end[1].$end_d[0]."-".$export_count.".zip";

                // 既に存在するかチェック？
                $this->DtoArchiveExport->setFile_name($export_file_name);
                $this->DtoArchiveExport->setStart($start_dt);
                $this->DtoArchiveExport->setEnd($end_dt);
                if ($this->MdbArchiveExport->checkExists($this->DtoArchiveExport)) {
                    exit();
                }

                // 先にDBに登録する
                $data = array(
                    "file_name" => $export_file_name,
                    "file_size" => 0,
                    "start"     => $start_dt,
                    "end"       => $end_dt
                );

                $archive_export_id = $this->MdbArchiveExport->insert($data);

                // エクスポート実行
                $this->ArchiveLogic->ArchiveExport($archive_export_id, $export_files, $export_file_name);

                // reset
                $export_files    = array();
                $export_size     = 0;
                $check_file_size = 0;

            } else {
                continue;
            }
        } // END foreach

        // 残りのファイル
        if (is_array($export_files) && $export_files) {
            $export_count++;

            // ファイル名
            $export_file_name = $sta[0].$sta[1].$sta_d[0]."-".$end[0].$end[1].$end_d[0]."-".$export_count.".zip";
            // 登録し直し
            $this->DtoArchiveExport->setFile_name($export_file_name);
            $data = array(
                "file_name" => $this->DtoArchiveExport->getFile_name(),
                "file_size" => 0,
                "start"     => $start_dt,
                "end"       => $end_dt
            );

            $archive_export_id =  $this->MdbArchiveExport->insert($data);          
            // エクスポート実行
            $this->ArchiveLogic->ArchiveExport($archive_export_id, $export_files, $export_file_name);
        }

        exit();

    }

    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoArchiveExport($DtoArchiveExport) {
        $this->DtoArchiveExport = $DtoArchiveExport;
    }
    function setMdbArchiveExport($MdbArchiveExport) {
        $this->MdbArchiveExport = $MdbArchiveExport;
    }
    function setDtoArchive($DtoArchive) {
        $this->DtoArchive = $DtoArchive;
    }
    function setMdbArchive($MdbArchive) {
        $this->MdbArchive = $MdbArchive;
    }
    function setDtoFlexi($DtoFlexi) {
        $this->DtoFlexi = $DtoFlexi;
    }
    function setArchiveLogic($ArchiveLogic) {
        $this->ArchiveLogic = $ArchiveLogic;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }

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
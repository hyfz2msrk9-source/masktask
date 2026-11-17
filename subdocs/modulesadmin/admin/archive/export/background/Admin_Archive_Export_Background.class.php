<?php
/*
 * [機能説明] WebFile 分割エクスポート
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

class Admin_Archive_Export_Background {
    var $session;
    var $DtoFlexi;
    var $MdbConfigs;
    var $DtoArchive;
    var $MdbArchive;
    var $DtoArchiveExport;
    var $MdbArchiveExport;
    var $ArchiveLogic;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Archive_Export_Backgroundが実行されました","Admin_Archive_Export_Background#execute");

        // 引数処理
        $arg = array();
        if (isset($_SERVER['argv'])) {
            $arg = $_SERVER['argv'];
        } else {
            exit;
        }
        // 引数チェック
        if (!isset($arg[1]) || !is_numeric($arg[1])) {
            exit;
        }
        if (!isset($arg[2])) {
            exit;
        }
        $archive_export_id = $arg[1];
        $file_name = $arg[2];

        $this->DtoArchiveExport->setArchive_export_id($archive_export_id);
        if (!$this->MdbArchiveExport->setInfo($this->DtoArchiveExport)) {
            $this->failureMail($file_name, "エクスポートファイルの情報を取得できませんでした");
        }
        $file_name = $this->DtoArchiveExport->getFile_name();

        // アーカイブ一覧取得
        $this->DtoArchive->setStart($this->DtoArchiveExport->getStart());
        $this->DtoArchive->setEnd($this->DtoArchiveExport->getEnd());
        $this->DtoArchive->setSearch_account($this->DtoArchiveExport->getSearch_account());
        $this->DtoArchive->setSearch_user_name($this->DtoArchiveExport->getSearch_user_name());
        $this->DtoArchive->setSearch_mailaddr($this->DtoArchiveExport->getSearch_mailaddr());
        $this->DtoArchive->setSearch_file_name($this->DtoArchiveExport->getSearch_file_name());
        $this->DtoArchive->setSearch_to_send($this->DtoArchiveExport->getSearch_to_send());
        $this->DtoArchive->setSearch_to_collect($this->DtoArchiveExport->getSearch_to_collect());
        $this->MdbArchive->getFlexiList($this->DtoFlexi, $this->DtoArchive, false);

        // 値セット
        $file_list  = $this->DtoArchive->getArchiveList();
        $file_count = $this->DtoArchive->getRow_count();

        // 2013.04.03 mod@uchida
        // ロックファイルを作らない方法に変更

        // 21030401-add-sawada
        $file_name = substr($file_name,0,-4);

        // エクスポートファイルの分割1ファイルあたりのサイズ
        if( !defined("EVI_FILE_SIZE_MB") ){
            exit();    // サイズ設定されていなければ、終わり
        }

        // 初期化
        $max_size     = EVI_FILE_SIZE_MB*1024*1024;
        $export_count = 0; // 分割エクスポート回数
        $export_size  = 0; // 分割エクスポートするファイルの合計サイズ
        $export_files = array(); //エクスポートするファイル一覧

        // 2014/07/03 @yano ファイルチェックを次のファイルサイズでチェックするよう改良
        foreach($file_list as $key => $val) {

            if (!$val["archive_file_id"]) {
                continue; 
            }
            
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
                "to_sms"             => $val["to_sms"],
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

                // エクスポートファイル名
                $export_file_name = $file_name."-".$export_count.".zip";

                if ($export_count == 1) {
                    // 1回消す
                    $this->MdbArchiveExport->delete(array("archive_export_id"=>$archive_export_id));
                }

                // 登録し直し
                $this->DtoArchiveExport->setFile_name($export_file_name);
                $data = array(
                    'file_name'         => $this->DtoArchiveExport->getFile_name(),
                    'file_size'         => $this->DtoArchiveExport->getFile_size(),
                    'start'             => $this->DtoArchiveExport->getStart(),
                    'end'               => $this->DtoArchiveExport->getEnd(),
                    'search_account'    => $this->DtoArchiveExport->getSearch_account(),
                    'search_user_name'  => $this->DtoArchiveExport->getSearch_user_name(),
                    'search_mailaddr'   => $this->DtoArchiveExport->getSearch_mailaddr(),
                    'search_file_name'  => $this->DtoArchiveExport->getSearch_file_name(),
                    'search_to_send'    => $this->DtoArchiveExport->getSearch_to_send(),
                    'search_to_collect' => $this->DtoArchiveExport->getSearch_to_collect(),
                    'in_dt'             => $this->DtoArchiveExport->getIn_dt()
                );
                $archive_export_id =  $this->MdbArchiveExport->insert($data);

                if (!$archive_export_id) {
                    $error_message  = "エクスポートの実行に失敗しました。\n\n";
                    $error_message .= "データベースの登録に失敗しました。再度、エクスポートを実行してください。\n";
                    $this->failureMail($file_name, $error_message);
                }

                // エクスポート実行
                $ret = $this->ArchiveLogic->ArchiveExport($archive_export_id, $export_files, $export_file_name);

                // reset
                $export_files    = array();
                $export_size     = 0;
                $check_file_size = 0;

            } else {
                continue;
            }
            
        } // END foreach

        // 残りをエクスポート
        if (is_array($export_files) && $export_files) {
            $export_count++;
            
            if ($export_count == 1) {
                // 1回消す
                $this->MdbArchiveExport->delete(array("archive_export_id"=>$archive_export_id));
            }
            
            // ファイル名
            $export_file_name = $file_name."-".$export_count.".zip";
            // 登録し直し
            $this->DtoArchiveExport->setFile_name($export_file_name);
            $data = array(
                'file_name'         => $this->DtoArchiveExport->getFile_name(),
                'file_size'         => $this->DtoArchiveExport->getFile_size(),
                'start'             => $this->DtoArchiveExport->getStart(),
                'end'               => $this->DtoArchiveExport->getEnd(),
                'search_account'    => $this->DtoArchiveExport->getSearch_account(),
                'search_user_name'  => $this->DtoArchiveExport->getSearch_user_name(),
                'search_mailaddr'   => $this->DtoArchiveExport->getSearch_mailaddr(),
                'search_file_name'  => $this->DtoArchiveExport->getSearch_file_name(),
                'search_to_send'    => $this->DtoArchiveExport->getSearch_to_send(),
                'search_to_collect' => $this->DtoArchiveExport->getSearch_to_collect(),
                'in_dt'             => $this->DtoArchiveExport->getIn_dt()
            );
            $archive_export_id =  $this->MdbArchiveExport->insert($data);          

            // エクスポート実行
            $this->ArchiveLogic->ArchiveExport($archive_export_id, $export_files, $export_file_name);
        }

        exit;
    }

    // エクスポート失敗メール送信関数
    function failureMail ($file_name, $error_msg) {
        $mailaddrs = $this->ArchiveLogic->getArchive_notice_addr();
        if ($mailaddrs) {
            $mail = new Mail;
            foreach ($mailaddrs as $to) {
                $mail->Evi_ExportFailure_Mail($to,$file_name, $error_msg);
            }
        }
        exit;
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
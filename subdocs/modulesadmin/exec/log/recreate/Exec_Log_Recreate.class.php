<?php

class Exec_Log_Recreate
{
    var $MdbLog;
    var $DtoLog;
    var $UserLogic;

    //showlist用
    var $MdbUpload;
    var $DtoUpload;
    var $MdbCollect;
    var $DtoCollect;
    var $MdbFolder;
    var $DtoFolder;
    var $MdbMchoice;
    var $MdbDownload;
    var $MdbDownloadMailer;
    var $MdbUser;
    var $MdbAdminuser;
    var $MdbDeliveryto;
    var $MdbDeliveryfile;

    var $log;
    // ログファイルが保管されているパス
    var $path;
    // 1回にとってくるログの件数
    var $limit_span;
    // テスト用にログファイルを全て作るフラグ
    var $test_flg;

    function __construct()
    {
        $this->log = &LogFactory::getLog();
        $this->path = BASE_DIR . "/htdocs" . HISTORY_PATH;
        $this->limit_span = 5000; // 1回にとってくるログの件数（ここをいじって調節してください）
        $this->test_flg = 0; //テスト環境で動作確認用に過去のログファイルを全て作る際にはこちらを1にしてください 
    }

    function execute()
    {
        // 全体の時間計測開始
        $time_start = microtime(true);

        $this->log->debug("Exec_Log_Recreateが実行されました。", "Exec_Log_Recreate#execute");

        // 処理する時間の最大値を設定(10時間)
        set_time_limit(36000);

        // 引数処理
        $arg = array();
        if (isset($_SERVER['argv'])) {
            $arg = $_SERVER['argv'];
        }

        // 引数が3つあった時のエラー処理
        if (!empty($arg[3])) {
            echo  "エラー：引数は3つ以上入力しないでください。\n";
            exit;
        }

        // 値を取得
        $response = $this->get_start_and_end_date($arg);
        if ($response == false) {
            echo "ログが存在しません。\n";
            exit;
        }

        // 過去のログファイルをテスト用に作成
        if ($this->test_flg == 1) {
            $this->create_test_log_file($response["start_date"], $response["end_date"]);
        }

        // 期間内のログファイルが存在しているか確認
        $files = $this->get_log_files_in_term($response["start_date"], $response["end_date"]);

        foreach ($files as $file) {
            // ログ作成時間計測開始
            $log_time_start = microtime(true);

            // ログ作成の範囲を指定
            $log_span = $this->create_log_span($file[0]);
            $log_type_num = $this->get_log_type_num($file[1]);
            if ($log_type_num == false) {
                continue;
            }

            // ログがなかったら終わる
            $limit_cnt = $this->MdbLog->getSpanLogCount($log_span["start_date"], $log_span["end_date"], $log_type_num);

            if (!$limit_cnt) {
                // テスト用で作成したならば余分なファイルは削除する
                if ($this->test_flg == 1) {
                    $this->delete_old_file($file[0]);
                }
                continue;
            }

            // 新しくログを書き込むファイルを用意
            $new_file = $this->create_new_log_file_open($file[0]);
            if ($new_file == false) {
                echo $file[0] . "の新しいログファイルの作成に失敗しました。\n";
                $this->log->error($file[0] . "の新しいログファイルの作成に失敗しました。", "Exec_Log_Recreate#execute");
                continue;
            }

            // ファイルロック
            $lock = flock($new_file, LOCK_EX);
            if ($lock == false) {
                echo "ファイルのロックに失敗しました。\n";
                $this->log->error("ファイルのロックに失敗しました。", "Exec_Log_Recreate#execute");
                fclose($new_file);
                continue;
            }

            // ファイルの1行目を入れる
            $csv_data = $this->get_headline($file[1]);
            $file_head_write = fwrite($new_file, $csv_data);
            if ($file_head_write == false) {
                echo $file[0] . "の新しいファイルの書き込みに失敗しました。\n";
                $this->log->error($file[0] . "の新しいファイルの書き込みに失敗しました。", "Exec_Log_Recreate#execute");
                fclose($new_file);
                continue;
            }
            // 書き出し
            $csv_write_result = $this->write_log($limit_cnt, $log_type_num, $log_span, $new_file);

            if ($csv_write_result == false) {
                echo $file[0] . "の新しいファイルの書き込みに失敗しました。\n";
                $this->log->error($file[0] . "の新しいファイルの書き込みに失敗しました。", "Exec_Log_Recreate#execute");
                fclose($new_file);
                continue;
            }

            // ファイルを閉じる
            fclose($new_file);
            unset($new_file);

            // 既存ファイルの削除
            $delete_old_file_result = $this->delete_old_file($file[0]);
            if ($delete_old_file_result == false) {
                echo $file[0] . "のファイルの削除に失敗しました。\n";
                $this->log->error($file[0] . "のファイルの削除に失敗しました。", "Exec_Log_Recreate#execute");
                continue;
            }

            // ファイルのリネーム
            $rename_result = rename($this->path . "/" . "new-" . $file[0], $this->path . "/" . $file[0]);
            if ($rename_result == false) {
                echo $file[0] . "の新しいファイルのリネームに失敗しました。\n";
                $this->log->error($file[0] . "の新しいファイルのリネームに失敗しました。", "Exec_Log_Recreate#execute");
                continue;
            }

            // ログ作成時間計測終了
            $log_time = microtime(true) - $log_time_start;
            echo $file[0] . "： " . $limit_cnt . "件のログの処理に" . round($log_time, 3) . "秒かかりました。\n";

            // ファイル名と時間の出力
        }
        // 全体の時間計測終了
        $time = microtime(true) - $time_start;
        echo "全体で" . round($time, 3) . "秒処理にかかりました。\n";
        exit;
    }

    function write_log($limit_cnt, $log_type_num, $log_span, $new_file)
    {
        $slm = 0;
        while ($slm <= $limit_cnt) {
            // ログ取得
            // DtoLogはLoglistのみしか使用しておらず、かつ毎ループで上書きされているので初期化する必要はなし
            $log_list = $this->get_log_list_from_log_type_num($log_type_num, $log_span, $slm);
            if ($log_list == false) {
                return false;
            }

            // ログ整形
            foreach ($log_list as $log) {
                // DTOの初期化
                $this->reset_dto();

                // ログのデータを入れ直し
                // データの日本語化と、配列の整形
                $info = $this->recreate_dto_log($log);

                // csv用のデータに成形
                $csv_data = $this->create_csv_data_from_log($info, $log_type_num);

                // エンコーディング
                $csv_data = mb_convert_encoding($csv_data, "SJIS-win", "UTF-8");
                // 書き出し
                $result_fwrite = fwrite($new_file, $csv_data);

                if ($result_fwrite == false) {
                    return false;
                }
            }

            // 次の取り出し位置を設定
            $slm = $slm + $this->limit_span;
        }
        return true;
    }

    function reset_dto()
    {
        // DTOの初期化
        // $this->DtoFolderの初期化
        $this->DtoFolder->init();

        // $this->DtoUploadの初期化
        $this->DtoUpload->init();

        // $this->DtoCollectの初期化
        $this->DtoCollect->init();
    }

    function create_csv_data_from_log($log, $log_type_num)
    {
        $csv_data = "";
        foreach ($log as $key => $val) {
            if ($log_type_num == 4) {
                if (!isset($val["name"])) {
                    $val["name"] = "(削除済みグループ)";
                }

                $csv_data .= StrChange::quoteCSV($val["name"]) . ',';
            }
            $csv_data .= StrChange::quoteCSV($val["account"]) . ',';
            $csv_data .= StrChange::quoteCSV($val["file_size"]) . ',';
            $csv_data .= StrChange::quoteCSV($val["file_name"]) . ',';
            $csv_data .= StrChange::quoteCSV($val["content"]) . ',';
            $csv_data .= StrChange::quoteCSV($val["in_dt"]) . ',';
            $csv_data .= StrChange::quoteCSV($val["ip_address"]) . ',';
            $csv_data .= StrChange::quoteCSV($val["memo"]) . ',';
            $csv_data .= StrChange::quoteCSV($val["mailaddr"]) . ',';
            $csv_data .= StrChange::quoteCSV($val["user_name"]) . ',';
            $csv_data .= StrChange::quoteCSV($val["user_memo"]) . "\n";
        }

        return $csv_data;
    }

    function delete_old_file($file_name)
    {
        $handle = opendir($this->path);
        if (!$handle) {
            return false;
        }
        
        // ファイルが存在していれば削除
        if (file_exists($this->path . "/" . $file_name)) {
            return unlink($this->path . "/" . $file_name);
        }
        // 旧ファイルが存在しない
        return true;
    }

    function recreate_dto_log($log)
    {
        $this->DtoLog->setLogList(array(0 => $log));
        $this->UserLogic->showlistall(
            $this->DtoLog,
            $this->MdbUpload,
            $this->DtoUpload,
            $this->MdbCollect,
            $this->DtoCollect,
            $this->MdbMchoice,
            $this->MdbDownload,
            $this->MdbUser,
            $this->MdbAdminuser,
            $this->MdbFolder,
            $this->DtoFolder,
            $this->MdbDownloadMailer,
            $this->MdbDeliveryto,
            $this->MdbDeliveryfile
        );

        return $this->DtoLog->getLogList();
    }

    function get_log_list_from_log_type_num($log_type_num, $log_span, $slm)
    {
        if ($log_type_num == 2) {
            $this->MdbLog->getSpanLogList2($this->DtoLog, $log_span["start_date"], $log_span["end_date"], $slm, $this->limit_span);
        } elseif ($log_type_num == 3) {
            $this->MdbLog->getSpanLogList3($this->DtoLog, $log_span["start_date"], $log_span["end_date"], $slm, $this->limit_span);
        } elseif ($log_type_num == 4) {
            $this->MdbLog->getSpanLogList4($this->DtoLog, $log_span["start_date"], $log_span["end_date"], $slm, $this->limit_span);
        } else {
            $this->DtoLog->init();
            return false;
        }

        return $this->DtoLog->getLogList();
    }


    /*
    ファイルを開くにに失敗した場合はfalseを返す
    データがある場合はログファイル名の配列を返す
    データがない場合は空の配列を返す
    $files = 
    ["yyyy-mm-admin-log.csv", "admin"],
    ["yyyy-mm-user-log.csv", "user"],
    ["yyyy-mm-group-log.csv", "group"],
    ...]
    */
    function get_log_files_in_term($start, $end)
    {
        $files = [];
        $handle = opendir($this->path);
        if (!$handle) {
            return false;
        }
        while ($file = @readdir($handle)) {
            // 名前が一致するか
            // yyyy-mm-(admin|user|group)-log.csv
            if (preg_match("/^\d{4}-\d{2}-(admin|user|group)-log.csv$/", $file, $matches)) {
                // 取得したログファイルが指定した期間内のものであれば配列に追加
                $matched_file_name = $matches[0];
                $file_generated_month = date("Y-m", strtotime(substr($matched_file_name, 0, 7) . "-01"));
                if ($start <= $file_generated_month && $file_generated_month <= $end) {
                    $files[] = $matches;
                }
            }
        }
        return $files;
    }

    function create_test_log_file($start, $end)
    {
        while ($start <= $end) {
            $admin_file = fopen($this->path . "/"  . $start . "-admin-log.csv", "a");
            fclose($admin_file);
            unset($admin_file);

            $group_file = fopen($this->path . "/"  . $start . "-group-log.csv", "a");
            fclose($group_file);
            unset($group_file);

            $user_file = fopen($this->path . "/"  . $start . "-user-log.csv", "a");
            fclose($user_file);
            unset($user_file);

            $start   = date('Y-m', strtotime($start . " +1 month"));
        }
    }

    function get_start_and_end_date($arg)
    {
        if (empty($arg[1])) {
            // 引数なし
            $start = $this->get_old_log_date();
            $end = date("Y-m");
        } else if (empty($arg[2])) {
            // 引数が1つだった時の処理
            $start = $this->get_old_log_date();
            $end = $this->get_validated_date($arg[1]);
        } else {
            // 引数が2つあった時の処理
            $start = $this->get_validated_date($arg[1]);
            $end = $this->get_validated_date($arg[2]);
        }

        if ($start == false) {
            return false;
        }
        if ($start > $end) {
            echo  "エラー：日付を入力する際は最初の引数が古い日付になるように入力してください。\n";
            exit;
        }
        return ["start_date" =>  $start, "end_date" => $end];
    }

    function get_old_log_date()
    {
        $old_date = $this->MdbLog->getLogOldestDate();
        if (empty($old_date)) {
            return false;
        }
        $date = date("Y-m", strtotime($old_date[0]["MIN(in_dt)"]));
        return $date;
    }

    function get_validated_date($arg)
    {
        if (!is_numeric($arg)) {
            echo  "エラー：6桁の数字を入力してください。\n";
            exit;
        }
        if (strlen($arg) != 6) {
            echo  "エラー：正しい桁数の数字を入力してください。\n";
            exit;
        }
        $date = date("Y-m", strtotime($arg . "01"));
        if ($date > date("Y-m")) {
            echo  "エラー：現在より過去の日付を入力してください。\n";
            exit;
        }

        return $date;
    }

    function create_new_log_file_open($file_name)
    {
        $handle = opendir($this->path);
        if (!$handle) {
            return false;
        }
        $delete_result = "";
        if (file_exists($this->path . "/new-" . $file_name)) {
            $delete_result = unlink($this->path . "/new-" . $file_name);
            if ($delete_result == false) {
                return false;
            }
        }

        // 新しいログファイルの作成
        $result = fopen($this->path . "/" . "new-" . $file_name, "a");

        return $result;
    }

    function get_headline($file_head_type)
    {
        $csv_data = '"アカウント","転送量","ファイル名","操作内容","操作日時","IPアドレス","備考","メールアドレス","氏名","メモ"' . "\n";
        if ($file_head_type == "group") {
            $csv_data = '"グループ名",' . $csv_data;
        }
        $csv_data = mb_convert_encoding($csv_data, "SJIS", "UTF-8");

        return $csv_data;
    }

    function create_log_span($file_name)
    {
        // ログ作成の範囲の日時をDBの形式で取得
        $start = date("Y-m-d", strtotime(substr($file_name, 0, 7) . "-01")) . " 00:00:00";
        $end   = date('Y-m-d', strtotime($start . " +1 month")) . " 00:00:00";
        if($end > date("Y-m-d 00:00:00")) {
            // 定期作成ログは前日分までが対象なので、今日のAM0:00で上書き
            $end   = date('Y-m-d') . " 00:00:00";
        }

        return ["start_date" =>  $start, "end_date" => $end];
    }

    function get_log_type_num($log_type)
    {
        // ファイルのタイプによって引数に渡す数値を変更

        if ($log_type == "admin") {
            // 管理者ログ
            $log_type_num = 2;
        } else if ($log_type == "user") {
            // 一般ユーザログ
            $log_type_num = 3;
        } else if ($log_type == "group") {
            // グループログ
            $log_type_num = 4;
        } else {
            // そのほかの値はあり得ないのでエラーにする
            return false;
        }
        return $log_type_num;
    }

    // setter
    function setDtoLog($DtoLog)
    {
        $this->DtoLog = &$DtoLog;
    }
    function setMdbLog($MdbLog)
    {
        $this->MdbLog = &$MdbLog;
    }
    function setUserLogic($UserLogic)
    {
        $this->UserLogic = &$UserLogic;
    }
    function setMdbUpload($MdbUpload)
    {
        $this->MdbUpload = &$MdbUpload;
    }
    function setDtoUpload($DtoUpload)
    {
        $this->DtoUpload = &$DtoUpload;
    }
    function setMdbCollect($MdbCollect)
    {
        $this->MdbCollect = &$MdbCollect;
    }
    function setDtoCollect($DtoCollect)
    {
        $this->DtoCollect = &$DtoCollect;
    }
    function setMdbFolder($MdbFolder)
    {
        $this->MdbFolder = &$MdbFolder;
    }
    function setDtoFolder($DtoFolder)
    {
        $this->DtoFolder = &$DtoFolder;
    }
    function setMdbMchoice($MdbMchoice)
    {
        $this->MdbMchoice = &$MdbMchoice;
    }
    function setMdbDownload($MdbDownload)
    {
        $this->MdbDownload = &$MdbDownload;
    }
    function setMdbDownloadMailer($MdbDownloadMailer)
    {
        $this->MdbDownloadMailer = $MdbDownloadMailer;
    }
    function setMdbUser($MdbUser)
    {
        $this->MdbUser = &$MdbUser;
    }
    function setMdbAdminuser($MdbAdminuser)
    {
        $this->MdbAdminuser = &$MdbAdminuser;
    }
    function setMdbDeliveryto(&$MdbDeliveryto)
    {
        $this->MdbDeliveryto = &$MdbDeliveryto;
    }
    function setMdbDeliveryfile(&$MdbDeliveryfile)
    {
        $this->MdbDeliveryfile = &$MdbDeliveryfile;
    }
}

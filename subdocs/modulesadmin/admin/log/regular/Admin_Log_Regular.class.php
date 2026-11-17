<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Log_Regular {
    var $MdbLog;
    var $DtoLog;
    var $UserLogic;
    var $mydate;

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

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Log_Regularが実行されました。", "Admin_Log_Regular#execute");

        set_time_limit(36000);

        // 変数初期値
        $flg = 1;
        $path = BASE_DIR."/htdocs".HISTORY_PATH;
        $limit_span = 5000; // 1回にとってくるログの件数（ここをいじって調節してください）

        for ($i = 2; $i < 5; $i++) {

            // ファイル名の定義
            $head = "";
            if ($i == 2 ) {
                // 管理者
                $head = "-admin";
            } else if ($i == 3 ) {
                // 一般ユーザ
                $head = "-user";
            } else if ($i == 4 ) {
                // グループ
                $head = "-group";
            }
            $before_filename = date("Y-m",strtotime("-2 day")).$head."-log.csv"; // 昨日までのファイル名
            $filename = date("Y-m",strtotime("-1 day")).$head."-log.csv";        // 今日作るファイル名

            // 昨日までのファイルを探す
            // 今日が2日なら、新たに新しい月のファイルを作る
            $tf = 0;
            if (date("d") == "02") {
                $start = date("Y-m",strtotime("-1 day"))."-01 00:00:00";
                $end = date("Y-m-d")." 00:00:00";

            } else {
                if ($handle = opendir($path)) {
                    while (false !== ($file = @readdir($handle))) {
                        if ($file == $before_filename) { $tf = 1; break; }
                    }
                }
                if ($tf == 0) {
                    // 昨日のファイルがなかったので最初から
                    $start = date("Y-m",strtotime("-1 day"))."-01 00:00:00";
                    $end = date("Y-m-d")." 00:00:00";

                } elseif ($tf == 1) {
                    // 昨日のファイルがあったので続きから
                    $start = date("Y-m-d",strtotime("-1 day"))." 00:00:00";
                    $end   = date("Y-m-d")." 00:00:00";
                }
            }

            // debug
            /*
             $tf = 1;
             $start = "2011-04-01 00:00:00";
             $end   = "2011-04-30 00:00:00";
            */

            // ログなかったら終わる
            $limit_cnt = null;
            $limit_cnt = $this->MdbLog->getSpanLogCount($start,$end,$i);
            if (!$limit_cnt) { continue; }

            // 新たにファイルを作るまえに、昨日までのファイルをリネーム
            /*
            if ($tf == 1) {
                rename($path."/".$before_filename, $path."/back_".$before_filename);
            }
            */

            // ファイル開く
            unset($result);
            $result = fopen($path."/".$filename, "a");
            // ロック
            $lock = flock($result, LOCK_EX);

            // フィールド名を出力
            if ($tf == 0) {
                $csv_data = '"アカウント","転送量","ファイル名","操作内容","操作日時","IPアドレス","備考","メールアドレス","氏名","メモ"' . "\n";
                if( $i == 4 ) {
                    $csv_data = '"グループ名",' . $csv_data;
                }
                $csv_data = mb_convert_encoding($csv_data,"SJIS","UTF-8");
                fwrite($result, $csv_data);
            }

            // 書き出し
            $slm = '0';
            $elm = $limit_span;
            while((integer)$slm <= (integer)$limit_cnt) {
                // ログ取得
                if ($i == 2) {
                    $this->MdbLog->getSpanLogList2($this->DtoLog,$start,$end,$slm,$limit_span);
                } elseif ($i == 3) {
                    $this->MdbLog->getSpanLogList3($this->DtoLog,$start,$end,$slm,$limit_span);
                } elseif ($i == 4) {
                    $this->MdbLog->getSpanLogList4($this->DtoLog,$start,$end,$slm,$limit_span);
                }

                // ログ整形
                $data = $this->DtoLog->getLogList();
                foreach($data as $data2) {
                    $this->DtoLog->setLogList(array(0=>$data2));
                    $this->UserLogic->showlistall($this->DtoLog,
                                                  $this->MdbUpload,$this->DtoUpload,
                                                  $this->MdbCollect,$this->DtoCollect,
                                                  $this->MdbMchoice,$this->MdbDownload,
                                                  $this->MdbUser,$this->MdbAdminuser,
                                                  $this->MdbFolder,$this->DtoFolder,
                                                  $this->MdbDownloadMailer,$this->MdbDeliveryto,$this->MdbDeliveryfile);

                    $info = $this->DtoLog->getLogList();
                    $csv_data = "";
                    if ($i == 4) {
                        foreach($info as $key => $val) {
                            if ( ! isset($val["name"])) {
                                $val["name"] = "(削除済みグループ)";
                            }
                            $csv_data .= StrChange::quoteCSV($val["name"]) . ',';
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
                    } else {
                        foreach($info as $key => $val) {
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
                    }
                    $csv_data = mb_convert_encoding($csv_data,"SJIS-win","UTF-8");
                    fwrite($result, $csv_data);
                }

                // 次の取り出し位置を設定
                $slm = $elm;
                $elm = $slm + $limit_span;
            }
            fclose($result);
            unset($result);

            // 昨日のファイルがあれば内容を追加します
            /*
            if ($lock) {
                if ($tf == 1) {
                    $result = fopen($path."/".$filename, "a");
                    $fp = fopen($path."/back_".$before_filename, "r");
                    while ($row = fgetcsv($fp, 3000000, ",")) {
                        $csv_data  = "";
                        $csv_data .= '"'.$row[0].'","';
                        $csv_data .= $row[1].'","';
                        $csv_data .= $row[2].'","';
                        $csv_data .= $row[3].'","';
                        $csv_data .= $row[4].'","';
                        $csv_data .= $row[5].'","';
                        $csv_data .= $row[6].'","';
                        $csv_data .= $row[7].'","';
                        $csv_data .= $row[8].'"';
                        $csv_data .= "\n";
                        fwrite($result, $csv_data);
                    }
                    fclose($result);

                    // 昨日までのファイル消します
                    fclose($fp);
                    unlink($path."/back_".$before_filename);
                }
            }
            */
        }

        exit;
    }

    function setDtoLog($DtoLog){
    	$this->DtoLog =& $DtoLog;
    }
    function setMdbLog($MdbLog){
    	$this->MdbLog =& $MdbLog;
    }
    function setUserLogic($UserLogic) {
        $this->UserLogic =& $UserLogic;
    }
    function setMdbUpload($MdbUpload){
    	$this->MdbUpload =& $MdbUpload;
    }
    function setDtoUpload($DtoUpload){
    	$this->DtoUpload =& $DtoUpload;
    }
    function setMdbCollect($MdbCollect){
    	$this->MdbCollect =& $MdbCollect;
    }
    function setDtoCollect($DtoCollect){
    	$this->DtoCollect =& $DtoCollect;
    }
    function setMdbFolder($MdbFolder){
    	$this->MdbFolder =& $MdbFolder;
    }
    function setDtoFolder($DtoFolder){
    	$this->DtoFolder =& $DtoFolder;
    }
    function setMdbMchoice($MdbMchoice){
    	$this->MdbMchoice =& $MdbMchoice;
    }
    function setMdbDownload($MdbDownload){
    	$this->MdbDownload =& $MdbDownload;
    }
    function setMdbDownloadMailer($MdbDownloadMailer) {
        $this->MdbDownloadMailer = $MdbDownloadMailer;
    }
    function setMdbUser($MdbUser){
    	$this->MdbUser =& $MdbUser;
    }
    function setMdbAdminuser($MdbAdminuser){
    	$this->MdbAdminuser =& $MdbAdminuser;
    }
    function setMdbDeliveryto(&$MdbDeliveryto) {
        $this->MdbDeliveryto =& $MdbDeliveryto;
    }
    function setMdbDeliveryfile(&$MdbDeliveryfile) {
        $this->MdbDeliveryfile =& $MdbDeliveryfile;
    }
}

?>

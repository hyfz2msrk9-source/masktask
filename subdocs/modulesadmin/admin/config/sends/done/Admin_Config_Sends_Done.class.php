<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] 各種設定 - 操作設定 - 配布に関する設定(完了)
 * @updater     Hiroko Yanai（2020/02/06）期限と回数の_sortバリデーション追加
 *
 * @package     WebFile
 * @version     3.17.0
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_Config_Sends_Done {
    var $session;
    var $DtoConfigs;
    var $MdbConfigs;
    var $DtoLog;
    var $MdbLog;
    var $MdbMchoice;
    var $DtoGroups;
    var $MdbGroups;

    var $sends_flg;
    var $mailer_flg;
    var $mailer_default_flg;
    var $sample_temp_flg;
    var $default_pass_flg;
    var $zip_pass_flg;
    var $default_auto_del_flg;
    var $sendcollect_flg;
    var $sendcollect_mailer_flg;
    var $send_mail_limit;
    var $file_send_limit;
    var $limitdt_val;
    var $limitdt_content;
    var $limitdt_maxsort;
    var $limitdt_sort;
    var $limitdt_mark;
    var $maxdltime_val;
    var $maxdltime_content;
    var $maxdltime_maxsort;
    var $maxdltime_sort;
    var $maxdltime_mark;
    var $limitdt_addlist;
    var $maxdltime_addlist;
    var $token;


    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Sends_Doneが実行されました","Admin_Config_Sends_Done#execute");

        $send_error_response = function ($message = "不正な値が入力されました。") {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => $message,
                'token_name' => 'SendsEditToken',
                'token_value' => $this->token->getValue('SendsEditToken')
            ]);
            exit;
        };

        if (SENDS_COLLECT_MAX_NUMBER != 0) {
            $this->send_mail_limit = SENDS_COLLECT_MAX_NUMBER;
        }elseif ($this->send_mail_limit === "" || $this->send_mail_limit === null) {
            $send_error_response("最大送信人数が入力されていません。");
        }
        if (!preg_match("/^[0-9]+$/", $this->send_mail_limit)) {
            $send_error_response("最大送信人数は整数で入力してください。");
        }
        $valMailLimit = (int)$this->send_mail_limit;
        if ($valMailLimit < 1 || $valMailLimit > 9999) {
            $send_error_response("最大送信人数は1〜9999の範囲で登録してください。");
        }

        if ($this->file_send_limit === "" || $this->file_send_limit === null) {
            $send_error_response("最大送信ファイル数が入力されていません。");
        }
        if (!preg_match("/^[0-9]+$/", $this->file_send_limit)) {
            $send_error_response("最大送信ファイル数は整数で入力してください。");
        }
        $valFileLimit = (int)$this->file_send_limit;
        if ($valFileLimit < 1 || $valFileLimit > 9999) {
            $send_error_response("最大送信ファイル数は1〜9999の範囲で登録してください。");
        }

        // メーラからの送信機能が無効の際は、メーラから送信をデフォルト表示にしない
        if ($this->mailer_flg == 0) {
            $this->mailer_default_flg = 0;
        }

        //入力チェック
        if (!array_key_exists($this->sends_flg, GlobalArray::getSends_Flg_Options())) $send_error_response("配布機能の指定が不正です。");
        if (!array_key_exists($this->mailer_flg, GlobalArray::getMailer_Flg_Options())) $send_error_response("メーラー送信機能の指定が不正です。");
        if (!array_key_exists($this->mailer_default_flg, GlobalArray::getMailer_Default_Flg_Options())) $send_error_response("デフォルト機能の指定が不正です。");
        if (!array_key_exists($this->sendcollect_flg, GlobalArray::getSendCollect_Flg_Options())) $send_error_response("引取り（収集）同時送信機能の指定が不正です。");
        
        if ($this->sendcollect_flg == 1) {
            if (!array_key_exists($this->sendcollect_mailer_flg, GlobalArray::getSendCollect_Mailer_Flg_Options())) $send_error_response("メーラー同時送信の指定が不正です。");
        }
        
        if (!array_key_exists($this->sample_temp_flg, GlobalArray::getSample_Temp_Flg_Options())) $send_error_response("サンプル文の指定が不正です。");
        if (!array_key_exists($this->default_pass_flg, GlobalArray::getDefault_Pass_Flg_Options())) $send_error_response("パスワードロックの指定が不正です。");
        
        if (ZIPDL_TYPE == 1) {
            if (!array_key_exists($this->zip_pass_flg, GlobalArray::getZip_Pass_Flg_Options())) $send_error_response("ZIPパスワードの指定が不正です。");
        }
        
        if (!array_key_exists($this->default_auto_del_flg, GlobalArray::getDefault_Auto_Del_Flg_Options())) $send_error_response("自動削除機能の指定が不正です。");


        //配布機能
        $this->MdbConfigs->setValue('sends_flg',$this->sends_flg);
        // 一斉配信の配布を無効化
        if (!$this->MdbConfigs->getDirectValue("sends_flg")) {
          $this->MdbConfigs->setValue('delivery_send_flg', '0');
        }

        //最大送信人数
        $this->MdbConfigs->setValue('send_mail_limit',$this->send_mail_limit);

        //最大送信ファイル数
        $this->MdbConfigs->setValue('file_send_limit',$this->file_send_limit);

        //メーラーから送信
        $this->MdbConfigs->setValue('mailer_flg',$this->mailer_flg);

        //配布時のデフォルト表示
        $this->MdbConfigs->setValue('mailer_default_flg',$this->mailer_default_flg);

        //引取り（収集）同時送信機能
        $this->MdbConfigs->setValue('sendcollect_flg',$this->sendcollect_flg);

        //引取り（収集）同時URL発行機能
        $this->MdbConfigs->setValue('sendcollect_mailer_flg',$this->sendcollect_mailer_flg);

        //サンプル文初期入力機能
        $this->MdbConfigs->setValue('sample_temp_flg',$this->sample_temp_flg);

        //パスワードロック機能
        $this->MdbConfigs->setValue('default_pass_flg',$this->default_pass_flg);

        //zipパスワード機能
        if(ZIPDL_TYPE == '1') {
            $this->MdbConfigs->setValue('zip_pass_flg',$this->zip_pass_flg);
        } else {
            //winなら無効をセット
            $this->MdbConfigs->setValue('zip_pass_flg','0');
        }

        //自動削除のデフォルト設定
        $this->MdbConfigs->setValue('default_auto_del_flg',$this->default_auto_del_flg);

        // ダウンロード有効期限 ここから
        // 追加分
        if ($this->limitdt_addlist) {
            $str = strtok($this->limitdt_addlist, ",");
            while ($str) {
                $data[0] = $str;
                $str = strtok(",");

                $data[1] = $str;
                if (!preg_match("/^[0-9]+$/", $data[1])) {
                    $send_error_response("ダウンロード有効期限が不正です。");
                } else if ((int)$data[1] >= 9999) {
                    $send_error_response("ダウンロード有効期限は9999日以内で入力してください。");
                }
                $str = strtok(",");

                $data[2] = $str;
                $str = strtok(",");

                $data[3] = $str;
                $str = strtok(",");

                if (defined("CHINESE_FLG") && CHINESE_FLG == 1) {
                    $data[4] = $str;
                    $str = strtok(",");

                    $data[5] = $str;
                    $str = strtok(",");
                }

                if (strlen($data[2]) > 20 || strlen($data[3]) > 20 || (CHINESE_FLG == 1 && (strlen($data[4]) > 20 || strlen($data[5]) > 20))) {
                    $send_error_response("表示は20文字以内で入力してください。");
                }

                $info[] = $data;
            }
        }

        // ダウンロード有効期限の追加と更新と削除
        $list = $this->MdbMchoice->getList("limit_dt");
        foreach ($list as $key => $val) {
            $id_list[] = $val["choice_id"];
        }

        $dt_add_memo = "";
        $dt_sort_memo = "";
        $dt_del_memo = "";

        if(isset($this->limitdt_sort) && $this->limitdt_sort != ""){
            $sort = explode(",",$this->limitdt_sort);
            // 画面上でのソート(idを,区切り)について、idが「数字」または「#数字」になっているか
            foreach($sort as $key => $choiceId) {
                if (!preg_match("/^#?[0-9]+$/", $choiceId)) {
                    $send_error_response("ダウンロード有効期限が不正です。");
                }
            }

            // 削除
            $dt_del_memo = "";
            $del_list = array_diff($id_list,$sort);
            foreach($del_list as $key => $val) {
                $delval = $this->MdbMchoice->getChoiceIdList($val,"limit_dt");
                $this->MdbMchoice->Delete($val);

                // ログ用のメモ
                $delmemo1 = $delval["value_id"];
                $delmemo2 = $delval["content"];
                $delmemo3 = $delval["content_en"];
                $delmemo4 = $delval["content_cn"];
                $delmemo5 = $delval["content_tcn"];
                $dt_del_memo .= "ダウンロード有効期限：日数:" . $delmemo1 . ",日本語:" . $delmemo2 . ",英語:" . $delmemo3;
                if( CHINESE_FLG == 1 ) {
                    $dt_del_memo .= ",簡体字:" . $delmemo4 . ",繁体字:" . $delmemo5;
                }
                $dt_del_memo .= "　を削除" . "\n";
            }

            // 追加と更新
            foreach($sort as $key => $choiceId){
                $sortNo = $key+1;
                if (substr($choiceId,0,1) == "#") {
                    foreach($info as $key2 => $val2) {
                        if ($val2[0] == $choiceId) {
                            if ($val2[1] == "00") {
                                $val2[1] = "0";
                            }
                            if (CHINESE_FLG == 1) {
                                $this->MdbMchoice->setInsert('limit_dt',$val2[1],$val2[2],$val2[3],$val2[4],$val2[5],$sortNo);
                            } else {
                                $this->MdbMchoice->setInsert('limit_dt',$val2[1],$val2[2],$val2[3],'','',$sortNo);
                            }

                            // ログ用のメモ
                            $dt_add_memo .= "ダウンロード有効期限：日数:" . $val2[1] . ",日本語:" . $val2[2] . ",英語:" . $val2[3];
                            if( CHINESE_FLG == 1 ) {
                                $dt_add_memo .= ",簡体字:" . $val2[4] . ",繁体字:" . $val2[5];
                            }
                            $dt_add_memo .= "　を追加" . "\n";
                        }
                    }
                } else {
                    $sortval = $this->MdbMchoice->getChoiceIdList($choiceId,"limit_dt");
                    if ((string)$sortval["sort"] != (string)$sortNo) {
                        $dt_sort_memo = "true";
                    }
                    $this->MdbMchoice->setSortUpdata($choiceId,$sortNo);
                }
            }
            // 一番上の値を初期値に設定
            $val_list = $this->MdbMchoice->getList("limit_dt");
            $val_id = $val_list[0]["value_id"];
            $this->MdbConfigs->setValue('limitdt_mark',$val_id);
        }
        // ダウンロード有効期限 ここまで

        // ダウンロード回数 ここから
        // 追加分
        if ($this->maxdltime_addlist) {
            $str2 = strtok($this->maxdltime_addlist, ",");
            while ($str2) {
                $dl_data[0] = $str2;
                $str2 = strtok(",");

                $dl_data[1] = $str2;
                if (!preg_match("/^[0-9]+$/", $dl_data[1])) {
                    $send_error_response("ダウンロード回数が不正です。");
                } else if ((int)$dl_data[1] >= 9999) {
                    $send_error_response("ダウンロード回数は9998以内で入力してください。");
                }
                $str2 = strtok(",");

                $dl_data[2] = $str2;
                $str2 = strtok(",");

                $dl_data[3] = $str2;
                $str2 = strtok(",");

                if (defined("CHINESE_FLG") && CHINESE_FLG == 1) {
                    $dl_data[4] = $str2;
                    $str2 = strtok(",");

                    $dl_data[5] = $str2;
                    $str2 = strtok(",");
                }
                if (strlen($dl_data[2]) > 20 || strlen($dl_data[3]) > 20 || (CHINESE_FLG == 1 && (strlen($dl_data[4]) > 20 || strlen($dl_data[5]) > 20))) {
                    $send_error_response("表示は20文字以内で入力してください。");
                }

                $info2[] = $dl_data;
            }
        }

        // ダウンロード回数の追加と更新と削除
        $list2 = $this->MdbMchoice->getList("max_dl_time");
        foreach ($list2 as $key => $val) {
            $id_list2[] = $val["choice_id"];
        }

        $dl_add_memo = "";
        $dl_sort_memo = "";
        $dl_del_memo = "";

        if(isset($this->maxdltime_sort) && $this->maxdltime_sort != ""){
            $sort2 = explode(",",$this->maxdltime_sort);
            // 画面上でのソート(idを,区切り)について、idが「数字」または「#数字」になっているか
            foreach($sort2 as $key => $choiceId2) {
                if (!preg_match("/^#?[0-9]+$/", $choiceId2)) {
                    $send_error_response("ダウンロード回数が不正です。");
                }
            }

            // 削除
            $dl_del_memo = "";
            $del_list2 = array_diff($id_list2,$sort2);
            foreach($del_list2 as $key => $val) {
                $delval2 = $this->MdbMchoice->getChoiceIdList($val,"max_dl_time");
                $this->MdbMchoice->Delete($val);

                // ログ用のメモ
                $delmemo1 = $delval2["value_id"];
                $delmemo2 = $delval2["content"];
                $delmemo3 = $delval2["content_en"];
                $delmemo4 = $delval2["content_cn"];
                $delmemo5 = $delval2["content_tcn"];
                $dl_del_memo .= "ダウンロード回数：回数:" . $delmemo1 . ",日本語:" . $delmemo2 . ",英語:" . $delmemo3;
                if( CHINESE_FLG == 1 ) {
                    $dl_del_memo .= ",簡体字:" . $delmemo4 . ",繁体字:" . $delmemo5;
                }
                $dl_del_memo .= "　を削除" . "\n";
            }

            // 追加と更新
            foreach($sort2 as $key => $choiceId2){
                $sortNo = $key+1;
                if (substr($choiceId2,0,1) == "#") {
                    foreach($info2 as $key2 => $val2) {
                        if ($val2[0] == $choiceId2) {
                            if ($val2[1] == "00") {
                                $val2[1] = "0";
                            }
                            if (CHINESE_FLG == 1) {
                                $this->MdbMchoice->setInsert('max_dl_time',$val2[1],$val2[2],$val2[3],$val2[4],$val2[5],$sortNo);
                            } else {
                                $this->MdbMchoice->setInsert('max_dl_time',$val2[1],$val2[2],$val2[3],'','',$sortNo);
                            }

                            // ログ用のメモ
                            $dl_add_memo .= "ダウンロード回数：回数:" . $val2[1] . ",日本語:" . $val2[2] . ",英語:" . $val2[3];
                            if( CHINESE_FLG == 1 ) {
                                $dl_add_memo .= ",簡体字:" . $val2[4] . ",繁体字:" . $val2[5];
                            }
                            $dl_add_memo .= "　を追加" . "\n";
                        }
                    }
                } else {
                    $sortval = $this->MdbMchoice->getChoiceIdList($choiceId2,"max_dl_time");
                    if ((string)$sortval["sort"] != (string)$sortNo) {
                        $dl_sort_memo = "true";
                    }
                    $this->MdbMchoice->setSortUpdata($choiceId2,$sortNo);
                }
            }
            // 一番上の値を初期値に設定
            $val_list = $this->MdbMchoice->getList("max_dl_time");
            $val_id = $val_list[0]["value_id"];
            $this->MdbConfigs->setValue('maxdltime_mark',$val_id);
        }
        // ダウンロード回数 ここまで

        // ユーザ側に設定のキャッシュファイルを作る
        //$this->MdbConfigs->getList($this->DtoConfigs);
        //$configlist =  $this->DtoConfigs->getConfigsList();
        //$this->MdbConfigs->defineSet($configlist);

        // Log
        $history_id = 112; // 112:各種設定＞機能設定
        $memo  = "";

        if ( $this->sends_flg != $this->session->getParameter("sends_flg")){
            if ($this->sends_flg == "1") {
                $flg = "有効";
            } else {
                $flg = "無効";
            }
            $memo .= "配布：".$flg."\n";
        }

        if ( $this->send_mail_limit != $this->session->getParameter("send_mail_limit")){
            //$aft = $this->send_mail_limit;
            //$bef = $this->session->getParameter("send_mail_limit");
            //$memo .= "最大送信人数：".$bef."→".$aft."\n";
            $memo .= "最大送信人数：".$this->send_mail_limit."\n";
        }

        if ( $this->file_send_limit != $this->session->getParameter("file_send_limit")){
            //$aft = $this->file_send_limit;
            //$bef = $this->session->getParameter("file_send_limit");
            //$memo .= "最大送信ファイル数：".$bef."→".$aft."\n";
            $memo .= "最大送信ファイル数：".$this->file_send_limit."\n";
        }

        if ( ($this->mailer_flg != $this->session->getParameter("mailer_flg"))
             || ($this->mailer_default_flg != $this->session->getParameter("mailer_default_flg"))
             ){
            if ($this->mailer_flg == "1") {
                $flg = "有効";
            } else {
                $flg = "無効";
            }
            $memo .= "メーラー送信：".$flg."\n";
            $options = GlobalArray::getMailer_Default_Flg_Options();
            $memo .= "　".$options[$this->mailer_default_flg]."\n";
        }

        if ( $this->sendcollect_flg != $this->session->getParameter("sendcollect_flg")){
            if ($this->sendcollect_flg == "1") {
                $flg = "有効";
            } else {
                $flg = "無効";
            }
            $memo .= "引取り（収集）同時送信：".$flg."\n";
        }

        if ( $this->sendcollect_mailer_flg != $this->session->getParameter("sendcollect_mailer_flg")){
            if ($this->sendcollect_mailer_flg == "1") {
                $flg = "有効";
            } else {
                $flg = "無効";
            }
            $memo .= "メーラーから引取り（収集）同時送信：".$flg."\n";
        }

        if ( $this->sample_temp_flg != $this->session->getParameter("sample_temp_flg")){
            if ($this->sample_temp_flg == "1") {
                $flg = "初期入力する";
            } else {
                $flg = "初期入力しない";
            }
            $memo .= "　サンプル文：".$flg."\n";
        }

        if ( $this->default_pass_flg != $this->session->getParameter("default_pass_flg")){
            $arr = GlobalArray::getDefault_Pass_Flg_Options();
            $flg = $arr[$this->default_pass_flg];
            $memo .= "パスワードロック：".$flg."\n";
        }

        if ( $this->zip_pass_flg != $this->session->getParameter("zip_pass_flg")){
            $flg = "";
            switch ($this->zip_pass_flg) {
            case 1:
                $flg .= "必ず設定する";
                break;
            case 2:
                $flg .= "任意で設定する";
                break;
            default:
                $flg .= "設定を無効にする";
                break;
            }
            $memo .= "ZIPパスワード：".$flg."\n";
        }

        if ( $this->default_auto_del_flg != $this->session->getParameter("default_auto_del_flg")){
            $arr = GlobalArray::getDefault_Auto_Del_Flg_Options();
            $flg = $arr[$this->default_auto_del_flg];
            $memo .= "ファイルの自動削除：".$flg."\n";
        }

        //ダウンロード有効期限の日数削除
        if ($dt_del_memo){
            $memo .= $dt_del_memo;
        }
        //ダウンロード有効期限の日数追加
        if ($dt_add_memo){
            $memo .= $dt_add_memo;
        }
        //ダウンロード有効期限の並び替え
        if ($dt_sort_memo == "true"){
            $memo .= "ダウンロード有効期限の並べ替えが実行されました"."\n";
        }
        //ダウンロード回数の回数削除
        if ($dl_del_memo){
            $memo .= $dl_del_memo;
        }
        //ダウンロード回数の回数追加
        if ($dl_add_memo){
            $memo .= $dl_add_memo;
        }
        //ダウンロード回数の並び替え
        if ($dl_sort_memo == "true"){
            $memo .= "ダウンロード回数の並べ替えが実行されました"."\n";
        }

        if ($memo) {
            $memo = "[送信機能＞配布に関する設定]\n".$memo;
            $this->DtoLog->setUser_id("");
            $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
            $this->DtoLog->setFile_id("");
            $this->DtoLog->setHistory_id($history_id);
            $this->DtoLog->setMemo($memo);
            $this->MdbLog->Logging($this->DtoLog);
        }

        //sessionを削除
        $this->session->removeParameter("sends_flg");
        $this->session->removeParameter("mailer_flg");
        $this->session->removeParameter("mailer_default_flg");
        $this->session->removeParameter("sendcollect_flg");
        $this->session->removeParameter("sendcollect_mailer_flg");
        $this->session->removeParameter("sample_temp_flg");
        $this->session->removeParameter("default_pass_flg");
        $this->session->removeParameter("zip_pass_flg");
        $this->session->removeParameter("default_auto_del_flg");

        $this->session->removeParameter("send_mail_limit");
        $this->session->removeParameter("file_send_limit");
        $this->session->removeParameter("limitdt_val");
        $this->session->removeParameter("limitdt_content");
        $this->session->removeParameter("limitdt_sort");
        $this->session->removeParameter("limitdt_mark");
        $this->session->removeParameter("maxdltime_val");
        $this->session->removeParameter("maxdltime_content");
        $this->session->removeParameter("maxdltime_sort");
        $this->session->removeParameter("maxdltime_mark");
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
    function setSends_flg(&$sends_flg)
    {
        $this->sends_flg =& $sends_flg;
    }
    function setMailer_flg(&$mailer_flg)
    {
        $this->mailer_flg =& $mailer_flg;
    }
    function setMailer_default_flg(&$mailer_default_flg)
    {
        $this->mailer_default_flg =& $mailer_default_flg;
    }
    function setSendcollect_flg(&$sendcollect_flg)
    {
        $this->sendcollect_flg =& $sendcollect_flg;
    }
    function setSendcollect_mailer_flg(&$sendcollect_mailer_flg)
    {
        $this->sendcollect_mailer_flg =& $sendcollect_mailer_flg;
    }
    function setSample_temp_flg(&$sample_temp_flg)
    {
        $this->sample_temp_flg =& $sample_temp_flg;
    }
    function setDefault_pass_flg(&$default_pass_flg)
    {
        $this->default_pass_flg =& $default_pass_flg;
    }
    function setZip_pass_flg(&$zip_pass_flg)
    {
        $this->zip_pass_flg =& $zip_pass_flg;
    }
    function setDefault_auto_del_flg(&$default_auto_del_flg)
    {
        $this->default_auto_del_flg =& $default_auto_del_flg;
    }
    function setSend_mail_limit(&$send_mail_limit) {
        $this->send_mail_limit =& $send_mail_limit;
    }
    function setFile_send_limit(&$file_send_limit) {
        $this->file_send_limit =& $file_send_limit;
    }
    function setLimitdt_val(&$limitdt_val) {
        $this->limitdt_val =& $limitdt_val;
    }
    function setLimitdt_content(&$limitdt_content) {
        $this->limitdt_content =& $limitdt_content;
    }
    function setLimitdt_sort(&$limitdt_sort) {
        $this->limitdt_sort =& $limitdt_sort;
    }
    function setLimitdt_mark(&$limitdt_mark) {
        $this->limitdt_mark =& $limitdt_mark;
    }
    function setLimitdt_maxsort(&$limitdt_maxsort) {
        $this->limitdt_maxsort =& $limitdt_maxsort;
    }
    function setMaxdltime_val(&$maxdltime_val) {
        $this->maxdltime_val =& $maxdltime_val;
    }
    function setMaxdltime_content(&$maxdltime_content) {
        $this->maxdltime_content =& $maxdltime_content;
    }
    function setMaxdltime_sort(&$maxdltime_sort) {
        $this->maxdltime_sort =& $maxdltime_sort;
    }
    function setMaxdltime_mark(&$maxdltime_mark) {
        $this->maxdltime_mark =& $maxdltime_mark;
    }
    function setMaxdltime_maxsort(&$maxdltime_maxsort) {
        $this->maxdltime_maxsort =& $maxdltime_maxsort;
    }
    function setLimitdt_addlist(&$limitdt_addlist) {
        $this->limitdt_addlist =& $limitdt_addlist;
    }
    function setMaxdltime_addlist(&$maxdltime_addlist) {
        $this->maxdltime_addlist =& $maxdltime_addlist;
    }
    function setToken(&$token) {
        $this->token =& $token;
    }
}
?>

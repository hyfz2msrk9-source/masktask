<?php
/**
 * [機能説明] 各種設定 - 操作設定 - 引取り(収集)に関する設定(完了)
 * @updater     Hiroko Yanai（2020/02/06）期限の_sortバリデーション追加
 *
 * @package     WebFile
 * @version     3.17.0
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Collect_Done {
    var $session;
    var $DtoConfigs;
    var $MdbConfigs;
    var $DtoLog;
    var $MdbLog;
    var $MdbMchoice;
    var $DtoGroups;
    var $MdbGroups;
    var $collect_folder_flg;
    var $default_collectpass_flg;
    var $send_mail_limit;
    var $file_collect_limit;

    var $limitdt_val;
    var $limitdt_content;
    var $limitdt_maxsort;
    var $limitdt_sort;
    var $limitdt_mark;
    var $limitdt_addlist;
    var $collect_mailer_flg;
    var $token;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Collect_Doneが実行されました","Admin_Config_Collect_Done#execute");

        $collect_error_response = function ($message = "不正な値が入力されました。") {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => $message,
                'token_name' => 'CollectEditToken',
                'token_value' => $this->token->getValue('CollectEditToken')
            ]);
            exit;
        };

        // 最大送信人数の設定があるとき
        if (SENDS_COLLECT_MAX_NUMBER != 0) {
            // 最大送信人数の値をsystem.phpにあわせる
            $this->send_mail_limit = SENDS_COLLECT_MAX_NUMBER;
        }

        if ($this->send_mail_limit === "" || $this->send_mail_limit === null) {
            $collect_error_response("最大送信人数が入力されていません。");
        }
        if (!preg_match("/^[0-9]+$/", $this->send_mail_limit)) {
            $collect_error_response("最大送信人数は整数で入力してください。");
        }
        $valMailLimit = (int)$this->send_mail_limit;
        if ($valMailLimit < 1 || $valMailLimit > 9999) {
            $collect_error_response("最大送信人数は1〜9999の範囲で登録してください。");
        }

        if ($this->file_collect_limit === "" || $this->file_collect_limit === null) {
            $collect_error_response("最大送信ファイル数が入力されていません。");
        }
        if (!preg_match("/^[0-9]+$/", $this->file_collect_limit)) {
            $collect_error_response("最大送信ファイル数は整数で入力してください。");
        }
        $valFileLimit = (int)$this->file_collect_limit;
        if ($valFileLimit < 1 || $valFileLimit > 9999) {
            $collect_error_response("最大送信ファイル数は1〜9999の範囲で登録してください。");
        }

        //入力チェック
        if (!array_key_exists($this->collect_folder_flg, GlobalArray::getCollect_Folder_Flg_Options()) ||
            !array_key_exists($this->default_collectpass_flg, GlobalArray::getDefault_CollectPass_Flg_Options()) ||
            !array_key_exists($this->collect_mailer_flg, GlobalArray::getCollect_Mailer_Flg_Options())) $collect_error_response("不正な値が入力されました。");

        //引取り先フォルダ指定機能
        $this->MdbConfigs->setValue('collect_folder_flg',$this->collect_folder_flg);

        //最大送信人数
        $this->MdbConfigs->setValue('send_mail_limit',$this->send_mail_limit);

        //最大収集ファイル数
        $this->MdbConfigs->setValue('file_collect_limit', $this->file_collect_limit);

        //パスワードロック機能
        $this->MdbConfigs->setValue('default_collectpass_flg',$this->default_collectpass_flg);

        // 収集有効期限 ここから
        // 追加分
        if ($this->limitdt_addlist) {
            $str = strtok($this->limitdt_addlist, ",");
            while ($str) {
                $data[0] = $str;
                $str = strtok(",");

                $data[1] = $str;
                if (!preg_match("/^[0-9]+$/", $data[1])) {
                    $collect_error_response("収集有効期限が正しく入力されていません。");
                } else if ((int)$data[1] >= 9999) {
                    $collect_error_response("収集有効期限は9999日以内で入力してください。");
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
                    $collect_error_response("表示は20文字以内で入力してください。");
                }

                $info[] = $data;
            }
        }

        // 収集有効期限の追加と更新と削除
        $list = $this->MdbMchoice->getList("collect_limit_dt");
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
                    $collect_error_response("収集有効期限が正しく入力されていません。");
                }
            }

            // 削除
            $dt_del_memo = "";
            $del_list = array_diff($id_list,$sort);
            foreach($del_list as $key => $val) {
                $delval = $this->MdbMchoice->getChoiceIdList($val,"collect_limit_dt");
                $this->MdbMchoice->Delete($val);

                // ログ用のメモ
                $delmemo1 = $delval["value_id"];
                $delmemo2 = $delval["content"];
                $delmemo3 = $delval["content_en"];
                $delmemo4 = $delval["content_cn"];
                $delmemo5 = $delval["content_tcn"];

                $dt_del_memo .= "収集有効期限：日数:" . $delmemo1 . ",日本語:" . $delmemo2 . ",英語:" . $delmemo3;
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
                                $this->MdbMchoice->setInsert('collect_limit_dt',$val2[1],$val2[2],$val2[3],$val2[4],$val2[5],$sortNo);
                            } else {
                                $this->MdbMchoice->setInsert('collect_limit_dt',$val2[1],$val2[2],$val2[3],'','',$sortNo);
                            }

                            // ログ用のメモ
                            $dt_add_memo .= "収集有効期限：日数:" . $val2[1] . ",日本語:" . $val2[2] . ",英語:" . $val2[3];
                            if( CHINESE_FLG == 1 ) {
                                $dt_add_memo .= ",簡体字:" . $val2[4] . ",繁体字:" . $val2[5];
                            }
                            $dt_add_memo .= "　を追加" . "\n";
                        }
                    }
                } else {
                    $sortval = $this->MdbMchoice->getChoiceIdList($choiceId,"collect_limit_dt");
                    if ((string)$sortval["sort"] != (string)$sortNo) {
                        $dt_sort_memo = "true";
                    }
                    $this->MdbMchoice->setSortUpdata($choiceId,$sortNo);
                }
            }
            // 一番上の値を初期値に設定
            $val_list = $this->MdbMchoice->getList("collect_limit_dt");
            $val_id = $val_list[0]["value_id"];
            $this->MdbConfigs->setValue('collect_limitdt_mark',$val_id);
        }
        // 収集有効期限 ここまで

        // メーラーからの引取機能
        $this->MdbConfigs->setValue('collect_mailer_flg',$this->collect_mailer_flg);

        // Log
        $history_id = 112; // 112:各種設定＞機能設定
        $memo  = "";
        if ( $this->collect_folder_flg != $this->session->getParameter("collect_folder_flg")){
            $flg = "";
            switch ($this->collect_folder_flg) {
                case 1:
                        $flg .= "全てのフォルダを指定可能にする";
                        break;
                case 2:
                        $flg .= "個人フォルダのみ指定可能にする";
                        break;
                case 3:
                        $flg .= "共有フォルダのみ指定可能にする";
                        break;
                default:
                        $flg .= "設定を無効にする";
                        break;
            }
            $memo .= "引取り先フォルダ指定：".$flg."\n";
        }
        if ( $this->send_mail_limit != $this->session->getParameter("send_mail_limit")){
            $memo .= "最大送信人数：".$this->send_mail_limit."\n";
        }
        if ( $this->file_collect_limit != $this->session->getParameter("file_collect_limit")){
            $memo .= "最大収集ファイル数：".$this->file_collect_limit."\n";
        }

        if ( $this->default_collectpass_flg != $this->session->getParameter("default_collectpass_flg")){
            if ($this->default_collectpass_flg == "1") {
                $flg = "ワンタイムパスを必ず設定する";
            } else if ($this->default_collectpass_flg == "2") {
                $flg = "指定パスを必ず設定する";
            } else {
                $flg = "自由に設定する";
            }
            $memo .= "パスワードロック（収集）：".$flg."\n";
        }

        if(isset($this->limitdt_sort) && $this->limitdt_sort != ""){
            //収集有効期限の日数削除
            if ($dt_del_memo){
                $memo .= $dt_del_memo;
            }
            //収集有効期限の日数追加
            if ($dt_add_memo){
                $memo .= $dt_add_memo;
            }
            //収集有効期限の並び替え
            if ($dt_sort_memo == "true"){
                $memo .= "収集有効期限の並べ替えが実行されました"."\n";
            }
        }

        if ($this->collect_mailer_flg != $this->session->getParameter("collect_mailer_flg")) {
            $flg = ($this->collect_mailer_flg == '1') ? "有効" : "無効";
            $memo .= "メーラーからの引取り送信機能：".$flg."\n";
        }

        if ($memo) {
            $memo = "[送信機能＞引取り(収集)に関する設定]\n".$memo;
            $this->DtoLog->setUser_id("");
            $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
            $this->DtoLog->setFile_id("");
            $this->DtoLog->setHistory_id($history_id);
            $this->DtoLog->setMemo($memo);
            $this->MdbLog->Logging($this->DtoLog);
        }

        //sessionを削除
        $this->session->removeParameter("mailer_flg");
        $this->session->removeParameter("collect_folder_flg");
        $this->session->removeParameter("default_collectpass_flg");

        $this->session->removeParameter("send_mail_limit");
        $this->session->removeParameter("file_collect_limit");
        $this->session->removeParameter("limitdt_val");
        $this->session->removeParameter("limitdt_content");
        $this->session->removeParameter("limitdt_sort");
        $this->session->removeParameter("limitdt_mark");
        $this->session->removeParameter("collect_limitdt_mark");
        $this->session->removeParameter("collect_mailer_flg");

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
    function setCollect_folder_flg(&$collect_folder_flg)
    {
        $this->collect_folder_flg =& $collect_folder_flg;
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
    function setLimitdt_addlist(&$limitdt_addlist) {
        $this->limitdt_addlist =& $limitdt_addlist;
    }
    function setDefault_collectpass_flg(&$default_collectpass_flg)
    {
        $this->default_collectpass_flg =& $default_collectpass_flg;
    }
    function setSend_mail_limit(&$send_mail_limit) {
        $this->send_mail_limit =& $send_mail_limit;
    }
    function setFile_collect_limit(&$file_collect_limit) {
        $this->file_collect_limit =& $file_collect_limit;
    }
    function setCollect_mailer_flg($collect_mailer_flg)
    {
        $this->collect_mailer_flg = $collect_mailer_flg;
    }
    function setToken($token) {
        $this->token = $token;
    }
}
?>

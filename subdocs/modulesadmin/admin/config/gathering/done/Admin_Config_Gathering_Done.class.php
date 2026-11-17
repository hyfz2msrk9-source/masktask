<?php

require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Gathering_Done {
    var $session;
    var $DtoConfigs;
    var $MdbConfigs;
    var $DtoLog;
    var $MdbLog;
    var $MdbMchoice;
    var $DtoGroups;
    var $MdbGroups;

    var $gathering_flg;
    var $gathering_folder_flg;
    var $gathering_sample_temp_flg;
    var $gathering_default_pass_flg;
    var $gathering_notice_pass_flg;
    var $gathering_mail_limit;
    var $gathering_file_limit;

    var $limitdt_val;
    var $limitdt_content;
    var $limitdt_maxsort;
    var $limitdt_sort;
    var $limitdt_mark;
    var $limitdt_addlist;
    var $token;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Gathering_Doneが実行されました", "Admin_Config_Gathering_Done#execute");

        $gathering_error_response = function ($message = "不正な値が入力されました。") {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => $message,
                'token_name' => 'GatheringEditToken',
                'token_value' => $this->token->getValue('GatheringEditToken')
            ]);
            exit;
        };

        if (!defined('GATHERING_FLG') || !GATHERING_FLG) {
            $gathering_error_response("一斉収集機能は、現在ご利用いただけません。");
        }

        // 入力チェック
        if (!array_key_exists($this->gathering_flg, GlobalArray::getGathering_Flg_Options()) ||
            !array_key_exists($this->gathering_folder_flg, GlobalArray::getGathering_Folder_Flg_Options()) ||
            !array_key_exists($this->gathering_sample_temp_flg, GlobalArray::getGathering_Sample_Temp_Flg_Options()) ||
            !array_key_exists($this->gathering_default_pass_flg, GlobalArray::getGathering_Default_Pass_Flg_Options()) ||
            !array_key_exists($this->gathering_notice_pass_flg, GlobalArray::getGathering_Notice_Pass_Flg_Options())) {
            $gathering_error_response("不正な値が入力されました。");
        }
        // 最大送信人数
        if ($this->gathering_mail_limit === "" || $this->gathering_mail_limit === null) {
            $gathering_error_response("最大送信人数が入力されていません。");
        }
        if (!preg_match("/^[0-9]+$/", $this->gathering_mail_limit)) {
            $gathering_error_response("最大送信人数は整数で入力してください。");
        }
        $valMailLimit = (int)$this->gathering_mail_limit;
        if ($valMailLimit < 1 || $valMailLimit > 9999) {
            $gathering_error_response("最大送信人数は1〜9999の範囲で登録してください。");
        }
        if ($this->gathering_file_limit === "" || $this->gathering_file_limit === null) {
            $gathering_error_response("最大送信ファイル数が入力されていません。");
        }
        if (!preg_match("/^[0-9]+$/", $this->gathering_file_limit)) {
            $gathering_error_response("最大送信ファイル数は整数で入力してください。");
        }
        $valFileLimit = (int)$this->gathering_file_limit;
        if ($valFileLimit < 1 || $valFileLimit > 9999) {
            $gathering_error_response("最大送信ファイル数は1〜9999の範囲で登録してください。");
        }
        // 一斉収集機能
        $this->MdbConfigs->setValue('gathering_flg', $this->gathering_flg);
        //収集先フォルダ指定機能
        $this->MdbConfigs->setValue('gathering_folder_flg',$this->gathering_folder_flg);
        
        if (GATHERING_MAIL_MAX_LIMIT != 0) {
            // 最大値チェック（最小値チェックはmaple.ini[validate]で設定）
            if (GATHERING_MAIL_MAX_LIMIT < $this->gathering_mail_limit) {
                $gathering_error_response("最大送信人数の値は、" . GATHERING_MAIL_MAX_LIMIT . "以下でご入力ください。");
            } else {
                $this->MdbConfigs->setValue('gathering_mail_limit', $this->gathering_mail_limit);
            }
        } else {
            $this->MdbConfigs->setValue('gathering_mail_limit', $this->gathering_mail_limit);
        }
        // 最大送信ファイル数
        if (GATHERING_FILE_MAX_LIMIT != 0) {
            // 最大値チェック（最小値チェックはmaple.ini[validate]で設定）
            if (GATHERING_FILE_MAX_LIMIT < $this->gathering_file_limit) {
                $gathering_error_response("最大送信ファイル数の値は、" . GATHERING_FILE_MAX_LIMIT . "以下でご入力ください。");
            } else {
                $this->MdbConfigs->setValue('gathering_file_limit', $this->gathering_file_limit);
            }
        } else {
            $this->MdbConfigs->setValue('gathering_file_limit', $this->gathering_file_limit);
        }
        // サンプル文初期入力機能
        $this->MdbConfigs->setValue('gathering_sample_temp_flg', $this->gathering_sample_temp_flg);
        // パスワードロック機能
        $this->MdbConfigs->setValue('gathering_default_pass_flg', $this->gathering_default_pass_flg);
        // 指定パスワード通知機能
        $this->MdbConfigs->setValue('gathering_notice_pass_flg', $this->gathering_notice_pass_flg);

        // アップロード有効期限
        if ($this->limitdt_addlist) {
            $str = strtok($this->limitdt_addlist, ",");
            while ($str) {
                $data[0] = $str;
                $str = strtok(",");

                $data[1] = $str;
                if (!preg_match("/^[0-9]+$/", $data[1])) {
                    $gathering_error_response("アップロード有効期限が正しく入力されていません。");
                } else if ((int)$data[1] >= 9999) {
                    $gathering_error_response("アップロード有効期限は9999日以内で入力してください。");
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
                    $gathering_error_response("表示は20文字以内で入力してください。");
                }

                $info[] = $data;
            }
        }

        // アップロード有効期限の追加と更新と削除
        $list = $this->MdbMchoice->getList("gathering_limit_dt");
        foreach ($list as $key => $val) {
            $id_list[] = $val["choice_id"];
        }

        $dt_add_memo = "";
        $dt_sort_memo = "";
        $dt_del_memo = "";

        if (isset($this->limitdt_sort) && $this->limitdt_sort != "") {
            $sort = explode(",", $this->limitdt_sort);
            // 画面上でのソート(idを,区切り)について、idが「数字」または「#数字」になっているか
            foreach($sort as $key => $choiceId) {
                if (!preg_match("/^#?[0-9]+$/", $choiceId)) {
                    $gathering_error_response("アップロード有効期限が正しく入力されていません。");
                }
            }

            // 削除
            $dt_del_memo = "";
            $del_list = array_diff($id_list, $sort);
            foreach ($del_list as $key => $val) {
                $delval = $this->MdbMchoice->getChoiceIdList($val, "gathering_limit_dt");
                $this->MdbMchoice->Delete($val);

                // ログ用のメモ
                $delmemo1 = $delval["value_id"];
                $delmemo2 = $delval["content"];
                $delmemo3 = $delval["content_en"];
                $delmemo4 = $delval["content_cn"];
                $delmemo5 = $delval["content_tcn"];
                $dt_del_memo .= "アップロード有効期限：日数:" . $delmemo1 . ",日本語:" . $delmemo2 . ",英語:" . $delmemo3;
                if (CHINESE_FLG == 1) {
                    $dt_del_memo .= ",簡体字:" . $delmemo4 . ",繁体字:" . $delmemo5;
                }
                $dt_del_memo .= "　を削除" . "\n";
            }

            // 追加と更新
            foreach ($sort as $key => $choiceId) {
                $sortNo = $key+1;
                if (substr($choiceId, 0, 1) == "#") {
                    foreach ($info as $key2 => $val2) {
                        if ($val2[0] == $choiceId) {
                            if ($val2[1] == "00") {
                                $val2[1] = "0";
                            }
                            if (CHINESE_FLG == 1) {
                                $this->MdbMchoice->setInsert('gathering_limit_dt', $val2[1], $val2[2], $val2[3], $val2[4], $val2[5], $sortNo);
                            } else {
                                $this->MdbMchoice->setInsert('gathering_limit_dt', $val2[1], $val2[2], $val2[3], '', '', $sortNo);
                            }

                            // ログ用のメモ
                            $dt_add_memo .= "アップロード有効期限：日数:" . $val2[1] . ",日本語:" . $val2[2] . ",英語:" . $val2[3];
                            if (CHINESE_FLG == 1) {
                                $dt_add_memo .= ",簡体字:" . $val2[4] . ",繁体字:" . $val2[5];
                            }
                            $dt_add_memo .= "　を追加" . "\n";
                        }
                    }
                } else {
                    $sortval = $this->MdbMchoice->getChoiceIdList($choiceId, "gathering_limit_dt");
                    if ((string)$sortval["sort"] != (string)$sortNo) {
                        $dt_sort_memo = "true";
                    }
                    $this->MdbMchoice->setSortUpdata($choiceId, $sortNo);
                }
            }

            // 一番上の値を初期値に設定
            $val_list = $this->MdbMchoice->getList("gathering_limit_dt");
            $val_id = $val_list[0]["value_id"];
            $this->MdbConfigs->setValue('gathering_limitdt_mark', $val_id);
        }

        // ログ
        $history_id = 112; // 112:各種設定＞機能設定
        $memo = "";

        if ($this->gathering_flg != $this->session->getParameter("gathering_flg")) {
            if ($this->gathering_flg == "1") {
                $flg = "有効";
            } else {
                $flg = "無効";
            }
            $memo .= "一斉収集：" . $flg . "\n";
        }

        if ($this->gathering_mail_limit != $this->session->getParameter("gathering_mail_limit")) {
            $memo .= "最大送信人数：" . $this->gathering_mail_limit . "\n";
        }

        if ($this->gathering_file_limit != $this->session->getParameter("gathering_file_limit")) {
            $memo .= "最大送信ファイル数：" . $this->gathering_file_limit . "\n";
        }

        if ($this->gathering_sample_temp_flg != $this->session->getParameter("gathering_sample_temp_flg")) {
            if ($this->gathering_sample_temp_flg == "1") {
                $flg = "初期入力する";
            } else {
                $flg = "初期入力しない";
            }
            $memo .= "サンプル文：" . $flg . "\n";
        }

        if ($this->gathering_default_pass_flg != $this->session->getParameter("gathering_default_pass_flg")) {
            $arr = GlobalArray::getGathering_Default_Pass_Flg_Options();
            $flg = $arr[$this->gathering_default_pass_flg];
            $memo .= "パスワードロック：" . $flg . "\n";
        }

        if ($this->gathering_notice_pass_flg != $this->session->getParameter("gathering_notice_pass_flg")) {
            if ($this->gathering_notice_pass_flg == "1") {
                $flg = "有効";
            } else {
                $flg = "無効";
            }
            $memo .= "パスワード通知：" . $flg . "\n";
        }

        // ダウンロード有効期限の削除
        if ($dt_del_memo) {
            $memo .= $dt_del_memo;
        }
        // ダウンロード有効期限の追加
        if ($dt_add_memo) {
            $memo .= $dt_add_memo;
        }
        // ダウンロード有効期限の並び替え
        if ($dt_sort_memo == "true") {
            $memo .= "ダウンロード有効期限の並べ替えが実行されました" . "\n";
        }

        if ($memo) {
            $memo = "[送信機能＞一斉収集に関する設定]\n" . $memo;
            $this->DtoLog->setUser_id("");
            $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
            $this->DtoLog->setFile_id("");
            $this->DtoLog->setHistory_id($history_id);
            $this->DtoLog->setMemo($memo);
            $this->MdbLog->Logging($this->DtoLog);
        }

        // sessionを削除
        $this->session->removeParameter('gathering_flg');
        $this->session->removeParameter('gathering_mail_limit');
        $this->session->removeParameter('gathering_file_limit');
        $this->session->removeParameter('gathering_sample_temp_flg');
        $this->session->removeParameter('gathering_default_pass_flg');
        $this->session->removeParameter('gathering_notice_pass_flg');
        $this->session->removeParameter('gathering_limitdt_mark');

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
    function setDtoConfigs($DtoConfigs) {
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
    function setDtoGroups($DtoGroups) {
        $this->DtoGroups = $DtoGroups;
    }
    function setMdbGroups($MdbGroups) {
        $this->MdbGroups = $MdbGroups;
    }

    function setGathering_Flg($gathering_flg) {
        $this->gathering_flg = $gathering_flg;
    }
    function setGathering_folder_flg(&$gathering_folder_flg) {
        $this->gathering_folder_flg =& $gathering_folder_flg;
    }
    function setGathering_Mail_Limit(&$gathering_mail_limit) {
        $this->gathering_mail_limit =& $gathering_mail_limit;
    }
    function setGathering_file_limit(&$gathering_file_limit) {
        $this->gathering_file_limit =& $gathering_file_limit;
    }
    function setGathering_Sample_Temp_Flg(&$gathering_sample_temp_flg) {
        $this->gathering_sample_temp_flg =& $gathering_sample_temp_flg;
    }
    function setGathering_Default_Pass_Flg(&$gathering_default_pass_flg) {
        $this->gathering_default_pass_flg =& $gathering_default_pass_flg;
    }
    function setGathering_Notice_Pass_Flg(&$gathering_notice_pass_flg) {
        $this->gathering_notice_pass_flg =& $gathering_notice_pass_flg;
    }
    function setLimitdt_Val(&$limitdt_val) {
        $this->limitdt_val =& $limitdt_val;
    }
    function setLimitdt_Content(&$limitdt_content) {
        $this->limitdt_content =& $limitdt_content;
    }
    function setLimitdt_Sort(&$limitdt_sort) {
        $this->limitdt_sort =& $limitdt_sort;
    }
    function setLimitdt_Mark(&$limitdt_mark) {
        $this->limitdt_mark =& $limitdt_mark;
    }
    function setLimitdt_Maxsort(&$limitdt_maxsort) {
        $this->limitdt_maxsort =& $limitdt_maxsort;
    }
    function setLimitdt_Addlist(&$limitdt_addlist) {
        $this->limitdt_addlist =& $limitdt_addlist;
    }
    function setToken($token) {
        $this->token = $token;
    }
}
?>

<?php
/**
 * [機能説明] 各種設定 - 操作設定 - 一斉配信に関する設定(完了)
 * @updater     Hiroko Yanai（2020/02/06）期限と回数の_sortバリデーション追加
 *
 * @package     WebFile
 * @version     3.17.0
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Delivery_Done {
    var $session;
    var $DtoConfigs;
    var $MdbConfigs;
    var $DtoLog;
    var $MdbLog;
    var $MdbMchoice;
    var $DtoGroups;
    var $MdbGroups;

    var $delivery_flg;
    var $delivery_nofile_flg;
    var $delivery_send_flg;
    var $delivery_sample_temp_flg;
    var $default_deliverypass_flg;
    var $zip_deliverypass_flg;
    var $password_notice_flg;
    var $delivery_mail_limit;
    var $file_delivery_limit;
    var $delivery_schedule_span_limit; //送信予定日時の上限

    var $limitdt_val;
    var $limitdt_content;
    var $limitdt_maxsort;
    var $limitdt_sort;
    var $limitdt_mark;
    var $limitdt_addlist;
    var $maxdltime_val;
    var $maxdltime_content;
    var $maxdltime_maxsort;
    var $maxdltime_sort;
    var $maxdltime_mark;
    var $maxdltime_addlist;
    var $token;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Delivery_Doneが実行されました", "Admin_Config_Delivery_Done#execute");

        $delivery_error_response = function ($message = "不正な値が入力されました。") {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => $message,
                'token_name' => 'DeliveryEditToken',
                'token_value' => $this->token->getValue('DeliveryEditToken')
            ]);
            exit;
        };


        if (!defined('DELIVERY_FLG') || !DELIVERY_FLG) {
            $delivery_error_response("一斉配信機能は、現在ご利用いただけません。");
        }
        if ($this->delivery_mail_limit === "" || $this->delivery_mail_limit === null) {
            $delivery_error_response("最大送信人数が入力されていません。");
        }
        if (!preg_match("/^[0-9]+$/", $this->delivery_mail_limit)) {
            $delivery_error_response("最大送信人数は整数で入力してください。");
        }
        $valMailLimit = (int)$this->delivery_mail_limit;
        if ($valMailLimit < 1 || $valMailLimit > 9999) {
            $delivery_error_response("最大送信人数は1〜9999の範囲で登録してください。");
        }

        if ($this->file_delivery_limit === "" || $this->file_delivery_limit === null) {
            $delivery_error_response("最大送信ファイル数が入力されていません。");
        }
        if (!preg_match("/^[0-9]+$/", $this->file_delivery_limit)) {
            $delivery_error_response("最大送信ファイル数は整数で入力してください。");
        }
        $valFileLimit = (int)$this->file_delivery_limit;
        if ($valFileLimit < 1 || $valFileLimit > 9999) {
            $delivery_error_response("最大送信ファイル数は1〜9999の範囲で登録してください。");
        }
        if ($this->delivery_schedule_span_limit === "" || $this->delivery_schedule_span_limit === null) {
            $delivery_error_response("送信予定日時が入力されていません。");
        }
        if (!preg_match("/^[0-9]+$/", $this->delivery_schedule_span_limit)) {
            $delivery_error_response("送信予定日時は整数で入力してください。");
        }
        $valMailLimit = (int)$this->delivery_schedule_span_limit;
        if ($valMailLimit > 3653) {
            $delivery_error_response("送信予定日時は3653以下で登録してください。");
        }

        // 入力チェック
        if (!array_key_exists($this->delivery_flg, GlobalArray::getDelivery_Flg_Options()) ||
            !preg_match("/^[0-1]{0,1}$/", $this->delivery_nofile_flg) ||
            !preg_match("/^[0-1]{0,1}$/", $this->delivery_send_flg) ||
            !array_key_exists($this->delivery_sample_temp_flg, GlobalArray::getDelivery_Sample_Temp_Flg_Options()) ||
            !array_key_exists($this->default_deliverypass_flg, GlobalArray::getDefault_DeliveryPass_Flg_Options())) {
            $delivery_error_response("不正な値が入力されました。");
        }
        if (ZIPDL_TYPE == 1) {
            if (!array_key_exists($this->zip_deliverypass_flg, GlobalArray::getZip_DeliveryPass_Flg_Options())) {
                $delivery_error_response("不正な値が入力されました。");
            }
        }
        if (!array_key_exists($this->password_notice_flg, GlobalArray::getPassword_Notice_Flg_Options())) {
            $delivery_error_response("不正な値が入力されました。");
        }

        // 一斉配信機能
        $this->MdbConfigs->setValue('delivery_flg', $this->delivery_flg);

        // 配信の種類
        if (!$this->delivery_nofile_flg && !$this->delivery_send_flg) {
            $delivery_error_response("配信の種類は、1つ以上ご選択ください。");
        } else {
            $this->MdbConfigs->setValue('delivery_nofile_flg', $this->delivery_nofile_flg);
            // 配布機能が無効の場合は、一斉配信の配布も無効
            if ($this->MdbConfigs->getDirectValue("sends_flg")) {
                $this->MdbConfigs->setValue('delivery_send_flg', $this->delivery_send_flg);
            }
        }

        // 最大送信人数
        if (DELIVERY_MAIL_MAX_LIMIT != 0) {
            // 最大値チェック（最小値チェックはmaple.ini[validate]で設定）
            if (DELIVERY_MAIL_MAX_LIMIT < $this->delivery_mail_limit) {
                $delivery_error_response("最大送信人数の値は、" . DELIVERY_MAIL_MAX_LIMIT . "以下でご入力ください。");
            } else {
                $this->MdbConfigs->setValue('delivery_mail_limit', $this->delivery_mail_limit);
            }
        } else {
            $this->MdbConfigs->setValue('delivery_mail_limit', $this->delivery_mail_limit);
        }

        // 最大送信ファイル数
        if (DELIVERY_FILE_MAX_LIMIT != 0) {
            // 最大値チェック（最小値チェックはmaple.ini[validate]で設定）
            if (DELIVERY_FILE_MAX_LIMIT < $this->file_delivery_limit) {
                $delivery_error_response("最大送信ファイル数の値は、" . DELIVERY_FILE_MAX_LIMIT . "以下でご入力ください。");
            } else {
                $this->MdbConfigs->setValue('file_delivery_limit', $this->file_delivery_limit);
            }
        } else {
            $this->MdbConfigs->setValue('file_delivery_limit', $this->file_delivery_limit);
        }

        //送信予定日時の上限
        $this->MdbConfigs->setValue('delivery_schedule_span_limit', $this->delivery_schedule_span_limit);

        // サンプル文初期入力機能
        $this->MdbConfigs->setValue('delivery_sample_temp_flg', $this->delivery_sample_temp_flg);

        // パスワードロック機能
        $this->MdbConfigs->setValue('default_deliverypass_flg', $this->default_deliverypass_flg);

        // ZIPパスワード機能
        if (ZIPDL_TYPE == 1) {
            $this->MdbConfigs->setValue('zip_deliverypass_flg', $this->zip_deliverypass_flg);
        } else {
            // winなら無効をセット
            $this->MdbConfigs->setValue('zip_deliverypass_flg', '0');
        }

        // 指定・ZIPパスワード通知機能
        $this->MdbConfigs->setValue('password_notice_flg', $this->password_notice_flg);

        // ダウンロード有効期限 ここから
        // 追加分
        if ($this->limitdt_addlist) {
            $str = strtok($this->limitdt_addlist, ",");
            while ($str) {
                $data[0] = $str;
                $str = strtok(",");

                $data[1] = $str;
                if (!preg_match("/^[0-9]+$/", $data[1])) {
                    $delivery_error_response("有効期限の値が不正です。");
                } else if ((int)$data[1] >= 9999) {
                    $delivery_error_response("有効期限は9999日以内で入力してください。");
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
                    $delivery_error_response("表示は20文字以内で入力してください。");
                }

                $info[] = $data;
            }
        }

        // ダウンロード有効期限の追加と更新と削除
        $list = $this->MdbMchoice->getList("delivery_limit_dt");
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
                    $delivery_error_response("有効期限の値が不正です。");
                }
            }

            // 削除
            $dt_del_memo = "";
            $del_list = array_diff($id_list, $sort);
            foreach ($del_list as $key => $val) {
                $delval = $this->MdbMchoice->getChoiceIdList($val, "delivery_limit_dt");
                $this->MdbMchoice->Delete($val);

                // ログ用のメモ
                $delmemo1 = $delval["value_id"];
                $delmemo2 = $delval["content"];
                $delmemo3 = $delval["content_en"];
                $delmemo4 = $delval["content_cn"];
                $delmemo5 = $delval["content_tcn"];
                $dt_del_memo .= "ダウンロード有効期限：日数:" . $delmemo1 . ",日本語:" . $delmemo2 . ",英語:" . $delmemo3;
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
                                $this->MdbMchoice->setInsert('delivery_limit_dt', $val2[1], $val2[2], $val2[3], $val2[4], $val2[5], $sortNo);
                            } else {
                                $this->MdbMchoice->setInsert('delivery_limit_dt', $val2[1], $val2[2], $val2[3], '', '', $sortNo);
                            }

                            // ログ用のメモ
                            $dt_add_memo .= "ダウンロード有効期限：日数:" . $val2[1] . ",日本語:" . $val2[2] . ",英語:" . $val2[3];
                            if (CHINESE_FLG == 1) {
                                $dt_add_memo .= ",簡体字:" . $val2[4] . ",繁体字:" . $val2[5];
                            }
                            $dt_add_memo .= "　を追加" . "\n";
                        }
                    }
                } else {
                    $sortval = $this->MdbMchoice->getChoiceIdList($choiceId, "delivery_limit_dt");
                    if ((string)$sortval["sort"] != (string)$sortNo) {
                        $dt_sort_memo = "true";
                    }
                    $this->MdbMchoice->setSortUpdata($choiceId, $sortNo);
                }
            }

            // 一番上の値を初期値に設定
            $val_list = $this->MdbMchoice->getList("delivery_limit_dt");
            $val_id = $val_list[0]["value_id"];
            $this->MdbConfigs->setValue('delivery_limitdt_mark', $val_id);
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
                    $delivery_error_response("ダウンロード回数の値が不正です。");
                } else if ((int)$dl_data[1] >= 9999) {
                        $delivery_error_response("ダウンロード回数は9999以内で入力してください。");
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
                    $delivery_error_response("表示は20文字以内で入力してください。");
                }


                $info2[] = $dl_data;
            }
        }

        // ダウンロード回数の追加と更新と削除
        $list2 = $this->MdbMchoice->getList("delivery_max_dl_time");
        foreach ($list2 as $key => $val) {
            $id_list2[] = $val["choice_id"];
        }

        $dl_add_memo = "";
        $dl_sort_memo = "";
        $dl_del_memo = "";

        if (isset($this->maxdltime_sort) && $this->maxdltime_sort != "") {
            $sort2 = explode(",", $this->maxdltime_sort);
            // 画面上でのソート(idを,区切り)について、idが「数字」または「#数字」になっているか
            foreach($sort2 as $key => $choiceId2) {
                if (!preg_match("/^#?[0-9]+$/", $choiceId2)) {
                    $delivery_error_response("ダウンロード回数の値が不正です。");
                }
            }

            // 削除
            $dl_del_memo = "";
            $del_list2 = array_diff($id_list2, $sort2);
            foreach ($del_list2 as $key => $val) {
                $delval2 = $this->MdbMchoice->getChoiceIdList($val, "delivery_max_dl_time");
                $this->MdbMchoice->Delete($val);

                // ログ用のメモ
                $delmemo1 = $delval2["value_id"];
                $delmemo2 = $delval2["content"];
                $delmemo3 = $delval2["content_en"];
                $delmemo4 = $delval2["content_cn"];
                $delmemo5 = $delval2["content_tcn"];
                $dl_del_memo .= "ダウンロード回数：回数:" . $delmemo1 . ",日本語:" . $delmemo2 . ",英語:" . $delmemo3;
                if (CHINESE_FLG == 1) {
                    $dl_del_memo .= ",簡体字:" . $delmemo4 . ",繁体字:" . $delmemo5;
                }
                $dl_del_memo .= "　を削除" . "\n";
            }

            // 追加と更新
            foreach ($sort2 as $key => $choiceId2) {
                $sortNo = $key+1;
                if (substr($choiceId2,0,1) == "#") {
                    foreach ($info2 as $key2 => $val2) {
                        if ($val2[0] == $choiceId2) {
                            if ($val2[1] == "00") {
                                $val2[1] = "0";
                            }
                            if (CHINESE_FLG == 1) {
                                $this->MdbMchoice->setInsert('delivery_max_dl_time', $val2[1],$val2[2], $val2[3], $val2[4], $val2[5], $sortNo);
                            } else {
                                $this->MdbMchoice->setInsert('delivery_max_dl_time', $val2[1],$val2[2], $val2[3], '', '', $sortNo);
                            }

                            // ログ用のメモ
                            $dl_add_memo .= "ダウンロード回数：回数:" . $val2[1] . ",日本語:" . $val2[2] . ",英語:" . $val2[3];
                            if (CHINESE_FLG == 1) {
                                $dl_add_memo .= ",簡体字:" . $val2[4] . ",繁体字:" . $val2[5];
                            }
                            $dl_add_memo .= "　を追加" . "\n";
                        }
                    }
                } else {
                    $sortval = $this->MdbMchoice->getChoiceIdList($choiceId2, "delivery_max_dl_time");
                    if ((string)$sortval["sort"] != (string)$sortNo) {
                        $dl_sort_memo = "true";
                    }
                    $this->MdbMchoice->setSortUpdata($choiceId2, $sortNo);
                }
            }
            // 一番上の値を初期値に設定
            $val_list = $this->MdbMchoice->getList("delivery_max_dl_time");
            $val_id = $val_list[0]["value_id"];
            $this->MdbConfigs->setValue('delivery_maxdltime_mark', $val_id);
        }
        // ダウンロード回数 ここまで

        // ログ
        $history_id = 112; // 112:各種設定＞機能設定
        $memo = "";

        if ($this->delivery_flg != $this->session->getParameter("delivery_flg")) {
            if ($this->delivery_flg == "1") {
                $flg = "有効";
            } else {
                $flg = "無効";
            }
            $memo .= "一斉配信：" . $flg . "\n";
        }

        if ($this->delivery_mail_limit != $this->session->getParameter("delivery_mail_limit")) {
            $memo .= "最大送信人数：" . $this->delivery_mail_limit . "\n";
        }

        if ($this->file_delivery_limit != $this->session->getParameter("file_delivery_limit")) {
            $memo .= "最大送信ファイル数：" . $this->file_delivery_limit . "\n";
        }

        if ($this->delivery_schedule_span_limit != $this->session->getParameter("delivery_schedule_span_limit")) {
            if ($this->delivery_schedule_span_limit === '0') {
                $memo .= "送信予定日時の上限：無期限\n";
            } else {
                $memo .= "送信予定日時の上限：" . $this->delivery_schedule_span_limit . "\n";
            }
        }

        if ($this->delivery_sample_temp_flg != $this->session->getParameter("delivery_sample_temp_flg")) {
            if ($this->delivery_sample_temp_flg == "1") {
                $flg = "初期入力する";
            } else {
                $flg = "初期入力しない";
            }
            $memo .= "サンプル文：" . $flg . "\n";
        }

        if ($this->default_deliverypass_flg != $this->session->getParameter("default_deliverypass_flg")) {
            $arr = GlobalArray::getDefault_DeliveryPass_Flg_Options();
            $flg = $arr[$this->default_deliverypass_flg];
            $memo .= "パスワードロック：" . $flg . "\n";
        }

        if ($this->zip_deliverypass_flg != $this->session->getParameter("zip_deliverypass_flg")) {
            $flg = "";
            switch ($this->zip_deliverypass_flg) {
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
            $memo .= "ZIPパスワード：" . $flg . "\n";
        }

        if ($this->password_notice_flg != $this->session->getParameter("password_notice_flg")) {
            if ($this->password_notice_flg == "1") {
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
        // ダウンロード回数の削除
        if ($dl_del_memo) {
            $memo .= $dl_del_memo;
        }
        // ダウンロード回数の追加
        if ($dl_add_memo) {
            $memo .= $dl_add_memo;
        }
        // ダウンロード回数の並び替え
        if ($dl_sort_memo == "true") {
            $memo .= "ダウンロード回数の並べ替えが実行されました" . "\n";
        }

        if ($memo) {
            $memo = "[操作＞一斉配信に関する設定]\n" . $memo;
            $this->DtoLog->setUser_id("");
            $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
            $this->DtoLog->setFile_id("");
            $this->DtoLog->setHistory_id($history_id);
            $this->DtoLog->setMemo($memo);
            $this->MdbLog->Logging($this->DtoLog);
        }

        // sessionを削除
        $this->session->removeParameter('delivery_flg');
        $this->session->removeParameter('delivery_nofile_flg');
        $this->session->removeParameter('delivery_send_flg');
        $this->session->removeParameter('delivery_mail_limit');
        $this->session->removeParameter('file_delivery_limit');
        $this->session->removeParameter('delivery_schedule_span_limit');
        $this->session->removeParameter('delivery_sample_temp_flg');
        $this->session->removeParameter('default_deliverypass_flg');
        $this->session->removeParameter('zip_deliverypass_flg');
        $this->session->removeParameter('password_notice_flg');
        $this->session->removeParameter('delivery_limitdt_mark');
        $this->session->removeParameter('delivery_maxdltime_mark');

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

    function setDelivery_Flg($delivery_flg) {
        $this->delivery_flg = $delivery_flg;
    }
    function setDelivery_Nofile_Flg($delivery_nofile_flg) {
        $this->delivery_nofile_flg = $delivery_nofile_flg;
    }
    function setDelivery_Send_Flg($delivery_send_flg) {
        $this->delivery_send_flg = $delivery_send_flg;
    }
    function setDelivery_Mail_Limit(&$delivery_mail_limit) {
        $this->delivery_mail_limit =& $delivery_mail_limit;
    }
    function setFile_Delivery_Limit(&$file_delivery_limit) {
        $this->file_delivery_limit =& $file_delivery_limit;
    }
    function setDelivery_Schedule_Span_Limit(&$delivery_schedule_span_limit)
    {
      $this->delivery_schedule_span_limit =& $delivery_schedule_span_limit;
    }
    function setDelivery_Sample_Temp_Flg(&$delivery_sample_temp_flg) {
        $this->delivery_sample_temp_flg =& $delivery_sample_temp_flg;
    }
    function setDefault_DeliveryPass_Flg(&$default_deliverypass_flg) {
        $this->default_deliverypass_flg =& $default_deliverypass_flg;
    }
    function setZip_DeliveryPass_Flg(&$zip_deliverypass_flg) {
        $this->zip_deliverypass_flg =& $zip_deliverypass_flg;
    }
    function setPassword_Notice_Flg(&$password_notice_flg) {
        $this->password_notice_flg =& $password_notice_flg;
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
    function setMaxdltime_Val(&$maxdltime_val) {
        $this->maxdltime_val =& $maxdltime_val;
    }
    function setMaxdltime_Content(&$maxdltime_content) {
        $this->maxdltime_content =& $maxdltime_content;
    }
    function setMaxdltime_Sort(&$maxdltime_sort) {
        $this->maxdltime_sort =& $maxdltime_sort;
    }
    function setMaxdltime_Mark(&$maxdltime_mark) {
        $this->maxdltime_mark =& $maxdltime_mark;
    }
    function setMaxdltime_Maxsort(&$maxdltime_maxsort) {
        $this->maxdltime_maxsort =& $maxdltime_maxsort;
    }
    function setLimitdt_Addlist(&$limitdt_addlist) {
        $this->limitdt_addlist =& $limitdt_addlist;
    }
    function setMaxdltime_Addlist(&$maxdltime_addlist) {
        $this->maxdltime_addlist =& $maxdltime_addlist;
    }
    function setToken($token) {
        $this->token = $token;
    }
}
?>

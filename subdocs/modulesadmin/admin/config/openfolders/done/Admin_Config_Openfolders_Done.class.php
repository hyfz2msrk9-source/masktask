<?php
/**
 * [機能説明] 各種設定 - 操作設定 - フォルダ公開に関する設定(完了)
 * @updater
 *
 * @package     WebFile
 * @version     3.?.?
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Openfolders_Done {
    var $session;
    var $DtoConfigs;
    var $MdbConfigs;
    var $DtoLog;
    var $MdbLog;
    var $MdbMchoice;
    var $DtoGroups;
    var $MdbGroups;

    var $openfolders_flg;
    var $open_subfolder_flg;
    var $openfolders_mailer_flg;
    var $openfolders_mailer_default_flg;
    var $thumbnail_flg;
    var $default_openpass_flg;

    var $limitdt_val;
    var $limitdt_content;
    var $limitdt_maxsort;
    var $limitdt_sort;
    var $limitdt_mark;
    var $limitdt_addlist;
    var $maxdltime_addlist;
    var $token;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Openfolders_Doneが実行されました","Admin_Config_Openfolders_Done#execute");

        $openfolder_error_response = function ($message = "不正な値が入力されました。") {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => $message,
                'token_name' => 'OpenfolderEditToken',
                'token_value' => $this->token->getValue('OpenfolderEditToken')
            ]);
            exit;
        };

        // メーラからの送信機能が無効の際は、メーラから送信をデフォルト表示にしない
        if ($this->openfolders_mailer_flg == 0) {
            $this->openfolders_mailer_default_flg = 0;
        }

        //入力チェック
        if (!array_key_exists($this->openfolders_flg, GlobalArray::getOpenfolders_Flg_Options()) ||
            !array_key_exists($this->open_subfolder_flg, GlobalArray::getOpen_Subfolder_Options()) ||
            !array_key_exists($this->openfolders_mailer_flg, GlobalArray::getOpenfolders_Mailer_Flg_Options()) ||
            !array_key_exists($this->openfolders_mailer_default_flg, GlobalArray::getOpenfolders_Mailer_Default_Flg_Options()) ||
            !array_key_exists($this->thumbnail_flg, GlobalArray::getThumbnail_Flg_Options()) ||
            !array_key_exists($this->default_openpass_flg, GlobalArray::getDefault_OpenPass_Flg_Options())) $openfolder_error_response("不正な値が入力されました。");

        //フォルダ公開
        $this->MdbConfigs->setValue('openfolders_flg',$this->openfolders_flg);

        //サブフォルダ公開
        $this->MdbConfigs->setValue('open_subfolder_flg',$this->open_subfolder_flg);

        //メーラーから送信
        $this->MdbConfigs->setValue('openfolders_mailer_flg',$this->openfolders_mailer_flg);

        //フォルダ公開時のデフォルト表示
        $this->MdbConfigs->setValue('openfolders_mailer_default_flg',$this->openfolders_mailer_default_flg);


        //サムネイル表示
        $this->MdbConfigs->setValue('thumbnail_flg',$this->thumbnail_flg);

        //パスワードロック機能
        $this->MdbConfigs->setValue('default_openpass_flg',$this->default_openpass_flg);

        // ダウンロード有効期限 ここから
        // 追加分
        if ($this->limitdt_addlist) {
            $str = strtok($this->limitdt_addlist,",");
            while ($str) {
                $data[0] = $str;
                $str = strtok(",");

                $data[1] = $str;
                if (!preg_match("/^[0-9]+$/", $data[1])) {
                    $openfolder_error_response("有効期限の値が不正です。");
                } else if ((int)$data[1] >= 9999) {
                    $openfolder_error_response("有効期限は9999日以内で入力してください。");
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
                    $openfolder_error_response("表示は20文字以内で入力してください。");
                }

                $info[] = $data;
            }
        }

        // ダウンロード有効期限の追加と更新と削除
        $list = $this->MdbMchoice->getList("open_folder_limit_dt");
        foreach ($list as $key => $val){
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
                    $openfolder_error_response("有効期限の値が不正です。");
                }
            }

            // 削除
            $dt_del_memo = "";
            $del_list = array_diff($id_list,$sort);
            foreach($del_list as $key => $val) {
                $delval = $this->MdbMchoice->getChoiceIdList($val,"open_folder_limit_dt");
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
                                $this->MdbMchoice->setInsert('open_folder_limit_dt',$val2[1],$val2[2],$val2[3],$val2[4],$val2[5],$sortNo);
                            } else {
                                $this->MdbMchoice->setInsert('open_folder_limit_dt',$val2[1],$val2[2],$val2[3],'','',$sortNo);
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
                    $sortval = $this->MdbMchoice->getChoiceIdList($choiceId,"open_folder_limit_dt");
                    if ((string)$sortval["sort"] != (string)$sortNo) {
                        $dt_sort_memo = "true";
                    }
                    $this->MdbMchoice->setSortUpdata($choiceId,$sortNo);
                }
            }
            // 一番上の値を初期値に設定
            $val_list = $this->MdbMchoice->getList("open_folder_limit_dt");
            $val_id = $val_list[0]["value_id"];
            $this->MdbConfigs->setValue('limitdt_mark',$val_id);
        }
        // ダウンロード有効期限 ここまで

        // Log
        $history_id = 112; // 112:各種設定＞機能設定
        $memo  = "";

        if ( $this->openfolders_flg != $this->session->getParameter("openfolders_flg")){
            if ($this->openfolders_flg == "1") {
                $flg = "有効";
            } else {
                $flg = "無効";
            }
            $memo .= "フォルダ公開：".$flg."\n";
        }
        if ( $this->open_subfolder_flg != $this->session->getParameter("open_subfolder_flg")){
            if ($this->open_subfolder_flg == "1") {
                $flg = "公開";
            } else {
                $flg = "非公開";
            }
            $memo .= "サブフォルダ公開：".$flg."\n";
        }

        if ( ($this->openfolders_mailer_flg != $this->session->getParameter("openfolders_mailer_flg"))
          || ($this->openfolders_mailer_default_flg != $this->session->getParameter("openfolders_mailer_default_flg"))
        ){
            if ($this->openfolders_mailer_flg == "1") {
                $flg = "有効";
            } else {
                $flg = "無効";
            }
            $memo .= "メーラー送信：".$flg."\n";
            $options = GlobalArray::getOpenfolders_Mailer_Default_Flg_Options();
            $memo .= "　".$options[$this->openfolders_mailer_default_flg]."\n";
        }

        if ( $this->thumbnail_flg != $this->session->getParameter("thumbnail_flg")){
            if ($this->thumbnail_flg == "1") {
                $flg = "表示する";
            } else {
                $flg = "表示しない";
            }
            $memo .= "サムネイル表示：".$flg."\n";
        }
        if ( $this->default_openpass_flg != $this->session->getParameter("default_openpass_flg")){
                        $arr = GlobalArray::getDefault_OpenPass_Flg_Options();
                        $flg = $arr[$this->default_openpass_flg];
            $memo .= "パスワードロック（フォルダ公開）：".$flg."\n";
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

        if($memo) {
            $memo = "[送信機能＞フォルダ公開に関する設定]\n".$memo;
            $this->DtoLog->setUser_id("");
            $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
            $this->DtoLog->setFile_id("");
            $this->DtoLog->setHistory_id($history_id);
            $this->DtoLog->setMemo($memo);
            $this->MdbLog->Logging($this->DtoLog);
        }

        //sessionを削除
        $this->session->removeParameter("openfolders_flg");
        $this->session->removeParameter("open_subfolder_flg");
        $this->session->removeParameter("openfolders_mailer_flg");
        $this->session->removeParameter("openfolders_mailer_default_flg");
        $this->session->removeParameter("thumbnail_flg");
        $this->session->removeParameter("default_openpass_flg");

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
    function setOpenfolders_flg(&$openfolders_flg)
    {
        $this->openfolders_flg =& $openfolders_flg;
    }
    function setOpen_subfolder_flg(&$open_subfolder_flg)
    {
        $this->open_subfolder_flg =& $open_subfolder_flg;
    }
    function setOpenfolders_Mailer_flg(&$openfolders_mailer_flg)
    {
        $this->openfolders_mailer_flg =& $openfolders_mailer_flg;
    }
    function setOpenfolders_Mailer_default_flg(&$openfolders_mailer_default_flg)
    {
        $this->openfolders_mailer_default_flg =& $openfolders_mailer_default_flg;
    }
    function setThumbnail_flg(&$thumbnail_flg)
    {
        $this->thumbnail_flg =& $thumbnail_flg;
    }
    function setDefault_openpass_flg(&$default_openpass_flg)
    {
        $this->default_openpass_flg =& $default_openpass_flg;
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
    function setToken($token) {
        $this->token = $token;
    }
}
?>

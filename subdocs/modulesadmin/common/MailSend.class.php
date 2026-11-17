<?php
require_once( WEBAPP_DIR . "/components/wf/mdb/Wf_Mdb_Configs.class.php");
require_once( WEBAPP_DIR . "/components/wf/template/Wf_Template_Logic.class.php");

class MailSend {
    var $to;
    var $subject;
    var $body;
    var $from;
    var $from_name;
    var $return_path;
    var $MdbConfigs;
    var $TemplateLogic;
    var $cc;
    var $bcc;

    function init() {
        $this->to = "";
        $this->cc = "";
        $this->bcc = "";
        $this->subject = "";
        $this->body = "";
        $this->from = "";
        $this->from_name = "";
        $this->return_path = "";
        $this->TemplateLogic = new Wf_Template_Logic();
    }

    // メール送信
    function send($html_flg = false)
    {
        if (defined("SMTP_FLG") && SMTP_FLG) {
            // SMTP送信
            return $this->_sendSMTP($html_flg);
        } else {
            // PHP送信
            return $this->_sendMail($html_flg);
        }
    }

    /************************************************************
     * PHPメール関数からメール送信
     ************************************************************/
    function _sendMail($html_flg = false)
    {
        // エンコード設定
        $encoding = "UTF-8";

        $to             = $this->to;
        $subject        = $this->subject;
        $body           = $this->body;
        $from           = $this->from;
        $from_name      = $this->from_name;
        $cc             = @$this->cc;
        $bcc            = @$this->bcc;
        $return_path    = $this->return_path;

        //宛先変換
        $to = str_replace(array("\r\n","\r","\n"), '', $to);

        //本文エンコード変換
        $body = mb_convert_kana($body, "KV");
        $body = str_replace("\r\n", "\n", $body);
        $body = chunk_split(base64_encode($body), 76, "\n");

        //サブジェクトエンコード変換
        $subject = str_replace(array("\r\n","\r","\n"), '', $subject);
        $subject = mb_convert_kana($subject, "KV");
        $subject = mb_encode_mimeheader($subject, $encoding, "B", "\n");

        //From,Cc,Bccを変換
        $from = str_replace(array("\r\n","\r","\n"), '', $from);
        $cc   = str_replace(array("\r\n","\r","\n"), '', $cc);
        $bcc  = str_replace(array("\r\n","\r","\n"), '', $bcc);

        //ヘッダーを作成
        $header = "";
        if ($from_name) {
            $from_name = mb_encode_mimeheader($from_name, $encoding, "B", "\n");
            $header = "From: ".$from_name." <".$from.">";
        } else {
            $header = "From: ".$from;
        }
        if ($cc)  $header .= "\nCc: ".$cc;
        if ($bcc) $header .= "\nBcc: ".$bcc;

        //return pathを変換
        $return_path = str_replace(array("\r\n","\r","\n"), '', $return_path);

        // 配信停止用ヘッダ
        $header .= "\nList-Unsubscribe-Post: " . LIST_UNSUBSCRIBE_POST;
        $header .= "\nList-Unsubscribe: " . LIST_UNSUBSCRIBE;
        //HTMLで表示する場合のヘッダ
        $Content_Type = $html_flg === true ? 'text/html' :  'text/plain';
        $header .= "\nContent-Type: " . $Content_Type . "; charset=". $encoding. "\nContent-Transfer-Encoding: base64";

        if (MAIL_EXTRA_HEADERS) {
            $extra_headers = explode(",", MAIL_EXTRA_HEADERS);
            foreach ($extra_headers as $extra_header) {
                $header .= "\n" . $extra_header;
            }
        }

        $result = mail($to, $subject, $body, $header, "-f " . $return_path);

        return $result;

    }

    /************************************************************
     * SMTPサーバを指定してメール送信
     ************************************************************/
    function _sendSMTP($html_flg = false)
    {
        // 20121220 丸文字の文字化け対応 メール送信時のエンコードをUTF-8に
        $default = mb_language();
        mb_language("uni");

        $to             = $this->to;
        $cc             = @$this->cc;
        $bcc            = @$this->bcc;
        $subject        = $this->subject;
        $body           = $this->body;
        $from           = $this->from;
        $from_name      = $this->from_name;
        $return_path    = $this->return_path;

        //ライブラリ読み込み
        require_once(MAPLE_DIR."/lib/pear/Mail.php");

        //宛先変換
        $to = str_replace(array("\r\n","\r","\n"), '', $to);

        //本文エンコード変換
        $body = mb_convert_kana($body, "KV");
        $body = mb_convert_encoding($body, "UTF-8");
        $body = str_replace("\r\n", "\n", $body);

        //サブジェクトエンコード変換
        $subject = str_replace(array("\r\n","\r","\n"), '', $subject);
        $subject = mb_convert_kana($subject, "KV");
        $subject = mb_convert_encoding($subject, "UTF-8");
        $subject = mb_encode_mimeheader($subject, "UTF-8", "B", "\n");

        //From,Cc,Bcc変換
        $from = str_replace(array("\r\n","\r","\n"), '', $from);
        $cc = str_replace(array("\r\n","\r","\n"), '', $cc);
        $bcc = str_replace(array("\r\n","\r","\n"), '', $bcc);

        //return path変換
        $return_path = str_replace(array("\r\n","\r","\n"), '', $return_path);

        //fromヘッダー
        if($from_name) {
            $from_header = mb_encode_mimeheader($from_name)."<".$from.">";
        } else {
            $from_header = $from;
        }

        $body = chunk_split(base64_encode($body), 76, "\n");

        $content_type = $html_flg === true ? 'text/html' :  'text/plain';
        $Content_Type = $content_type . '; charset="UTF-8"';

        // ヘッダ
        $headers = array(
            'Subject'                   => $subject,
            'To'                        => $to,
            'From'                      => $from_header,
            'Return-Path'               => $return_path,
            'MIME-Version'              => '1.0',
            'Content-Type'              => $Content_Type,
            'Content-Transfer-Encoding' => 'base64',
            'Date'                      => date("D, j M Y H:i:s") . ' +0900 (JST)'
        );
        if (MAIL_EXTRA_HEADERS) {
            $extra_headers = explode(",", MAIL_EXTRA_HEADERS);
            foreach ($extra_headers as $extra_header) {
                list($key, $value) = explode(":", $extra_header, 2);
                if ($key && $value) {
                    $headers[trim($key)] = trim($value);
                }
            }
        }
        if($cc){
            $headers['cc'] = $cc;
            $to .= ",".$cc;
        }
        if($bcc) {
            $headers['bcc'] = $bcc;
            $to .= ",".$bcc;
        }

        // message-idが自動で入らない時のための対応
        $overRandomBytes = bin2hex(random_bytes(6));  // 6バイト分のランダムなバイト列を生成し、12桁の16進数に
        $randomBytes = substr($overRandomBytes, 0, -1); //Googleのフォーマットに合わせるため1桁削る
        $idFrom  = mb_strstr($from, '@');
        $headers["Message-ID"] =   "<" . date('YmdHis', time()) . '.' . $randomBytes . $idFrom . ">";

        // 配信停止ヘッダを追加
        $headers["List-Unsubscribe-Post"] = LIST_UNSUBSCRIBE_POST;
        $headers["List-Unsubscribe"] = LIST_UNSUBSCRIBE;

        // メール送信
        $params = array(
            'host'     => SMTP_HOST,
            'port'     => SMTP_PORT,
            'auth'     => SMTP_AUTH,
            'username' => SMTP_USERNAME,
            'password' => SMTP_PASSWORD,
            'timeout'  => 10
        );
        $mail = Mail::factory('smtp', $params);
        $ret = $mail->send($to, $headers, $body);
        if (PEAR::isError($ret)) {
            // エラーログ生成
            $log = "[".date("Y/m/d G:i:s")."] [code:".$ret->getCode()."] ".$ret->getMessage()."\n";
            error_log($log, 3, SMTP_LOG_PATH);
        }
        // 20121126 丸文字の文字化け対応 念の為に元に戻す
        mb_language($default);

        //初期化
        //$this->init();

        return $ret;
    }

    /************************************************************
     * Evi 容量警告メール
     ************************************************************/
    function Evi_Autonotice_Mail ($to,$percentage,$export=0) {
        $this->init();
        $this->MdbConfigs = new Wf_Mdb_Configs();
        $original_body = "[##header##]\n\n";
        if ($export) {
            $subject = EVI_EXPORT_AUTONOTICE_MAIL_SUBJECT;
            $original_body.= $this->TemplateLogic->get_template_file_contents("", "evi_export_autonotice_mail_body.txt");
        } else {
            $subject = EVI_AUTONOTICE_MAIL_SUBJECT;
            $original_body.= $this->TemplateLogic->get_template_file_contents("", "evi_autonotice_mail_body.txt");
        }
        $original_body.= "\n\n[##footer##]\n";

        $this->from        = SYS_FROM;
        $this->return_path = SYS_FROM;
        $this->to          = $to;
        $this->subject     = $subject;

        $autodelete = "";
        if ($export) {
            $ex_delete = $this->MdbConfigs->getDirectValue("evi_archive_export_autodelete");
            $ex_notice = $this->MdbConfigs->getDirectValue("evi_archive_export_autonotice");
            if ($ex_delete) {
                $autodelete = "エクスポートファイルの保存容量が全体の".$ex_delete."％を超えると、古いファイル"."\n";
                $autodelete.= "から削除されます。";
            }
            $parts = array($percentage,$ex_notice,$autodelete);
        } else {
            $autodelete_flg =  $this->MdbConfigs->getDirectValue("evi_archive_autodelete");
            $autonotice =  $this->MdbConfigs->getDirectValue("evi_archive_autonotice");
            if ($autodelete_flg) {
                $autodelete = "アーカイブファイルの保存容量が全体の".$autodelete_flg."％を超えると、古いファイル"."\n";
                $autodelete.= "から削除されます。";
            }
            $parts = array($percentage,$autonotice,$autodelete);
        }

        // 本文置換
        $original_body = StrChange::EviAutonotice($original_body, $parts);
        $tmp_body = $original_body;
        //ヘッダーの適用
        $tmp_body = StrChange::TplHeader($tmp_body, "header.txt", 1, "", $this->TemplateLogic);
        //フッターの適用
        $this->body = StrChange::TplFooter($tmp_body, "footer.txt", 1, "", $this->TemplateLogic);
        // メール送信
        return $this->send();
    }

    /************************************************************
     * Evi 削除・エクスポート　タイムアウト通知
     ************************************************************/
    function Evi_Timeout_Mail($to,$operation,$time) {
        $this->init();
        $original_body = "[##header##]\n\n";
        $subject = EVI_TIMEOUT_MAIL_SUBJECT;
        $original_body.= $this->TemplateLogic->get_template_file_contents("", "evi_timeout_mail_body.txt");
        $original_body.= "\n\n[##footer##]\n";

        $this->from        = SYS_FROM;
        $this->return_path = SYS_FROM;
        $this->to          = $to;
        $this->subject     = $subject;

        // 本文
        $parts = array($time,$operation);
        $tmp_body = StrChange::evi_timeout_mail($parts,$original_body);
        // ヘッダー
        $tmp_body = StrChange::TplHeader($tmp_body, "header.txt", 1, "", $this->TemplateLogic);
        // フッター
        $this->body = StrChange::TplFooter($tmp_body, "footer.txt", 1, "", $this->TemplateLogic);
        // メール送信
        return $this->send();
    }

    /************************************************************
     * Evi エクスポート完了通知
     ************************************************************/
    function Evi_Export_Mail($to,$zipname,$time,$alert_msg=NULL) {
        $this->init();
        $original_body = "[##header##]\n\n";
        $subject = EVI_EXPORT_MAIL_SUBJECT;
        $original_body.= $this->TemplateLogic->get_template_file_contents("", "evi_export_mail_body.txt");
        $original_body.= "\n\n[##footer##]\n";

        $this->from        = SYS_FROM;
        $this->return_path = SYS_FROM;
        $this->to          = $to;
        $this->subject     = $subject;

        // 本文
        $parts = array($time,$zipname);
        $tmp_body = StrChange::evi_export_mail($parts,$original_body);
        if (!is_null($alert_msg)) {
            $tmp_body .= $alert_msg;
        }
        // ヘッダー
        $tmp_body = StrChange::TplHeader($tmp_body, "header.txt", 1, "", $this->TemplateLogic);
        // フッター
        $this->body = StrChange::TplFooter($tmp_body, "footer.txt", 1, "", $this->TemplateLogic);
        // メール送信
        return $this->send();
    }

    /************************************************************
     * Evi エクスポート保存エラーファイル
     ************************************************************/
    function Evi_ExportSaveError_Mail($to,$file_name,$percentage) {
        $this->init();
        $this->MdbConfigs = new Wf_Mdb_Configs();
        $original_body = "[##header##]\n\n";
        $subject = EVI_EXPORT_ERROR_MAIL_SUBJECT;
        $original_body.= $this->TemplateLogic->get_template_file_contents("", "evi_exporterror_mail_body.txt");
        $original_body.= "\n\n[##footer##]\n";

        $this->from        = SYS_FROM;
        $this->return_path = SYS_FROM;
        $this->to          = $to;
        $this->subject     = $subject;

        $autodelete = "";
        $autodelete_flg =  $this->MdbConfigs->getDirectValue("evi_archive_autodelete");
        $autonotice =  $this->MdbConfigs->getDirectValue("evi_archive_autonotice");
        if ($autodelete_flg) {
            $autodelete = "アーカイブファイルの保存容量が全体の".$autodelete_flg."％を超えると、古いファイル"."\n";
            $autodelete.= "から削除されます。";
        }

        // 本文置換
        $parts = array($file_name,$percentage,$autodelete);
        $original_body = StrChange::evi_export_save_error_mail($original_body, $parts);
        $tmp_body = $original_body;
        //ヘッダーの適用
        $tmp_body = StrChange::TplHeader($tmp_body, "header.txt", 1, "", $this->TemplateLogic);
        //フッターの適用
        $this->body = StrChange::TplFooter($tmp_body, "footer.txt", 1, "", $this->TemplateLogic);
        // メール送信
        return $this->send();
    }

    /************************************************************
     * Evi エクスポート失敗通知
     ************************************************************/
    function Evi_ExportFailure_Mail($to,$file_name,$error_msg) {
        $this->init();
        $original_body = "[##header##]\n\n";
        $subject = EVI_EXPORT_ERROR_MAIL_SUBJECT;
        $original_body.= $this->TemplateLogic->get_template_file_contents("", "evi_exportfailure_mail_body.txt");
        $original_body.= "\n\n[##footer##]\n";

        $this->from        = SYS_FROM;
        $this->return_path = SYS_FROM;
        $this->to          = $to;
        $this->subject     = $subject;

        // 本文
        $parts = array($file_name, $error_msg);
        $tmp_body = StrChange::evi_exportfailure_mail($parts, $original_body);
        // ヘッダー
        $tmp_body = StrChange::TplHeader($tmp_body, "header.txt", 1, "", $this->TemplateLogic);
        // フッター
        $this->body = StrChange::TplFooter($tmp_body, "footer.txt", 1, "", $this->TemplateLogic);
        // メール送信
        return $this->send();
    }

    /************************************************************
     * ユーザ情報通知メール
     ************************************************************/
    function User_Inform_Mail($DtoUser, $body) {
        $this->from        = SYS_FROM;
        $this->return_path = SYS_FROM;
        $this->to          = $DtoUser->getMailaddr();
        $this->subject     = USER_INFORM_SUBJECT;
        $original_body = $body;

        //本文
        $parts = array(SYS_NAME.SYS_NAME_SUB,$DtoUser->getUser_name(),$DtoUser->getAccount(),$DtoUser->getPassword(),APP_URL.APP_PATH."/");
        $this->body = StrChange::user_inform_mail($parts, $original_body);
        //ヘッダーとフッターは使用しない
        // メール送信
        $this->send();
    }

    /************************************************************
     * ファイルのロック解除通知メール
     ************************************************************/
    function Unlock_Mail($DtoUser,$MdbUser,$DtoGroups,$share_folder_nm,$filenames,$user_id,$memo) {
        $this->init();
        $memo2 = str_replace("\r\n","\n",$memo);
        $memo3 = explode("\n",$memo2);
        $memos = null;
        foreach ($memo3 as $memo_val) {
            $memos.= "  ".$memo_val."\n";
        }
        $subject = UNLOCK_MAIL_SUBJECT;
        $original_body = "";
        $original_body.= "グループ名：".$DtoGroups->getName()."\n";
        $original_body.= "フォルダ名：".$share_folder_nm."\n";
        $original_body.= "ファイル名："."\n".$filenames."\n";
        $original_body.= "メモ： "."\n".$memos."\n";

        $DtoUser->setUser_id($user_id);
        $MdbUser->getUserInfo($DtoUser);

        $this->from        = SYS_FROM;
        $this->return_path = SYS_FROM;
        $this->to          = $DtoUser->getMailaddr();
        $this->subject     = $subject;
        $original_body     = "[##header##]\n[##hmsg##]\n\n".$original_body."\n[##footer##]\n";

        // 本文テンプレートの適用
        $tmp_body = $original_body;
        //ヘッダーの適用
        $tmp_body = StrChange::TplHeader($tmp_body, "header.txt", 1, "", $this->TemplateLogic);
        //ヘッダーMsgの適用
        $tmp_body= StrChange::TplHmsg($tmp_body, "unlock_hmsg.txt", 1, $this->TemplateLogic);
        //フッターの適用
        $this->body  = StrChange::TplFooter($tmp_body, "footer.txt", 1, "", $this->TemplateLogic);
        // メール送信
        return $this->send();
    }

    /************************************************************
     * 容量圧迫時の通知メール
     ************************************************************/
    function CapacityAlertMail($to_mail, $memo){
        $this->init();

        $original_body = "[##header##]\n";
        $original_body .= $memo."\n";
        $original_body .= "[##footer##]\n";

        $this->to = $to_mail;
        $this->from = SYS_FROM;
        $this->return_path = SYS_FROM;
        $this->subject = CAPACITY_ALERT_SUBJECT;

        // 本文の適用
        $tmp_body = $original_body;
        // ヘッダーの適用
        $tmp_body = StrChange::TplHeader($tmp_body, "header.txt", 1, "", $this->TemplateLogic);
        // フッターの適用
        $tmp_body = StrChange::TplFooter($tmp_body, "footer.txt", 1, "", $this->TemplateLogic);
        $this->body = $tmp_body;

        //メール送信
        $this->send();
    }

    /**
    * 連続ログイン通知
    * @updater  Hiroko Yanai（2018/01/29） SYS_NAMEを追加
    *
    * @access   public
    * @author   Hiroko Yanai（2017/12/08）
    * @param    string $to 通知先メールアドレス
    * @param    array $data ログイン試行ユーザ情報
    * @return   なし
    **/
    function ContinuousLogin_Mail ($to, $data) {
        $this->init();
        $original_body = "[##header##]\n\n";
        $subject = CONTINUOUSLOGIN_MAIL_SUBJECT;
        $original_body.= $this->TemplateLogic->get_template_file_contents("", "continuouslogin_mail_body_admin.txt");
        $original_body.= "\n\n[##footer##]\n";

        $this->from        = SYS_FROM;
        $this->return_path = SYS_FROM;
        $this->to          = $to;
        $this->subject     = $subject;

        // 本文置換
        $parts = array(
            $data['config_min'], $data['config_num'],
            preg_replace("/\\\\/", "\\\\\\\\", $data['account']),
            $data['level'],
            $data['last_login_try_dt'], $data['login_try_log'],
            SYS_NAME
        );
        $original_body = StrChange::ContinuousLogin($original_body, $parts);
        $tmp_body = $original_body;
        // ヘッダーの適用
        $tmp_body = StrChange::TplHeader($tmp_body, "header.txt", 1, "", $this->TemplateLogic);
        // フッターの適用
        $this->body = StrChange::TplFooter($tmp_body, "footer.txt", 1, "", $this->TemplateLogic);
        // メール送信
        return $this->send();
    }

    /**
     * 上長承認：ユーザ宛の、承認待ちが却下された通知メール(上長ユーザが削除された時に送るver)
     * @updater
     *
     * @version  3.17.0
     * @author   Hiroko Yanai（2019/08/02）
     * @param    array $user_info 依頼者の情報
     * @param    string $reason 却下理由
     * @param    string $language 言語
     * @param    int $count 却下した件数
     * @return   bool 送信結果
     **/
    function Rejection_Mail_By_Admin ($user_info, $reason, $language, $count) {
        $this->init();

        // ユーザの情報
        $user_addr = $user_info["mailaddr"];

        if ($language === 'en') {
            $url = APP_URL . APP_PATH . "/index-" . $language .".php?action=superior&superior_flg=0";
            $subject = REJECTION_MAIL_SUBJECT_EN;
            $text = "[##header##]\n\n";
            $text.= $reason . ", " . $count . " approval request(s) was rejected.\n";
            $text.= "Please check the approval list for details.\n";
            $text.= $url;
            $text.= "\n\n[##footer##]\n";
        } else if ($language === 'cn') {
            $url = APP_URL . APP_PATH . "/index-" . $language .".php?action=superior&superior_flg=0";
            $subject = REJECTION_MAIL_SUBJECT_CN;
            $text = "[##header##]\n\n";
            $text.= $reason . "，等待批准已被驳回" . $count . "次。\n";
            $text.= "请查看确认一览了解详情。\n";
            $text.= $url;
            $text.= "\n\n[##footer##]\n";
        } else if ($language === 'tcn') {
            $url = APP_URL . APP_PATH . "/index-" . $language .".php?action=superior&superior_flg=0";
            $subject = REJECTION_MAIL_SUBJECT_TCN;
            $text = "[##header##]\n\n";
            $text.= $reason . "，等待批準已被駁回" . $count . "次。\n";
            $text.= "請查看確認一覽暸解詳情。\n";
            $text.= $url;
            $text.= "\n\n[##footer##]\n";
        } else {
            $url = APP_URL . APP_PATH . "/index.php?action=superior&superior_flg=0";
            $subject = REJECTION_MAIL_SUBJECT;
            $text = "[##header##]\n\n";
            $text.= $reason . "、承認依頼が" . $count . "件却下されました。\n";
            $text.= "詳細は承認一覧をご確認ください。\n";
            $text.= $url;
            $text.= "\n\n[##footer##]\n";
        }

        $this->from        = SYS_FROM;
        $this->return_path = SYS_FROM;
        $this->to          = $user_addr;
        $this->subject     = $subject;
        $original_body     = "\n";
        $original_body    .= $text;

        // 本文テンプレートの適用
        $tmp_body = $original_body;
        // ヘッダーの適用
        $tmp_body = StrChange::TplHeader($tmp_body, "header.txt", 1, $language, $this->TemplateLogic);
        // フッターの適用
        $this->body = StrChange::TplFooter($tmp_body, "footer.txt", 1, $language, $this->TemplateLogic);
        // メール送信
        return $this->send();
    }
    /**
     * HTMLで表示できるようにContent-Type:text/htmlでメールを送信
     *
     * @param [type] $mailAddr 送信先メールアドレス
     * @param [string] $body 本文
     * @param [string] $subject 件名
     * @return void
     */
    function sendHtmlMail($mailAddr, $body, $subject)
    {
        $this->init();
        $this->to = $mailAddr;
        $this->subject = $subject;
        $this->from = SYS_FROM;
        $this->return_path = SYS_FROM;
        $this->body = $body;
        // メール送信
        return $this->send(true);
    }
    /*
     * 二要素認証確認コード通知メール
     * @param    string $to_mail 送信先メールアドレス
     * @param    string $pass パスワード
     * @param    string $issued_at 発行日時
     * @return   bool 送信結果
     **/
    function Confirmation_Code_Mail($to_mail, $pass, $issued_at)
    {
        $this->init();

        $this->to          = $to_mail;
        $this->subject     = TWO_STEP_CODE_MAIL_SUBJECT;
        $this->from_name   = SYS_NAME;
        $this->from        = SYS_FROM;
        $this->return_path = SYS_FROM;

        // 確認コードのメッセージを置換
        $original_body = $this->TemplateLogic->get_template_file_contents('', "confirmation_code.txt");
        // 本文テンプレートの適用
        $tmp_body = preg_replace(
            array("/\[##password##\]/i", "/\[##expiration##\]/i"),
            array($pass, StrChange::getOneTimePasswordExpiration($issued_at)),
            $original_body
        );
        //ヘッダーの適用をフラグにかかわらず無効化
        $tmp_body = StrChange::TplHeader($tmp_body, "header.txt", 0, "", $this->TemplateLogic);
        // ヘッダー
        $tmp_body = StrChange::TplHeader($tmp_body, "header.txt", 0, "", $this->TemplateLogic);
        //フッターの適用をフラグにかかわらず無効化
        $this->body  = StrChange::TplFooter($tmp_body, "footer.txt", 0, "", $this->TemplateLogic);
        // メール送信
        return $this->send();
    }


}
?>

<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Template_Edittcn_Done {
    var $template_name_tcn;
    var $session;
    var $TemplateLogic;
    var $naiyo;
    var $DtoLog;
    var $MdbLog;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Template_Edittcn_Doneが実行されました","Admin_Template_Edittcn_Done#execute");
        if (!$this->TemplateLogic->checkTemplate($this->template_name_tcn,  $this->naiyo)) {
            return "input";
        }
        // 中国語版が使用可能かチェック
        if( !defined("CHINESE_FLG")  ||  CHINESE_FLG == 0 ) {
            $this->session->removeParameter("TemplateEditToken");
            return "error";
        }

        switch ($this->template_name_tcn) {
            case "download":
                $this->TemplateLogic->put_template_file_contents("tcn", "download_sample.txt", $this->naiyo);
                break;
            case "upload":
                $this->TemplateLogic->put_template_file_contents("tcn", "upload_sample.txt", $this->naiyo);
                break;
            case "sendcollect":
                $this->TemplateLogic->put_template_file_contents("tcn", "sendcollect_sample.txt", $this->naiyo);
                break;
            case "download_onetime_password":
                $this->TemplateLogic->put_template_file_contents("tcn", "download_onetime_pass.txt", $this->naiyo);
                break;
            case "upload_onetime_password":
                $this->TemplateLogic->put_template_file_contents("tcn", "upload_onetime_pass.txt", $this->naiyo);
                break;
            case "download_onetime_password_sms":
                $this->TemplateLogic->put_template_file_contents("tcn", "download_onetime_pass_sms.txt", $this->naiyo);
                break;
            case "upload_onetime_password_sms":
                $this->TemplateLogic->put_template_file_contents("tcn", "upload_onetime_pass_sms.txt", $this->naiyo);
                break;
            case "confirmation_code":
                $this->TemplateLogic->put_template_file_contents("tcn", "confirmation_code.txt", $this->naiyo);
                break;
            case "header":
                if( preg_match("/$\n/",$this->naiyo) == 0 ){
                    $this->naiyo = $this->naiyo."\n";
                }
                $this->TemplateLogic->put_template_file_contents("tcn", "header.txt", $this->naiyo);
                break;
            case "footer":
                $this->TemplateLogic->put_template_file_contents("tcn", "footer.txt", $this->naiyo);
                break;
        }

        $hmsg_name = "";
        if( $this->template_name_tcn == "header" && $this->hmsg_sub !="0" ){
            if( preg_match("/$\n/",$this->naiyo_sub) == 0 ){
                $this->naiyo_sub = $this->naiyo_sub."\n";
            }
            switch ($this->hmsg_sub) {
                case "1":
                    $hmsg_name = "ファイル配布メール";
                    $this->TemplateLogic->put_template_file_contents("tcn", "download_hmsg.txt", $this->naiyo_sub);
                    break;
                case "2":
                    $hmsg_name = "ファイル配布の送信完了";
                    $this->TemplateLogic->put_template_file_contents("tcn", "download_send_hmsg.txt", $this->naiyo_sub);
                    break;
                case "3":
                    $hmsg_name = "ファイル配布の送信完了";
                    $this->TemplateLogic->put_template_file_contents("tcn", "download_open_hmsg.txt", $this->naiyo_sub);
                    break;
                case "4":
                    $hmsg_name = "ファイル配布のダウンロード完了のお知らせ";
                    $this->TemplateLogic->put_template_file_contents("tcn", "download_done_hmsg.txt", $this->naiyo_sub);
                    break;
                case "5":
                    $hmsg_name = "ファイルダウンロード用パスワードのお知らせ";
                    $this->TemplateLogic->put_template_file_contents("tcn", "download_pass_hmsg.txt", $this->naiyo_sub);
                    break;
                case "6":
                    $hmsg_name = "ファイル配布メーラーの送信準備完了";
                    $this->TemplateLogic->put_template_file_contents("tcn", "download_sendmailer_hmsg.txt", $this->naiyo_sub);
                    break;
                case "7":
                    $hmsg_name = "ファイル配布・収集メール";
                    $this->TemplateLogic->put_template_file_contents("tcn", "sendcollect_hmsg.txt", $this->naiyo_sub);
                    break;
                case "8":
                    $hmsg_name = "ファイル配布・収集メールの送信完了";
                    $this->TemplateLogic->put_template_file_contents("tcn", "sendcollect_send_hmsg.txt", $this->naiyo_sub);
                    break;
                case "9":
                    $hmsg_name = "ファイル収集先のアップロード完了";
                    $this->TemplateLogic->put_template_file_contents("tcn", "upload_comp_hmsg.txt", $this->naiyo_sub);
                    break;
                case "10":
                    $hmsg_name = "ファイル収集メール";
                    $this->TemplateLogic->put_template_file_contents("tcn", "upload_hmsg.txt", $this->naiyo_sub);
                    break;
                case "11":
                    $hmsg_name = "ファイル収集メールの送信完了";
                    $this->TemplateLogic->put_template_file_contents("tcn", "upload_send_hmsg.txt", $this->naiyo_sub);
                    break;
                case "12":
                    $hmsg_name = "ファイル収集用パスワードのお知らせ";
                    $this->TemplateLogic->put_template_file_contents("tcn", "upload_pass_hmsg.txt", $this->naiyo_sub);
                    break;
                case "13":
                    $hmsg_name = "通知メール送信承認依頼";
                    $this->TemplateLogic->put_template_file_contents("tcn", "approval_request_hmsg.txt", $this->naiyo_sub);
                    break;
                case "14":
                    $hmsg_name = "通知メール送信却下通知";
                    $this->TemplateLogic->put_template_file_contents("tcn", "rejection_mail_hmsg.txt", $this->naiyo_sub);
                    break;
                case "15":
                    $hmsg_name = "通知メール送信承認通知";
                    $this->TemplateLogic->put_template_file_contents("tcn", "approval_mail_hmsg.txt", $this->naiyo_sub);
                    break;
                case "16":
                    $hmsg_name = "共有フォルダへのファイルアップロード通知";
                    $this->TemplateLogic->put_template_file_contents("tcn", "share_upload_comp_hmsg.txt", $this->naiyo_sub);
                    break;
            }
        }else if( $this->template_name_tcn == "header" && $this->hmsg_sub =="0" ){
            $hmsg_name = "編集なし";
        }

        // Log
        $history_id = 106; // 106:テンプレートの編集
        $memo = "";

        switch ($this->template_name_tcn) {
            case "download":
                $memo = DOWNLOAD_STR.FOR_TEMPLATE."（繁体字）";
                break;
            case "upload":
                $memo = UPLOAD_STR.FOR_TEMPLATE."（繁体字）";
                break;
            case "sendcollect":
                $memo = SENDCOLLECT_STR.FOR_TEMPLATE."（繁体字）";
                break;
            case "download_onetime_password":
                $memo = DOWNLOAD_ONETIMEPASSWORD_STR.FOR_TEMPLATE."（繁体字）";
                break;
            case "upload_onetime_password":
                $memo = UPLOAD_ONETIMEPASSWORD_STR.FOR_TEMPLATE."（繁体字）";
                break;
            case "download_onetime_password_sms":
                $memo = DOWNLOAD_ONETIMEPASSWORD_SMS_STR.FOR_TEMPLATE."（繁体字）";
                break;
            case "upload_onetime_password_sms":
                $memo = UPLOAD_ONETIMEPASSWORD_SMS_STR.FOR_TEMPLATE."（繁体字）";
                break;
            case "confirmation_code":
                $memo = CONFIRMATION_CODE_STR.FOR_TEMPLATE."（繁体字）";
                break;
            case "header":
                $memo = HEADER_STR.FOR_TEMPLATE."(".$hmsg_name.")"."（繁体字）";
                break;
            case "footer";
                $memo = FOOTER_STR.FOR_TEMPLATE."（繁体字）";
                break;
        }
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        return "success";
    }
    /************************************
    * setter
    *************************************/
    function setSession($session) {
        $this->session = $session;
        $this->naiyo = $this->session->getParameter("template_naiyo");
        $this->naiyo_sub = $this->session->getParameter("template_naiyo_sub");
        $this->hmsg_sub = $this->session->getParameter("template_hmsg_sub");
        $this->session->removeParameter("template_naiyo");
        $this->session->removeParameter("template_naiyo_sub");
        $this->session->removeParameter("template_hmsg_sub");
    }
    function setDtoLog ( &$DtoLog ) {
        $this->DtoLog =& $DtoLog;
    }
    function setMdbLog ( &$MdbLog ) {
        $this->MdbLog =& $MdbLog;
    }
    function setTemplateLogic($TemplateLogic) {
        $this->TemplateLogic = $TemplateLogic;
    }
    function setTemplate_name_tcn($template_name_tcn) {
        $this->template_name_tcn = $template_name_tcn;
    }
    function getNaiyo() {
        return $this->naiyo;
    }
    function setNaiyo_sub($naiyo_sub){
        $this->naiyo_sub = $naiyo_sub;
    }
    function setHmsg_sub($Hmsg_sub){
        $this->hmsg_sub = $hmsg_sub;
    }
    function getTemplate_name_tcn() {
        return $this->template_name_tcn;
    }
    function getName() {
        return $this->TemplateLogic->getTitleName($this->template_name_tcn);
    }
    function getTemplate_type() {
        if ($this->TemplateLogic->checkInsertTemplate($this->template_name_tcn)) {
            return $this->template_name_tcn;
        }
        return false;
    }
}
?>

<?php
/**
 * [機能説明] 各種設定 - その他設定 - テンプレートに関する設定：選択した冒頭文を取得
 * @updater
 *
 * @package     WebFile
 * @version     3.?.?
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
require_once( MODULE_DIR . "/common/common.php");

class Admin_Template_Edit_Read {
    var $session;
    var $hmsg_sub;
    var $TemplateLogic;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Template_Edit_Readが実行されました","Admin_Template_Edit_Read#execute");

        $naiyo = "";
        switch ($this->hmsg_sub) {
            case "0":
                $naiyo = "";
                break;
            case "1":
                // ファイル配布メール
                $naiyo = $this->TemplateLogic->get_template_file_contents("", "download_hmsg.txt");
                break;
            case "2":
                // ファイル配布の送信完了
                $naiyo = $this->TemplateLogic->get_template_file_contents("", "download_send_hmsg.txt");
                break;
            case "3":
                // ファイル配布メールの開封通知
                $naiyo = $this->TemplateLogic->get_template_file_contents("", "download_open_hmsg.txt");
                break;
            case "4":
                // ファイル配布のダウンロード完了のお知らせ
                $naiyo = $this->TemplateLogic->get_template_file_contents("", "download_done_hmsg.txt");
                break;
            case "5":
                // ファイルダウンロード用パスワードのお知らせ
                $naiyo = $this->TemplateLogic->get_template_file_contents("", "download_pass_hmsg.txt");
                break;
            case "6":
                // ファイル配布メーラーの送信準備完了
                $naiyo = $this->TemplateLogic->get_template_file_contents("", "download_sendmailer_hmsg.txt");
                break;
            case "7":
                // ファイルの配布・収集メール
                $naiyo = $this->TemplateLogic->get_template_file_contents("", "sendcollect_hmsg.txt");
                break;
            case "8":
                // ファイルの配布・収集メールの送信完了
                $naiyo = $this->TemplateLogic->get_template_file_contents("", "sendcollect_send_hmsg.txt");
                break;
            case "9":
                // ファイル収集先のアップロード完了
                $naiyo = $this->TemplateLogic->get_template_file_contents("", "upload_comp_hmsg.txt");
                break;
            case "10":
                // ファイル収集メール
                $naiyo = $this->TemplateLogic->get_template_file_contents("", "upload_hmsg.txt");
                break;
            case "11":
                // ファイル収集メールの送信完了メール
                $naiyo = $this->TemplateLogic->get_template_file_contents("", "upload_send_hmsg.txt");
                break;
            case "12":
                // ファイル収集用パスワードのお知らせ
                $naiyo = $this->TemplateLogic->get_template_file_contents("", "upload_pass_hmsg.txt");
                break;
            case "13":
                // 通知メールの送信承認依頼
                $naiyo = $this->TemplateLogic->get_template_file_contents("", "approval_request_hmsg.txt");
                break;
            case "14":
                // 通知メールの送信却下通知
                $naiyo = $this->TemplateLogic->get_template_file_contents("", "rejection_mail_hmsg.txt");
                break;
            case "15":
                // 通知メールの送信承認通知
                $naiyo = $this->TemplateLogic->get_template_file_contents("", "approval_mail_hmsg.txt");
                break;
            case "16":
                // 共有フォルダへのファイルアップロード通知
                $naiyo = $this->TemplateLogic->get_template_file_contents("", "share_upload_comp_hmsg.txt");
                break;
        default:
            header("Content-type: text/plain; charset=UTF-8");
            print("ERROR"."<:>"."不正なパラメーターを検知しました。");
            exit();
        }
        header("Content-type: text/plain; charset=UTF-8");
        print($this->hmsg_sub."<:>".$naiyo);
        exit();
    }
    function setSession($session) {
        $this->session = $session;
    }
    function setHmsg_sub($hmsg_sub){
        $this->hmsg_sub = $hmsg_sub;
    }
    function setTemplateLogic($TemplateLogic) {
        $this->TemplateLogic = $TemplateLogic;
    }
}
?>

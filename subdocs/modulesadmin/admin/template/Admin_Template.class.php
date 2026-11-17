<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Template {
    var $session;
    var $DtoUser;
    var $ListLogic;
    var $MdbUser;
    var $DtoLog;
    var $MdbLog;
    var $MdbConfigs;

    var $AdminTemplateToken;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Templateが実行されました","Admin_Template#execute");

        // メールヘッダ・フッタ 編集
        if( $this->DtoUser->getHf_edit_flg() =="1" ){

            if($this->session->getParameter("AdminTemplateToken") != $this->AdminTemplateToken ) {
                // CSRF対策
                return "error";
            }

            // 入力チェック
            $flg1 = $this->DtoUser->getMail_header_flg();
            $flg2 = $this->DtoUser->getMail_footer_flg();
            if (( $flg1 !="0" && $flg1 !="1" ) || ( $flg2 !="0" && $flg2 !="1" )) {
                return "error";
            }

            $container = &DIContainerFactory::getContainer();
            $request = &$container->getComponent("Request");
            $display_unsubscribe_flg = $request->getParameter("display_unsubscribe_flg") ?? $this->getDisplay_unsubscribe_flg(); //一斉配信・一斉収集両方オフなら選択肢が表示されないので、DBの値をそのまま使う
            if (!in_array($display_unsubscribe_flg, array("0", "1"))) {
                return "error";
            }
            
            // Configsテーブル更新
            $this->MdbConfigs->setValue('mail_header_flg', $this->DtoUser->getMail_header_flg());
            $this->MdbConfigs->setValue('mail_footer_flg', $this->DtoUser->getMail_footer_flg());
            $this->MdbConfigs->setValue('display_unsubscribe_flg', $display_unsubscribe_flg);

            // USER テーブル更新
            $this->MdbUser->editHFFlg($this->DtoUser);
            // Log
            $history_id = 111; // 111:テンプレート＞メールヘッダ・フッタ
            $memo = "";
            //header
            if($this->DtoUser->getMail_header_flg() == "1"){
                $memo .= "ヘッダ：付加する\n";
            }else{
                $memo .= "ヘッダ：付加しない\n";
            }
            //footer
            if($this->DtoUser->getMail_footer_flg() == "1"){
                $memo .= "フッタ：付加する\n";
            }else{
                $memo .= "フッタ：付加しない\n";
            }
            //gmail規約変更対応：配信停止リンク
            if ($display_unsubscribe_flg) {
                $memo .= "配信停止：付加する\n";
            } else {
                $memo .= "配信停止：付加しない\n";
            }
            $this->DtoLog->setUser_id("");
            $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
            $this->DtoLog->setFile_id("");
            $this->DtoLog->setHistory_id($history_id);
            $this->DtoLog->setMemo($memo);
            $this->MdbLog->Logging($this->DtoLog);

            $container   = &DIContainerFactory::getContainer(); 
            $actionChain = &$container->getComponent("ActionChain"); 
            $errorList   = &$actionChain->getCurErrorList(); 
            $errorList->add("Admin_template::error", "更新しました。");
        }

        // メールヘッダ・フッタ 取得
        // 2012-11-19 @yano Configsテーブルから取得に変更
        $this->DtoUser->setMail_footer_flg($this->MdbConfigs->getDirectValue('mail_footer_flg'));
        $this->DtoUser->setMail_header_flg($this->MdbConfigs->getDirectValue('mail_header_flg'));
        //$this->MdbUser->getHFFlg($this->DtoUser);

        return "success";
    }

    // setter
    function setSession($session) {
        $this->session = $session;
        $this->session->removeParameter("template_naiyo");
    }
    function setDtoUser($DtoUser){
        $this->DtoUser = $DtoUser;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = $MdbUser;
    }
    function setListLogic($ListLogic) {
        $this->ListLogic = $ListLogic;
    }
    function setDtoLog ( &$DtoLog ) {
        $this->DtoLog =& $DtoLog;
    }
    function setMdbLog ( &$MdbLog ) {
        $this->MdbLog =& $MdbLog;
    }
    function setMdbConfigs($MdbConfigs)
    {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setAdminTemplateToken($token) {
        $this->AdminTemplateToken = $token;
    }

    // getter
    function getTemplate() {
        $template = array();
        $template[] = array(
                            "name" => DOWNLOAD_STR.FOR_TEMPLATE,
                            "template_name" => "download",
                           );
        $template[] = array(
                            "name" => UPLOAD_STR.FOR_TEMPLATE,
                            "template_name" => "upload",
                           );
        $template[] = array(
                            "name" => SENDCOLLECT_STR.FOR_TEMPLATE,
                            "template_name" => "sendcollect",
                           );
        $template[] = array(
                            "name" => DOWNLOAD_ONETIMEPASSWORD_STR.FOR_TEMPLATE,
                            "template_name" => "download_onetime_password",
                           );
        $template[] = array(
                            "name" => UPLOAD_ONETIMEPASSWORD_STR.FOR_TEMPLATE,
                            "template_name" => "upload_onetime_password",
                           );
        if ($this->MdbConfigs->getDirectValue("two_step_auth_flg") == "2" && TWO_STEP_AUTH_FLG == "1") {
            $template[] = array(
                            "name" => CONFIRMATION_CODE_STR.FOR_TEMPLATE,
                            "template_name" => "confirmation_code",
                            );
        }
                           // confirmation_code
        //if(defined('SMS_PASS_FLG') && SMS_PASS_FLG == "2"){
        //    $template[] = array(
        //                     "name" => DOWNLOAD_ONETIMEPASSWORD_SMS_STR.FOR_TEMPLATE,
        //                     "template_name" => "download_onetime_password_sms",
        //                    );
        //}
        // $template[] = array(
        //                     "name" => UPLOAD_ONETIMEPASSWORD_SMS_STR.FOR_TEMPLATE,
        //                      "template_name" => "upload_onetime_password_sms",
        //                     );
        $template[] = array(
                            "name" => HEADER_STR.FOR_TEMPLATE,
                            "template_name" => "header",
                           );
        $template[] = array(
                            "name" => FOOTER_STR.FOR_TEMPLATE,
                            "template_name" => "footer",
                           );
        return $template;
    }
    function getMail_header_flg() {
        return $this->DtoUser->getMail_header_flg();
    }
    function getMail_footer_flg() {
        return $this->DtoUser->getMail_footer_flg();
    }
    function getDisplay_unsubscribe_flg()
    {
        return $this->MdbConfigs->getDirectValue('display_unsubscribe_flg');
    }
    function getRadio()
    {
        return $this->ListLogic->getHFRadio_text();
    }
    function getTokenName() {
        return "AdminTemplateToken";
    }
    function getTokenValue() {
        $token = md5(uniqid(mt_rand(), true));
        $this->session->setParameter("AdminTemplateToken",$token);
        return $token;
    }

}
?>
<?php
require_once(MODULE_DIR . '/common/common.php');
/**
 * [機能説明] ユーザ管理 - ユーザ情報通知(保存)
 * @updater     Hiroko Yanai（2018/04/11）SAML認証(OneLogin)に対応
 *
 * @package     WebFile
 * @version     3.15.2
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_User_Inform_Save {

    var $session;
    var $body;
    var $templateToken;
    var $AdminUserLogic;
    var $CommonList;
    var $TemplateLogic;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_User_Inform_Saveが実行されました", "Admin_User_Inform_Save#execute");

        // エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        // パスワード認証を使わない場合
        $usage_auth_types = $this->CommonList->getUsageAuthTypes();
        $inform_invalid = false;
        if (!isset($usage_auth_types[0])) {
            $inform_invalid = true;
        }
        // AD連携時、パスワード認証を使わない場合は利用できない
        if (AD_FLG || PASSWORD_REQUIRED_FLG <> 1 || $inform_invalid) {
            echo $this->getErrorResponce("ユーザ情報通知機能はご利用頂けません。");
            exit();
        }

        // メールアドレス重複登録が許可されている場合は利用できない
        if (DUPLICATE_EMAIL_ENABLE_FLG) {
            echo $this->getErrorResponce("ユーザ情報通知機能はご利用頂けません。");
            exit();
        }

        // 本文必須チェック
        if ($this->body == '') {
            echo $this->getErrorResponce("本文を入力してください。");
            exit();
        }

        // パスワードだけチェック
        if (!preg_match("/\[##password##]/", $this->body)) {
            echo $this->getErrorResponce("[##password##]を入力してください。");
            exit();
        }

        // 本文中にURLが入力されていないかチェック
        if ($this->AdminUserLogic->is_url($this->body)) {
            echo $this->getErrorResponce("本文中にURLを入力することはできません。");
            exit();
        }

        // token
        if ($this->session->getParameter('templateToken') != $this->templateToken) {
            echo $this->getErrorResponce("不正なパラメーターを検出しました。");
            exit();
        }

        // ユーザ管理者は編集不可
        if ( $this->session->getParameter("login_admin_level") == "3" ) {
            echo $this->getErrorResponce("ユーザ管理者はテンプレートを変更できません。");
            exit();
        }

        // テンプレートを保存する
        $this->TemplateLogic->put_template_file_contents("", "user_inform_body.txt", $this->body);

        $responce = new stdClass;
        $responce->res = 'success';
        $responce->message = $this->body;

        echo json_encode($responce, JSON_UNESCAPED_UNICODE);
        exit();
    }

    //************************************************************
    // setter
    //************************************************************
    function setSession($session) {
        $this->session = $session;
    }
    function setBody($body) {
        $this->body = $body;
    }
    function setTemplateToken($templateToken) {
        $this->templateToken = $templateToken;
    }
    function setAdminUserLogic($AdminUserLogic) {
        $this->AdminUserLogic = $AdminUserLogic;
    }
    function setCommonList($CommonList) {
        $this->CommonList =& $CommonList;
    }
    function setTemplateLogic($TemplateLogic) {
        $this->TemplateLogic = $TemplateLogic;
    }

    //************************************************************
    // getter
    //************************************************************
    // エラー用JSONレスポンス
    function getErrorResponce($message) {
        $responce = new stdClass;
        $responce->res = 'error';
        $responce->message = htmlspecialchars($message);
        return json_encode($responce, JSON_UNESCAPED_UNICODE);
    }
}
?>

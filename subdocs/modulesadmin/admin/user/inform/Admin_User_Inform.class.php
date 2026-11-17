<?php
require_once(MODULE_DIR . '/common/common.php');
/**
 * [機能説明] ユーザ管理 - ユーザ情報通知
 * @updater     Hiroko Yanai（2018/04/11）SAML認証(OneLogin)に対応
 *
 * @package     WebFile
 * @version     3.15.2
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_User_Inform {

    var $session;
    var $DtoUser;
    var $MdbUser;
    var $count;
    var $CommonList;
    var $TemplateLogic;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_User_Informが実行されました", "Admin_User_Inform#execute");

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
            $errorList->add("Admin_User_Inform::error","ユーザ情報通知機能はご利用頂けません。");
            return "error";
        }

        // メールアドレス重複登録が許可されている場合は利用できない
        if (DUPLICATE_EMAIL_ENABLE_FLG) {
            $errorList->add("Admin_User_Inform::error","ユーザ情報通知機能はご利用頂けません。");
            return "error";
        }

        // 未ログインユーザを一覧を取得
        $sql = "password = '' AND (last_login_dt IS NULL OR last_login_dt <= 0)";
        // AD連携、Google SSO認証、SAML認証(OneLogin)は除外
        $sql.=" AND ad_flg = '0' AND sso_flg = '0' AND saml_flg = '0' AND stop_flg = '0' AND del_flg='0'";
        $where = array($sql);

        // ユーザ管理者は他の管理者が追加したアカウントに通知できない
        if ( $this->session->getParameter("login_admin_level") == 3 ) {
            $where[] = "owner_admin_id=".Db_Common::escape_string( $this->session->getParameter("admin_id") );
        }

        $this->count = $this->MdbUser->getUserListWhere($where,1);
        return "success";
    }

    //************************************************************
    // setter
    //************************************************************
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoUser($DtoUser) {
        $this->DtoUser = $DtoUser;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = $MdbUser;
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
    function getUserCount() {
        return $this->count;
    }
    function getBody() {
        return $this->TemplateLogic->get_template_file_contents("", "user_inform_body.txt");
    }
    function getTemplateToken() {
        $token = md5(uniqid(rand(),1));
        $this->session->setParameter('templateToken',$token);
        return $token;
    }
}
?>

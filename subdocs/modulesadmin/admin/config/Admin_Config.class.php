<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] 各種設定 - 一覧
 * @updater     Hiroko Yanai（2018/04/13）htmlで、SAML認証(OneLogin)に対応。SSOやSAMLでも、設定は更新可能とする
 *
 * @package     WebFile
 * @version     3.15.2
 * @author
 * @copyright   KEY POINT
 * @access      public
 */
class Admin_Config {

    var $session;
    var $MdbConfigs;
    var $DtoConfigs;
    var $CommonList;
    var $MdbMchoice;
    var $FileformatLogic;
    var $DomainLogic;
    var $MdbDomain;
    var $TemplateLogic;
    var $MdbNews;
    var $DtoNews;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Configが実行されました","Admin_Config#execute");
        $this->MdbNews->getList($this->DtoNews);

        return "success";
    }

    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setDtoConfigs($DtoConfigs) {
        $this->DtoConfigs = $DtoConfigs;
    }
    function setCommonList($CommonList) {
        $this->CommonList =& $CommonList;
    }
    function setMdbMchoice($MdbMchoice) {
        $this->MdbMchoice = $MdbMchoice;
    }
    function setMdbDomain($MdbDomain) {
        $this->MdbDomain = $MdbDomain;
    }
    function setDomainLogic($DomainLogic) {
        $this->DomainLogic = $DomainLogic;
    }
    function setFileformatLogic($FileformatLogic) {
        $this->FileformatLogic = $FileformatLogic;
    }
    function setTemplateLogic($TemplateLogic) {
        $this->TemplateLogic = $TemplateLogic;
    }
    function setDtoNews($DtoNews) {
        $this->DtoNews = $DtoNews;
    }
    function setMdbNews($MdbNews) {
        $this->MdbNews = $MdbNews;
    }

    // getter
    function getSaml_authentication_available() {
        $usage_auth_types = $this->CommonList->getUsageAuthTypes();
        if (!isset($usage_auth_types[2])) {
            return false;
        }
        return true;
    }
    function getConfigList() {
        $this->MdbConfigs->getList($this->DtoConfigs);
        return $this->DtoConfigs->getConfigsList();
    }
    function getOperateOptions()
    {
        return [
            'sends'                      => GlobalArray::getSends_Flg_Options(),
            'mailer'                     => GlobalArray::getMailer_Flg_Options(),
            'mailer_default'             => GlobalArray::getMailer_Default_Flg_Options(),
            'send_collect'               => GlobalArray::getSendCollect_Flg_Options(),
            'send_collect_mailer'        => GlobalArray::getSendCollect_Mailer_Flg_Options(),
            'start_time'                 => GlobalArray::getStart_time_Options(),
            'sample_temp'                => GlobalArray::getSample_Temp_Flg_Options(),
            'default_pass'               => GlobalArray::getDefault_Pass_Flg_Options(),
            'zip_pass'                   => GlobalArray::getZip_Pass_Flg_Options(),
            'default_auto_del'           => GlobalArray::getDefault_Auto_Del_Flg_Options(),
            'collect_folder'             => GlobalArray::getCollect_Folder_Flg_Options(),
            'default_collect_pass'       => GlobalArray::getDefault_CollectPass_Flg_Options(),
            'collect_mailer'             => GlobalArray::getCollect_Mailer_Flg_Options(),
            'openfolders'                => GlobalArray::getOpenfolders_Flg_Options(),
            'open_subfolder'             => GlobalArray::getOpen_Subfolder_Options(),
            'openfolders_mailer'         => GlobalArray::getOpenfolders_Mailer_Flg_Options(),
            'openfolders_mailer_default' => GlobalArray::getOpenfolders_Mailer_Default_Flg_Options(),
            'thumbnail'                  => GlobalArray::getThumbnail_Flg_Options(),
            'default_open_pass'          => GlobalArray::getDefault_OpenPass_Flg_Options(),
            'default_sender_setting'     => GlobalArray::getDefault_Sender_setting_flg_Option(),
            'delivery'                   => GlobalArray::getDelivery_Flg_Options(),
            'delivery_sample_temp'       => GlobalArray::getDelivery_Sample_Temp_Flg_Options(),
            'default_delivery_pass'      => GlobalArray::getDefault_DeliveryPass_Flg_Options(),
            'zip_delivery_pass'          => GlobalArray::getZip_DeliveryPass_Flg_Options(),
            'password_notice'            => GlobalArray::getPassword_Notice_Flg_Options(),
            'gathering'                  => GlobalArray::getGathering_Flg_Options(),
            'gathering_folder'           => GlobalArray::getGathering_Folder_Flg_Options(),
            'gathering_sample_temp'      => GlobalArray::getGathering_Sample_Temp_Flg_Options(),
            'gathering_default_pass'     => GlobalArray::getGathering_Default_Pass_Flg_Options(),
            'gathering_notice_pass'      => GlobalArray::getGathering_Notice_Pass_Flg_Options(),
            'send_wait'                 => GlobalArray::getSend_Wait_Options()
        ];
    }
    function getLimitDt_List()
    {
      return $this->MdbMchoice->getList("limit_dt");
    }
    function getMaxDlTime_List()
    {
        return $this->MdbMchoice->getList("max_dl_time");
    }
    function getCollect_LimitDt_List()
    {
        return $this->MdbMchoice->getList("collect_limit_dt");
    }
    function getOpenFolderLimitDt_List()
    {
        return $this->MdbMchoice->getList("open_folder_limit_dt");
    }
    function getDelivery_LimitDt_List()
    {
        return $this->MdbMchoice->getList("delivery_limit_dt");
    }
    function getDelivery_MaxDlTime_List()
    {
        return $this->MdbMchoice->getList("delivery_max_dl_time");
    }
    function getGathering_LimitDt_List()
    {
        return $this->MdbMchoice->getList("gathering_limit_dt");
    }

    function getSecurityOptions()
    {
        return [
            'accountlock_mail'            => GlobalArray::getAccountlock_Mail_Flg_Options(),
            'accountlock_mail_type'                   => GlobalArray::getAccountlock_Mail_Type_Options(),
            'reissue'                     => GlobalArray::getReissue_Flg_Options(),
            'reissue_check'               => GlobalArray::getReissue_Check_Options(),
            'pass_type'                   => $this->MdbMchoice->getOptions("pass_type_id"),
            'pass_differ'                 => GlobalArray::getPass_Differ_Flg_Options(),
            'pass_account_ng'             => GlobalArray::getPass_Account_NG_Flg_Options(),
            'two_step_auth'               => GlobalArray::getTwo_Step_Auth_Flg_Options(),
            'admin_two_step_auth'         => GlobalArray::getTwo_Step_Auth_Flg_Options(),
            'continuous_login_notice'     => GlobalArray::getContinuous_Login_Notice_Flg_Options(),
            'continuous_login_each_time'  => GlobalArray::getContinuous_Login_Notice_Each_Time_Flg_Options(),
            'access'                      => GlobalArray::getAccess_Flg_Options(),
            'client_auth'                 => GlobalArray::getClientAuthOptions(),
            'client_auth_api'             => GlobalArray::getClientAuthApiOptions(),
            'fileformat_type'             => GlobalArray::getList_Type_Name_Options(),
            'superior_file_check'         => GlobalArray::getSuperior_File_Check_Type_Options(),
            'remove_unsanitized_file'     => GlobalArray::getRemove_unsanitized_fileOptions(),
        ];
    }

    function getAccountlock_mail_body() {
        return $this->TemplateLogic->get_template_file_contents("", "accountlock_mail_body.txt");
    }
    function getAccountlock_mail_body_en() {
        return $this->TemplateLogic->get_template_file_contents("en", "accountlock_mail_body.txt");
    }
    function getAccountlock_mail_body_cn() {
        return $this->TemplateLogic->get_template_file_contents("cn", "accountlock_mail_body.txt");
    }
    function getAccountlock_mail_body_tcn() {
        return $this->TemplateLogic->get_template_file_contents("tcn", "accountlock_mail_body.txt");
    }
    function getAuth_type_notice_main() {
        $str = '';
        $usage_auth_types = $this->CommonList->getUsageAuthTypes();
        if (isset($usage_auth_types[1]) OR isset($usage_auth_types[2])) {
            unset($usage_auth_types[0]);
            $str = implode('、', $usage_auth_types);
            $str = '<br /><span>※' . $str . 'のユーザには適用されない設定項目があります。</span>';
        }
        return $str;
    }
    function getAuth_type_notice() {
        $str = '';
        $usage_auth_types = $this->CommonList->getUsageAuthTypes();
        if (isset($usage_auth_types[1]) OR isset($usage_auth_types[2])) {
            unset($usage_auth_types[0]);
            $str = implode('、', $usage_auth_types);
            $str = '<br /><span>※' . $str . 'のユーザには適用されません。</span>';
        }
        return $str;
    }
    function getContinuous_login_mail_body_user()
    {
        return $this->TemplateLogic->get_template_file_contents("", "continuouslogin_mail_body_user.txt");
    }
    function getContinuous_login_mail_body_admin()
    {
        return $this->TemplateLogic->get_template_file_contents("", "continuouslogin_mail_body_admin.txt");
    }
    function getCommonDomainCountWhite() {
        return $this->DomainLogic->getCommonCount($this->MdbDomain,"0");
    }
    function getCommonDomainCountBlack() {
        return $this->DomainLogic->getCommonCount($this->MdbDomain,"1");
    }
    function getCommonDomainCountUser() {
        return $this->DomainLogic->getCountByUser($this->MdbDomain);
    }
    function getFileformatCountWhite() {
        return $this->FileformatLogic->getFileformatCount("0");
    }
    function getFileformatCountBlack() {
        return $this->FileformatLogic->getFileformatCount("1");
    }
    function getVirus_check_size_limit() {
        return StrChange::CapacityUnit(VIRUS_CHECK_SIZE_LIMIT*1024*1024);
    }

    function getViewOptions()
    {
        return [
            'manual_dl'                 => GlobalArray::getManual_Dl_Options(),
            'meter'                     => GlobalArray::getMeter_Flg_Options(),
            'folder_tree_scroll_bar'    => GlobalArray::getFolder_Tree_Scroll_Bar_Flg_Options(),
            'folder_full_path'          => GlobalArray::getFolder_Full_Path_Flg_Options(),
            'folder_display_inline'     => GlobalArray::getFolder_Display_Inline_Flg_Options(),
            'upload_user_name'          => GlobalArray::getUpload_User_Name_Flg_Options(),
            'upload_return'              => GlobalArray::getUpload_Return_Flg_Options(),
            'share_search'              => GlobalArray::getShare_Search_Flg_Options(),
            'view_common_address_book'  => GlobalArray::getView_Common_Address_Book_Flg_Options(),
            'view_history_ip'           => GlobalArray::getView_History_Ip_Flg_Options(),
            'kp_news'                   => GlobalArray::getKp_News_Flg_Options(),
        ];
    }
    function getNewsList() {
        return $this->DtoNews->getNewsList();
    }
    function getSystemOptions()
    {
        return [
            'file_move'                 => GlobalArray::getFile_Move_Flg_Options(),
            'folder_dl'                 => GlobalArray::getFolder_Dl_Flg_Options(),
            'regular_export'            => GlobalArray::getRegular_Export_Flg_Options(),
            'radio'                     => GlobalArray::getHFRadio_text(),
            'api'                       => GlobalArray::getApi_Flg_Options()
        ];
    }
    function getCn_images() {
        return file_exists(WF_PATH."/htdocs/images-cn");
	}
    function getOutlookImgPath() {
        if (file_exists(WF_PATH."/htdocs/images/outlook_addin/icon-80.png")) {
            return APP_PATH . "/images/outlook_addin/icon-80.png";
        } else {
            return APP_PATH . "/images/outlook_addin/icon-128-org.gif";
        }
    }
    //ロゴの取得
    function getLogoByLanguage($lang)
    {
        $postfix = "";
        if (in_array($lang, ['en', 'cn', 'tcn'])) { //他言語
            $postfix  = "-" . $lang;
        }
        if (file_exists(WF_PATH . "/htdocs/images" . $postfix . "/" . LOGO_PREFIX . "logo.gif")) {
            return APP_PATH . "/images" . $postfix . "/" . LOGO_PREFIX . "logo.gif";
        } else {
            return APP_PATH . "/images" . $postfix . "/default_logo.gif";
        }
    }
    function getLogo()
    {
        return $this->getLogoByLanguage("");
    }
    function getLogoEn()
    {
        return $this->getLogoByLanguage("en");
    }
    function getLogoCn()
    {
        return $this->getLogoByLanguage("cn");
    }
    function getLogoTcn()
    {
        return $this->getLogoByLanguage("tcn");
    }
}
?>

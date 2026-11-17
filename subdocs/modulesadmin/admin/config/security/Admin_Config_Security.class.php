<?php
/**
 * [機能説明] 各種設定 - セキュリティ設定 - 一覧
 * @updater     Hiroko Yanai（2018/09/28）passmust_span -> user_passmust_span
 * @updater     Hiroko Yanai（2018/10/01）admin_passmust_span の設定
 *
 * @package     WebFile
 * @version     3.16.0
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Security {

    var $session;
    var $DtoConfigs;
    var $MdbConfigs;
    var $MdbMchoice;
    var $MdbDomain;
    var $DomainLogic;
    var $FileformatLogic;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Securityが実行されました","Admin_Config_Security#execute");

        $this->MdbConfigs->getList($this->DtoConfigs);

        return "success";
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
    function setDtoGroups($DtoGroups){
        $this->DtoGroups = $DtoGroups;
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

    // getter
    function getConfig() {
        return $this->DtoConfigs->configslist;
    }
    function getAccountlock_Mail_Flg_Options()
    {
        return GlobalArray::getAccountlock_Mail_Flg_Options();
    }
    function getReissue_Flg_Options()
    {
        return GlobalArray::getReissue_Flg_Options();
    }
    function getReissue_Check_Options()
    {
        return GlobalArray::getReissue_Check_Options();
    }
    function getPasstype_Options()
    {
        return $this->MdbMchoice->getOptions("pass_type_id");
    }
    function getPass_Differ_Flg_Options()
    {
        return GlobalArray::getPass_Differ_Flg_Options();
    }
    function getPassmin()
    {
        $this->MdbConfigs->getInfo($this->DtoConfigs,"pass_min_num");
        return $this->DtoConfigs->getValue();
    }
    function getPassmax()
    {
        $this->MdbConfigs->getInfo($this->DtoConfigs,"pass_max_num");
        return $this->DtoConfigs->getValue();
    }
    function getSMSPassmin()
    {
        $this->MdbConfigs->getInfo($this->DtoConfigs,"smspass_min_num");
        return $this->DtoConfigs->getValue();
    }
    function getSMSPassmax()
    {
        $this->MdbConfigs->getInfo($this->DtoConfigs,"smspass_max_num");
        return $this->DtoConfigs->getValue();
    }
    function getPass_Account_NG_Flg_Options()
    {
        return GlobalArray::getPass_Account_NG_Flg_Options();
    }
    function getTwo_step_auth_flg_options()
    {
        return GlobalArray::getTwo_Step_Auth_Flg_Options();
    }
    function getAdmin_Two_step_auth_flg_options()
    {
        return GlobalArray::getTwo_Step_Auth_Flg_Options();
    }
    function getAccess_Flg_Options()
    {
        return GlobalArray::getAccess_Flg_Options();
    }
    function getVirusPassProtectFlg()
    {
      $this->MdbConfigs->getInfo($this->DtoConfigs,"virus_pass_protect_flg");
      return $this->DtoConfigs->getValue();
    }
    function getVirusTimeoutFlg()
    {
      $this->MdbConfigs->getInfo($this->DtoConfigs,"virus_timeout_flg");
      return $this->DtoConfigs->getValue();
    }
    function getVirusRiskwareFlg() {
        return $this->MdbConfigs->getDirectValue("virus_riskware_flg");
    }
    function getVirusMaliceFlg() {
        return $this->MdbConfigs->getDirectValue("virus_malice_flg");
    }
    function getVirusCantUploadWhenScannerErrorFlg() {
        return $this->MdbConfigs->getDirectValue("virus_cant_upload_when_scanner_error_flg");
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
    function getUser_account_lock_cnt() {
        return $this->MdbConfigs->getDirectValue("user_account_lock_cnt");
    }
    function getUser_account_lock_time() {
        return $this->MdbConfigs->getDirectValue("user_account_lock_time");
	}
    function getAdmin_account_lock_cnt() {
        return $this->MdbConfigs->getDirectValue("admin_account_lock_cnt");
	}
    function getAdmin_account_lock_time() {
        return $this->MdbConfigs->getDirectValue("admin_account_lock_time");
	}
    function getClientAuthAdmin() {
         $option = GlobalArray::getClientAuthOptions();
         return $option[ $this->MdbConfigs->getDirectValue('client_auth_admin')];
    }
    function getClientAuthUser() {
         $option = GlobalArray::getClientAuthOptions();
         return $option[ $this->MdbConfigs->getDirectValue('client_auth_user')];
    }
    function getClientAdminIpFlg() {
        return $this->MdbConfigs->getDirectValue('client_admin_ip_flg');
    }
    function getClientUserIpFlg() {
        return $this->MdbConfigs->getDirectValue('client_user_ip_flg');
    }
    function getClientAuthApi() {
         $option = GlobalArray::getClientAuthApiOptions();
         return $option[ $this->MdbConfigs->getDirectValue('client_auth_api_flg')];
    }

    function getFileformatCountWhite() {
        return $this->FileformatLogic->getFileformatCount("0");
    }
    function getFileformatCountBlack() {
        return $this->FileformatLogic->getFileformatCount("1");
    }
    function getFileformat_type() {
        $list = GlobalArray::getList_Type_Name_Options();
        return $list[$this->MdbConfigs->getDirectValue("fileformat_type")];
    }

    function getSuperiorFileDownloadCheckFlg(){
        $option = GlobalArray::getSuperior_File_Check_Type_Options();
        return $option[$this->MdbConfigs->getDirectValue("superior_file_check_type")];
    }

    function getRemove_unsanitized_file()
    {
        $option = GlobalArray::getRemove_unsanitized_fileOptions();
        return $option[$this->MdbConfigs->getDirectValue("remove_unsanitized_file")];
    }

    // 2017/12/04 @yanai 連続ログイン通知
    function getContinuous_Login_Notice_Flg_Options() {
        return GlobalArray::getContinuous_Login_Notice_Flg_Options();
    }
    function getContinuous_login_notice_flg() {
        return $this->MdbConfigs->getDirectValue("Continuous_login_notice_flg");
    }
    function getContinuous_login_span_minute() {
        return $this->MdbConfigs->getDirectValue("continuous_login_span_minute");
    }
    function getContinuous_login_num() {
        return $this->MdbConfigs->getDirectValue("continuous_login_num");
    }
    function getContinuous_login_notice_address() {
        return $this->MdbConfigs->getDirectValue("continuous_login_notice_address");
    }
    function getContinuous_Login_Notice_Each_Time_Flg_Options() {
        return GlobalArray::getContinuous_Login_Notice_Each_Time_Flg_Options();
    }
    function getContinuous_login_notice_each_time_flg() {
        return $this->MdbConfigs->getDirectValue("continuous_login_notice_each_time_flg");
    }
}
?>

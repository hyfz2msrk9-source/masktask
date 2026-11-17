<?php
/**
 * [機能説明] 各種設定 - その他設定 - 一覧
 * @updater     Hiroko Yanai（2018/06/25）フォルダツリーのスクロールバー
 * @updater     Hiroko Yanai（2018/06/26）フォルダのフルパス
 * @updater     Hiroko Yanai（2018/06/27）登録者を表示する
 * @updater     Hiroko Yanai（2018/06/29）フォルダをファイル一覧に表示する
 *
 * @package     WebFile
 * @version     3.15.1
 * @author
 * @copyright   KEYPOINT.INC
 * @access      public
 */
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] 各種設定 - その他設定 - 一覧
 * @updater     Hiroko Yanai（2018/04/13）SAML認証(OneLogin)に対応。SSOやSAMLでも、設定は更新可能とする
 *
 * @package     WebFile
 * @version     3.15.2
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_Config_Othermenu {

    var $session;
    var $DtoNews;
    var $MdbNews;
    var $DtoConfigs;
    var $MdbConfigs;
    var $DtoUser;
    var $MdbUser;
    var $ListLogic;

    function execute() {

        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Othermenuが実行されました","Admin_Config_Othermenu#execute");

        $this->MdbConfigs->getList($this->DtoConfigs);

        // お知らせ件数取得
        $this->MdbNews->getListCount($this->DtoNews);

        // メールヘッダ・フッタ 取得
        $this->MdbUser->getHFFlg($this->DtoUser);

        return "success";
    }

    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoNews($DtoNews){
        $this->DtoNews = $DtoNews;
    }
    function setMdbNews($MdbNews){
        $this->MdbNews = $MdbNews;
    }
    function setDtoConfigs($DtoConfigs){
        $this->DtoConfigs = $DtoConfigs;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setDtoUser(&$DtoUser) {
        $this->DtoUser = $DtoUser;
    }
    function setMdbUser(&$MdbUser) {
        $this->MdbUser = $MdbUser;
    }
    function setListLogic(&$ListLogic) {
        $this->ListLogic = $ListLogic;
    }
    function getNotify_personal_folder_usage()
    {
        return $this->MdbConfigs->getDirectValue("notify_personal_folder_usage");
    }
    function getNotify_share_folder_usage()
    {
        return $this->MdbConfigs->getDirectValue("notify_share_folder_usage");
    }

    // getter
    function getConfig() {
        return $this->DtoConfigs->configslist;
    }
    function getFile_Move_Flg_Options()
    {
        return GlobalArray::getFile_Move_Flg_Options();
    }
    function getFolder_Dl_Flg_Options()
    {
        return GlobalArray::getFolder_Dl_Flg_Options();
    }
    function getShare_Search_Flg_Options()
    {
        return GlobalArray::getShare_Search_Flg_Options();
    }
    function getViewHistoryIpFlg()
    {
      $this->MdbConfigs->getInfo($this->DtoConfigs,"view_history_ip_flg");
      return $this->DtoConfigs->getValue();
    }
    function getNewsCount() {
        return $this->DtoNews->getRow_count();
    }
    function getRegular_Export_Flg_Options()
    {
        return GlobalArray::getRegular_Export_Flg_Options();
    }
    function getArchive_notice_addr() {
        // アーカイブ通知先
        $addrs = null;
        if ($this->MdbConfigs->getDirectValue("archive_notice_addr")) {
            $addrs = explode(",", $this->MdbConfigs->getDirectValue("archive_notice_addr"));
        }
        return $addrs;
    }
    function getEvi_archive_autonotice() {
        return $this->MdbConfigs->getDirectValue("evi_archive_autonotice");
    }
    function getEvi_archive_autodelete() {
        return $this->MdbConfigs->getDirectValue("evi_archive_autodelete");
    }
    function getEvi_archive_export_autonotice() {
        return $this->MdbConfigs->getDirectValue("evi_archive_export_autonotice");
    }
    function getEvi_archive_export_autodelete() {
        return $this->MdbConfigs->getDirectValue("evi_archive_export_autodelete");
    }

    function getDeldts(){
        if ($this->MdbConfigs->getValCnt("del_span") >= 1) {
            $this->MdbConfigs->getInfo($this->DtoConfigs,"del_span");
            return $this->DtoConfigs->getValue();
        } else {
            return "";
        }
    }
    function getRadio() {
        return $this->ListLogic->getHFRadio_text();
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
    function getMeter_Flg_Options()
    {
        return GlobalArray::getMeter_Flg_Options();
    }
    function getFolder_Tree_Scroll_Bar_Flg_Options()
    {
        return GlobalArray::getFolder_Tree_Scroll_Bar_Flg_Options();
    }
    function getFolder_Full_Path_Flg_Options()
    {
        return GlobalArray::getFolder_Full_Path_Flg_Options();
    }
    function getFolder_Display_Inline_Flg_Options()
    {
        return GlobalArray::getFolder_Display_Inline_Flg_Options();
    }
    function getUpload_User_Name_Flg_Options()
    {
        return GlobalArray::getUpload_User_Name_Flg_Options();
    }
    function getViewCommonAddressBookFlg()
    {
      $this->MdbConfigs->getInfo($this->DtoConfigs,"view_common_address_book_flg");
      return $this->DtoConfigs->getValue();
    }
    function getValue() {
        return $this->MdbConfigs->getDirectValue("mail_addr");
    }
    function getCapacity_alert_notice(){
        return $this->MdbConfigs->getDirectValue("capacity_alert_notice");
    }
    function getCapacity_alert_notice_addr(){
        $addrs = null;
        if($this->MdbConfigs->getDirectValue("capacity_alert_notice_addr")){
            $addrs = explode(",", $this->MdbConfigs->getDirectValue("capacity_alert_notice_addr"));
        }
        return $addrs;
    }
    function getSaml_authentication_issuer() {
        return $this->MdbConfigs->getDirectValue("saml_authentication_issuer");
    }
    function getSaml_authentication_saml_endpoint() {
        return $this->MdbConfigs->getDirectValue("saml_authentication_saml_endpoint");
    }
    function getAdmin_tel_flg(){
        return $this->DtoConfigs->getConfigsList()["admin_tel_flg"];
    }
    function getAdmin_mail_flg(){
        return $this->DtoConfigs->getConfigsList()["admin_mail_flg"];
    }
    function getUserKpNewsText()
    {
        $flg = $this->MdbConfigs->getDirectValue("user_kp_news_flg");
        $array = GlobalArray::getKp_News_Flg_Options();
        return  $array[$flg];
    }
    function getAdminKpNewsText()
    {
        $flg = $this->MdbConfigs->getDirectValue("admin_kp_news_flg");
        $array = GlobalArray::getKp_News_Flg_Options();
        return  $array[$flg];
    }
}
?>

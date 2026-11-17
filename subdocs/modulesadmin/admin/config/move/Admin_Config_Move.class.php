<?php
/**
 * [機能説明] 各種設定 - ?
 * 2018/09/28@yanai 不使用?
 * @updater
 *
 * @package     WebFile
 * @version     3.?.?
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Move {
    var $session;
    var $DtoConfigs;
    var $MdbConfigs;
    var $DtoGroups;
    var $MdbGroups;
    var $MdbMchoice;
    var $use_capacity;
    var $group_count;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Moveが実行されました","Admin_Config_Move#execute");
        $this->MdbConfigs->getList($this->DtoConfigs);
        $this->use_capacity = $this->MdbGroups->getUseCapacity();
        $this->group_count = $this->MdbGroups->getCount();

        $list = $this->DtoConfigs->getConfigslist();

        //sessionに記入
        $this->session->setParameter("sends_flg",         $list['sends_flg']);
        $this->session->setParameter("file_move_flg",     $list['file_move_flg']);
        $this->session->setParameter("mailer_flg",        $list['mailer_flg']);
        $this->session->setParameter("reissue_flg",       $list['reissue_flg']);
        $this->session->setParameter("del_span",          $list['del_span']);
        $this->session->setParameter("openfolders_flg",   $list['openfolders_flg']);
        $this->session->setParameter("open_subfolder_flg",$list['open_subfolder_flg']);
        $this->session->setParameter("thumbnail_flg",     $list['thumbnail_flg']);
        $this->session->setParameter("passmust_span",     $list['passmust_span']);
        $this->session->setParameter("pass_min_num",      $list['pass_min_num']);
        $this->session->setParameter("pass_max_num",      $list['pass_max_num']);
        $this->session->setParameter("pass_type_id",      $list['pass_type_id']);
        $this->session->setParameter("pass_differ_flg",   $list['pass_differ_flg']);
        $this->session->setParameter("sample_temp_flg",   $list['sample_temp_flg']);
        $this->session->setParameter("default_pass_flg",   $list['default_pass_flg']);
        $this->session->setParameter("default_openpass_flg",   $list['default_openpass_flg']);
        $this->session->setParameter("default_collectpass_flg",   $list['default_collectpass_flg']);
        $this->session->setParameter("zip_pass_flg",   $list['zip_pass_flg']);

	$this->session->setParameter("send_mail_limit",   $list['send_mail_limit']);
	$this->session->setParameter("file_send_limit",   $list['file_send_limit']);
	$this->session->setParameter("file_upload_limit", $list['file_upload_limit']);
	$this->session->setParameter("limitdt_mark",      $list['limitdt_mark']);
	$this->session->setParameter("maxdltime_mark",    $list['maxdltime_mark']);
	$this->session->setParameter("collect_limitdt_mark",      $list['collect_limitdt_mark']);

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
    function setMdbGroups($MdbGroups) {
        $this->MdbGroups = $MdbGroups;
    }
    function setMdbMchoice($MdbMchoice) {
        $this->MdbMchoice = $MdbMchoice;
    }

    // getter
    function getFile_Move_Flg_Options()
    {
        return GlobalArray::getFile_Move_Flg_Options();
    }
    function getConfig() {
        return $this->DtoConfigs->configslist;
    }
}
?>

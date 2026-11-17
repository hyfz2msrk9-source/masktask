<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Archive {
    var $session;
    var $MdbConfigs;

    var $MdbArchiveFile;
    var $MdbArchiveExport;
    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Archiveが実行されました","Admin_Config_Archive#execute");
        //
        // エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();
        
        if(!EVI_ARCHIVE_FLG){
            $errorList->add("archive::error", "この機能は、現在ご利用頂けません。","Sorry, this function cannot be used now. ");
            return "error";
        }
        $this->session->setParameter("upload_archive_flg",   $this->getUpload_archive_flg());
        $this->session->setParameter("send_archive_flg",     $this->getSend_archive_flg());
        $this->session->setParameter("collect_archive_flg",  $this->getCollect_archive_flg());
        
        $this->session->setParameter("archive_storage_span", $this->getArchive_storage_span());
        if($this->getEdit_flg()){
            $this->session->setParameter("evi_archive_autonotice",$this->getEvi_archive_autonotice());
            $this->session->setParameter("evi_archive_autodelete",$this->getEvi_archive_autodelete());
            $this->session->setParameter("evi_archive_export_autonotice",$this->getEvi_archive_export_autonotice());
            $this->session->setParameter("evi_archive_export_autodelete",$this->getEvi_archive_export_autodelete());
        }    
        $this->session->setParameter("regular_export_flg",   $this->getRegular_export_flg());
        $this->session->setParameter("export_storage_span",  $this->getExport_storage_span());
        $this->session->setParameter("archive_notice_addr",  $this->getArchive_notice_addr());        

        return "success";
    }
    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setMdbArchiveFile($MdbArchiveFile) {
        $this->MdbArchiveFile = $MdbArchiveFile;
    }
    function setMdbArchiveExport($MdbArchiveExport) {
        $this->MdbArchiveExport = $MdbArchiveExport;
    }

    // getter
    function getRegular_Export_Flg_Options()
    {
        return GlobalArray::getRegular_Export_Flg_Options();
    }
    function getUpload_archive_flg()
    {
        return $this->MdbConfigs->getDirectValue("upload_archive_flg");
    }
    function getSend_archive_flg()
    {
        return $this->MdbConfigs->getDirectValue("send_archive_flg");
    }
    function getCollect_archive_flg()
    {
        return $this->MdbConfigs->getDirectValue("collect_archive_flg");
    }
    function getArchive_storage_span()
    {
        return $this->MdbConfigs->getDirectValue("archive_storage_span");
    }
    function getRegular_export_flg()
    {
        return $this->MdbConfigs->getDirectValue("regular_export_flg");
    }
    function getExport_storage_span()
    {
        return $this->MdbConfigs->getDirectValue("export_storage_span");
    }
    function getArchive_notice_addr()
    {
        return $this->MdbConfigs->getDirectValue("archive_notice_addr");
    }
    function getEvi_archive_autonotice()
    {
        return $this->MdbConfigs->getDirectValue("Evi_archive_autonotice");
    }
    function getEvi_archive_autodelete()
    {
        return $this->MdbConfigs->getDirectValue("Evi_archive_autodelete");
    }
    function getEvi_archive_export_autonotice() 
    {
        return $this->MdbConfigs->getDirectValue("evi_archive_export_autonotice");
    }
    function getEvi_archive_export_autodelete() 
    {
        return $this->MdbConfigs->getDirectValue("evi_archive_export_autodelete");
    }
    function getEdit_flg(){
        if($this->session->getParameter("login_admin_level") == 10){
            return true;
        }else{
            return false;
        }    
    }

    // アーカイブ容量
    function getArchive_capacity_B() {
        return EVI_ARCHIVE_MAX_SIZE_B;
    }
    function getArchive_capacity() {
        return StrChange::CapacityUnit($this->getArchive_capacity_B());
    }
    function getArchive_used_capacity_B() {
        return $this->MdbArchiveFile->getUsedCapacity();
    }
    function getArchive_used_capacity() {
        return StrChange::CapacityUnit($this->getArchive_used_capacity_B());
    }
    function getArchive_rate() {
        return (int)($this->getArchive_used_capacity_B() / $this->getArchive_capacity_B() * 100);
    }
    function getArchive_count() {
        return $this->MdbArchiveFile->getCount();
    }

    // エクスポート容量
    function getExport_capacity_B() {
        return EVI_ARCHIVE_EXPORT_MAX_SIZE_B;
    }
    function getExport_capacity() {
        return StrChange::CapacityUnit($this->getExport_capacity_B());
    }
    function getExport_used_capacity_B() {
        return $this->MdbArchiveExport->getUsedCapacity();
    }
    function getExport_used_capacity() {
        return StrChange::CapacityUnit($this->getExport_used_capacity_B());
    }
    function getExport_rate() {
        return (int)($this->getExport_used_capacity_B() / $this->getExport_capacity_B() * 100);
    }
    function getExport_count() {
        return $this->MdbArchiveExport->getCount();
    }
}
/*
 * Local Variables:
 * mode: php
 * coding: utf-8-unix
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * indent-tabs-mode: nil
 * End:
 */
?>

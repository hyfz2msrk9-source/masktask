<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Archive_Done {
    var $session;
    var $MdbConfigs;

    var $MdbArchiveFile;
    var $MdbArchiveExport;

    var $DtoLog;
    var $MdbLog;

    // request
    var $upload_archive_flg;
    var $send_archive_flg;
    var $collect_archive_flg;
    var $archive_storage_span;
    var $regular_export_flg;
    var $export_storage_span;
    var $archive_notice_addr;

    var $evi_archive_autonotice;
    var $evi_archive_autodelete;
    var $evi_archive_export_autonotice;
    var $evi_archive_export_autodelete;
    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Archive_Doneが実行されました","Admin_Config_Archive_Done#execute");

        // エラー発生
        $container =& DIContainerFactory::getContainer();
        $actionChain =& $container->getComponent("ActionChain");
        $errorList =& $actionChain->getCurErrorList();
        // 入力チェック
        if (!preg_match("/^[0-1]{0,1}$/", $this->upload_archive_flg)) {
            return "error";
        }
        if (!preg_match("/^[0-1]{0,1}$/", $this->send_archive_flg)) {
            return "error";
        }
        if (!preg_match("/^[0-1]{0,1}$/", $this->collect_archive_flg)) {
            return "error";
        }
        if (!array_key_exists($this->regular_export_flg, $this->getRegular_Export_Flg_Options())) {
            return "error";
        }

        if($this->getEdit_flg()){
            //アーカイブ設定
            if($this->evi_archive_autonotice != ""){
                if(is_numeric($this->evi_archive_autonotice)){
                    if($this->evi_archive_autonotice >= 0 && $this->evi_archive_autonotice <= 100){
                        if($this->evi_archive_autonotice !== '0' && (!preg_match("/^[1-9][0-9]*$/", $this->evi_archive_autonotice))){
    		        		$errorList->add("Admin_Config_Archive_Done::error", "アーカイブ:警告メールを送信する容量設定は整数で入力してください。");
            				return "input";
                        }else{
                            $this->MdbConfigs->setValue('evi_archive_autonotice',$this->evi_archive_autonotice);
                        }
                    }else{
                       $errorList->add("Admin_Config_Archive_Done::error", "アーカイブ:警告メールを送信する容量設定は0～100の範囲で入力してください。");
                       return "input";
                    }
                }else{
                    $errorList->add("Admin_Config_Archive_Done::error", "アーカイブ:警告メールを送信する容量設定は整数で入力してください。");
                    return "input";
                }
            }else{
                    $errorList->add("Admin_Config_Archive_Done::error", "アーカイブ:警告メールを送信する容量設定が入力されていません。");
                    return "input";
            }

            //アーカイブ設定
            if($this->evi_archive_autodelete != ""){
                if(is_numeric($this->evi_archive_autodelete)){
                    if($this->evi_archive_autodelete >= 0 && $this->evi_archive_autodelete <= 100){
                        if($this->evi_archive_autodelete !== '0' && (!preg_match("/^[1-9][0-9]*$/",$this->evi_archive_autodelete))){
    		        		$errorList->add("Admin_Config_Archive_Done::error", "アーカイブ:データを削除する容量設定は整数で入力してください。");
            				return "input";
                        }else{
                            $this->MdbConfigs->setValue('evi_archive_autodelete',$this->evi_archive_autodelete);
                        }
                    }else{
                       $errorList->add("Admin_Config_Archive_Done::error", "アーカイブ:データを削除する容量設定は0～100の範囲で入力してください。");
                       return "input";
                    }
                }else{
                    $errorList->add("Admin_Config_Archive_Done::error", "アーカイブ:データを削除する容量設定は整数で入力してください。");
                    return "input";
                }
            }else{
                    $errorList->add("Admin_Config_Archive_Done::error", "アーカイブ:データを削除する容量設定が入力されていません。");
                    return "input";
            }

            //設定
            if($this->evi_archive_export_autonotice != ""){
                if(is_numeric($this->evi_archive_export_autonotice)){
                    if($this->evi_archive_export_autonotice >= 0 && $this->evi_archive_export_autonotice <= 100){
                        if($this->evi_archive_export_autonotice !== '0' && (!preg_match("/^[1-9][0-9]*$/", $this->evi_archive_export_autonotice))){
    		        		$errorList->add("Admin_Config_Admin_Done::error", "エクスポート:警告メールを送信する容量設定は整数で入力してください。");
            				return "input";
                        }else{
                            $this->MdbConfigs->setValue('evi_archive_export_autonotice',$this->evi_archive_export_autonotice);
                        }
                    }else{
                       $errorList->add("Admin_Config_Admin_Done::error", "エクスポート:警告メールを送信する容量設定は0～100の範囲で入力してください。");
                       return "input";
                    }
                }else{
                    $errorList->add("Admin_Config_Admin_Done::error", "エクスポート:警告メールを送信する容量設定は整数で入力してください。");
                    return "input";
                }
            }else{
                    $errorList->add("Admin_Config_Admin_Done::error", "エクスポート:警告メールを送信する容量設定が入力されていません。");
                    return "input";
            }

            //設定
            if($this->evi_archive_export_autodelete != ""){
                if(is_numeric($this->evi_archive_export_autodelete)){
                    if($this->evi_archive_export_autodelete >= 0 && $this->evi_archive_export_autodelete <= 100){
                        if($this->evi_archive_export_autodelete !== '0' && (!preg_match("/^[1-9][0-9]*$/",$this->evi_archive_export_autodelete))){
    		        		$errorList->add("Admin_Config_Admin_Done::error", "エクスポート:データを削除する容量設定は整数で入力してください。");
            				return "input";
                        }else{
                            $this->MdbConfigs->setValue('evi_archive_export_autodelete',$this->evi_archive_export_autodelete);
                        }
                    }else{
                       $errorList->add("Admin_Config_Admin_Done::error", "エクスポート:データを削除する容量設定は0～100の範囲で入力してください。");
                       return "input";
                    }
                }else{
                    $errorList->add("Admin_Config_Admin_Done::error", "エクスポート:データを削除する容量設定は整数で入力してください。");
                    return "input";
                }
            }else{
                    $errorList->add("Admin_Config_Admin_Done::error", "エクスポート:データを削除する容量設定が入力されていません。");
                    return "input";
            }
        }

        
        // メールアドレス重複チェック
        $update_data = "";
        $update_memo = "";
        if ($this->archive_notice_addr) {
            $addrs2 = explode(",", $this->archive_notice_addr);
            $addrs = array_unique($addrs2);
            foreach ($addrs as $val) {
                if (!$update_data) { $update_data = $val; } else { $update_data.= ",".$val; }
                $update_memo.= "　　".$val."\n";
            }
            $this->archive_notice_addr = $update_data;
        }
        // 更新
        $this->MdbConfigs->setValue('upload_archive_flg',   $this->upload_archive_flg);
        $this->MdbConfigs->setValue('send_archive_flg',     $this->send_archive_flg);
        $this->MdbConfigs->setValue('collect_archive_flg',  $this->collect_archive_flg);
        $this->MdbConfigs->setValue('archive_storage_span', $this->archive_storage_span);
        if($this->getEdit_flg()){
            $this->MdbConfigs->setValue('evi_archive_autonotice',$this->evi_archive_autonotice);
            $this->MdbConfigs->setValue('evi_archive_autodelete',$this->evi_archive_autodelete);
            $this->MdbConfigs->setValue('evi_archive_export_autonotice',$this->evi_archive_export_autonotice);
            $this->MdbConfigs->setValue('evi_archive_export_autodelete',$this->evi_archive_export_autodelete);
        }
        $this->MdbConfigs->setValue('regular_export_flg',   $this->regular_export_flg);
        $this->MdbConfigs->setValue('export_storage_span',  $this->export_storage_span);
        $this->MdbConfigs->setValue('archive_notice_addr',  $this->archive_notice_addr);

        // Log
        $history_id = 112; // 112:各種設定＞機能設定
        $memo = "";
        // アップロード機能設定
        $change_archive_flg = 0;
        if ( $this->upload_archive_flg != $this->session->getParameter("upload_archive_flg")){
            if ($this->upload_archive_flg) {
                $memo .= "　　アップロード時にアーカイブを行う\n";
            }
            $change_archive_flg = 1;
        }
        if ( $this->send_archive_flg != $this->session->getParameter("send_archive_flg")){
            if ($this->send_archive_flg) {
                $memo .= "　　配布時・フォルダ公開時にアーカイブを行う\n";
            }
            $change_archive_flg = 1;
        }
        if ( $this->collect_archive_flg != $this->session->getParameter("collect_archive_flg")){
            if ($this->collect_archive_flg) {
                $memo .= "　　収集時にアーカイブを行う\n";
            }
            $change_archive_flg = 1;
        }
        if ($change_archive_flg) {
            if ($memo !== "") {
                $memo = "アーカイブ機能設定：\n" . $memo;
            } else {
                $memo = "アーカイブ機能設定：アーカイブしない\n";
            }
        }else{
            $memo = "アーカイブ機能設定：変更なし\n";
        }
        if($this->getEdit_flg()){
            if ( $this->evi_archive_autonotice != $this->session->getParameter("evi_archive_autonotice")){
                $memo .= "アーカイブ：警告メールが送信される容量設定：".$this->evi_archive_autonotice."%\n";
            }
            if ( $this->evi_archive_autodelete != $this->session->getParameter("evi_archive_autodelete")){
                $memo .= "アーカイブ：データを削除する容量設定：".$this->evi_archive_autodelete."%\n";
            }
            if ( $this->evi_archive_export_autonotice != $this->session->getParameter("evi_archive_export_autonotice")){
                $memo .= "Eviエクスポート：警告メールが送信される容量設定：".$this->evi_archive_export_autonotice."%\n";
            }
            if ( $this->evi_archive_export_autodelete != $this->session->getParameter("evi_archive_export_autodelete")){
                $memo .= "Eviエクスポート：データを削除する容量設定：".$this->evi_archive_export_autodelete."%\n";
            }
        }
                
        
        if ($this->archive_storage_span != $this->session->getParameter("archive_storage_span")){
            $aft = $this->archive_storage_span;
            $bef = $this->session->getParameter("archive_storage_span");
            $memo .= "保管期間設定：".$bef."→".$aft."\n";
        }
        if ( $this->regular_export_flg != $this->session->getParameter("regular_export_flg")){
            $options = $this->getRegular_Export_Flg_Options();
            $memo .= "定期エクスポート：".$options[$this->regular_export_flg]."\n";
        }
        if ($this->export_storage_span != $this->session->getParameter("export_storage_span")){
            $aft = $this->export_storage_span;
            $bef = $this->session->getParameter("export_storage_span");
            $memo .= "エクスポートファイル保管期間設定：".$bef."→".$aft."\n";
        }
        if ($this->archive_notice_addr != $this->session->getParameter("archive_notice_addr")){
            $memo .= "メールアドレス：\n";
            /*
            $addrs2 = explode(",", $this->archive_notice_addr);
            $addrs = array_unique($addrs2);
            foreach ($addrs as $val) {
                $memo .= "　　" . $val . "\n";
            }
            */
            $memo .= $update_memo;
        }
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        // セッションクリア
        $this->session->removeParameter("upload_archive_flg");
        $this->session->removeParameter("send_archive_flg");
        $this->session->removeParameter("collect_archive_flg");

        $this->session->removeParameter("archive_storage_span");
        $this->session->removeParameter("evi_archive_autonotice");
        $this->session->removeParameter("evi_archive_autodelete");
        $this->session->removeParameter("evi_archive_export_autonotice");
        $this->session->removeParameter("evi_archive_export_autodelete");
        
        $this->session->removeParameter("regular_export_flg");
        $this->session->removeParameter("export_storage_span");
        $this->session->removeParameter("archive_notice_addr");

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
    function setDtoLog($DtoLog) {
      $this->DtoLog = $DtoLog;
    }
    function setMdbLog($MdbLog) {
      $this->MdbLog = $MdbLog;
    }

    function setUpload_archive_flg($upload_archive_flg) {
        $this->upload_archive_flg = $upload_archive_flg;
    }
    function setSend_archive_flg($send_archive_flg) {
        $this->send_archive_flg = $send_archive_flg;
    }
    function setCollect_archive_flg($collect_archive_flg) {
        $this->collect_archive_flg = $collect_archive_flg;
    }
    function setArchive_storage_span($archive_storage_span) {
        $this->archive_storage_span = $archive_storage_span;
    }
    function setRegular_export_flg($regular_export_flg) {
        $this->regular_export_flg = $regular_export_flg;
    }
    function setExport_storage_span($export_storage_span) {
        $this->export_storage_span = $export_storage_span;
    }
    function setArchive_notice_addr($archive_notice_addr) {
        $this->archive_notice_addr = $archive_notice_addr;
    }
    function setEvi_archive_autonotice(&$evi_archive_autonotice)
    {
        $this->evi_archive_autonotice =& $evi_archive_autonotice;
    }
    function setEvi_archive_autodelete(&$evi_archive_autodelete)
    {
        $this->evi_archive_autodelete =& $evi_archive_autodelete;
    }    
    function setEvi_archive_export_autonotice(&$evi_archive_export_autonotice)
    {
        $this->evi_archive_export_autonotice =& $evi_archive_export_autonotice;
    }
    function setEvi_archive_export_autodelete(&$evi_archive_export_autodelete)
    {
        $this->evi_archive_export_autodelete =& $evi_archive_export_autodelete;
    }

    // getter
    function getRegular_Export_Flg_Options()
    {
        return GlobalArray::getRegular_Export_Flg_Options();
    }
    function getUpload_archive_flg()
    {
        return $this->upload_archive_flg;
    }
    function getSend_archive_flg()
    {
        return $this->send_archive_flg;
    }
    function getCollect_archive_flg()
    {
        return $this->collect_archive_flg;
    }
    function getArchive_storage_span()
    {
        return $this->archive_storage_span;
    }
    function getRegular_export_flg()
    {
        return $this->regular_export_flg;
    }
    function getExport_storage_span()
    {
        return $this->export_storage_span;
    }
    function getArchive_notice_addr()
    {
        return $this->archive_notice_addr;
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
        return ($this->getArchive_used_capacity_B() / $this->getArchive_capacity_B()) * 100;
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
        return ($this->getExport_used_capacity_B() / $this->getExport_capacity_B()) * 100;
    }
    function getExport_count() {
        return $this->MdbArchiveExport->getCount();
    }
    function getEvi_archive_autonotice() {
        //return $this->MdbConfigs->getDirectValue("evi_archive_autonotice");
        return $this->evi_archive_autonotice;
    }
    function getEvi_archive_autodelete() {
        //return $this->MdbConfigs->getDirectValue("evi_archive_autodelete");
        return $this->evi_archive_autodelete;
    }    
    function getEvi_archive_export_autonotice() {
        //return $this->MdbConfigs->getDirectValue("evi_archive_export_autonotice");
        return $this->evi_archive_export_autonotice;
    }
    function getEvi_archive_export_autodelete() {
        //return $this->MdbConfigs->getDirectValue("evi_archive_export_autodelete");
        return $this->evi_archive_export_autodelete;
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

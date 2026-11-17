<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_User_Done {
    var $session;
    var $DtoConfigs;
    var $MdbConfigs;
    var $DtoLog;
    var $MdbLog;
    var $MdbMchoice;
    var $DtoGroups;
    var $MdbGroups;

    var $max_file_size;
    var $user_delete_limit_dt;
    var $user_file_delete_limit_dt;


    function execute() {

        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_User_Doneが実行されました","Admin_Config_User_Done#execute");

        // エラー発生
        $container =& DIContainerFactory::getContainer();
        $actionChain =& $container->getComponent("ActionChain");
        $errorList =& $actionChain->getCurErrorList();

        //グループ管理者によるユーザ作成時の容量設定
        if(SHARE_GROUP_FLG){
            // 容量
            if($this->max_file_size != ""){
                if(is_numeric($this->max_file_size)){
                    if($this->max_file_size >= 1 && $this->max_file_size < 10000){
                        if($this->max_file_size !== '0' && (!preg_match("/^[1-9][0-9]*$/", $this->max_file_size))){
                            $errorList->add("Admin_Config_User_Done::error", "グループ管理者によるユーザ作成時の容量は整数で入力してください。");
                            return "input";
                        }else{
                            $this->MdbConfigs->setValue('max_file_size',$this->max_file_size);
                        }
                    }else{
                       $errorList->add("Admin_Config_User_Done::error", "グループ管理者によるユーザ作成時の容量は1～9999の範囲で入力してください。");
                       return "input";
                    }
                }else{
                    $errorList->add("Admin_Config_User_Done::error", "グループ管理者によるユーザ作成時の容量は数値のみを入力してください。");
                    return "input";
                }
            }else{
                    $errorList->add("Admin_Config_User_Done::error", "グループ管理者によるユーザ作成時の容量が入力されていません。");
                    return "input";
            }
            
            // 定期削除の個別設定
            if($this->user_file_delete_limit_dt != ""){
                if(is_numeric($this->user_file_delete_limit_dt)){
                    if($this->user_file_delete_limit_dt >= 0 && $this->user_file_delete_limit_dt < 1000){
                        if($this->user_file_delete_limit_dt !== '0' && (!preg_match("/^[1-9][0-9]*$/", $this->user_file_delete_limit_dt))){
                            $errorList->add("Admin_Config_User_Done::error", "グループ管理者によるユーザ作成時のファイルの定期削除の個別設定日数は整数で入力してください。");
                            return "input";
                        }else{
                            $this->MdbConfigs->setValue('user_file_delete_limit_dt',$this->user_file_delete_limit_dt);
                        }
                    }else{
                       $errorList->add("Admin_Config_User_Done::error", "グループ管理者によるユーザ作成時のファイルの定期削除の個別設定日数は0～999の範囲で入力してください。");
                       return "input";
                    }
                }else{
                    $errorList->add("Admin_Config_User_Done::error", "グループ管理者によるユーザ作成時のファイルの定期削除の個別設定日数は数値のみを入力してください。");
                    return "input";
                }
            }else{
                    $errorList->add("Admin_Config_User_Done::error", "グループ管理者によるユーザ作成時のファイルの定期削除の個別設定日数が入力されていません。");
                    return "input";
            }
        }

        

		if($this->user_delete_limit_dt !== '0') {
            if(!preg_match("/^[1-9][0-9]*$/", $this->user_delete_limit_dt)){
                $errorList->add("Admin_Config_User_Done::error", "未ログインのアカウント削除日数は整数で入力してください。");
                return "input";
            }
        }
/*
        //グループ管理者によるユーザ作成時の容量
        $this->MdbConfigs->setValue('max_file_size',$this->max_file_size);

        //アーカイブ：警告メールが送信される容量設定
        $this->MdbConfigs->setValue('evi_archive_autonotice',$this->evi_archive_autonotice);

        //アーカイブ：設定容量を超える場合はデータを削除します
        $this->MdbConfigs->setValue('evi_archive_autodelete',$this->evi_archive_autodelete);
*/

        //未ログインのアカウント削除日数
        $this->MdbConfigs->setValue('user_delete_limit_dt',$this->user_delete_limit_dt);



        // Log
        $history_id = 112; // 112:各種設定＞機能設定
        $memo  = "";

        if(SHARE_GROUP_FLG){
            if ( $this->max_file_size != $this->session->getParameter("max_file_size")){
                $memo .= "グループ管理者によるユーザ作成時の容量：".$this->max_file_size."MB\n";
            }
            if ( $this->user_file_delete_limit_dt != $this->session->getParameter("user_file_delete_limit_dt")){
            	if ($this->user_file_delete_limit_dt != 0) {
            		$memo .= "グループ管理者によるユーザ作成時のファイルの定期削除の個別設定日数：".$this->user_file_delete_limit_dt."日前のファイルを削除\n";
            	} else {
            		$memo .= "グループ管理者によるユーザ作成時のファイルの定期削除の個別設定日数：個別の定期削除は行わない\n";
            	}
            }
        }


        if ($this->user_delete_limit_dt != $this->session->getParameter("user_delete_limit_dt")){
            $memo .= "未ログインのアカウント削除日数：".$this->user_delete_limit_dt."日\n";
        }

	
        if ($memo) {
            $memo = "[システム＞ユーザに関する設定]\n".$memo;
            $this->DtoLog->setUser_id("");
            $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
            $this->DtoLog->setFile_id("");
            $this->DtoLog->setHistory_id($history_id);
            $this->DtoLog->setMemo($memo);
            $this->MdbLog->Logging($this->DtoLog);
        }

        //sessionを削除
        $this->session->removeParameter("max_file_size");
        $this->session->removeParameter("user_delete_limit_dt");
        $this->session->removeParameter("user_file_delete_limit_dt");
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success'
        ]);
        exit;
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
    function setDtoLog ( &$DtoLog ) {
        $this->DtoLog =& $DtoLog;
    }
    function setMdbLog ( &$MdbLog ) {
        $this->MdbLog =& $MdbLog;
    }
    function setMdbMchoice($MdbMchoice) {
        $this->MdbMchoice =& $MdbMchoice;
    }
    function setDtoGroups($DtoGroups){
        $this->DtoGroups = $DtoGroups;
    }
    function setMdbGroups($MdbGroups) {
        $this->MdbGroups = $MdbGroups;
    }
    function setMax_file_size(&$max_file_size)
    {
        $this->max_file_size =& $max_file_size;
    }

    function setUser_delete_limit_dt(&$user_delete_limit_dt)
    {
        $this->user_delete_limit_dt =& $user_delete_limit_dt;
    }
    function setUser_file_delete_limit_dt(&$user_file_delete_limit_dt)
    {
        $this->user_file_delete_limit_dt =& $user_file_delete_limit_dt;
    }

    // getter
    function getConfig() {
        $this->MdbConfigs->getList($this->DtoConfigs);
        return $this->DtoConfigs->configslist;
    }
    function getSends_Flg_Options()
    {
        return GlobalArray::getSends_Flg_Options();
    }
    function getMailer_Flg_Options()
    {
        return GlobalArray::getMailer_Flg_Options();
    }
    function getMailer_Default_Flg_Options()
    {
        return GlobalArray::getMailer_Default_Flg_Options();
    }
    function getSample_Temp_Flg_Options()
    {
        return GlobalArray::getSample_Temp_Flg_Options();
    }
    function getDefault_Pass_Flg_Options()
    {
        return GlobalArray::getDefault_Pass_Flg_Options();
    }
    function getZip_Pass_Flg_Options()
    {
        return GlobalArray::getZip_Pass_Flg_Options();
    }
    function getSendCollect_Flg_Options()
    {
        return GlobalArray::getSendCollect_Flg_Options();
    }
    function getSendMailLimit()
    {
        $this->MdbConfigs->getInfo($this->DtoConfigs,"send_mail_limit");
        return $this->DtoConfigs->getValue();
    }
    function getFileSendLimit()
    {
        $this->MdbConfigs->getInfo($this->DtoConfigs,"file_send_limit");
        return $this->DtoConfigs->getValue();
    }
    function getLimitDt_List()
    {
        return $list = ($this->MdbMchoice->getList("limit_dt"));
    }
    function getLimitDt_Maxsort()
    {
        $list = ($this->MdbMchoice->getList("limit_dt"));
        //sortの最大数を求める
        foreach($list as $val)
            {
                foreach($val as  $key => $work)
                    {
                        if($key == "sort"){
                            $Maxsort = $work;
                        }
                    }
            }
        if($Maxsort == "NULL" || !$Maxsort){
            $Maxsort = "0";
        }
        return  $Maxsort;
    }
    function getMaxDlTime_List()
    {
        return $list = ($this->MdbMchoice->getList("max_dl_time"));
    }
    function getMaxDlTime_Maxsort()
    {
        $list = ($this->MdbMchoice->getList("max_dl_time"));
        //sortの最大数を求める
        foreach($list as $val)
            {
                foreach($val as  $key => $work)
                    {
                        if($key == "sort"){
                            $Maxsort = $work;
                        }
                    }
            }
        if($Maxsort == "NULL" || !$Maxsort){
            $Maxsort = "0";
        }
        return  $Maxsort;
    }
    function getStart_time_Options() {
        return GlobalArray::getStart_time_Options();
    }
    function getDl_start_time_value() {
        return $this->MdbConfigs->getDirectValue("dl_start_time_value");
    }

    function getManual_dl_flg() {
        return $this->MdbConfigs->getDirectValue("manual_dl_flg");
    }
    function getMax_file_size() {
        return $this->MdbConfigs->getDirectValue("max_file_size");
    }
    function getUser_delete_limit_dt() {
        return $this->MdbConfigs->getDirectValue("user_delete_limit_dt");
	}
    function getUser_file_delete_limit_dt() {
        return $this->MdbConfigs->getDirectValue("user_file_delete_limit_dt");
    }
    function getUser_manual_dl_flg() {
        return $this->MdbConfigs->getDirectValue("user_manual_dl_flg");
    }
}
?>

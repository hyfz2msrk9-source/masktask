<?php
/**
 * [機能説明] 各種設定 - ?(完了)
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

class Admin_Config_Move_Done {
	var $session;
	var $DtoConfigs;
	var $MdbConfigs;
	var $DtoLog;
	var $MdbLog;
	var $MdbMchoice;
	var $DtoGroups;
	var $MdbGroups;
	var $file_move_flg;

	function execute() {
		$log =& LogFactory::getLog();
		$log->debug("Admin_Config_Move_Doneが実行されました","Admin_Config_Move_Done#execute");

		// エラー発生
		$container =& DIContainerFactory::getContainer();
		$actionChain =& $container->getComponent("ActionChain");
		$errorList =& $actionChain->getCurErrorList();

		//ファイル移動機能
		$this->MdbConfigs->setValue('file_move_flg',$this->file_move_flg);

		// ユーザ側に設定のキャッシュファイルを作る
		//$this->MdbConfigs->getList($this->DtoConfigs);
		//$configlist =  $this->DtoConfigs->getConfigsList();
		//$this->MdbConfigs->defineSet($configlist);

		// Log
		$history_id = 112; // 112:各種設定＞機能設定
		$memo  = "";
		if ( $this->file_move_flg != $this->session->getParameter("file_move_flg")){
			if ($this->file_move_flg == "1") {
				$flg = "有効";
			} else {
				$flg = "無効";
			}
			$memo .= "ファイル移動：".$flg."\n";
		}

		if ($memo) {
			$this->DtoLog->setUser_id("");
			$this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
			$this->DtoLog->setFile_id("");
			$this->DtoLog->setHistory_id($history_id);
			$this->DtoLog->setMemo($memo);
			$this->MdbLog->Logging($this->DtoLog);
		}

		//sessionを削除
		$this->session->removeParameter("del_span");
		$this->session->removeParameter("sends_flg");
		$this->session->removeParameter("file_move_flg");
		$this->session->removeParameter("mailer_flg");
		$this->session->removeParameter("reissue_flg");
		$this->session->removeParameter("openfolders_flg");
		$this->session->removeParameter("open_subfolder_flg");
		$this->session->removeParameter("thumbnail_flg");
		$this->session->removeParameter("passmust_span");
		$this->session->removeParameter("pass_min_num");
		$this->session->removeParameter("pass_max_num");
		$this->session->removeParameter("pass_type_id");
		$this->session->removeParameter("pass_differ_flg");
		$this->session->removeParameter("sample_temp_flg");
		$this->session->removeParameter("default_pass_flg");
		$this->session->removeParameter("default_openpass_flg");
		$this->session->removeParameter("default_collectpass_flg");
		$this->session->removeParameter("zip_pass_flg");

		$this->session->removeParameter("send_mail_limit");
		$this->session->removeParameter("file_send_limit");
		$this->session->removeParameter("file_upload_limit");
		$this->session->removeParameter("limitdt_val");
		$this->session->removeParameter("limitdt_content");
		$this->session->removeParameter("before_limitdt_content");
		$this->session->removeParameter("before_limitdt_val");
		$this->session->removeParameter("after_limitdt_content");
		$this->session->removeParameter("after_limitdt_val");
		$this->session->removeParameter("limitdt_sort");
		$this->session->removeParameter("limitdt_mark");
		$this->session->removeParameter("maxdltime_val");
		$this->session->removeParameter("maxdltime_content");
		$this->session->removeParameter("before_maxdltime_content");
		$this->session->removeParameter("before_maxdltime_val");
		$this->session->removeParameter("after_maxdltime_content");
		$this->session->removeParameter("after_maxdltime_val");
		$this->session->removeParameter("maxdltime_sort");
		$this->session->removeParameter("maxdltime_mark");
		$this->session->removeParameter("collect_limitdt_mark");
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
	function setDtoLog ( &$DtoLog ) {
		$this->DtoLog =& $DtoLog;
	}
	function setMdbLog ( &$MdbLog ) {
		$this->MdbLog =& $MdbLog;
	}
	function setMdbMchoice($MdbMchoice) {
		$this->MdbMchoice =& $MdbMchoice;
	}
	function setFile_move_flg(&$file_move_flg)
	{
		$this->file_move_flg =& $file_move_flg;
	}

	// getter
	function getConfig() {
		$this->MdbConfigs->getList($this->DtoConfigs);
		return $this->DtoConfigs->configslist;
	}
	function getFile_Move_Flg_Options()
	{
		return GlobalArray::getFile_Move_Flg_Options();
	}

}
?>

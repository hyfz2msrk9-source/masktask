<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_File_Confirm {
    var $session;
    var $ListLogic;
    var $fileDto;
    var $fileLogic;
    var $MdbCollect;
    var $MdbUpload;
    var $MdbConfigs;
    var $error_msg;
    
    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_File_Confirm","Admin_File_Confirm#execute");

        if (mktime(0,0,0,$this->fileDto->getClear_m(),$this->fileDto->getClear_d(),$this->fileDto->getClear_y()) >= mktime(0,0,0,date("m"),date("d"),date("y"))) {
            $container =& DIContainerFactory::getContainer();
            $actionChain =& $container->getComponent("ActionChain");
            $errorList =& $actionChain->getCurErrorList();
            $errorList->add('Admin_File_Confirm::error', '昨日以前の日付を指定してください。');
        	return "input";
        }
        $this->fileLogic->getDelfile_num($this->fileDto,$this->MdbCollect,$this->MdbUpload);
        
        return "success";
    }
    /************************************
    * setter
    *************************************/
    function setSession($session) {
        $this->session = $session;
    }
    function setFileDto($fileDto) {
    	$this->fileDto = $fileDto;
    }
    function setFileLogic($fileLogic) {
    	$this->fileLogic = $fileLogic;
    }
    function setListLogic($ListLogic) {
    	$this->ListLogic = $ListLogic;
    }
    function setFile_del_back($file_del_back) {
    	$this->file_del_back = $file_del_back;
    }
    function setMdbCollect($MdbCollect) {
    	$this->MdbCollect = $MdbCollect;
    }
    function setMdbUpload($MdbUpload) {
    	$this->MdbUpload = $MdbUpload;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }

    /************************************
    * getter
    *************************************/
    function getYear() {
        return $this->ListLogic->getYear_between(FILE_DEL_START_YEAR,date("Y"));
    }
    function getMonth() {
        return $this->ListLogic->getMonth();
    }
    function getDay() {
        return $this->ListLogic->getDay();
    }
    function getClear_y() {
        return $this->fileDto->getClear_y();
    }
    function getClear_m() {
        return $this->fileDto->getClear_m();
    }
    function getClear_d() {
        return $this->fileDto->getClear_d();
    }
    function getDelfile_num() {
    	return $this->fileDto->getDelfile_num();
    }
    function getDeldts() {
        return $this->MdbConfigs->getDirectValue("del_span");
    }
    function getDelspan_error() {
        return $this->error_msg;
    }
}
?>

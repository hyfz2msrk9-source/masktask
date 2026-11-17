<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Fileformat_Done {

    var $session;
    var $MdbConfigs;

    var $fileformat_type;

    function execute() {

        if (!array_key_exists($this->fileformat_type, $this->getList_Type_Options())) {
            return "error";
        }

		$this->MdbConfigs->setValue('fileformat_type',$this->fileformat_type);
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
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setFileformat_type($fileformat_type) {
        $this->fileformat_type = $fileformat_type;
    }

    // getter
    function getList_Type_Options() {
        return GlobalArray::getList_Type_Options();
    }
}
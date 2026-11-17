<?php
require_once('DB/DataObject.php');

class collectInfo {
    var $db;
    
    function __construct() {
        $config = parse_ini_file(MODULE_DIR."/DataObject/dbdo.ini", true);
        $options = &PEAR::getStaticProperty("DB_DataObject","options");
        $options = $config["DB_DataObject"];
        $this->db = DB_DataObject::factory("COLLECT");
    }

    function upload(&$dto) {
        $this->db->get($dto->getCollect_id());

        $this->db->up_file_oid  = $dto->getUp_file_oid();
        $this->db->up_file_name = $dto->getUp_file_name();
        $this->db->up_file_type = $dto->getUp_file_type();
        $this->db->up_file_size = $dto->getUp_file_size();
        $this->db->up_memo      = $dto->getUp_memo();
        $this->db->up_dt        = $dto->getUp_dt();
        $this->db->del_flg      = $dto->getDel_flg();
        
        $this->db->update();
    }
}
?>

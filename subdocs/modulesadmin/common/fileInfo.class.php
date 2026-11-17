<?php
require_once('DB/DataObject.php');

class fileInfo {
    var $db;
    
    function __construct() {
        $config = parse_ini_file(MODULE_DIR."/DataObject/dbdo.ini", true);
        $options = &PEAR::getStaticProperty("DB_DataObject","options");
        $options = $config["DB_DataObject"];
        $this->db =& DB_DataObject::factory("FILE");
    }

    function add(&$dto) {
        //var_dump::display($dto);
        $file_id = $dto->getFile_id();
        $sql = "insert into FILE(file_id) values($file_id)";
        $this->db->query($sql);
        
        $this->db->get($file_id);
        $this->db->file_name = $dto->getFile_name();
        $this->db->file_type = $dto->getFile_type();
        $this->db->file_size = $dto->getFile_size();
        $this->db->file_oid  = $dto->getFile_oid();
        $this->db->user_id   = $dto->getUser_id();
        $this->db->memo      = $dto->getMemo();
        $this->db->folder_id = $dto->getFolder_id();
        $this->db->in_dt     = $dto->getIn_dt();
        $this->db->updated_at     = $dto->getUpdated_at();
        $this->db->del_flg   = $dto->getDel_flg();
        
        $rs = $this->db->update();

    }
    function moveCollectFile($dto,$DtoFile) {
        $file_id = $DtoFile->getFile_id();
        $sql = "insert into FILE(file_id) values($file_id)";
        $this->db->query($sql);

        $memo = '['.date("Y")."年".date("m")."月".date("d").'日に、収集より保管しました。]'."\n";
        $folder_id = 0;
        $this->db->get($file_id);
        $this->db->file_name = $dto->getUp_file_name();
        $this->db->file_type = $dto->getUp_file_type();
        $this->db->file_size = $dto->getUp_file_size();
        $this->db->file_oid  = $dto->getUp_file_oid();
        $this->db->user_id   = $dto->getUser_id();
        $this->db->memo      = $memo.$dto->getUp_memo();
        $this->db->folder_id = $folder_id;
        $this->db->in_dt     = date("Y-m-d G:i:s");
        $this->db->updated_at     = date("Y-m-d G:i:s");
        $this->db->del_flg   =  0;
        
        $this->db->update();
    }
}
?>

<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_FILE','UPLOAD');

class UploadTable extends BaseTable
{
    var $table        = TABLE_FILE;

    var $primaryCol   = 'file_id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_FILE,
                  'shortName' => TABLE_FILE,
                  'autoIncrement' => true
            )
      );
}
?>
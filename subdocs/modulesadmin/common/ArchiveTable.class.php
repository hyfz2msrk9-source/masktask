<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_Archive','Archive');

class ArchiveTable extends BaseTable
{
    var $table        = TABLE_Archive;
    //var $sequenceName = TABLE_ADDR;

    var $primaryCol   = 'archive_id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_Archive,
                  'shortName' => TABLE_Archive,
                  'autoIncrement' => true
            )
      );
}
?>
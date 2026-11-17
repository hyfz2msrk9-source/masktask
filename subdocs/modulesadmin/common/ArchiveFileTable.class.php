<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_ArchiveFile','ArchiveFile');

class ArchiveFileTable extends BaseTable
{
    var $table        = TABLE_ArchiveFile;
    //var $sequenceName = TABLE_ADDR;

    var $primaryCol   = 'archive_file_id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_ArchiveFile,
                  'shortName' => TABLE_ArchiveFile,
                  'autoIncrement' => true
            )
      );
}
?>
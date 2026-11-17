<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_ArchiveAddr','ArchiveAddr');

class ArchiveAddrTable extends BaseTable
{
    var $table        = TABLE_ArchiveAddr;
    //var $sequenceName = TABLE_ADDR;

    var $primaryCol   = 'archive_addr_id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_ArchiveAddr,
                  'shortName' => TABLE_ArchiveAddr,
                  'autoIncrement' => true
            )
      );
}
?>
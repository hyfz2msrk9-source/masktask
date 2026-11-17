<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_ArchiveExport','ArchiveExport');

class ArchiveExportTable extends BaseTable
{
    var $table        = TABLE_ArchiveExport;
    //var $sequenceName = TABLE_ADDR;

    var $primaryCol   = 'archive_export_id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_ArchiveExport,
                  'shortName' => TABLE_ArchiveExport,
                  'autoIncrement' => true
            )
      );
}
?>
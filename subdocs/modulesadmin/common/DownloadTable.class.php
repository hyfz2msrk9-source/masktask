<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_DOWNLOAD','DOWNLOAD');

class DownloadTable extends BaseTable
{
    var $table        = TABLE_DOWNLOAD;

    var $primaryCol   = 'download_id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_DOWNLOAD,
                  'shortName' => TABLE_DOWNLOAD,
                  'autoIncrement' => true
            )
      );
}
?>
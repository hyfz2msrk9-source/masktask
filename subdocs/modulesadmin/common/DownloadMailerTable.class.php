<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_DownloadMailer','DownloadMailer');

class DownloadMailerTable extends BaseTable
{
    var $table        = TABLE_DownloadMailer;

    var $primaryCol   = 'download_id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_DownloadMailer,
                  'shortName' => TABLE_DownloadMailer,
                  'autoIncrement' => true
            )
      );
}
?>
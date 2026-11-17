<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_VirusLog','VirusLog');

class VirusLogTable extends BaseTable
{
    var $table        = TABLE_VirusLog;

    var $primaryCol   = 'virus_log_id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_VirusLog,
                  'shortName' => TABLE_VirusLog,
                  'autoIncrement' => true
            )
      );
}
?>
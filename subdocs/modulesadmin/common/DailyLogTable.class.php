<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_DailyLog','DailyLog');

class DailyLogTable extends BaseTable
{
    var $table        = TABLE_DailyLog;
    //var $sequenceName = TABLE_DailyLog;

    var $primaryCol   = 'log_id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_DailyLog,
                  'shortName' => TABLE_DailyLog,
                  'autoIncrement' => true
            )
      );
}
?>
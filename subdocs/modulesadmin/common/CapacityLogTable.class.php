<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_CapacityLog', 'CapacityLog');

class CapacityLogTable extends BaseTable
{
      var $table        = TABLE_CapacityLog;

      var $primaryCol   = 'capacity_log_id';

      var $tableSpec    = array(
            array(
                  'name'      => TABLE_CapacityLog,
                  'shortName' => TABLE_CapacityLog,
                  'autoIncrement' => true
            )
      );
}

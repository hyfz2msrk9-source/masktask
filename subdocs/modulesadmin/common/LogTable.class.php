<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_Log','Log');

class LogTable extends BaseTable
{
    var $table        = TABLE_Log;

    var $primaryCol   = 'log_id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_Log,
                  'shortName' => TABLE_Log,
                  'autoIncrement' => true
            )
      );
}
?>
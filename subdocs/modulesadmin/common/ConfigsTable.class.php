<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_Configs','configs');

class ConfigsTable extends BaseTable
{
    var $table        = TABLE_Configs;

    var $primaryCol   = 'id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_Configs,
                  'shortName' => TABLE_Configs,
                  'autoIncrement' => true
            )
      );
}
?>
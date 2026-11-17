<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_COLLECT','COLLECT');

class CollectTable extends BaseTable
{
    var $table        = TABLE_COLLECT;
    //var $sequenceName = TABLE_COLLECT;

    var $primaryCol   = 'collect_id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_COLLECT,
                  'shortName' => TABLE_COLLECT,
                  'autoIncrement' => true
            )
      );
}
?>
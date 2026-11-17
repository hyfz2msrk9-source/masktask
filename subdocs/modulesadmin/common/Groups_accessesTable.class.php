<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_GROUPS_ACCESSES','groups_accesses');

class Groups_accessesTable extends BaseTable
{
    var $table        = TABLE_GROUPS_ACCESSES;

    var $primaryCol   = 'access_id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_GROUPS_ACCESSES,
                  'shortName' => TABLE_GROUPS_ACCESSES,
                  'autoIncrement' => true
            )
      );
}
?>
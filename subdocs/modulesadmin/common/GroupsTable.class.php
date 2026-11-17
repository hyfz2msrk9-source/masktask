<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_GROUPS', '`groups`');

class GroupsTable extends BaseTable
{
    var $table        = TABLE_GROUPS;

    var $primaryCol   = 'id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_GROUPS,
                  'shortName' => TABLE_GROUPS,
                  'autoIncrement' => true
            )
      );
}
?>

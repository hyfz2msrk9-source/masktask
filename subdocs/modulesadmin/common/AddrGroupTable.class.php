<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_AddrGroup','AddrGroup');

class AddrGroupTable extends BaseTable
{
    var $table        = TABLE_AddrGroup;

    var $primaryCol   = 'group_id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_AddrGroup,
                  'shortName' => TABLE_AddrGroup,
                  'autoIncrement' => true
            )
      );
}
?>
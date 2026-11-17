<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_ADDR','ADDR');

class AddrTable extends BaseTable
{
    var $table        = TABLE_ADDR;

    var $primaryCol   = 'addr_id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_ADDR,
                  'shortName' => TABLE_ADDR,
                  'autoIncrement' => true
            )
      );
}
?>
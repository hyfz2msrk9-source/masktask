<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_Access','Access');

class AccessTable extends BaseTable
{
    var $table        = TABLE_Access;
    //var $sequenceName = TABLE_ADDR;

    var $primaryCol   = 'access_id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_Access,
                  'shortName' => TABLE_Access,
                  'autoIncrement' => true
            )
      );
}
?>
<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_SIGN','SIGN');

class SignTable extends BaseTable
{
    var $table        = TABLE_SIGN;

    var $primaryCol   = 'id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_SIGN,
                  'shortName' => TABLE_SIGN,
                  'autoIncrement' => true
            )
      );
}
?>
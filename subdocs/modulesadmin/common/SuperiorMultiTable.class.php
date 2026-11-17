<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_SuperiorMulti','SuperiorMulti');

class SuperiorMultiTable extends BaseTable
{
    var $table        = TABLE_SuperiorMulti;

    var $primaryCol   = 'id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_SuperiorMulti,
                  'shortName' => TABLE_SuperiorMulti,
                  'autoIncrement' => true
            )
      );
}
?>

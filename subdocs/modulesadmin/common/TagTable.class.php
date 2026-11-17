<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_TAG','Tag');

class TagTable extends BaseTable
{
    var $table        = TABLE_TAG;

    var $primaryCol   = 'tag_id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_TAG,
                  'shortName' => TABLE_TAG,
                  'autoIncrement' => true
            )
      );
}
?>
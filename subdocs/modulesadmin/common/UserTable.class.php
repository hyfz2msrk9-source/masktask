<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_USER','USER');

class UserTable extends BaseTable
{
    var $table        = TABLE_USER;

    var $primaryCol   = 'user_id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_USER,
                  'shortName' => TABLE_USER,
                  'autoIncrement' => true
            )
      );
}
?>
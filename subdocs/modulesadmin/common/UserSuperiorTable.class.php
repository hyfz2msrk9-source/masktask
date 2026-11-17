<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_USER_SUPERIOR','UserSuperior');

class UserSuperiorTable extends BaseTable
{
    var $table        = TABLE_USER_SUPERIOR;

    var $primaryCol   = '';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_USER_SUPERIOR,
                  'shortName' => TABLE_USER_SUPERIOR,
                  'autoIncrement' => true
            )
      );
}
?>
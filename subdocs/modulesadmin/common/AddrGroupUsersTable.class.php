<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_AddrGroupUsers','AddrGroupUsers');

class AddrGroupUsersTable extends BaseTable
{
    var $table        = TABLE_AddrGroupUsers;

    var $primaryCol   = 'id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_AddrGroupUsers,
                  'shortName' => TABLE_AddrGroupUsers,
                  'autoIncrement' => true
            )
      );
}
?>
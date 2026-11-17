<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_GROUPS_USERS','groups_users');

class Groups_usersTable extends BaseTable
{
    var $table        = TABLE_GROUPS_USERS;

    var $primaryCol   = 'groups_id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_GROUPS_USERS,
                  'shortName' => TABLE_GROUPS_USERS,
                  'autoIncrement' => true
            )
      );
}
?>
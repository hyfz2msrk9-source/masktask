<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_GROUPS_ADMIN_USERS','groups_admin_users');

class Groups_admin_usersTable extends BaseTable
{
    var $table        = TABLE_GROUPS_ADMIN_USERS;

    var $primaryCol   = 'groups_id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_GROUPS_ADMIN_USERS,
                  'shortName' => TABLE_GROUPS_ADMIN_USERS,
                  'autoIncrement' => true
            )
      );
}
?>
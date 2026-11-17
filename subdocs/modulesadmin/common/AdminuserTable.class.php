<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_AdminUser','AdminUser');

class AdminuserTable extends BaseTable
{
    var $table        = TABLE_AdminUser;
    var $sequenceName = TABLE_AdminUser;

    var $primaryCol   = 'admin_id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_AdminUser,
                  'shortName' => TABLE_AdminUser,
                  'autoIncrement' => true
            )
      );
}
?>
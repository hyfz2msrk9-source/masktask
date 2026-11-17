<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_AdminMail','AdminMail');

class AdminmailTable extends BaseTable
{
    var $table        = TABLE_AdminMail;
    var $sequenceName = TABLE_AdminMail;

    var $primaryCol   = 'admin_mail_id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_AdminMail,
                  'shortName' => TABLE_AdminMail,
                  'autoIncrement' => true
            )
      );
}
?>
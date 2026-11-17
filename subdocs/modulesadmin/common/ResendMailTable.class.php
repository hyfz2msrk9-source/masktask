<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_ResendMail','ResendMail');

class ResendMailTable extends BaseTable
{
    var $table        = TABLE_ResendMail;

    var $primaryCol   = 'resend_mail_id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_ResendMail,
                  'shortName' => TABLE_ResendMail,
                  'autoIncrement' => true
            )
      );
}
?>
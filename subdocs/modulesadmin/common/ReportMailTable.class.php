<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_ReportMail','ReportMail');

class ReportMailTable extends BaseTable
{
    var $table        = TABLE_ReportMail;

    var $primaryCol   = 'reportmail_id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_ReportMail,
                  'shortName' => TABLE_ReportMail,
                  'autoIncrement' => true
            )
      );
}
?>

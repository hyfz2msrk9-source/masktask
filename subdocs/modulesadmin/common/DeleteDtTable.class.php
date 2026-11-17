<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_DeleteDt','DeleteDt');

class DeleteDtTable extends BaseTable
{
    var $table        = TABLE_DeleteDt;
    var $sequenceName = TABLE_DeleteDt;

    var $primaryCol   = 'delete_id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_DeleteDt,
                  'shortName' => TABLE_DeleteDt,
                  'autoIncrement' => true
            )
      );
}
?>
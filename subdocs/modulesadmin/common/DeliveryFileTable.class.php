<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_DeliveryFile','DeliveryFile');

class DeliveryFileTable extends BaseTable
{
    var $table        = TABLE_DeliveryFile;

    var $primaryCol   = 'delivery_file_id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_DeliveryFile,
                  'shortName' => TABLE_DeliveryFile,
                  'autoIncrement' => true
            )
      );
}
?>

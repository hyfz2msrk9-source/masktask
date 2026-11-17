<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_DeliveryTo','DeliveryTo');

class DeliveryToTable extends BaseTable
{
    var $table        = TABLE_DeliveryTo;

    var $primaryCol   = 'delivery_to_id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_DeliveryTo,
                  'shortName' => TABLE_DeliveryTo,
                  'autoIncrement' => true
            )
      );
}
?>

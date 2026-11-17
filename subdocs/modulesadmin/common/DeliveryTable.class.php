<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_Delivery','Delivery');

class DeliveryTable extends BaseTable
{
    var $table        = TABLE_Delivery;

    var $primaryCol   = 'delivery_id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_Delivery,
                  'shortName' => TABLE_Delivery,
                  'autoIncrement' => true
            )
      );
}
?>

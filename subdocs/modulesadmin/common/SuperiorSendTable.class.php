<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_SuperiorSend','SuperiorSend');

class SuperiorSendTable extends BaseTable
{
    var $table        = TABLE_SuperiorSend;

    var $primaryCol   = '';

    var $tableSpec    = array(
        array(
            'name'      => TABLE_SuperiorSend,
            'shortName' => TABLE_SuperiorSend,
            'autoIncrement' => true
        )
    );
}
?>

<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_MChoice','MChoice');

class MChoiceTable extends BaseTable
{
    var $table        = TABLE_MChoice;

    var $primaryCol   = 'choice_id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_MChoice,
                  'shortName' => TABLE_MChoice,
                  'autoIncrement' => true
            )
      );
}
?>
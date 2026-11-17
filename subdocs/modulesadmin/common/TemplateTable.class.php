<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_TEMPLATE','TEMPLATE');

class TemplateTable extends BaseTable
{
    var $table        = TABLE_TEMPLATE;

    var $primaryCol   = 'id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_TEMPLATE,
                  'shortName' => TABLE_TEMPLATE,
                  'autoIncrement' => true
            )
      );
}
?>
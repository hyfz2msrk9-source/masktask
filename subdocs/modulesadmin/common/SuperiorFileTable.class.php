<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_SuperiorFile','SuperiorFile');

class SuperiorFileTable extends BaseTable
{
    var $table        = TABLE_SuperiorFile;

    var $primaryCol   = 'superior_file_id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_SuperiorFile,
                  'shortName' => TABLE_SuperiorFile,
                  'autoIncrement' => true
            )
      );
}
?>
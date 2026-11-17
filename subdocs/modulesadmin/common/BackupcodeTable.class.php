<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_Backupcode', 'Backupcode');

class BackupcodeTable extends BaseTable
{
      var $table        = TABLE_Backupcode;

      var $primaryCol   = 'backupcode_id';

      var $tableSpec    = array(
            array(
                  'name'      => TABLE_Backupcode,
                  'shortName' => TABLE_Backupcode,
                  'autoIncrement' => true
            )
      );
}

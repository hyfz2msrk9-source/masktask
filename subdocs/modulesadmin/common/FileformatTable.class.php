<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_Fileformat','Fileformat');

class FileformatTable extends BaseTable
{
    var $table        = TABLE_Fileformat;

    var $primaryCol   = 'format_id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_Fileformat,
                  'shortName' => TABLE_Fileformat,
                  'autoIncrement' => true
            )
      );
}

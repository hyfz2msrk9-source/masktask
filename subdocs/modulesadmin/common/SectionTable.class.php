<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_SECTION','section');

class SectionTable extends BaseTable
{
    var $table        = TABLE_SECTION;

    var $primaryCol   = 'section_id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_SECTION,
                  'shortName' => TABLE_SECTION,
                  'autoIncrement' => true
            )
      );
}
?>
<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_SECTIONUSERS','sectionusers');

class SectionusersTable extends BaseTable
{
    var $table        = TABLE_SECTIONUSERS;

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_SECTIONUSERS,
                  'shortName' => TABLE_SECTIONUSERS,
                  'autoIncrement' => true
            )
      );
}
?>
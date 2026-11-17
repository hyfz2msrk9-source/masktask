<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_Domain','Domain');

class DomainTable extends BaseTable
{
    var $table        = TABLE_Domain;

    var $primaryCol   = 'domain_id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_Domain,
                  'shortName' => TABLE_Domain,
                  'autoIncrement' => true
            )
      );
}
?>
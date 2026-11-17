<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_ClientAuth','ClientAuth');

class ClientAuthTable extends BaseTable
{
    var $table        = TABLE_ClientAuth;

    var $primaryCol   = 'id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_ClientAuth,
                  'shortName' => TABLE_ClientAuth,
                  'autoIncrement' => true
            )
      );
}
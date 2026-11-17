<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_AdminTwostepauth', 'AdminTwostepauth');

class AdminTwostepauthTable extends BaseTable
{
      var $table        = TABLE_AdminTwostepauth;

      var $primaryCol   = 'admin_two_step_auth_id';

      var $tableSpec    = array(
            array(
                  'name'      => TABLE_AdminTwostepauth,
                  'shortName' => TABLE_AdminTwostepauth,
                  'autoIncrement' => true
            )
      );
}

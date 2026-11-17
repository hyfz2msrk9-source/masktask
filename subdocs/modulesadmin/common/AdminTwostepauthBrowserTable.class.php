<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_AdminTwostepauthBrowser', 'AdminTwostepauthBrowser');

class AdminTwostepauthBrowserTable extends BaseTable
{
      var $table        = TABLE_AdminTwostepauthBrowser;

      var $primaryCol   = 'admin_id';

      var $tableSpec    = array(
            array(
                  'name'      => TABLE_AdminTwostepauthBrowser,
                  'shortName' => TABLE_AdminTwostepauthBrowser,
                  'autoIncrement' => false
            )
      );
}

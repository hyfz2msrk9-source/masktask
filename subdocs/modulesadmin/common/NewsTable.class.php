<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_NEWS','News');

class NewsTable extends BaseTable
{
    var $table        = TABLE_NEWS;

    var $primaryCol   = 'news_id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_NEWS,
                  'shortName' => TABLE_NEWS,
                  'autoIncrement' => true
            )
      );
}
?>
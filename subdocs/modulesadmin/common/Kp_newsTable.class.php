<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_KP_NEWS', 'kp_news');

class Kp_newsTable extends BaseTable
{
    var $table        = TABLE_KP_NEWS;
    // ダッシュボードのnews_idを主キーとするので、auto_incrementされるカラムはない

    var $tableSpec    = array(
        array(
            'name'      => TABLE_KP_NEWS,
            'shortName' => TABLE_KP_NEWS,
            'autoIncrement' => true
        )
    );
}

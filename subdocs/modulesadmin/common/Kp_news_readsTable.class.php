<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_KP_NEWS_READS', 'kp_news_reads');

class Kp_news_readsTable extends BaseTable
{
    var $table        = TABLE_KP_NEWS_READS;
    // ダッシュボードのnews_idを主キーとするので、auto_incrementされるカラムはない

    var $tableSpec    = array(
        array(
            'name'      => TABLE_KP_NEWS_READS,
            'shortName' => TABLE_KP_NEWS_READS,
            'autoIncrement' => true
        )
    );
}

<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define( 'TABLE_TWO_STEP_AUTH_BROWSER', 'TwoStepAuthBrowser' );

class TwoStepAuthBrowserTable extends BaseTable
{
    var $table      = TABLE_TWO_STEP_AUTH_BROWSER;
    var $primaryCol = 'user_id';
    var $tableSpec  = array(
        array(
            'name'      => TABLE_TWO_STEP_AUTH_BROWSER,
            'shortName' => TABLE_TWO_STEP_AUTH_BROWSER,
            'autoIncrement' => true
        )
    );
}

<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_FOLDER','FOLDER');

class FolderTable extends BaseTable
{
    var $table        = TABLE_FOLDER;

    var $primaryCol   = 'folder_id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_FOLDER,
                  'shortName' => TABLE_FOLDER,
                  'autoIncrement' => true
            )
      );
}
?>
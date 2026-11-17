<?php
require_once(MODULE_DIR . '/common/BaseTable.class.php');

define('TABLE_FavoriteFolders','FavoriteFolders');

class FavoriteFoldersTable extends BaseTable
{
    var $table        = TABLE_FavoriteFolders;

    var $primaryCol   = 'id';

    var $tableSpec    = array(
            array(
                  'name'      => TABLE_FavoriteFolders,
                  'shortName' => TABLE_FavoriteFolders,
                  'autoIncrement' => true
            )
      );
}
?>
<?php
require_once('MDB/QueryTool.php');

/**
 * BaseTable
 *
 * AUTO_INCREMENT管理用の基底クラス
 */
class BaseTable extends MDB_QueryTool
{
    function _isAutoIncrement()
    {
        return isset($this->tableSpec[0]['autoIncrement']) ? $this->tableSpec[0]['autoIncrement'] : true;
    }

    /**
     * tableSpecの'autoIncrement'が未指定またはtrueの場合はnullを返しMySQLのAUTO_INCREMENTに委ねる。
     * 明示的にfalseの場合のみシーケンステーブルを使う
     */
    function _getNextId()
    {
        if ($this->_isAutoIncrement()) {
            return null;
        }
        return $this->db->nextId($this->sequenceName);
    }

    /**
     * AUTO_INCREMENTモードではPKをINSERTから除外し、INSERT後にLAST_INSERT_ID()で実際のIDを取得する。
     * シーケンステーブルモードは親クラスに委譲する。
     */
    function add($newData)
    {
        if (!$this->_isAutoIncrement()) {
            return parent::add($newData);
        }

        // AUTO_INCREMENTに委ねるためPKはINSERTに含めない
        unset($newData[$this->primaryCol]);

        $newData = $this->_checkColumns($newData, 'add');
        $newData = $this->_quoteArray($newData);

        $tmpData = [];
        foreach ($newData as $key => $val) {
            $tmpData[$this->_quoteIdentifier($key)] = $val;
        }

        $query = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', array_keys($tmpData)),
            $this->_localeSafeImplode(', ', $tmpData)
        );

        if (!$this->execute($query, 'query')) {
            return false;
        }

        $id = (int)$this->execute('SELECT LAST_INSERT_ID()', 'queryOne');
        return $id ?: true;
    }

    /**
     * AUTO_INCREMENTモードでは親クラスのMySQL最適化パス（バッチINSERT）を使わず、
     * add()を1件ずつ呼ぶ。
     * MySQL 8.0ではバッチINSERTのAUTO_INCREMENT IDが連番にならない場合があるため。
     * シーケンステーブルモードは親クラスに委譲する。
     */
    function addMultiple($data)
    {
        if (!$this->_isAutoIncrement()) {
            return parent::addMultiple($data);
        }

        if (!sizeof($data)) {
            return false;
        }

        $retIds = $this->primaryCol ? [] : true;

        foreach ($data as $entity) {
            $id = $this->add($entity);
            if ($id === false) {
                return false;
            }
            if (is_array($retIds)) {
                $retIds[] = $id;
            }
        }

        return $retIds;
    }
}

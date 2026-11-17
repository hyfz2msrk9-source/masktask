<?php

/**
 * 毎日の容量をDB(CapacityLogic)に保存する（単位はMB）
 */
require_once(MODULE_DIR . "/common/common.php");

class Exec_Capacity_Record
{
    var $MdbCapacityLog;
    var $MdbUpload;
    var $CapacityLogic;
    var $MdbCollect;

    function execute()
    {
        $log = &LogFactory::getLog();
        $log->debug(__CLASS__ . 'が実行されました', __CLASS__ . '#execute');
        //日付の取得
        $today = new DateTime();
        $formattedToday = $today->format('Y-m-d');
        if ($this->MdbCapacityLog->hasSameDateCapacityLog($formattedToday)) {
            //同じ日程で保存されていればDBに登録しない
            exit;
        }
        // 基本容量総使用量の取得(B)
        $personalTotalCapacity = (int)($this->MdbUpload->getAllUserCapacity() ?? 0) + (int)($this->MdbCollect->getUpMaxSize_all() ?? 0);
        // 共有総使用量の取得(B)
        $shareTotalCapacity = $this->MdbUpload->getAllUserShareCapacity();
        $data = array(
            "personal_folder_usage_size" => strval($personalTotalCapacity),
            "share_folder_usage_size" => strval($shareTotalCapacity) ?? '0',
            "save_dt" => strval($formattedToday) ?? '',
        );
        $result = $this->MdbCapacityLog->save($data);
        if (!$result) exit('ERROR:' . __CLASS__ . $formattedToday . '分のデータの保存に失敗しました。');
        exit;
    }
    function setMdbCapacityLog($MdbCapacityLog)
    {
        $this->MdbCapacityLog = $MdbCapacityLog;
    }
    function setMdbUpload($MdbUpload)
    {
        $this->MdbUpload = $MdbUpload;
    }
    function setCapacityLogic($CapacityLogic)
    {
        $this->CapacityLogic = $CapacityLogic;
    }
    function setMdbCollect($MdbCollect)
    {
        $this->MdbCollect = $MdbCollect;
    }
}

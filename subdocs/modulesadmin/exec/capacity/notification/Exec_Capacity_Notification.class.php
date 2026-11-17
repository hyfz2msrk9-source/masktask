<?php

/**
 * 各種設定>その他設定>使用容量通知に関する設定>使用状況通知設定
 */
require_once(MODULE_DIR . "/common/common.php");

class Exec_Capacity_Notification
{
    var $MdbConfigs;
    var $MdbCapacityLog;
    var $MdbUpload;
    var $CapacityLogic;
    var $MdbCollect;

    function execute()
    {
        $log = &LogFactory::getLog();
        $log->debug(__CLASS__ . 'が実行されました', __CLASS__ . '#execute');
        $notifyPersonalFolderUsage = $this->MdbConfigs->getDirectValue('notify_personal_folder_usage');
        $notifyShareFolderUsage = $this->MdbConfigs->getDirectValue('notify_share_folder_usage');
        //各種設定>使用容量通知に関する設定>使用状況通知設定のチェックボックスが２つともオフなら終了
        if (!$notifyPersonalFolderUsage && !$notifyShareFolderUsage) exit();

        $capacityTableHtml = array();
        $personalUsedTotalCapacity = '';
        $shareTotalUsedCapacity = '';
        //個人フォルダ使用状況の推移を通知する場合
        if ($notifyPersonalFolderUsage) {
            // 基本容量総使用量の取得
            $baseUsedCapacity = $this->MdbUpload->getAllUserCapacity() + $this->MdbCollect->getUpMaxSize_all();
            // 拡張総使用量の取得
            $extraUsedCapacity = $this->CapacityLogic->getExtra_usedAll() ?? 0;
            $personalUsedTotalCapacity = StrChange::CapacityUnit($baseUsedCapacity + $extraUsedCapacity);
            $capacityTableHtml[] = $this->getCapacityTableHtml(true);
        }
        //共有フォルダ使用状況の推移を通知する場合
        if ($notifyShareFolderUsage && SHARE_GROUP_FLG) {
            // 共有総使用量の取得
            $shareTotalUsedCapacity = StrChange::CapacityUnit($this->MdbUpload->getAllUserShareCapacity());
            $capacityTableHtml[]  = $this->getCapacityTableHtml(false);
        }
        $mailBody = $this->getMailBody($personalUsedTotalCapacity, $shareTotalUsedCapacity, $capacityTableHtml);
        if (!$mailBody) {
            $log->error('使用容量の通知メール作成に失敗しました');
            exit();
        }
        //メールの送信
        $mail = new MailSend();
        $capacityAlertNoticeAddr = $this->MdbConfigs->getDirectValue("capacity_alert_notice_addr");
        $toMailAddresses = explode(",", $capacityAlertNoticeAddr);
        if (!$toMailAddresses[0]) exit();
        foreach ($toMailAddresses as $toMailAddress) {
            $mail->sendHtmlMail(trim($toMailAddress), $mailBody, CAPACITY_NOTICE_SUBJECT);
        }

        exit();
    }

    /**
     * 個人フォルダもしくは共有フォルダの容量を表示するHTMLのテーブルを取得する
     *
     * @param [boolean] $isPersonal true:個人用フォルダ false:共有用フォルダ
     * @return string capacity_table.phpに容量についての情報を入れたHTML
     */
    function getCapacityTableHtml($isPersonal)
    {
        //容量の推移を取得
        $weekCapacities = $this->MdbCapacityLog->getDailyIntervalCapacity($isPersonal);
        $yearCapacities = $this->MdbCapacityLog->getMonthlyMaxFolderUsage($isPersonal);
        if (!$weekCapacities || !$yearCapacities) return false;
        //曜日を付ける
        foreach ($weekCapacities as &$thisWeekCapacity) {
            $thisWeekCapacity['save_dt'] =  StrChange::getWeekdayFromDateJP(date: $thisWeekCapacity['save_dt'], format: "Y年m月d日", weekdayFlg: true);
        }
        foreach ($yearCapacities as &$yearCapacity) {
            $yearCapacity['save_dt'] =  StrChange::getWeekdayFromDateJP(date: $yearCapacity['save_dt'], format: "Y年m月d日", weekdayFlg: false);
        }
        $monthCapacityByYears = $this->getMonthCapacityByYears($yearCapacities);
        $years = array_keys($monthCapacityByYears);
        //テンプレートからHTMLを取得
        return $this->includeTpl(MODULE_DIR . '/common/tpl/capacity_table.php', [
            'folderType' => $isPersonal ? '個人用フォルダ' : '共有フォルダ',
            'weekCapacities' => $weekCapacities,
            'yearCapacities' => $yearCapacities,
            'headerMonths' => $this->getSortedMonths($monthCapacityByYears, $years),
            'monthCapacityByYears' => $monthCapacityByYears,
        ]);
    }
    /**
     * [年=>[[月=>容量(B)],[月=>容量(B)]...]になるように配列を変更
     *
     * @param [type] $yearCapacities [["save_dt"=>string フォーマットされた日付,"usage_size"=>容量],...]
     * @return array
     */
    private function getMonthCapacityByYears($yearCapacities)
    {
        $monthCapacityByYears = [];
        foreach ($yearCapacities as $yearCapacity) {
            //[年月=>容量(B),...]になるように配列を作成
            preg_match('/(.*年)(.*月)/', $yearCapacity['save_dt'], $matches);
            $year = $matches[1]; // 年を取得
            $months = $matches[2]; // 月を取得
            $monthCapacityByYears[$year][$months] = $yearCapacity['usage_size'];
        }
        return $monthCapacityByYears;
    }
    /**
     * HTMLに表示する月を1月から12月の順に取得する
     * @param [array] $monthCapacityByYears [年=>[[月=>容量(B)],[月=>容量(B)]...]
     * @param [boolean] $years true:個人用フォルダ false:共有用フォルダ
     * @return array 表示する月の配列 ex ['01月','02月'...]
     */
    private function getSortedMonths($monthCapacityByYears, $years)
    {
        $allMonths = [];
        foreach ($years as $year) {
            $allMonths = array_merge($allMonths, $monthCapacityByYears[$year]);
        }
        // 重複を削除して1月からソートする
        $uniqueMonths = array_keys($allMonths);
        $intMonths = array_map(function ($uniqueMonth) {
            return (int)str_replace('月', '', $uniqueMonth);
        }, $uniqueMonths);
        sort($intMonths);
        $headerMonths = array_map(function ($intMonth) {
            return str_pad($intMonth, 2, '0', STR_PAD_LEFT) . '月';
        }, $intMonths);
        return $headerMonths;
    }

    /**
     * 容量のデータからメールの本文を作成する
     * 
     * @param string $personalUsedTotalCapacity 本文に表示する個人フォルダの使用容量
     * @param string $shareTotalUsedCapacity 本文に表示する共有フォルダの使用容量
     * @param string $capacityTableHtml 使用容量の推移についてのHTMLでできたテーブル
     * @return string メールの本文
     */
    function getMailBody($personalUsedTotalCapacity = '0', $shareTotalUsedCapacity = '0', $capacityTableHtml = '')
    {
        if (!$capacityTableHtml) return false;
        return $this->includeTpl(MODULE_DIR . '/common/tpl/capacity_notification.php', [
            'personalTotalCapacity' => $personalUsedTotalCapacity,
            'shareTotalCapacity' => $shareTotalUsedCapacity,
            'capacityTableHtml' => $capacityTableHtml
        ]);
    }
    /**
     * テンプレートを変換する
     *
     * @param [string] $tpl 変換したいテンプレートのパス
     * @param [array] $data 変換に利用する変数
     * @return string or false 変換後のテンプレートの内容
     */
    function includeTpl($tpl, $data = null)
    {
        if (!empty($data)) {
            extract($data);
        }
        if (is_file($tpl)) {
            ob_start();
            include $tpl;
            return ob_get_clean();
        }
        return false;
    }
    // setter
    function setMdbConfigs($MdbConfigs)
    {
        $this->MdbConfigs = $MdbConfigs;
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

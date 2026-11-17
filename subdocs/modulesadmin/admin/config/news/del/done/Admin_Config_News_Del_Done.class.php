<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * [機能説明] お知らせ設定 - 削除 - 完了
 *
 * @package     WebFile
 * @author      Yusuke Numata
 * @copyright   KeyPoint
 * @access      public
 */

require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_News_Del_Done {
    var $session;
    var $DtoNews;
    var $MdbNews;

    var $DtoLog;
    var $MdbLog;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_News_Del_Doneが実行されました","Admin_Config_News_Del_Done#execute");

        // ログ用に削除前に情報をとっておく
        $this->MdbNews->getInfo($this->DtoNews);
        $open_at = $this->DtoNews->getOpen_at();
        $expiry_at = $this->DtoNews->getExpiry_at();
        $title = $this->DtoNews->getTitle();
        $title_en = $this->DtoNews->getTitle_en();
        $title_cn = $this->DtoNews->getTitle_cn();
        $title_tcn = $this->DtoNews->getTitle_tcn();

        // お知らせ削除
        $this->MdbNews->delete($this->DtoNews);

        // ログ
        $history_id = 137; // 137:各種設定＞お知らせ機能設定＞お知らせ削除
        $memo = "";
        $memo .= "公開日付：".$open_at."\n";
        if ($expiry_at) {
            $memo .= "公開期限：".$expiry_at."\n";
        }
        $memo .= "タイトル：".$title."\n";
        $memo .= "タイトル（英語）：".$title_en."\n";
        if( defined("CHINESE_FLG")  &&  CHINESE_FLG == 1 ) {
            $memo .= "タイトル（簡体字）：".$title_cn."\n";
            $memo .= "タイトル（繁体字）：".$title_tcn."\n";
        }
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id($this->session->getParameter('admin_id'));
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        // セッションクリア
        $this->session->removeParameter("news_id");

        return "success";
    }

    /***************************************************
     * setter
     **************************************************/
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoNews($DtoNews) {
        $this->DtoNews = $DtoNews;
    }
    function setMdbNews($MdbNews) {
        $this->MdbNews = $MdbNews;
    }
    function setDtoLog($DtoLog) {
        $this->DtoLog = $DtoLog;
    }
    function setMdbLog($MdbLog) {
        $this->MdbLog = $MdbLog;
    }

    /***************************************************
     * getter
     **************************************************/
}

/*
 * Local Variables:
 * mode: php
 * coding: utf-8-unix
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * indent-tabs-mode: nil
 * End:
 */

?>
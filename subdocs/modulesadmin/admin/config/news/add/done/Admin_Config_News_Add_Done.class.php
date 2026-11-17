<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * [機能説明] お知らせ設定 - 追加 - 完了
 *
 * @package     WebFile
 * @author      Yusuke Numata
 * @copyright   KeyPoint
 * @access      public
 */

require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_News_Add_Done {
    var $session;
    var $DtoNews;
    var $MdbNews;

    var $DtoLog;
    var $MdbLog;

    // request
    var $open_at_year;
    var $open_at_month;
    var $open_at_day;

    var $expiry_at_year;
    var $expiry_at_month;
    var $expiry_at_day;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_News_Add_Doneが実行されました","Admin_Config_News_Add_Done#execute");

        // エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        $open_at   = date("Y-m-d G:i:s", mktime(0, 0, 0, $this->open_at_month, $this->open_at_day, $this->open_at_year));
        $expiry_at = null;
        $error_flg = false;
        // 公開期限が入力されているか
        if ($this->expiry_at_year || $this->expiry_at_month || $this->expiry_at_day) {
            $expiry_at = date("Y-m-d G:i:s", mktime(23, 59, 59, $this->expiry_at_month, $this->expiry_at_day, $this->expiry_at_year));

            // 有効な日付の範囲をチェック
            if ($open_at > $expiry_at) {
                $errorList->add("Admin_Config_News_Add_Done::error", "公開日付〜公開期限の範囲を正しく入力してください。");
                $error_flg = true;
            }
        }

        // 中国語入力チェック
        if( defined("CHINESE_FLG")  &&  CHINESE_FLG == 1 ) {
            if( mb_strlen( $this->DtoNews->getTitle_cn(), "UTF-8" ) > 40 ) {
                $errorList->add( "Admin_Config_News_Add_Done::error", "タイトル（簡体字）を40文字以内で入力してください。" );
                $error_flg = true;
            }
            if( mb_strlen( $this->DtoNews->getTitle_tcn(), "UTF-8" ) > 40 ) {
                $errorList->add( "Admin_Config_News_Add_Done::error", "タイトル（繁体字）を40文字以内で入力してください。" );
                $error_flg = true;
            }
            if( mb_strlen( $this->DtoNews->getBody_cn(), "UTF-8" ) > 300 ) {
                $errorList->add( "Admin_Config_News_Add_Done::error", "本文（簡体字）を300文字以内で入力してください。" );
                $error_flg = true;
            }
            if( mb_strlen( $this->DtoNews->getBody_tcn(), "UTF-8" ) > 300 ) {
                $errorList->add( "Admin_Config_News_Add_Done::error", "本文（繁体字）を300文字以内で入力してください。" );
                $error_flg = true;
            }
        }

        // エラー表示
        if( $error_flg === true ) {
            return "input";
        }

        // DTOに公開日付をセット
        $this->DtoNews->setOpen_at($open_at);
        $this->DtoNews->setExpiry_at($expiry_at);

        // お知らせ追加
        $this->MdbNews->insert($this->DtoNews);

        // ログ
        $history_id = 135; // 135:各種設定＞お知らせ機能設定＞お知らせ追加
        $memo = "";
        $memo .= "公開日付：".$this->DtoNews->getOpen_at()."\n";
        if ($this->DtoNews->getExpiry_at()) {
            $memo .= "公開期限：".$this->DtoNews->getExpiry_at()."\n";
        }
        $memo .= "タイトル：".$this->DtoNews->getTitle()."\n";
        $memo .= "タイトル（英語）：".$this->DtoNews->getTitle_en()."\n";
        if( defined("CHINESE_FLG")  &&  CHINESE_FLG == 1 ) {
            $memo .= "タイトル（簡体字）：".$this->DtoNews->getTitle_cn()."\n";
            $memo .= "タイトル（繁体字）：".$this->DtoNews->getTitle_tcn()."\n";
        }
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id($this->session->getParameter('admin_id'));
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

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

    function setOpen_at_year($open_at_year) {
        $this->open_at_year = $open_at_year;
    }
    function setOpen_at_month($open_at_month) {
        $this->open_at_month = $open_at_month;
    }
    function setOpen_at_day($open_at_day) {
        $this->open_at_day = $open_at_day;
    }
    function setExpiry_at_year($expiry_at_year) {
        $this->expiry_at_year = $expiry_at_year;
    }
    function setExpiry_at_month($expiry_at_month) {
        $this->expiry_at_month = $expiry_at_month;
    }
    function setExpiry_at_day($expiry_at_day) {
        $this->expiry_at_day = $expiry_at_day;
    }

    /***************************************************
     * getter
     **************************************************/
    function getOpen_at_year() {
        return $this->open_at_year;
    }
    function getOpen_at_month() {
        return $this->open_at_month;
    }
    function getOpen_at_day() {
        return $this->open_at_day;
    }
    function getExpiry_at_year() {
        return $this->expiry_at_year;
    }
    function getExpiry_at_month() {
        return $this->expiry_at_month;
    }
    function getExpiry_at_day() {
        return $this->expiry_at_day;
    }
    function getTitle() {
        return $this->DtoNews->getTitle();
    }
    function getTitle_en() {
        return $this->DtoNews->getTitle_en();
    }
    function getTitle_cn() {
        return $this->DtoNews->getTitle_cn();
    }
    function getTitle_tcn() {
        return $this->DtoNews->getTitle_tcn();
    }
    function getBody() {
        return $this->DtoNews->getBody();
    }
    function getBody_en() {
        return $this->DtoNews->getBody_en();
    }
    function getBody_cn() {
        return $this->DtoNews->getBody_cn();
    }
    function getBody_tcn() {
        return $this->DtoNews->getBody_tcn();
    }
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
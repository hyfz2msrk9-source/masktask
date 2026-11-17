<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * [機能説明] お知らせ設定 - 編集
 *
 * @package     WebFile
 * @author      Yusuke Numata
 * @copyright   KeyPoint
 * @access      public
 */

require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_News_Edit {
    var $session;
    var $DtoNews;
    var $MdbNews;

    // response
    var $open_at_year;
    var $open_at_month;
    var $open_at_day;

    var $expiry_at_year;
    var $expiry_at_month;
    var $expiry_at_day;
    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_News_Editが実行されました","Admin_Config_News_Edit#execute");

        // IDチェック
        if(!is_numeric($this->DtoNews->getNews_id())) {
            return "error";
        }

        $log->debug("ここ", "Admin_Config_News_Edit#execute");
        // お知らせ情報取得
        if (!$this->MdbNews->getInfo($this->DtoNews)){
            return "error";
        }

        // セッションにセット
        $this->session->setParameter("news_id", $this->DtoNews->getNews_id());

        // 公開日付を分解し、変数にセット
        $date = explode("-", date("Y-m-d", strtotime($this->DtoNews->getOpen_at())));
        $this->open_at_year  = $date[0];
        $this->open_at_month = $date[1];
        $this->open_at_day   = $date[2];

        // 公開期限を分解し、変数にセット
        if (!is_null($this->DtoNews->getExpiry_at())) {
            $date = explode("-", date("Y-m-d", strtotime($this->DtoNews->getExpiry_at())));
            $this->expiry_at_year  = $date[0];
            $this->expiry_at_month = $date[1];
            $this->expiry_at_day   = $date[2];
        }

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
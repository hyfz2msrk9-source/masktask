<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * [機能説明] お知らせ設定 - 削除
 *
 * @package     WebFile
 * @author      Yusuke Numata
 * @copyright   KeyPoint
 * @access      public
 */

require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_News_Del {
    var $session;
    var $DtoNews;
    var $MdbNews;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_News_Delが実行されました","Admin_Config_News_Del#execute");

        // IDチェック
        if(!is_numeric($this->DtoNews->getNews_id())) {
            return "error";
        }
        
        // お知らせ情報取得
        if (!$this->MdbNews->getInfo($this->DtoNews)){
            return "error";
        }

        // セッションにセット
        $this->session->setParameter("news_id", $this->DtoNews->getNews_id());

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
    function getOpen_at() {
        return $this->DtoNews->getOpen_at();
    }
    function getExpiry_at() {
        return $this->DtoNews->getExpiry_at();
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
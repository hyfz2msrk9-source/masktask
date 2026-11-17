<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * [機能説明] お知らせ設定 - インデックス
 *
 * @package     WebFile
 * @author      Yusuke Numata
 * @copyright   KeyPoint
 * @access      public
 */

require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_News {
    var $session;
    var $DtoNews;
    var $MdbNews;
    var $MdbConfigs;
    var $token;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Newsが実行されました","Admin_Config_News#execute");

        // お知らせ一覧取得
        $this->MdbNews->getList($this->DtoNews);

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'token_name' => 'NewsEditToken',
            'token_value' => $this->token->getValue('NewsEditToken')
        ]);
        exit;
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
    function setMdbConfigs ($MdbConfigs)    
    {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setToken($token) {
        $this->token = $token;
    }
    /***************************************************
     * getter
     **************************************************/
    function getNewsList() {
        return $this->DtoNews->getNewsList();
    }

    function getUserKpNewsFlg()
    {
       return $this->MdbConfigs->getDirectValue("user_kp_news_flg");
    }
    function getAdminKpNewsFlg()
    {
       return $this->MdbConfigs->getDirectValue("admin_kp_news_flg");
    }

    function getKpNewsOptions(){
        return GlobalArray::getKp_News_Flg_Options();
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

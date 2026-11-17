<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_News {
    var $session;
    var $kpNewsList;

    var $MdbKpNews;
    /** @var \Wf_Mdb_Configs */
    var $MdbConfigs;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug(__CLASS__ . "が実行されました",__CLASS__ . "#execute");

        if ($this->MdbConfigs->getDirectValue('admin_kp_news_flg') !== '1') {
            // 管理者ニュースが無効な場合はエラー
            return "error";
        }
        
        // ニュース一覧取得
        $this->kpNewsList = $this->MdbKpNews->getList();

        return "success";
    }
    /************************************
    * setter
    *************************************/
    function setMdbKpNews($MdbKpNews)
    {
        $this->MdbKpNews = $MdbKpNews;
    }
    function setMdbConfigs($MdbConfigs)
    {
        $this->MdbConfigs = $MdbConfigs;
    }

    /************************************
    * getter
    *************************************/
    function getKpNewsList() {
        if (!$this->kpNewsList) {
            return [];
        }
        return $this->kpNewsList;
    }
}
?>

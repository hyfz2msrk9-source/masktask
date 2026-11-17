<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_News_Info {
    var $session;
    /** @var \Wf_Mdb_KpNews */
    var $MdbKpNews;
    var $news;
    var $news_id;
    var $newsList;//前後のニュースがあるか見るのに使う

    /** @var \Wf_Mdb_KpNewsReads */
    var $MdbKpNewsReads;
    /** @var \Wf_Mdb_Configs */
    var $MdbConfigs;
    /** @var \Wf_Kpn_ewsLogic */
    var $KpnewsLogic;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_News_Info","Admin_News_Info#execute");

        if ($this->MdbConfigs->getDirectValue('admin_kp_news_flg') !== '1') {
            // 管理者ニュースが無効な場合はエラー
            return "error";
        }

        $this->news = $this->MdbKpNews->getNews($this->news_id);
        if (!$this->news) {
            return "error";
        }
        $admin_id = $this->session->getParameter("admin_id");
        $this->newsList = $this->MdbKpNews->getList();
        // 既読にする
        if (!$this->MdbKpNewsReads->isRead($this->news_id, $admin_id)) {
            $this->MdbKpNewsReads->setNewsRead($this->news_id, $admin_id);
        }
        return "success";
    }
    /************************************
    * setter
    *************************************/
    function setSession($session) {
        $this->session = $session;
    }
    function setMdbKpNews($MdbKpNews) {
        $this->MdbKpNews = $MdbKpNews;
    }
    function setNews_id($news_id) {
        $this->news_id = $news_id;
    }
    function setMdbKpNewsReads($MdbKpNewsReads)
    {
        $this->MdbKpNewsReads = $MdbKpNewsReads;
    }
    function setMdbConfigs($MdbConfigs)
    {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setKpnewsLogic($KpnewsLogic)
    {
        $this->KpnewsLogic = $KpnewsLogic;
    }
    /************************************
    * getter
    *************************************/
    function getNews(){
        return $this->news;
    }

    function getPrevNewsId() {
        if (!$this->newsList || count($this->newsList) == 1) {
            return false;
        }
        $index = array_search($this->news_id, array_column($this->newsList, 'news_id'));
        if(!isset($this->newsList[$index + 1])){
            return false;
        }
        return $this->newsList[$index + 1]['news_id'];
    }

    function getNextNewsId() {
        if (!$this->newsList || count($this->newsList) == 1) {
            return false;
        }
        $index = array_search($this->news_id, array_column($this->newsList, 'news_id'));
        if(!isset($this->newsList[$index - 1])){
            return false;
        }
        return $this->newsList[$index - 1]['news_id'];
    }
    function getContents()
    {
        return $this->KpnewsLogic->getEscapedNewsWithLinks($this->news['contents']);
    }

}
?>

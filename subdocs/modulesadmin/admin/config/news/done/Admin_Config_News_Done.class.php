<?php

/**
 * 運営からのお知らせを表示するかの設定（完了）
 */
require_once( MODULE_DIR . "/common/common.php");
require_once( MODULE_DIR . "/admin/config/news/Admin_Config_News.class.php");

class Admin_Config_News_Done extends Admin_Config_News {
    var $DtoLog;
    var $MdbLog;

    var $user_kp_news_flg;
    var $admin_kp_news_flg;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug(__CLASS__ . "が実行されました",__CLASS__ . "#execute");

        if(SHOW_KP_NEWS_CONFIG != 1){
            // KPニュース設定非表示の場合は処理しない
            return "error";
        }

        // 入力チェックはvalidationで実施

        // 元の値
        $prevUserKpNewsFlg = $this->MdbConfigs->getDirectValue("user_kp_news_flg");
        $prevAdminKpNewsFlg = $this->MdbConfigs->getDirectValue("admin_kp_news_flg");

        // 設定値保存
        $this->MdbConfigs->setValue('user_kp_news_flg', $this->user_kp_news_flg);
        $this->MdbConfigs->setValue('admin_kp_news_flg', $this->admin_kp_news_flg);

        $memo = "";
        $oldUserFlg = "";
        $oldAdminFlg = "";
        if ($this->user_kp_news_flg != $prevUserKpNewsFlg) {
            $oldUserFlg = ($prevUserKpNewsFlg == "1") ? "表示する→" : "表示しない→";
        }
        if ($this->admin_kp_news_flg != $prevAdminKpNewsFlg) {
            $oldAdminFlg = ($prevAdminKpNewsFlg == "1") ? "表示する→" : "表示しない→";
            // メニュー描画用にセッションに保存
            $this->session->setParameter("login_admin_kp_news", $this->MdbConfigs->getDirectValue('admin_kp_news_flg'));
        }
        $memo .= "ユーザ画面お知らせ表示：" . $oldUserFlg . ($this->user_kp_news_flg == "1" ? "表示する" : "表示しない") . "\n";
        $memo .= "管理画面お知らせ表示：" . $oldAdminFlg . ($this->admin_kp_news_flg == "1" ? "表示する" : "表示しない") . "\n";

        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id($this->session->getParameter('admin_id'));
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id(210); // 各種設定＞お知らせに関する設定
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success'
        ]);
        exit;
    }

    // setter
    function setDtoLog(&$DtoLog) { $this->DtoLog = $DtoLog; }
    function setMdbLog($MdbLog) { $this->MdbLog = $MdbLog; }
    function setUser_kp_news_flg($user_kp_news_flg) {
        $this->user_kp_news_flg = $user_kp_news_flg;
    }
    function setAdmin_kp_news_flg($admin_kp_news_flg) {
        $this->admin_kp_news_flg = $admin_kp_news_flg;
    }

    // 完了画面表示用
    function getReturnAction() {
        return "admin_config_news";
    }
    function getTitle() {
        return "その他設定 (お知らせに関する設定)  ";
    }
    function getExplanation() {
        return "運営からのお知らせについて、設定が完了しました。";
    }
    function getHelp(){
        return 'help/html/config/news.html';
    }

}

?>

<?php

require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Gathering {
    var $session;
    var $DtoConfigs;
    var $MdbConfigs;
    var $token;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Gatheringが実行されました", "Admin_Config_Gathering#execute");

        $this->MdbConfigs->getList($this->DtoConfigs);

        $list = $this->DtoConfigs->getConfigslist();

        if (!defined('GATHERING_FLG') || !GATHERING_FLG) {
            echo json_encode([
                'status' => 'error',
                'message' => '不正なアクセスを検知しました。'
            ]);
            exit;
        }

        // sessionに記入
        $this->session->setParameter("gathering_flg", $list['gathering_flg']); // 一斉収集フラグ
        $this->session->setParameter("gathering_folder_flg", $list['gathering_folder_flg']); // 一斉収集の保管先フォルダの種類
        $this->session->setParameter("gathering_mail_limit", $list['gathering_mail_limit']); // 最大送信メール数
        $this->session->setParameter("gathering_file_limit", $list['gathering_file_limit']); // 最大送信ファイル数
        $this->session->setParameter("gathering_sample_temp_flg", $list['gathering_sample_temp_flg']); // サンプル文初期入力
        $this->session->setParameter("gathering_default_pass_flg", $list['gathering_default_pass_flg']); // 収集パスワードロック
        $this->session->setParameter("gathering_notice_pass_flg", $list['gathering_notice_pass_flg']); // 指定パスワード通知
        $this->session->setParameter("gathering_limitdt_mark", $list['gathering_limitdt_mark']); // アップロード有効期限の最小値

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'token_name' => 'GatheringEditToken',
            'token_value' => $this->token->getValue('GatheringEditToken')
        ]);
        exit;
    }

    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoConfigs($DtoConfigs) {
        $this->DtoConfigs = $DtoConfigs;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setToken($token) {
        $this->token = $token;
    }
}
?>

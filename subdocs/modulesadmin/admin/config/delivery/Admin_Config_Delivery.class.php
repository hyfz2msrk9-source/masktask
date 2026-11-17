<?php
/**
 * [機能説明] 各種設定 - 操作設定 - 一斉配信に関する設定
 * @updater
 *
 * @package     WebFile
 * @version     3.15.0?
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Delivery {
    var $session;
    var $DtoConfigs;
    var $MdbConfigs;
    var $DtoGroups;
    var $MdbGroups;
    var $MdbMchoice;
    var $use_capacity;
    var $group_count;
    var $token;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Deliveryが実行されました", "Admin_Config_Delivery#execute");

        $this->MdbConfigs->getList($this->DtoConfigs);

        $list = $this->DtoConfigs->getConfigslist();

        if (!defined('DELIVERY_FLG') || !DELIVERY_FLG) {
            echo json_encode([
                'status' => 'error',
                'message' => '不正なアクセスを検知しました。'
            ]);
            exit;
        }

        // sessionに記入
        $this->session->setParameter("delivery_flg", $list['delivery_flg']); // 一斉配信フラグ
        $this->session->setParameter("delivery_nofile_flg", $list['delivery_nofile_flg']); // 添付ファイルなしフラグ
        $this->session->setParameter("delivery_send_flg", $list['delivery_send_flg']); // 一斉配信の配布フラグ
        $this->session->setParameter("delivery_mail_limit", $list['delivery_mail_limit']); // 最大送信人数
        $this->session->setParameter("file_delivery_limit", $list['file_delivery_limit']); // 最大送信ファイル数
        $this->session->setParameter("delivery_schedule_span_limit", $list['delivery_schedule_span_limit']); // 送信予定日時の上限
        $this->session->setParameter("delivery_sample_temp_flg", $list['delivery_sample_temp_flg']); // サンプル文初期入力
        $this->session->setParameter("default_deliverypass_flg", $list['default_deliverypass_flg']); // 配布パスワードロック
        $this->session->setParameter("zip_deliverypass_flg", $list['zip_deliverypass_flg']); // ZIPパスワードロック
        $this->session->setParameter("password_notice_flg", $list['password_notice_flg']); // 指定・ZIPパスワード通知
        $this->session->setParameter("delivery_limitdt_mark", $list['delivery_limitdt_mark']); // ダウンロード有効期限の最小値
        $this->session->setParameter("delivery_maxdltime_mark", $list['delivery_maxdltime_mark']); // ダウンロード回数の最大値

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'token_name' => 'DeliveryEditToken',
            'token_value' => $this->token->getValue('DeliveryEditToken')
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
    function setDtoGroups($DtoGroups) {
        $this->DtoGroups = $DtoGroups;
    }
    function setMdbGroups($MdbGroups) {
        $this->MdbGroups = $MdbGroups;
    }
    function setMdbMchoice($MdbMchoice) {
        $this->MdbMchoice = $MdbMchoice;
    }
    function setToken($token) {
        $this->token = $token;
    }
}
?>

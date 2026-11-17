<?php
/**
 * その他設定（ユーザ画面に関する設定）
 * @updater     Hiroko Yanai（2018/06/25）フォルダツリーのスクロールバー
 * @updater     Hiroko Yanai（2018/06/26）フォルダのフルパス
 * @updater     Hiroko Yanai（2018/06/27）登録者を表示する
 * @updater     Hiroko Yanai（2018/06/29）フォルダをファイル一覧に表示する
 *
 * @package     WebFile
 * @version     3.15.1
 * @author
 * @copyright   KEYPOINT.INC
 * @access      public
 */
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_View {
    var $session;
    var $DtoConfigs;
    var $MdbConfigs;
    var $token;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Viewが実行されました","Admin_Config_View#execute");
        $this->MdbConfigs->getList($this->DtoConfigs);

        $list = $this->DtoConfigs->getConfigslist();
        //sessionに記入
        $this->session->setParameter("user_meter_flg", $list['user_meter_flg']);
        $this->session->setParameter("folder_tree_scroll_bar_flg", $list['folder_tree_scroll_bar_flg']);
        $this->session->setParameter("folder_full_path_flg", $list['folder_full_path_flg']);
        $this->session->setParameter("folder_display_inline_flg", $list['folder_display_inline_flg']);
        if (SHARE_GROUP_FLG) {
            $this->session->setParameter("share_search_flg", $list['share_search_flg']);
            $this->session->setParameter("share_meter_flg", $list['share_meter_flg']);
            $this->session->setParameter("upload_user_name_flg", $list['upload_user_name_flg']);
        }
        $this->session->setParameter("upload_return_flg",   $list['upload_return_flg']);
        $this->session->setParameter("view_common_address_book_flg",$list['view_common_address_book_flg']);
        $this->session->setParameter("view_history_ip_flg", $list['view_history_ip_flg']);
        $this->session->setParameter("admin_tel_flg", $list['admin_tel_flg']);
        $this->session->setParameter("admin_tel_number", $list['admin_tel_number']);
        $this->session->setParameter("admin_mail_flg", $list['admin_mail_flg']);
        $this->session->setParameter("admin_mail_to_addr", $list['admin_mail_to_addr']);
        $this->session->setParameter("admin_mail_cc_addr", $list['admin_mail_cc_addr']);

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'token_name' => 'ViewEditToken',
            'token_value' => $this->token->getValue('ViewEditToken')
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

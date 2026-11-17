<?php
/**
 * その他設定（ユーザ画面に関する設定）（完了）
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

class Admin_Config_View_Done {
    var $session;
    var $DtoConfigs;
    var $MdbConfigs;
    var $DtoLog;
    var $MdbLog;
    var $MdbMchoice;
    var $DtoGroups;
    var $MdbGroups;

    var $file_move_flg;
    var $folder_dl_flg;
    var $share_search_flg;
    var $user_meter_flg;
    var $share_meter_flg;
    var $folder_tree_scroll_bar_flg;
    var $folder_full_path_flg;
    var $upload_user_name_flg;
    var $folder_display_inline_flg;
    var $view_common_address_book_flg;
    var $view_history_ip_flg;
    var $upload_return_flg;
    var $admin_tel_flg;
    var $admin_tel_number;
    var $admin_mail_flg;
    var $admin_mail_to_addr;
    var $admin_mail_cc_addr;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_View_Doneが実行されました","Admin_Config_View_Done#execute");

        // エラー発生
        $container =& DIContainerFactory::getContainer();
        $actionChain =& $container->getComponent("ActionChain");
        $errorList =& $actionChain->getCurErrorList();

        // 入力チェック
        if (($this->user_meter_flg != "0" && $this->user_meter_flg != "1") ||
            ($this->folder_tree_scroll_bar_flg !="0" && $this->folder_tree_scroll_bar_flg != "1") ||
            ($this->folder_full_path_flg !="0" && $this->folder_full_path_flg != "1") ||
            ($this->folder_display_inline_flg !="0" && $this->folder_display_inline_flg != "1") ||
            ($this->view_common_address_book_flg != "0" && $this->view_common_address_book_flg != "1") ||
            ($this->view_history_ip_flg !="0" && $this->view_history_ip_flg !="1") ||
            ($this->upload_return_flg != "0" && $this->upload_return_flg != "1")
        ) {
            return "error";
        }

        // 個人フォルダメータ表示の入力チェック
        $meter_options = GlobalArray::getMeter_Flg_Options();
        if (!array_key_exists($this->user_meter_flg, $meter_options)) return "error";

        if (SHARE_GROUP_FLG) {
            // 共有フォルダ検索の入力チェック
            if ($this->share_search_flg != "0" && $this->share_search_flg != "1") {
                return "error";
            }
            // 共有フォルダメータ表示の入力チェック
            if (!array_key_exists($this->share_meter_flg, $meter_options)) return "error";
            // ファイルの登録者の入力チェック
            if ($this->upload_user_name_flg != "0" && $this->upload_user_name_flg != "1") {
                return "error";
            }
        }

        $shouldReturn = false;
        $admin_tel_flg = $this->admin_tel_flg === "1" ? "1" : "0"; //チェックされていない時nullになるため0を入れる
        $admin_mail_flg = $this->admin_mail_flg === "1" ? "1" : "0";

        if($admin_tel_flg === '1') {
            if($this->admin_tel_number === ''){
                $errorList->add("Admin_Config_User_Done::error", "電話番号を入力してください。");
                $shouldReturn = true;
            }
        }

        if($admin_mail_flg === '1') {
            if($this->admin_mail_to_addr === ''){
                $errorList->add("Admin_Config_User_Done::error", "メールアドレス To: を入力してください。");
                $shouldReturn = true;
            }
        }

        if ($shouldReturn) return "input";

        //履歴画面IPアドレス表示・非表示
        $this->MdbConfigs->setValue('user_meter_flg',$this->user_meter_flg);
        $this->MdbConfigs->setValue('folder_tree_scroll_bar_flg',$this->folder_tree_scroll_bar_flg);
        $this->MdbConfigs->setValue('folder_full_path_flg',$this->folder_full_path_flg);
        $this->MdbConfigs->setValue('folder_display_inline_flg',$this->folder_display_inline_flg);
        if (SHARE_GROUP_FLG) {
            $this->MdbConfigs->setValue('share_search_flg',$this->share_search_flg);
            $this->MdbConfigs->setValue('share_meter_flg',$this->share_meter_flg);
            $this->MdbConfigs->setValue('upload_user_name_flg',$this->upload_user_name_flg);
        }
        $this->MdbConfigs->setValue('view_common_address_book_flg',$this->view_common_address_book_flg);
        $this->MdbConfigs->setValue('view_history_ip_flg',$this->view_history_ip_flg);

        $this->MdbConfigs->setValue('admin_tel_flg', $admin_tel_flg);
        $this->MdbConfigs->setValue('admin_tel_number',$this->admin_tel_number);
        $this->MdbConfigs->setValue('admin_mail_flg', $admin_mail_flg);
        $this->MdbConfigs->setValue('admin_mail_to_addr',$this->admin_mail_to_addr);
        $this->MdbConfigs->setValue('admin_mail_cc_addr',$this->admin_mail_cc_addr);


        $history_id = 112; // 112:各種設定＞機能設定
        $memo  = "";

        if ($this->user_meter_flg != $this->session->getParameter('user_meter_flg')) {
            $memo .= "個人フォルダのメーター表示：".($this->user_meter_flg == "1" ? "表示する" : "表示しない");
            $memo .= "\n";
        }
        if (SHARE_GROUP_FLG && ($this->share_meter_flg != $this->session->getParameter('share_meter_flg'))) {
            $memo .= "共有フォルダのメーター表示：".($this->share_meter_flg == "1" ? "表示する" : "表示しない");
            $memo .= "\n";
        }
        if ($this->folder_tree_scroll_bar_flg != $this->session->getParameter('folder_tree_scroll_bar_flg')) {
            $memo .= "フォルダツリー：".($this->folder_tree_scroll_bar_flg == "1" ? "スクロールバーを表示する" : "スクロールバーを表示しない");
            $memo .= "\n";
        }
        if ($this->folder_full_path_flg != $this->session->getParameter('folder_full_path_flg')) {
            $memo .= "フォルダのフルパス：".($this->folder_full_path_flg == "1" ? "表示する" : "表示しない");
            $memo .= "\n";
        }
        if ($this->folder_display_inline_flg != $this->session->getParameter('folder_display_inline_flg')) {
            $memo .= "ファイル一覧の項目＞フォルダの表示：".($this->folder_display_inline_flg == "1" ? "表示する" : "表示しない");
            $memo .= "\n";
        }
        if (SHARE_GROUP_FLG) {
            if ($this->upload_user_name_flg != $this->session->getParameter('upload_user_name_flg')) {
                $memo .= "ファイル一覧の項目＞登録者：".($this->upload_user_name_flg == "1" ? "表示する" : "表示しない");
                $memo .= "\n";
            }
        }
        if ($this->upload_return_flg != $this->session->getParameter("upload_return_flg")) {
            $flg = "";
            switch ($this->upload_return_flg) {
                case 1:
                    $flg .= "ファイル一覧へ戻る";
                    break;
                default:
                    $flg .= "完了画面を表示する";
                    break;
            }
            $memo .= "アップロード完了後：" . $flg . "\n";
        }
        if (SHARE_GROUP_FLG) {
            if ( $this->share_search_flg != $this->session->getParameter("share_search_flg")) {
                if ($this->share_search_flg == "1") {
                    $flg = "有効";
                } else {
                    $flg = "無効";
                }
                $memo .= "共有フォルダ検索機能：".$flg."\n";
            }
        }

        if ( $this->view_common_address_book_flg != $this->session->getParameter("view_common_address_book_flg")) {
            if ($this->session->getParameter("view_common_address_book_flg") == "1") {
                $flg1 = "表示しない";
            } else {
                $flg1 = "表示する";
            }
            if ($this->view_common_address_book_flg == "1") {
                $flg2 = "表示しない";
            } else {
                $flg2 = "表示する";
            }
            $memo .= "共通アドレス帳：".$flg1."→".$flg2."\n";
        }

        if ( $this->view_history_ip_flg != $this->session->getParameter("view_history_ip_flg")) {
            if ($this->session->getParameter("view_history_ip_flg") == "1") {
                $flg1 = "表示しない";
            } else {
                $flg1 = "表示する";
            }
            if ($this->view_history_ip_flg == "1") {
                $flg2 = "表示しない";
            } else {
                $flg2 = "表示する";
            }
            $memo .= "履歴画面IPアドレス：".$flg1."→".$flg2."\n";
        }

        $contacts = array();
        if ($admin_tel_flg !== $this->session->getParameter("admin_tel_flg") || $admin_mail_flg !== $this->session->getParameter("admin_mail_flg")){
            if ($admin_tel_flg !== "1" && $admin_mail_flg !== "1") {
                $contacts[] = "なし";
            } else {
                if ($admin_tel_flg == "1") {
                    $contacts[] = "電話";
                }

                if ($admin_mail_flg == "1") {
                    $contacts[] = "メール";
                }
            }
        }

        if (count($contacts) > 0) {
            $contact = implode("・", $contacts);
            $memo .= "管理者への連絡方法：$contact\n";
        }

        $contactDetails = array();
        if ($this->admin_tel_number !== $this->session->getParameter("admin_tel_number")) {
            $contactDetails['telNum'] = $this->admin_tel_number;
        }
        if ($this->admin_mail_to_addr !== $this->session->getParameter("admin_mail_to_addr")) {
            $contactDetails['toAddr'] = $this->admin_mail_to_addr;
        }
        if ($this->admin_mail_cc_addr !== $this->session->getParameter("admin_mail_cc_addr")) {
            $contactDetails['ccAddr'] = $this->admin_mail_cc_addr;
        }
        if (count($contactDetails) > 0) {
            if (isset($contactDetails['telNum'])) {
                $memo .= "管理者の連絡先電話番号：" . $contactDetails['telNum'] . "\n";
            }
            if (isset($contactDetails['toAddr'])) {
                $memo .= "管理者の連絡先メールアドレス（To）：" . $contactDetails['toAddr'] . "\n";
            }
            if (isset($contactDetails['ccAddr'])) {
                $memo .= "管理者の連絡先メールアドレス（Cc）：" . $contactDetails['ccAddr'] . "\n";
            }
        }

        if ($memo) {
            $memo = "[その他＞ユーザ画面に関する設定]"."\n".$memo;

            $this->DtoLog->setUser_id("");
            $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
            $this->DtoLog->setFile_id("");
            $this->DtoLog->setHistory_id($history_id);
            $this->DtoLog->setMemo($memo);
            $this->MdbLog->Logging($this->DtoLog);
        }

        //sessionを削除
        $this->session->removeParameter("user_meter_flg");
        $this->session->removeParameter("folder_tree_scroll_bar_flg");
        $this->session->removeParameter("folder_full_path_flg");
        $this->session->removeParameter("folder_display_inline_flg");
        if (SHARE_GROUP_FLG) {
            $this->session->removeParameter("share_search_flg");
            $this->session->removeParameter("share_meter_flg");
            $this->session->removeParameter("upload_user_name_flg");
        }
        $this->session->removeParameter("upload_return_flg");
        $this->session->removeParameter("view_common_address_book_flg");
        $this->session->removeParameter("view_history_ip_flg");
        $this->session->removeParameter("admin_tel_flg");
        $this->session->removeParameter("admin_tel_number");
        $this->session->removeParameter("admin_mail_flg");
        $this->session->removeParameter("admin_mail_to_addr");
        $this->session->removeParameter("admin_mail_cc_addr");

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success'
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
    function setDtoLog ( &$DtoLog ) {
        $this->DtoLog =& $DtoLog;
    }
    function setMdbLog ( &$MdbLog ) {
        $this->MdbLog =& $MdbLog;
    }
    function setMdbMchoice($MdbMchoice) {
        $this->MdbMchoice =& $MdbMchoice;
    }
    function setDtoGroups($DtoGroups) {
        $this->DtoGroups = $DtoGroups;
    }
    function setMdbGroups($MdbGroups) {
        $this->MdbGroups = $MdbGroups;
    }
    function setUpload_return_flg($upload_return_flg) {
        $this->upload_return_flg = $upload_return_flg;
    }
    function setShare_search_flg($share_search_flg) {
        $this->share_search_flg = $share_search_flg;
    }
    function setUser_meter_flg($user_meter_flg) {
        $this->user_meter_flg = $user_meter_flg;
    }
    function setShare_meter_flg($share_meter_flg) {
        $this->share_meter_flg = $share_meter_flg;
    }
    function setFolder_tree_scroll_bar_flg($folder_tree_scroll_bar_flg) {
        $this->folder_tree_scroll_bar_flg = $folder_tree_scroll_bar_flg;
    }
    function setFolder_full_path_flg($folder_full_path_flg) {
        $this->folder_full_path_flg = $folder_full_path_flg;
    }
    function setFolder_display_inline_flg($folder_display_inline_flg) {
        $this->folder_display_inline_flg = $folder_display_inline_flg;
    }
    function setUpload_user_name_flg($upload_user_name_flg) {
        $this->upload_user_name_flg = $upload_user_name_flg;
    }
    function setView_common_address_book_flg($view_common_address_book_flg) {
    	$this->view_common_address_book_flg = $view_common_address_book_flg;
    }
    function setView_history_ip_flg(&$view_history_ip_flg)
    {
        $this->view_history_ip_flg =& $view_history_ip_flg;
    }

    function setAdmin_tel_flg(&$admin_tel_flg)
    {
        $this->admin_tel_flg =& $admin_tel_flg;
    }

    function setAdmin_tel_number(&$admin_tel_number)
    {
        $this->admin_tel_number =& $admin_tel_number;
    }

    function setAdmin_mail_flg(&$admin_mail_flg)
    {
        $this->admin_mail_flg =& $admin_mail_flg;
    }

    function setAdmin_mail_to_addr(&$admin_mail_to_addr)
    {
        $this->admin_mail_to_addr =& $admin_mail_to_addr;
    }

    function setAdmin_mail_cc_addr(&$admin_mail_cc_addr)
    {
        $this->admin_mail_cc_addr =& $admin_mail_cc_addr;
    }

    // getter
    function getConfig() {
        $this->MdbConfigs->getList($this->DtoConfigs);
        return $this->DtoConfigs->configslist;
    }
    function getView_History_Ip_Flg_Options()
    {
        return GlobalArray::getView_History_Ip_Flg_Options();
    }

    function getFile_Move_Flg_Options()
    {
        return GlobalArray::getFile_Move_Flg_Options();
    }
    function getFolder_Dl_Flg_Options()
    {
        return GlobalArray::getFolder_Dl_Flg_Options();
    }
//    function getView_History_Ip_flg_Options()
//    {
//        return GlobalArray::getView_History_Ip_Flg_Options();
//    }
    function getMeter_Flg_Options()
    {
        return GlobalArray::getMeter_Flg_Options();
    }
    function getShare_Search_Flg_Options()
    {
        return GlobalArray::getShare_Search_Flg_Options();
    }
    function getFolder_Tree_Scroll_Bar_Flg_Options()
    {
        return GlobalArray::getFolder_Tree_Scroll_Bar_Flg_Options();
    }
    function getFolder_Full_Path_Flg_Options()
    {
        return GlobalArray::getFolder_Full_Path_Flg_Options();
    }
    function getFolder_Display_Inline_Flg_Options()
    {
        return GlobalArray::getFolder_Display_Inline_Flg_Options();
    }
    function getUpload_User_Name_Flg_Options()
    {
        return GlobalArray::getUpload_User_Name_Flg_Options();
    }
    function getView_Common_Address_Book_flg_Options()
    {
        return GlobalArray::getView_Common_Address_Book_Flg_Options();
    }
}
?>

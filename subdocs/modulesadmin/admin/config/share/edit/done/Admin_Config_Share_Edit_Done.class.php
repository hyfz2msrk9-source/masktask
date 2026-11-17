<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] グループ共有機能 - グループ共有機能に関する設定 - グループ編集(完了)
 * なお、権限設定からもこのアクションが呼び出される。
 * @updater     Hiroko Yanai（2018/04/13）SAML認証(OneLogin)に対応。SSOやSAMLでも、設定は更新可能とする
 *
 * @package     WebFile
 * @version     3.15.2
 * @author
 * @copyright   KEY POINT
 * @access      public
 */
class Admin_Config_Share_Edit_Done {
    var $session;
    var $DtoGroups;
    var $MdbGroups;
    var $MdbGroups_users;
    var $groupDto;
    var $DtoFolder;
    var $MdbFolder;
    var $DtoUser;
    var $MdbUser;
    var $DtoLog;
    var $MdbLog;
    var $MdbUpload;
    var $DtoSection;
    var $MdbSection;
    var $DtoSectionusers;
    var $MdbSectionusers;
    var $DtoGroupsadminusers;
    var $MdbGroupsadminusers;
    var $GroupsusersLogic;
    var $GroupLogic;
    var $SectionLogic;
    var $MdbMchoice;
    var $DtoAdminUser;
    var $AdminUserLogic;
    var $MdbConfigs;
    /** @var \Wf_Superior_Logic */
    var $SuperiorLogic;

    var $MdbAdminuser;
    var $AdminUserDto;

    var $use_capacity;
    var $limit_capacity;
    var $alledit_flg;
    var $user_open_flg;
    var $leader_open_flg;
    var $section_open_flg;
    var $group_edit_flg;
    var $back_flg;

    var $CommonList;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Share_Edit_Doneが実行されました","Admin_Config_Share_Edit_Done#execute");

        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        // 定期削除機能が無効の時
        if (AUTO_DEL_FILE_STOP_FLG == 1) {
            // 削除日数を強制的に0にする
            $this->groupDto->setDel_span(0);
        }

        // パラメータチェック
        if($this->section_open_flg > 1 || $this->user_open_flg > 1 || $this->leader_open_flg > 1) {
            return "error";
        }
        if (!is_numeric($this->groupDto->getId())) {
            return "error";
        }

        // 現在の設定容量
        $this->DtoGroups->setId($this->groupDto->id);
        $this->MdbGroups->getInfo($this->DtoGroups);

        //if($this->group_edit_flg) { // hiddenタグ書き換え対策のため、常にチェックを行う
            // convert
            $this->GroupLogic->convertGroup($this->groupDto);
            $this->groupDto->setDel_span(mb_convert_kana($this->groupDto->getDel_span(),"n"));  //チェック前に行なうか、全角を許容してチェック後にconvert

            // 入力チェック
            if($this->groupDto->name == "") {
                $errorList->add("Admin_Config_Share_Edit_Done::error", "グループ名を入力してください。");
                return 'input';
            }
            if($this->groupDto->folder_name == "") {
                $errorList->add("Admin_Config_Share_Edit_Done::error", "共有フォルダ名を入力してください。");
                return 'input';
            }
            if(strlen($this->groupDto->name) > 150) {
                $errorList->add("Admin_Config_Share_Edit_Done::error", "グループ名を全角50文字以内で入力してください。");
                return 'input';
            }
            if(strlen($this->groupDto->folder_name) > 150) {
                $errorList->add("Admin_Config_Share_Edit_Done::error", "共有フォルダ名を全角50文字以内で入力してください。");
                return 'input';
            }
            if($this->groupDto->del_span == "") {
                $errorList->add("Admin_Config_Share_Edit_Done::error","定期削除の日数を入力してください。");
                return 'input';
            }
            if(!preg_match("/^[0-9]+$/", $this->groupDto->del_span)) {  //先にconvertすれば、/^[0-9|０-９]+$/の様な全角の許容は、しない様に出来る
                $errorList->add("Admin_Config_Share_Edit_Done::error","定期削除の日数は0日以上の半角数字を入力してください。");
                return 'input';
            }
            if($this->groupDto->del_span < 0) {
                $errorList->add("Admin_Config_Share_Edit_Done::error","定期削除の日数は0日以上を入力してください。");
                return 'input';
            }
            if($this->groupDto->del_span > 99999) {
                $errorList->add("Admin_Config_Share_Edit_Done::error","定期削除の日数は99999日以下を入力してください。");
                return 'input';
            }
        //}

        //レベルによって編集可能かチェックする
        if(!$this->AdminUserLogic->checkAdminGroup($this->DtoAdminUser, $this->DtoGroups)) {
            $errorList->add("Admin_Config_Share_Edit_Done::error", "権限がありません。再度、一覧からグループを選択してください。");
            return "error";
        }

        // 割当て機能の場合は容量の確認
        if(!defined('SHARE_SPACE_FLG') || SHARE_SPACE_FLG == 0) {
            $this->use_capacity = $this->MdbGroups->getUseCapacity();
            if(!$this->group_edit_flg) {
                $this->groupDto->setCapacity( round($this->groupDto->capacity/1024/1024,2) );
            }
            $this->groupDto->setCapacity(mb_convert_kana($this->groupDto->getCapacity(),"n"));
            $this->groupDto->setCapacity(preg_replace("/．/",".",$this->groupDto->capacity));

            //容量必須入力チェック
            if($this->groupDto->capacity == '') {
                $errorList->add("Admin_Config_Share_Edit_Done::error", "容量を入力してください。");
                return 'input';
            }
            //容量入力数値チェック
            if (!is_numeric($this->groupDto->capacity)) {
                $errorList->add("Admin_Config_Share_Edit_Done::error", "容量は、数字のみで入力してください。");
                return 'input';
            }
            //容量入力値チェック
            if($this->groupDto->capacity < 0.01) {
                $errorList->add("Admin_Config_Share_Edit_Done::error", "容量は、0.01MB以上を指定してください。");
                return 'input';
            }
            $this->groupDto->setCapacity(round($this->groupDto->capacity*1024*1024));
            $this->limit_capacity = SHARE_MAX_CAPACITY*1024*1024*1024 - $this->use_capacity;
            //容量制限チェック
            if (($this->limit_capacity - $this->groupDto->capacity + $this->DtoGroups->capacity)<0) {
                // 上限に達しているので、追加出来ない
                $errorList->add("Admin_Config_Share_Edit_Done::error", "容量が、制限値を越えています。");
                $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                return "input";
            }

            //現在の使用容量チェック
            $use_capacity =0;
            $use_capacity = $this->MdbUpload->getUseFolderCapacity($this->groupDto->folder_id);
            if ($use_capacity > $this->groupDto->capacity) {
                $errorList->add("Admin_Config_Share_Edit_Done::error", "容量は使用容量".StrChange::CapacityUnit($use_capacity)."より多く設定してください。");
                $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                return "input";
            }
        }

        if (!$this->groupDto->getId() || !$this->groupDto->getFolder_id()) {
            return "error";
        }

        //readonly_flg入力チェック
        if (!$this->GroupLogic->checkValue($this->groupDto,$errorList)) {
            return "error";
        }


        //ファイルロック機能チェック
        if(FILE_LOCK_OPTION_FLG) {
            if (!array_key_exists($this->groupDto->getLock_flg(), $this->getLockOptions())) return "error";
            if (!array_key_exists($this->groupDto->getLock_dl_flg(), $this->getLockDlOptions())) return "error";

            $this->groupDto->setCancel_span(mb_convert_kana($this->groupDto->getCancel_span(),"n"));

            //解除日数
            if(!is_numeric($this->groupDto->getCancel_span())) {
                $errorList->add("Admin_Config_Share_Edit_Done::error", "ロック解除日数は数値で入力してください。");
                $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                return "input";
            }
            if(!preg_match("/^[0-9]+$/",$this->groupDto->getCancel_span())){
                $errorList->add("Admin_Config_Share_Edit_Done::error", "ロック解除日数は整数で入力してください。");
                $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                return "input";
            }
            //!preg_match("/^[0-9]+$/",$入力値)をみれば、!is_numeric()は不要 http://openlab.dino.co.jp/2007/11/23/121351149.html

            //"定期削除の日数を入力してください。"の条件。!is_numeric()で引っかかるので、不要
            /*
            if($this->groupDto->getCancel_span() == "") {
                $errorList->add("Admin_Config_Share_Edit_Done::error","ロック解除日数を入力してください。");
                return 'input';
            }
            */
            if($this->groupDto->getCancel_span() < 0) {
                $errorList->add("Admin_Config_Share_Edit_Done::error","ロック解除日数は0日以上を入力してください。");
                $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                return 'input';
            }
            if($this->groupDto->getCancel_span() > 99999) {
                $errorList->add("Admin_Config_Share_Edit_Done::error","ロック解除日数は99999日以下を入力してください。");
                $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                return 'input';
            }
        } else {
            $this->DtoGroups->setCancel_span('0');
        }

        //有効期限チェック
        if(SHARE_FOLDER_TERM_REQUIRED_FLG == '1') {
            if($this->groupDto->getTerm_flg() != '1') {
                $errorList->add("Admin_Config_Share_Edit_Done::error", "有効期限設定のチェックを必ず行ってください。");
                $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                return "input";
            }
        }
        if($this->groupDto->getTerm_flg() == '1') {
            $arr = $this->getTerm_type_options();
            //日付チェック
            if (!preg_match("|\d{4}-\d{1,2}-\d{1,2}|", $this->groupDto->getStart_dt())) {
                $errorList->add("Admin_Config_Share_Edit_Done::error", "有効期限の開始日を正しく入力してください。");
                $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                return "input";
            }
            if(!checkdate($this->groupDto->getStart_month(), $this->groupDto->getStart_day(), $this->groupDto->getStart_year())) {
                $errorList->add("Admin_Config_Share_Edit_Done::error", "有効期限の開始日を正しく入力してください。");
                $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                return "input";
            }
            //終了日
            if (!preg_match("|\d{4}-\d{1,2}-\d{1,2}|", $this->groupDto->getEnd_dt())) {
                $errorList->add("Admin_Config_Share_Edit_Done::error", "有効期限の終了日を正しく入力してください。");
                $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                return "input";
            }
            if(!checkdate($this->groupDto->getEnd_month(), $this->groupDto->getEnd_day(), $this->groupDto->getEnd_year())) {
                $errorList->add("Admin_Config_Share_Edit_Done::error", "有効期限の終了日を正しく入力してください。");
                $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                return "input";
            }
            $arr = $this->getTerm_type_options();
            if(!array_key_exists($this->groupDto->getTerm_type_id(), $arr)) {
                $errorList->add("Admin_Config_Share_Edit_Done::error", "有効期限切れのフォルダ操作を正しく選択してください。");
                $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                return "input";
            }
        } else {
            $this->groupDto->setTerm_type_id(0);
            $this->groupDto->setStart_year("");
            $this->groupDto->setStart_month("");
            $this->groupDto->setStart_day("");
            $this->groupDto->setEnd_year("");
            $this->groupDto->setEnd_month("");
            $this->groupDto->setEnd_day("");
        }

        // API機能の利用
        // Google SSO認証のみのときは使えない
        if(API_FLG && (LOGIN_FLG || (!LOGIN_FLG && !SSO_FLG))) {
            if($this->groupDto->getApi_flg()) {
                if(!array_key_exists($this->groupDto->getApi_flg(), $this->getApi_Flg_Options())) {
                    $errorList->add("Admin_Config_Share_Edit_Done::error", API_CLIENT_NAME."機能の利用を正しく選択してください。");
                    $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                    return "input";
                }
            }
        }

        //配布設定
        if($this->groupDto->getSend_flg() > 1 || !is_numeric($this->groupDto->getSend_flg())) {
            $errorList->add("Admin_Config_Share_Edit_Done::error", "配布設定を正しく選択してください。");
            $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
            return "input";
        }

        // ログ共有設定
        if ($this->groupDto->getLog_share_flg() > 1 || !is_numeric($this->groupDto->getLog_share_flg())) {
            $errorList->add("Admin_Config_Share_Edit_Done::error", "履歴共有設定を正しく選択してください。");
            $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
            return "input";
        }

        // メアド公開設定
        if ($this->groupDto->getMail_show_flg() > 1 || !is_numeric($this->groupDto->getMail_show_flg())) {
            $errorList->add("Admin_Config_Share_Edit_Done::error", "メールアドレスの公開設定を正しく選択してください。");
            $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
            return "input";
        }

        // サブ管理者編集
        if($this->session->getParameter("login_admin_level") >= 5) {
            if (!in_array($this->groupDto->getAdmin_edit_flg(),array('0','1'),true)) {
                $errorList->add("Admin_Config_Share_Edit_Done::error", "サブ管理者の編集権限設定を正しく選択してください。");
                $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                return "input";
            }
        } else {
            //システム管理者以外は許可に強制設定
            $this->groupDto->setAdmin_edit_flg($this->session->getParameter("log_admin_edit_flg"));
        }

        if(SHARE_ADMIN_MUST_FLG==1) {
            if ($this->group_edit_flg) {
                if (!$this->session->getParameter("old_leaders")) {
                    // 管理者ユーザは必須入力
                    $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                    $errorList->add("Admin_Config_Share_Edit_Done::error", "権限設定画面で、グループ管理者を選択してください。");
                    return "input";
                }
            } else if (!$this->groupDto->getLeaders()) {
                // 管理者ユーザは必須入力
                $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                $errorList->add("Admin_Config_Share_Edit_Done::error", "権限設定画面で、グループ管理者を選択してください。");
                return "input_user";

            }
        }

        //グループ管理者によるユーザ管理
        if(!in_array($this->groupDto->getGroup_admin_operate_flg(),array('0','1'),true)) {
            $errorList->add("Admin_Config_Share_Edit_Done::error", "グループ管理者によるユーザ管理操作の設定を正しく選択してください。");
            $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
            return "input_user";
        }
        // グループ編集画面のみ遷移で変更した場合、グループ管理者によるユーザ管理をセット
        if (!$this->leader_open_flg && !$this->back_flg) {
            $this->groupDto->setGroup_admin_operate_flg($this->DtoGroups->getGroup_admin_operate_flg());
        }

        // folderの名前を変更
        $this->DtoFolder->setParent_id('');
        $this->DtoFolder->setFolder_id($this->groupDto->folder_id);
        $this->DtoFolder->setFolder_name($this->groupDto->folder_name);
        $this->DtoFolder->setFolder_type(1);  // folder_type 1 が共有フォルダ
        $this->DtoFolder->setUser_id(0);      // 共有フォルダは0を入れる

        // 共有フォルダ名チェック
        if(OVERWRITE_FLG) {
            if($this->MdbFolder->getFolderNameTF($this->DtoFolder)) {
                $errorList->add("Admin_Config_Share_Edit_Done::error", "同名の共有フォルダ名があるため、別の共有フォルダ名にしてください。");
                $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                return "input";
            }
        }
        $this->MdbFolder->updateFolder($this->DtoFolder);

        // groupを変更する
        $this->groupDto->setAccess_flg($this->DtoGroups->getAccess_flg());
        $this->groupDto->setAccess_share_admin_flg($this->DtoGroups->getAccess_share_admin_flg());
        $this->MdbGroups->edit($this->groupDto);

        // 管理者登録
        // 現在の管理者情報を取得
        // 編集画面から直接変更する場合は、権限を変更しない
        $topFolderId = $this->groupDto->folder_id; // updateGroupAuthするとfolderIdが変わるので先に取得する
        if(!$this->group_edit_flg) {
            $this->DtoGroupsadminusers->setGroup_id($this->groupDto->getId());
            $this->updateGroupAuth($this->groupDto, $this->DtoSectionusers, $this->DtoGroupsadminusers);
        }
        $this->SuperiorLogic->autoRevertExternalizationForFolder($topFolderId);
        // Log
        $history_id = 115; // 114:各種設定＞グループ共有機能＞グループ編集

        $member_name = "";
        $member = array();
        $member = $this->groupDto->getUsers();
        if ($member) {
            $member_name .= "　[ユーザ]\n";
            for($i=0;$i<count($member);$i++) {
                $this->DtoUser->setUser_id($member[$i]);
                $this->MdbUser->getUserInfo($this->DtoUser);
                $member_name .= "　".$this->DtoUser->getAccount()."（".$this->DtoUser->getUser_name() ."）\n";
            }
        }
        $member = array();
        $member = $this->groupDto->getSections();
        if ($member) {
            $member_name .= "　[部　署]\n";
            for($i=0;$i<count($member);$i++) {
                $this->DtoSection->setSection_id($member[$i]);
                $this->MdbSection->getInfo($this->DtoSection);
                $member_name .= "　".$this->DtoSection->getSection_name()."\n";
            }
        }
        $memo = "";
        if ( $this->groupDto->getName() != $this->session->getParameter("log_g_name") ){
            $g_bef = $this->session->getParameter("log_g_name");
            $g_aft = $this->groupDto->getName();
            $memo .= "グループ名：".$g_bef."→".$g_aft."\n";
        } else {
            $memo .= "グループ名：".$this->session->getParameter("log_g_name")."\n";
        }
        if ( $this->groupDto->getFolder_name() != $this->session->getParameter("log_f_name") ){
            $f_bef = $this->session->getParameter("log_f_name");
            $f_aft = $this->groupDto->getFolder_name();
            $memo .= "共有フォルダ名：".$f_bef."→".$f_aft."\n";
        } else {
            $memo .= "共有フォルダ名：".$this->session->getParameter("log_f_name")."\n";
        }
        if ($this->groupDto->getDel_span() != $this->session->getParameter("log_del_span")) {
            $d_bef = $this->session->getParameter("log_del_span");
            $d_aft = $this->groupDto->getDel_span();
            $memo .= "定期削除の日数：".$d_bef."→".$d_aft."\n";
        } else {
            $memo .= "定期削除の日数：".$this->session->getParameter("log_del_span")."\n";
        }

        //割当て容量機能の場合はログを記録
        if(!defined('SHARE_SPACE_FLG') || SHARE_SPACE_FLG == 0) {
            if ( $this->groupDto->getCapacity() != $this->session->getParameter("log_capa") ){
                $c_bef = $this->session->getParameter("log_capa");
                $c_bef = StrChange::CapacityUnit($c_bef);
                $c_aft = $this->groupDto->getCapacity();
                $c_aft = StrChange::CapacityUnit($c_aft);
                $memo .= "容量：".$c_bef."→".$c_aft."\n";
            } else {
                $c_bef = $this->session->getParameter("log_capa");
                $c_bef = StrChange::CapacityUnit($c_bef);
                $memo .= "容量：".$c_bef."\n";
            }
        }
        if(!defined('FILE_LOCK_OPTION_FLG') || FILE_LOCK_OPTION_FLG) {
            if($this->groupDto->lock_flg != $this->session->getParameter('log_lock_flg')) {
                $arry = array("無効","有効");
                $memo .= "ファイルロック機能：" .$arry[$this->session->getParameter('log_lock_flg')]."→".$arry[$this->groupDto->lock_flg]."\n";
            }
            if($this->groupDto->lock_dl_flg != $this->session->getParameter('log_lock_dl_flg')) {
                $arry = array("許可","禁止");
                $memo .= "ロック中のダウンロード：" . $arry[$this->session->getParameter('log_lock_dl_flg')]."→".$arry[$this->groupDto->lock_dl_flg]."\n";
            }
            if($this->groupDto->cancel_span != $this->session->getParameter('log_cancel_flg')) {
                $memo .= "ロック解除日数：";
                $memo.= ($this->session->getParameter('log_cancel_span') === '0') ? "解除しない" : $this->session->getParameter('log_cancel_span')."日後に解除";
                $memo .= "→";
                $memo .= ($this->groupDto->cancel_span === '0') ? "解除しない" : $this->groupDto->cancel_span."日後に解除";
                $memo .= "\n";
            }
        }
        //有効期限
        if($this->session->getParameter("log_term_flg") != $this->groupDto->getTerm_flg()) {
            $memo .= "有効期限の設定：" . ($this->session->getParameter("log_term_flg") ? "ON" : "OFF")."→".($this->groupDto->getTerm_flg() ? "ON":"OFF")."\n";
        }
        if($this->session->getParameter("log_start_dt") != $this->groupDto->getStart_dt()) {
            $memo .= "有効期限開始日：" . ($this->session->getParameter("log_start_dt") ? $this->session->getParameter("log_start_dt") : "設定なし")."→".($this->groupDto->getStart_dt() ? $this->groupDto->getStart_dt() : "設定なし")."\n";
        }
        if($this->session->getParameter("log_end_dt") != $this->groupDto->getEnd_dt()) {
            $memo .= "有効期限終了日：" . ($this->session->getParameter("log_end_dt") ? $this->session->getParameter("log_end_dt") : "設定なし")."→".($this->groupDto->getEnd_dt() ? $this->groupDto->getEnd_dt() : "設定なし")."\n";
        }
        if($this->session->getParameter("log_term_type_id") != $this->groupDto->getTerm_type_id()) {
            $arr = $this->getTerm_type_options();
            $memo .= "有効期限切れのフォルダ操作 :"."\n";
            $memo .= $arr[$this->session->getParameter("log_term_type_id")]."→".$arr[$this->groupDto->getTerm_type_id()]."\n";
        }

        // Google SSO認証のみのときは使えない
        if (LOGIN_FLG && (!LOGIN_FLG && !SSO_FLG)) {
            //API機能の利用
            if($this->session->getParameter('log_api_flg') != $this->groupDto->getApi_flg()) {
                $arr = $this->getApi_Flg_Options();
                $memo .= API_CLIENT_NAME."機能の利用：".$arr[$this->session->getParameter('log_api_flg')]. "→" .$arr[$this->groupDto->getApi_flg()]."\n";
            }
        }

        //配布設定
        if($this->session->getParameter('log_send_flg') != $this->groupDto->getSend_flg()) {
            $arr = array("許可","禁止");
            $memo .= "配布設定：".$arr[$this->session->getParameter('log_send_flg')]. "→" .$arr[$this->groupDto->getSend_flg()]."\n";
        }
        // ログ共有
        if($this->session->getParameter('log_log_share_flg') != $this->groupDto->getLog_share_flg()) {
            $arr = array("共有しない","共有する");
            $memo .= "履歴共有設定：".$arr[$this->session->getParameter('log_log_share_flg')]. "→" .$arr[$this->groupDto->getLog_share_flg()]."\n";
        }
        // メアド公開
        if($this->session->getParameter('log_mail_show_flg') != $this->groupDto->getMail_show_flg()) {
            $arr = array("公開しない","公開する");
            $memo .= "メールアドレスの公開設定：".$arr[$this->session->getParameter('log_mail_show_flg')]. "→" .$arr[$this->groupDto->getMail_show_flg()]."\n";
        }
        //サブ管理者による編集設定 @システム管理者のみ
        if($this->session->getParameter("login_admin_level") >= 5) {
            if($this->session->getParameter('log_admin_edit_flg') != $this->groupDto->getAdmin_edit_flg()) {
                $arr = array("許可","禁止");
                $memo .= "サブ管理者の編集権限設定：".$arr[$this->session->getParameter('log_admin_edit_flg')]. "→" .$arr[$this->groupDto->getAdmin_edit_flg()]."\n";
            }
        }
        //グループ管理者によるユーザ管理設定
        if($this->session->getParameter('log_group_admin_operate_flg') != $this->groupDto->getGroup_admin_operate_flg()) {
            $arr = array("許可","禁止");
            $memo .= "グループ管理者によるユーザ管理設定：".$arr[$this->session->getParameter('log_group_admin_operate_flg')]. "→" .$arr[$this->groupDto->getGroup_admin_operate_flg()]."\n";
        }
        $leader = "";
        if ($this->groupDto->getLeader_id()) {
            $info = "";
            $info = $this->MdbUser->getInfo($this->session->getParameter("old_leader_id"));
            if ($info) {
                $leader = "　".$info["user_name"]."（".$info["mailaddr"]."）";
            }
            $info = "";
            $info = $this->MdbUser->getInfo($this->groupDto->getLeader_id());
            if ($info) {
                $leader .= "→".$info["user_name"]."（".$info["mailaddr"]."）";
            }
        }
        if ($leader) {
            $memo .= "グループ管理者"."\n".$leader."\n";
        }

        if($member_name) {
            $memo .= "メンバー"."\n".$member_name;
        }

        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        //sessionを削除
        $this->session->removeParameter("id");
        $this->session->removeParameter("folder_id");
        $this->session->removeParameter("old_leader_id");
        $this->session->removeParameter("log_g_name");
        $this->session->removeParameter("log_f_name");
        $this->session->removeParameter("log_capa");
        $this->session->removeParameter("log_del_span");
        $this->session->removeParameter("log_lock_flg");
        $this->session->removeParameter("log_lock_dl_flg");
        $this->session->removeParameter("log_cancel_span");
        $this->session->removeParameter("log_term_flg");
        $this->session->removeParameter("log_term_type_id");
        $this->session->removeParameter("log_send_flg");
        $this->session->removeParameter("log_log_share_flg");
        $this->session->removeParameter("log_mail_show_flg");
        $this->session->removeParameter("log_start_dt");
        $this->session->removeParameter("log_end_dt");
        $this->session->removeParameter("users");
        $this->session->removeParameter("leaders");
        $this->session->removeParameter("sections");
        $this->session->removeParameter("member_edit");
        $this->session->removeParameter("readonly_flgs");
        $this->session->removeParameter("section_readonly_flgs");
        $this->session->removeParameter("alledit_flg");
        $this->session->removeParameter("group_admin_operate_flg");
        $this->session->removeParameter("leader_open_flg");
        $this->session->removeParameter("user_open_flg");
        $this->session->removeParameter("section_open_flg");
        $this->session->removeParameter("groupDto");
        $this->session->removeParameter("sub_folder_id"); //サブフォルダ権限設定で利用したfolder_id
        $this->session->removeParameter("term_flg");
        $this->session->removeParameter("old_leaders");

        // Google SSO認証のみのときは使えない
        if (LOGIN_FLG || (!LOGIN_FLG && !SSO_FLG)) {
            $this->session->removeParameter("log_api_flg");
            $this->session->removeParameter("api_flg");
        }

        return "success";
    }


    // 単純なuser_idの配列にする
    // 部署(section)に所属するユーザも分解して入れる
    function changeUserArray($list, $sections=null) {
        $id_list = array();
        foreach($list as $key => $val) {
            if ($val["user_id"] && !$sections) {
                $id_list[] = $val["user_id"];
            } else {
                if ($val["section_id"] || $sections) {
                    if ($sections) {
                        $section_id = $val;
                    } else {
                        $section_id = $val["section_id"];
                    }
                    $section_list = $this->MdbSectionusers->getList($section_id);
                    if ($section_list) {
                        foreach($section_list as $sec_key => $sec_val) {
                            $id_list[] = $sec_val["user_id"];
                        }
                    }
                }
            }
        }
        return $id_list;
    }

    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoGroups($DtoGroups){
        $this->DtoGroups = $DtoGroups;
    }
    function setMdbGroups($MdbGroups) {
        $this->MdbGroups = $MdbGroups;
    }
    function setMdbGroups_users($MdbGroups_users) {
        $this->MdbGroups_users = $MdbGroups_users;
    }
    function setGroupDto($groupDto)
    {
        $this->groupDto = $groupDto;
    }
    function setDtoFolder($DtoFolder)
    {
        $this->DtoFolder = $DtoFolder;
    }
    function setMdbFolder($MdbFolder)
    {
        $this->MdbFolder = $MdbFolder;
    }
    function setDtoUser($DtoUser)
    {
        $this->DtoUser = $DtoUser;
    }
    function setMdbUser($MdbUser)
    {
        $this->MdbUser = $MdbUser;
    }
    function setDtoLog ( &$DtoLog ) {
        $this->DtoLog =& $DtoLog;
    }
    function setMdbLog ( &$MdbLog ) {
        $this->MdbLog =& $MdbLog;
    }
    function setMdbUpload ( &$MdbUpload ) {
        $this->MdbUpload =& $MdbUpload;
    }
    function setDtoSection($DtoSection) {
        $this->DtoSection = $DtoSection;
    }
    function setMdbSection($MdbSection) {
        $this->MdbSection = $MdbSection;
    }
    function setDtoSectionusers($DtoSectionusers) {
        $this->DtoSectionusers = $DtoSectionusers;
    }
    function setMdbSectionusers($MdbSectionusers) {
        $this->MdbSectionusers = $MdbSectionusers;
    }
    function setDtoGroupsadminusers($DtoGroupsadminusers) {
        $this->DtoGroupsadminusers = $DtoGroupsadminusers;
    }
    function setMdbGroupsadminusers($MdbGroupsadminusers) {
        $this->MdbGroupsadminusers = $MdbGroupsadminusers;
    }
    function setGroupLogic($GroupLogic) {
        $this->GroupLogic = $GroupLogic;
    }
    function setSectionLogic($SectionLogic) {
        $this->SectionLogic = $SectionLogic;
    }
    function setGroupsusersLogic($GroupsusersLogic) {
        $this->GroupsusersLogic = $GroupsusersLogic;
    }
    function setAlledit_flg($alledit_flg) {
        $this->alledit_flg = $alledit_flg;
    }
    function setSection_open_flg($section_open_flg) {
        $this->section_open_flg = $section_open_flg;
    }
    function setLeader_open_flg($leader_open_flg) {
        $this->leader_open_flg = $leader_open_flg;
    }
    function setUser_open_flg($user_open_flg) {
        $this->user_open_flg = $user_open_flg;
    }
    function setMdbMchoice($MdbMchoice) {
        $this->MdbMchoice = $MdbMchoice;
    }
    function setDtoAdminUser($DtoAdminUser) {
        $this->DtoAdminUser = $DtoAdminUser;
    }
    function setAdminUserLogic($AdminUserLogic) {
        $this->AdminUserLogic = $AdminUserLogic;
    }
    function setMdbAdminuser($MdbAdminuser) {
        $this->MdbAdminuser = $MdbAdminuser;
    }
    function setAdminUserDto($AdminUserDto) {
        $this->AdminUserDto = $AdminUserDto;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setGroup_edit_flg($group_edit_flg) {
        $this->group_edit_flg = $group_edit_flg;
    }
    function setBack_flg($back_flg) {
        $this->back_flg = $back_flg;
    }
    function setCommonList($CommonList) {
        $this->CommonList =& $CommonList;
    }
    function setSuperiorLogic($SuperiorLogic)
    {
        $this->SuperiorLogic = &$SuperiorLogic;
    }

    // getter
    function getGroupList(){
        return $this->group_list;
    }
    function getShareMaxCapacity()
    {
        return SHARE_MAX_CAPACITY."G";
    }
    function getUseCapacity()
    {
        $this->use_capacity = $this->MdbGroups->getUseCapacity();
        return StrChange::CapacityUnit($this->use_capacity);
    }
    function getGroupCount()
    {
        return $this->group_count;
    }
    function getLimitCapacity()
    {
        $this->limit_capacity = SHARE_MAX_CAPACITY*1024*1024*1024 - $this->use_capacity;
        return StrChange::CapacityUnit($this->limit_capacity);
    }
    function getChangeCapacity()
    {
        return StrChange::CapacityUnit($this->limit_capacity+$this->DtoGroups->capacity);
    }
    function getName()
    {
        return $this->groupDto->getName();
    }
    function getFolder_name()
    {
        return $this->groupDto->getFolder_name();
    }
    function getCapacity()
    {
        return $this->groupDto->getCapacity();
    }
    function getSend_flg()
    {
      return $this->groupDto->getSend_flg();
    }
    function getDel_span()
    {
        return $this->groupDto->getDel_span();
    }
    function getFolder_id()
    {
        return $this->groupDto->getFolder_id();
        //return $this->DtoGroups->folder_id;
    }
    function getId()
    {
        return $this->groupDto->getId();
        //return $this->DtoGroups->id;
    }
    function getUser_array()
    {
        $user_array = array();
        $users = "";
        $users = $this->groupDto->users;
        $user_read_flg = "";
        $user_read_flg = $this->groupDto->readonly_flgs;
        if ($users) {
            foreach ($users as $val) {
                // 管理者とかぶってたら飛ばす
                if ($this->groupDto->getLeaders()) {
                    $rs = false;
                    $rs = array_search($val, $this->groupDto->getLeaders());
                    if ($rs !== false && !is_null($rs)) {
                        continue;
                    }
                }

                $this->DtoUser->setUser_id($val);
                $this->MdbUser->getUserInfo($this->DtoUser);
                $ary = array();
                $ary = array(
                             "user_id"   => $this->DtoUser->user_id,
                             "user_name" => $this->DtoUser->user_name,
                             "mailaddr"  => $this->DtoUser->mailaddr
                             );
                if ($user_read_flg) {
                    if (isset($user_read_flg[$val]["readonly_flg"])) {
                        $ary["readonly_flg"] = $user_read_flg[$val]["readonly_flg"];
                    } else {
                        $ary["readonly_flg"] = 0;
                    }
                } else {
                    $ary["readonly_flg"] = 0;
                }
                $user_array[] = $ary;
            }
        }
        return $user_array;
    }
    function getSection_array()
    {
        $section_array = array();
        $sections = "";
        $sections = $this->groupDto->sections;
        $section_read_flg = "";
        $section_read_flg = $this->groupDto->section_readonly_flgs;
        if ($sections) {
            foreach ($sections as $val) {
                $this->DtoSection->setSection_id($val);
                $this->MdbSection->getInfo($this->DtoSection);
                $ary = array();
                $ary = array("section_id"   => $this->DtoSection->section_id,
                             "section_name" => $this->DtoSection->section_name
                             );
                if ($section_read_flg) {
                    if (isset($section_read_flg[$val]["section_readonly_flg"])) {
                        $ary["readonly_flg"] = $section_read_flg[$val]["section_readonly_flg"];
                    } else {
                        $ary["readonly_flg"] = 0;
                    }
                } else {
                    $ary["readonly_flg"] = 0;
                }
                $section_array[] = $ary;
            }
        }
        return $section_array;
    }
    function getLeader_id() {
        return $this->groupDto->getLeader_id();
    }
    function getLeader_name()
    {
        if ($this->groupDto->getLeader_id()) {
            $info = "";
            $info = $this->MdbUser->getInfo($this->groupDto->getLeader_id());
            if ($info) {
                return $info["user_name"];
            }
        }
    }
    function getLeader_mail()
    {
        if ($this->groupDto->getLeader_id()) {
            $info = "";
            $info = $this->MdbUser->getInfo($this->groupDto->getLeader_id());
            if ($info) {
                return $info["mailaddr"];
            }
        }
    }
    function getLockOptions() {
        return GlobalArray::getFile_Lock_Options();
    }
    function getLockDlOptions() {
        return GlobalArray::getFile_Lock_Download_Options();
    }
    function getLockFlg() {
        return $this->groupDto->getLock_flg();
    }
    function getLockDlFlg() {
        return $this->groupDto->getLock_dl_flg();
    }
    function getCancel_span() {
        if($this->groupDto->getCancel_span() != "") {
            return ($this->groupDto->getCancel_span() != '0') ? $this->groupDto->getCancel_span() : '0';
        }
        return 0;
    }

    function getTerm_flg()
    {
        if (SHARE_FOLDER_TERM_REQUIRED_FLG) {
            return SHARE_FOLDER_TERM_REQUIRED_FLG;
        }
        return $this->groupDto->getTerm_flg();
    }

    function getStart_year() {
        return $this->groupDto->getStart_year();
    }
    function getStart_month() {
        return $this->groupDto->getStart_month();
    }
    function getStart_day() {
        return $this->groupDto->getStart_day();
    }
    function getEnd_year() {
        return $this->groupDto->getEnd_year();
    }
    function getEnd_month() {
        return $this->groupDto->getEnd_month();
    }
    function getEnd_day() {
        return $this->groupDto->getEnd_day();
    }
    function getTerm_type_options() {
        return $this->MdbMchoice->getOptions("share_term_type_id");
    }
    function getTerm_type_id() {
        return $this->groupDto->getTerm_type_id();
    }
    function getAdmin_edit_flg() {
        return $this->groupDto->getAdmin_edit_flg();
    }
    function getGroup_admin_operate_flg() {
        return $this->groupDto->getGroup_admin_operate_flg();
    }

    function getAdmin_id() {
        return $this->groupDto->getAdmin_id();
    }
    function getAdmin_account() {
        // 管理者情報取得
        $this->DtoAdminUser->setAdmin_id($this->groupDto->getAdmin_id());
        $this->MdbAdminuser->getAdminuserInfo($this->DtoAdminUser);
        return $this->DtoAdminUser->getAccount();
    }
    function getAdmin_list()
    {
        // 管理者リストの取得
        $list = $this->AdminUserLogic->getSelectList($this->MdbAdminuser);
        return $list;
    }
    function getLog_share_flg() {
        return $this->groupDto->getLog_share_flg();
    }
    function getMail_show_flg() {
        return $this->groupDto->getMail_show_flg();
    }
    function getApi_Flg_Options() {
        return GlobalArray::getApi_Flg_Options();
    }
    function getApi_flg() {
        return $this->groupDto->getApi_flg();
    }
    function getAlledit_flg() {
        if (is_null($this->alledit_flg)) {
            return  $this->session->getParameter("alledit_flg");
        }
        return $this->alledit_flg;
    }
    function getAuth_type_notice() {
        $str = '';
        $usage_auth_types = $this->CommonList->getUsageAuthTypes();
        if (isset($usage_auth_types[1]) OR isset($usage_auth_types[2])) {
            unset($usage_auth_types[0]);
            $str = implode('、', $usage_auth_types);
            $str = '<br />※' . $str . 'のユーザには適用されません。';
        }
        return $str;
    }

    function updateGroupAuth($groupDto, $DtoSectionUsers, $DtoGroupsadminusers) {
        $this->MdbGroupsadminusers->getList($DtoGroupsadminusers);
        $list = $DtoGroupsadminusers->getGroupsAdminList();
        $before_leaders = array();
        if ($list) {
            $before_leaders = $this->changeUserArray($list);
        }

        // 新規管理者
        if ($groupDto->getLeaders()) {
            if ($before_leaders) {
                $new_leaders = array_diff($groupDto->getLeaders(), $before_leaders);
            } else {
                $new_leaders = $groupDto->getLeaders();
            }
            // 追加
            foreach ($new_leaders as $val) {
                // DBに保存
                $DtoGroupsadminusers->setSection_id('0');
                $DtoGroupsadminusers->setUser_id($val);
                $this->MdbGroupsadminusers->insert($DtoGroupsadminusers);
            }
        }
        // 削除される管理者
        $del_leaders = null;
        if ($groupDto->getLeaders() && $before_leaders) {
            $del_leaders = array_diff($before_leaders, $groupDto->getLeaders());
        } elseif ($before_leaders) {
            $del_leaders = $before_leaders;
        }
        if ($del_leaders) {
            foreach ($del_leaders as $val) {
                // ロックファイル削除
                $leader_id = $val;
                $this->GroupLogic->updateLeaderFileLockCancel($leader_id, $groupDto->folder_id, $this->MdbUpload, $this->MdbFolder);
                // 管理者を削除
                $where = array(
                    "group_id='" . Db_Common::escape_string($groupDto->getId()) . "'",
                    "user_id=" . $val
                );
                $this->MdbGroupsadminusers->deleteWhere($where);
            }
        }

        // 管理者とメンバーでidの重複があれば、メンバーから削除
        if ($groupDto->getLeaders() && $groupDto->getUsers()) {
            $nonDuplicatedUserIds = array_diff($groupDto->getUsers(), $groupDto->getLeaders());
            $groupDto->setUsers($nonDuplicatedUserIds);
        }

        // groups_usersに追加する（個人も部署も）
        if ($this->alledit_flg) {
            // サブフォルダまで同じ内容で更新
            $this->GroupsusersLogic->setSaveMemberAll($this->MdbGroups_users, $this->MdbSection, $this->MdbSectionusers, $this->MdbUser, $this->MdbFolder, $DtoSectionUsers, $groupDto, $this->GroupLogic, $this->SectionLogic, $this->MdbUpload, $this->MdbGroupsadminusers);
        } else {
            // 親フォルダだけ更新（サブフォルダの権限は変更しない）
            $this->GroupsusersLogic->setSaveMember($this->MdbGroups_users, $this->MdbSection, $this->MdbSectionusers, $this->MdbUser, $DtoSectionUsers, $groupDto, $this->SectionLogic, $this->MdbUpload, $this->MdbGroupsadminusers, $this->MdbFolder, $this->GroupLogic);
        }
    }

}
?>

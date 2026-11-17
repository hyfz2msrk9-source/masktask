<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] グループ共有機能 - グループ共有機能に関する設定 - グループ編集(権限設定)
 * @updater     Hiroko Yanai（2018/04/13）SAML認証(OneLogin)に対応。SSOやSAMLでも、設定は更新可能とする
 *
 * @package     WebFile
 * @version     3.15.2
 * @author
 * @copyright   KEY POINT
 * @access      public
 */
class Admin_Config_Share_Edit_User {
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

    var $MdbAdminuser;
    var $AdminUserDto;

    var $use_capacity;
    var $limit_capacity;
    var $user_open_flg;
    var $leader_open_flg;
    var $section_open_flg;
    var $leader_array;
    var $user_array;
    var $section_array;
    var $subfolder_edit_flg; // サブフォルダ設定画面からの遷移 1:true

    var $CommonList;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Share_Edit_Userが実行されました","Admin_Config_Share_Edit_User#execute");

        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        // サブフォルダ設定画面からの遷移かどうか
        $this->subfolder_edit_flg = ($this->subfolder_edit_flg == 1) ? 1 : 0;

        /* 2018/06/05 @yanai サブフォルダ設定でセッションfolder_idを上書きしないよう修正した為、
           folder_idの戻しは不要
        // サブフォルダ設定から戻ったらfolder_idを戻す
        if($this->session->getParameter("top_folder_id") && $this->subfolder_edit_flg === 1) {
            $this->DtoFolder->setFolder_id($this->session->getParameter("top_folder_id"));
            $this->groupDto->setFolder_id($this->session->getParameter("top_folder_id"));
        }
        */

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

        //レベルによって編集可能かチェックする
        if(!$this->AdminUserLogic->checkAdminGroup($this->DtoAdminUser, $this->DtoGroups)) {
            $errorList->add("Admin_Config_Share_Edit_Done::error", "権限がありません。再度、一覧からグループを選択してください。");
            return "error";
        }

        // 割当て機能の場合は容量の確認
        if(!defined('SHARE_SPACE_FLG') || SHARE_SPACE_FLG == 0) {
            $this->use_capacity = $this->MdbGroups->getUseCapacity();
            $this->groupDto->setCapacity(mb_convert_kana($this->groupDto->capacity,"n"));
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

        //ファイルロック機能チェック
        if(FILE_LOCK_OPTION_FLG) {
            if (!array_key_exists($this->groupDto->getLock_flg(), $this->getLockOptions())) return "error";
            if (!array_key_exists($this->groupDto->getLock_dl_flg(), $this->getLockDlOptions())) return "error";

            //解除日数
            if(!is_numeric($this->groupDto->getCancel_span())) {
                $errorList->add("Admin_Config_Share_Add_Done::error", "ロック解除日数は数値で入力してください。");
                $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                return "input";
            }
            if(!preg_match("/^[0-9]+$/",$this->groupDto->getCancel_span())){
                $errorList->add("Admin_Config_Share_Add_Done::error", "ロック解除日数は整数で入力してください。");
                $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                return "input";
            }
        } else {
            $this->DtoGroups->setCancel_span('0');
        }

        //有効期限チェック
        if(SHARE_FOLDER_TERM_REQUIRED_FLG == '1') {
            if($this->groupDto->getTerm_flg() != '1') {
                $errorList->add("Admin_Config_Share_Add_Done::error", "有効期限設定のチェックを必ず行ってください。");
                $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                return "input";
            }
        }
        if( !isset($_POST["term_flg"]) ) {
            $this->groupDto->setTerm_flg(0);
        }
        if($this->groupDto->getTerm_flg() == '1') {
            $arr = $this->getTerm_type_options();
            //日付チェック
            if (!preg_match("|\d{4}-\d{1,2}-\d{1,2}|", $this->groupDto->getStart_dt())) {
                $errorList->add("Admin_Config_Share_Add_Done::error", "有効期限の開始日を正しく入力してください。");
                $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                return "input";
            }
            if(!checkdate($this->groupDto->getStart_month(), $this->groupDto->getStart_day(), $this->groupDto->getStart_year())) {
                $errorList->add("Admin_Config_Share_Add_Done::error", "有効期限の開始日を正しく入力してください。");
                $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                return "input";
            }
            //終了日
            if (!preg_match("|\d{4}-\d{1,2}-\d{1,2}|", $this->groupDto->getEnd_dt())) {
                $errorList->add("Admin_Config_Share_Add_Done::error", "有効期限の終了日を正しく入力してください。");
                $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                return "input";
            }
            if(!checkdate($this->groupDto->getEnd_month(), $this->groupDto->getEnd_day(), $this->groupDto->getEnd_year())) {
                $errorList->add("Admin_Config_Share_Add_Done::error", "有効期限の終了日を正しく入力してください。");
                $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                return "input";
            }
            $arr = $this->getTerm_type_options();
            if(!array_key_exists($this->groupDto->getTerm_type_id(), $arr)) {
                $errorList->add("Admin_Config_Share_Add_Done::error", "有効期限切れのフォルダ操作を正しく選択してください。");
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
                    $errorList->add("Admin_Config_Share_Add_Done::error", API_CLIENT_NAME."機能の利用を正しく選択してください。");
                    $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                    return "input";
                }
            }
        }

        //配布設定
        if($this->groupDto->getSend_flg() > 1 || !is_numeric($this->groupDto->getSend_flg())) {
            $errorList->add("Admin_Config_Share_Add_Done::error", "配布設定を正しく選択してください。");
            $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
            return "input";
        }

        // ログ共有設定
        if ($this->groupDto->getLog_share_flg() > 1 || !is_numeric($this->groupDto->getLog_share_flg())) {
            $errorList->add("Admin_Config_Share_Add_Done::error", "履歴共有設定を正しく選択してください。");
            $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
            return "input";
        }

        // メアド公開設定
        if ($this->groupDto->getMail_show_flg() > 1 || !is_numeric($this->groupDto->getMail_show_flg())) {
            $errorList->add("Admin_Config_Share_Add_Done::error", "メールアドレスの公開設定を正しく選択してください。");
            $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
            return "input";
        }

        // サブ管理者編集
        if($this->session->getParameter("login_admin_level") >= 5) {
            if (!in_array($this->groupDto->getAdmin_edit_flg(),array('0','1'),true)) {
                $errorList->add("Admin_Config_Share_Add_Done::error", "サブ管理者の編集権限設定を正しく選択してください。");
                $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                return "input";
            }
        } else {
            //システム管理者以外は許可に強制設定
            $this->groupDto->setAdmin_edit_flg($this->session->getParameter("log_admin_edit_flg"));
        }

        /* この画面ではチェック不要
        if(SHARE_ADMIN_MUST_FLG==1 && !$this->groupDto->getLeaders()) {
            // 管理者ユーザは必須入力
            $errorList->add("Admin_Config_Share_Add_Done::error", "グループ管理者を選択してください。");
            $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
            return "input";
        }

        //グループ管理者によるユーザ管理
        if(!in_array($this->groupDto->getGroup_admin_operate_flg(),array('0','1'),true)) {
            $errorList->add("Admin_Config_Share_Add_Done::error", "グループ管理者によるユーザ管理操作の設定を正しく選択してください。");
            $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
            return "input";
        }
        */

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

        // 未編集の場合
        if( $this->session->getParameter("member_edit") != 1) {
            // リーダーの一覧を取得
            $this->DtoGroupsadminusers->setGroup_id($this->DtoGroups->id);
            $this->MdbGroupsadminusers->getList($this->DtoGroupsadminusers);
            $this->leader_array = array();
            if ($this->DtoGroupsadminusers->getGroupsAdminList()) {
                foreach($this->DtoGroupsadminusers->getGroupsAdminList() as $key => $val) {
                    $this->DtoUser->setUser_id($val["user_id"]);
                    $this->MdbUser->getUserInfo($this->DtoUser);
                    array_push($this->leader_array, array("user_id"  =>$this->DtoUser->getUser_id(),
                                                          "user_name"=>$this->DtoUser->getUser_name(),
                                                          "mailaddr" =>$this->DtoUser->getMailaddr()));
                }
            }

            // メンバーの一覧を取得
            $where = array("user_id>0",
                           "folder_id=".$this->DtoGroups->folder_id);
            $userlist = $this->MdbGroups_users->getUserlist($this->DtoGroups->id,$where);
            $this->user_array = array();

            foreach ($userlist as $val){
                $this->DtoUser->setUser_id($val["user_id"]);
                $this->MdbUser->getUserInfo($this->DtoUser);
                array_push($this->user_array, array(
                                              "user_id"   => $this->DtoUser->user_id,
                                              "user_name" => $this->DtoUser->user_name,
                                              "mailaddr"  => $this->DtoUser->mailaddr,
                                              "readonly_flg" => $val["readonly_flg"]
                                              ));
            }

            // 部署の一覧を取得
            $where = array("section_id>0",
                           "folder_id=".$this->DtoGroups->folder_id);
            $sectionlist = $this->MdbGroups_users->getUserlist($this->DtoGroups->id,$where);
            $this->section_array = array();
            foreach ($sectionlist as $val) {
                $this->DtoSection->setSection_id($val["section_id"]);
                $this->MdbSection->getInfo($this->DtoSection);
                array_push($this->section_array,array(
                                                      "section_id" => $this->DtoSection->getSection_id(),
                                                      "section_name" => $this->DtoSection->getSection_name(),
                                                      "readonly_flg" => $val["readonly_flg"]
                                                      ));
            }
            $this->groupDto->setGroup_admin_operate_flg($this->DtoGroups->getGroup_admin_operate_flg());

        // サブフォルダ画面の場合は管理者以外をDBから 管理者をセッションから
        } elseif ($this->subfolder_edit_flg === 1) {

            // 管理者はセッションから
            $array = $this->session->getParameter("leaders");
            $this->leader_array = array();
            $readonly_flgs = $this->session->getParameter("readonly_flgs");
            if(is_array($array)) {
                foreach($array as $key => $val) {
                    $this->DtoUser->setUser_id($val);
                    $this->MdbUser->getUserInfo($this->DtoUser);
                    array_push($this->leader_array, array("user_id"  =>$this->DtoUser->getUser_id(),
                                                          "user_name"=>$this->DtoUser->getUser_name(),
                                                          "mailaddr" =>$this->DtoUser->getMailaddr()));
                }
            }

            // メンバーの一覧を取得 from db
            $where = array("user_id>0",
                           "folder_id=".$this->DtoGroups->folder_id);
            $userlist = $this->MdbGroups_users->getUserlist($this->DtoGroups->id,$where);
            $this->user_array = array();

            foreach ($userlist as $val){
                $this->DtoUser->setUser_id($val["user_id"]);
                $this->MdbUser->getUserInfo($this->DtoUser);
                array_push($this->user_array, array(
                                              "user_id"   => $this->DtoUser->user_id,
                                              "user_name" => $this->DtoUser->user_name,
                                              "mailaddr"  => $this->DtoUser->mailaddr,
                                              "readonly_flg" => $val["readonly_flg"]
                                              ));
            }

            // 部署の一覧を取得 from db
            $where = array("section_id>0",
                           "folder_id=".$this->DtoGroups->folder_id);
            $sectionlist = $this->MdbGroups_users->getUserlist($this->DtoGroups->id,$where);
            $this->section_array = array();
            foreach ($sectionlist as $val) {
                $this->DtoSection->setSection_id($val["section_id"]);
                $this->MdbSection->getInfo($this->DtoSection);
                array_push($this->section_array,array(
                                                      "section_id" => $this->DtoSection->getSection_id(),
                                                      "section_name" => $this->DtoSection->getSection_name(),
                                                      "readonly_flg" => $val["readonly_flg"]
                                                      ));
            }
            $this->groupDto->setGroup_admin_operate_flg($this->session->getParameter('group_admin_operate_flg'));


        // 編集後の場合はsessionから
        } else {
            $array = $this->session->getParameter("leaders");
            $this->leader_array = array();
            $readonly_flgs = $this->session->getParameter("readonly_flgs");
            if(is_array($array)) {
                foreach($array as $key => $val) {
                    $this->DtoUser->setUser_id($val);
                    $this->MdbUser->getUserInfo($this->DtoUser);
                    array_push($this->leader_array, array("user_id"  =>$this->DtoUser->getUser_id(),
                                                          "user_name"=>$this->DtoUser->getUser_name(),
                                                          "mailaddr" =>$this->DtoUser->getMailaddr()));
                }
            }
            $array = $this->session->getParameter("sections");
            $this->section_array = array();
            $section_readonly_flgs = $this->session->getParameter("section_readonly_flgs");
            $users_section = $this->session->getParameter("users_section");
            if(is_array($array)) {
                foreach($array as $key => $val) {
                $this->DtoSection->setSection_id($val);
                $this->MdbSection->getInfo($this->DtoSection);
                    array_push($this->section_array,array(
                                                          "section_id" => $this->DtoSection->getSection_id(),
                                                          "section_name" => $this->DtoSection->getSection_name(),
                                                          "readonly_flg" => $section_readonly_flgs[$this->DtoSection->getSection_id()]["section_readonly_flg"]
                                                          ));
                }
            }
            $array = $this->session->getParameter("users");
            $this->user_array = array();
            if(is_array($array)) {
                foreach($array as $key => $val) {
                    $this->DtoUser->setUser_id($val);
                    $this->MdbUser->getUserInfo($this->DtoUser);
                    array_push($this->user_array, array("user_id"  =>$this->DtoUser->getUser_id(),
                                                          "user_name"=>$this->DtoUser->getUser_name(),
                                                          "mailaddr" =>$this->DtoUser->getMailaddr(),
                                                          "readonly_flg" => $readonly_flgs[$this->DtoUser->getUser_id()]["readonly_flg"]));
                }
            }
        }

        //sessionを削除

        // セッションに保存
        $this->session->setParameter( "admin_edit_flg", $this->groupDto->getadmin_edit_flg() );
        $this->session->setParameter( "name", $this->groupDto->getName() );
        $this->session->setParameter("folder_id", $this->groupDto->getFolder_id());
        $this->session->setParameter( "folder_name", $this->groupDto->getFolder_name() );
        $this->session->setParameter( "del_span", $this->groupDto->getDel_span() );
        //$this->session->setParameter( "capacity", round($this->groupDto->capacity/1024/1024,2) );
        $this->session->setParameter( "capacity", $this->groupDto->getCapacity() );
        $this->session->setParameter( "lock_flg", $this->groupDto->getLock_flg() );
        $this->session->setParameter( "lock_dl_flg", $this->groupDto->getLock_dl_flg() );
        $this->session->setParameter( "term_flg", $this->groupDto->getTerm_flg() );
        $this->session->setParameter( "term_type_id", $this->groupDto->getTerm_type_id() );
        $this->session->setParameter( "start_year", $this->groupDto->getStart_year() );
        $this->session->setParameter( "start_month", $this->groupDto->getStart_month() );
        $this->session->setParameter( "start_day", $this->groupDto->getStart_day() );
        $this->session->setParameter( "end_year", $this->groupDto->getEnd_year() );
        $this->session->setParameter( "end_month", $this->groupDto->getEnd_month() );
        $this->session->setParameter( "end_day", $this->groupDto->getEnd_day() );
        $this->session->setParameter( "cancel_span", $this->groupDto->getCancel_span() );
        $this->session->setParameter( "send_flg", $this->groupDto->getSend_flg() );
        $this->session->setParameter( "log_share_flg", $this->groupDto->getLog_share_flg() );
        $this->session->setParameter( "mail_show_flg", $this->groupDto->getMail_show_flg() );

        // Google SSO認証のみのときは使えない
        if (LOGIN_FLG || (!LOGIN_FLG && !SSO_FLG)) {
            $this->session->setParameter( "api_flg", $this->groupDto->getApi_flg() );
        }
//        $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
//        $this->session->setParameter( "groupDto_serialize", serialize($this->groupDto) );
        //$this->session->setParameter( "del_span_init", $this->del_span_init);

        $this->session->removeParameter("leaders");

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
    function setSubfolder_edit_flg($subfolder_edit_flg) {
        $this->subfolder_edit_flg = $subfolder_edit_flg;
    }
    function setCommonList($CommonList) {
        $this->CommonList =& $CommonList;
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
/*    function getUser_array()
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
 *
 */
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

    function getTerm_flg() {
        if(SHARE_FOLDER_TERM_REQUIRED_FLG) {
            return SHARE_FOLDER_TERM_REQUIRED_FLG;
        }
        return $this->groupDto->getTerm_flg();
    }
    function getStart_year() {
        if(is_null($this->groupDto->getStart_dt())) {
            return date("Y", strtotime($this->DtoGroups->getCreated()));
        }
        return $this->groupDto->getStart_year();
    }
    function getStart_month() {
        if(is_null($this->groupDto->getStart_dt())) {
            return date("m", strtotime($this->DtoGroups->getCreated()));
        }
        return $this->groupDto->getStart_month();
    }
    function getStart_day() {
        if(is_null($this->groupDto->getStart_dt())) {
            return date("d", strtotime($this->DtoGroups->getCreated()));
        }
        return $this->groupDto->getStart_day();
    }
    function getEnd_year() {
        if(is_null($this->groupDto->getEnd_dt())) {
            if(SHARE_FOLDER_TERM_DEFAULT_SPAN > 0) {
                $span_time = SHARE_FOLDER_TERM_DEFAULT_SPAN  * 24 * 60 * 60;
                return date("Y", strtotime($this->DtoGroups->getCreated()) + $span_time);
            } else {
                return date("Y");
            }
        }
        return $this->groupDto->getEnd_year();
    }
    function getEnd_month() {
        if(is_null($this->groupDto->getEnd_dt())) {
            if(SHARE_FOLDER_TERM_DEFAULT_SPAN > 0) {
                $span_time = SHARE_FOLDER_TERM_DEFAULT_SPAN  * 24 * 60 * 60;
                return date("m", strtotime($this->DtoGroups->getCreated()) + $span_time);
            } else {
                return date("m");
            }
        }
        return $this->groupDto->getEnd_month();
    }
    function getEnd_day() {
        if(is_null($this->groupDto->getEnd_dt())) {
            if(SHARE_FOLDER_TERM_DEFAULT_SPAN > 0) {
                $span_time = SHARE_FOLDER_TERM_DEFAULT_SPAN  * 24 * 60 * 60;
                return date("d", strtotime($this->DtoGroups->getCreated()) + $span_time);
            } else {
                return date("d");
            }
        }
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
        $this->AdminUserLogic->getList($this->MdbAdminuser,$this->DtoAdminUser,$this->AdminUserDto);
        $list = array();
        foreach($this->DtoAdminUser->getAdminuserList() as $key => $val) {
            $list[$val["admin_id"]] = $val["account"];
		}
        return $list;
    }
    function getLog_share_flg() {
        return $this->groupDto->getLog_share_flg();
    }
    function getMail_show_flg() {
        return $this->groupDto->getMail_show_flg();
    }
    function getLeader_array()
    {
        return $this->leader_array;
    }
    function getUser_array()
    {
        return $this->user_array;
    }
    function getSection_array()
    {
        return $this->section_array;
    }
    function getAlledit_flg() {
        return  $this->session->getParameter("alledit_flg");
    }
    function getApi_Flg_Options() {
        return GlobalArray::getApi_Flg_Options();
    }
    function getApi_flg() {
        return $this->groupDto->getApi_flg();
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
    function getSend_flg() {
        return $this->groupDto->getSend_flg();
    }

}
?>

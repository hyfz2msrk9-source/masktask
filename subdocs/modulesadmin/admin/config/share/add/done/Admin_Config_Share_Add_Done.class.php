<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] グループ共有機能 - グループ共有機能に関する設定 - グループ追加(完了)
 * @updater     Hiroko Yanai（2018/04/13）SAML認証(OneLogin)に対応。SSOやSAMLでも、設定は更新可能とする
 *
 * @package     WebFile
 * @version     3.15.2
 * @author
 * @copyright   KEY POINT
 * @access      public
 */
class Admin_Config_Share_Add_Done {
    var $session;
    var $DtoGroups;
    var $MdbGroups;
    var $GroupLogic;
    var $MdbGroups_users;
    var $groupDto;
    var $DtoFolder;
    var $MdbFolder;
    var $DtoUser;
    var $MdbUser;
    var $DtoLog;
    var $MdbLog;
    var $DtoSection;
    var $MdbSection;
    var $DtoSectionusers;
    var $MdbSectionusers;
    var $DtoGroupsadminusers;
    var $MdbGroupsadminusers;
    var $SectionLogic;
    var $DtoConfigs;
    var $MdbConfigs;
    var $MdbMchoice;

    var $use_capacity;
    var $limit_capacity;
    var $del_span_init;
    var $user_open_flg;
    var $leader_open_flg;
    var $section_open_flg;
    var $group_add_flg;

    var $CommonList;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Share_Add_Doneが実行されました","Admin_Config_Share_Add_Done#execute");

        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        //$this->groupDto = unserialize( $this->session->getParameter("groupDto_serialize" ) );

        // 定期削除機能が無効の時
        if (AUTO_DEL_FILE_STOP_FLG == 1) {
            // 削除日数を強制的に0にする
            $this->del_span_init = 0;
        }

        // convert
        //if ($this->group_add_flg) { // hiddenタグ書き換え対策のため、常にチェックを行う
             $this->GroupLogic->convertGroup($this->groupDto);

            // 入力チェック
            if ($this->groupDto->name == "") {
                $errorList->add("Admin_Config_Share_Add_Done::error", "グループ名を入力してください。");
                return 'input';
            }
            if ($this->groupDto->folder_name == "") {
                $errorList->add("Admin_Config_Share_Add_Done::error", "共有フォルダ名を入力してください。");
                return 'input';
            }
            if (strlen($this->groupDto->name) > 150) {
                $errorList->add("Admin_Config_Share_Add_Done::error", "グループ名を全角50文字以内で入力してください。");
                return 'input';
            }
            if (strlen($this->groupDto->folder_name) > 150) {
                $errorList->add("Admin_Config_Share_Add_Done::error", "共有フォルダ名を全角50文字以内で入力してください。");
                return 'input';
            }
            if ($this->del_span_init == "") {
                $errorList->add("Admin_Config_Share_Add_Done::error","定期削除の日数を入力してください。");
                return 'input';
            }
            if (!preg_match("/^[0-9|０-９]+$/", $this->del_span_init)) {
                $errorList->add("Admin_Config_Share_Add_Done::error","定期削除の日数は0日以上の半角数字を入力してください。");
                return 'input';
            }
            if ($this->del_span_init < 0) {
                $errorList->add("Admin_Config_Share_Add_Done::error","定期削除の日数は0日以上を入力してください。");
                return 'input';
            }
            if ($this->del_span_init > 99999) {
                $errorList->add("Admin_Config_Share_Add_Done::error","定期削除の日数は99999日以下を入力してください。");
                return 'input';
            }
        //}

        // パラメータチェック
        if ($this->section_open_flg > 1 || $this->user_open_flg > 1 || $this->leader_open_flg > 1) {
            return "error";
        }

        if (!$this->group_add_flg) {
            $this->del_span_init = $this->session->getParameter("del_span_init");
        }
        $this->groupDto->setDel_span(mb_convert_kana($this->del_span_init,"n"));
        $this->groupDto->setCapacity(mb_convert_kana($this->groupDto->capacity,"n"));

        if (!defined('SHARE_SPACE_FLG') || SHARE_SPACE_FLG == 0) {
            $this->use_capacity = $this->MdbGroups->getUseCapacity();

            //容量入力必須チェック
            if ($this->groupDto->capacity == '') {
                $errorList->add("Admin_Config_Share_Add_Done::error", "容量を入力してください。");
                $this->groupDto->setCapacity("");
                return "input";
            }
            //容量入力数値チェック
            //if (!preg_match("/^[0-9|０-９]+$/", $this->groupDto->capacity)) {
            if (!is_numeric($this->groupDto->capacity)) {
                $errorList->add("Admin_Config_Share_Add_Done::error", "容量は、数字のみで入力してください。");
                $this->groupDto->setCapacity("");
                return "input";
            }
            //容量入力値チェック
            if ($this->groupDto->capacity < 0.01) {
                $errorList->add("Admin_Config_Share_Add_Done::error", "容量は、0.01MB以上を指定してください。");
                $this->groupDto->setCapacity("");
                return "input";
            }
            //残容量チェック
            $this->groupDto->setCapacity(round($this->groupDto->capacity*1024*1024));
            $this->limit_capacity = SHARE_MAX_CAPACITY*1024*1024*1024 - $this->use_capacity;
            if ((SHARE_MAX_CAPACITY*1024*1024*1024 - $this->use_capacity - $this->groupDto->capacity)<0) {
                // 上限に達しているので、追加出来ない
                $errorList->add("Admin_Config_Share_Add_Done::error", "容量が、制限値を越えています。");
                $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                return "input";
            }
        } else {
            // 各グループに容量を割り当てない時は0で初期化
            $this->groupDto->capacity = 0;
        }

        //readonly_flg入力チェック
        if (!$this->GroupLogic->checkValue($this->groupDto,$errorList)) {
            return "error";
        }

        //ファイルロック機能チェック
        if (FILE_LOCK_OPTION_FLG) {
            if (!array_key_exists($this->groupDto->getLock_flg(), $this->getLockOptions())) return "error";
            if (!array_key_exists($this->groupDto->getLock_dl_flg(), $this->getLockDlOptions())) return "error";

            $this->groupDto->setCancel_span(mb_convert_kana($this->groupDto->getCancel_span(),"n"));

            //解除日数
            if (!is_numeric($this->groupDto->getCancel_span())) {
                $errorList->add("Admin_Config_Share_Add_Done::error", "ロック解除日数は数値で入力してください。");
                $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                return "input";
            }
            if (!preg_match("/^[0-9]+$/",$this->groupDto->getCancel_span())) {
                $errorList->add("Admin_Config_Share_Add_Done::error", "ロック解除日数は整数で入力してください。");
                $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                return "input";
            }
            //Admin_Config_Share_Edit_Doneも参照
            //!preg_match("/^[0-9]+$/",$入力値)をみれば、!is_numeric()は不要 http://openlab.dino.co.jp/2007/11/23/121351149.html

            //"定期削除の日数を入力してください。"の条件。!is_numeric()で引っかかるので、不要
            /*
            if ($this->getCancel_span() == "") {
                $errorList->add("Admin_Config_Share_Add_Done::error","ロック解除日数を入力してください。");
                return 'input';
            }
            */
            if ($this->getCancel_span() < 0) {
                $errorList->add("Admin_Config_Share_Add_Done::error","ロック解除日数は0日以上を入力してください。");
                $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                return 'input';
            }
            if ($this->getCancel_span() > 99999) {
                $errorList->add("Admin_Config_Share_Add_Done::error","ロック解除日数は99999日以下を入力してください。");
                $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                return 'input';
            }
        } else {
            $this->DtoGroups->setCancel_span('0');
        }
        if ($this->groupDto->getTerm_flg() == '1') {
            $arr = $this->getTerm_type_options();
            //日付チェック
            if (!preg_match("|\d{4}-\d{1,2}-\d{1,2}|", $this->groupDto->getStart_dt())) {
                $errorList->add("Admin_Config_Share_Add_Done::error", "有効期限の開始日を正しく入力してください。");
                $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                return "input";
            }
            if (!checkdate($this->groupDto->getStart_month(), $this->groupDto->getStart_day(), $this->groupDto->getStart_year())) {
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
            if (!checkdate($this->groupDto->getEnd_month(), $this->groupDto->getEnd_day(), $this->groupDto->getEnd_year())) {
                $errorList->add("Admin_Config_Share_Add_Done::error", "有効期限の終了日を正しく入力してください。");
                $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                return "input";
            }
            $arr = $this->getTerm_type_options();
            if (!array_key_exists($this->groupDto->getTerm_type_id(), $arr)) {
                $errorList->add("Admin_Config_Share_Add_Done::error", "有効期限切れのフォルダ操作を正しく選択してください。");
                $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                return "input";
            }
        } else {
            $this->groupDto->setTerm_flg(0);
            $this->groupDto->setTerm_type_id(0);
            $this->groupDto->setStart_year("");
            $this->groupDto->setStart_month("");
            $this->groupDto->setStart_day("");
            $this->groupDto->setEnd_year("");
            $this->groupDto->setEnd_month("");
            $this->groupDto->setEnd_day("");
        }

        // API機能の利用
        if (API_FLG) {
            if ($this->groupDto->getApi_flg()) {
                if (!array_key_exists($this->groupDto->getApi_flg(), $this->getApi_Flg_Options())) {
                    $errorList->add("Admin_Config_Share_Add_Done::error", API_CLIENT_NAME."機能の利用を正しく選択してください。");
                    $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                    return "input";
                }
            }
        }

        //配布設定
        if ($this->groupDto->getSend_flg() > 1 || !is_numeric($this->groupDto->getSend_flg())) {
            $errorList->add("Admin_Config_Share_Add_Done::error", "配布設定を正しく選択してください。");
            $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
            return "input";
        }

        // ログ共有
        if ($this->groupDto->getLog_share_flg() > 1 || !is_numeric($this->groupDto->getLog_share_flg())) {
            $errorList->add("Admin_Config_Share_Add_Done::error", "履歴共有設定を正しく選択してください。");
            $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
            return "input";
        }

        // メアド公開
        if ($this->groupDto->getMail_show_flg() > 1 || !is_numeric($this->groupDto->getMail_show_flg())) {
            $errorList->add("Admin_Config_Share_Add_Done::error", "メールアドレスの公開設定を正しく選択してください。");
            $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
            return "input";
        }

        // サブ管理者編集
        if ($this->session->getParameter("login_admin_level") >= 5) {
            if (!in_array($this->groupDto->getAdmin_edit_flg(),array('0','1'),true)) {
                $errorList->add("Admin_Config_Share_Add_Done::error", "サブ管理者の編集権限設定を正しく選択してください。");
                $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                return "input";
            }
        } else {
            //システム管理者以外は許可に強制設定
            $this->groupDto->setAdmin_edit_flg(1);
        }

        if (SHARE_ADMIN_MUST_FLG==1 && !$this->groupDto->getLeaders()) {
            // 管理者ユーザは必須入力
            $errorList->add("Admin_Config_Share_Add_Done::error", "グループ管理者を選択してください。");
            $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));

            if (isset($this->group_add_flg) && $this->group_add_flg) {
                return "input";
            } else {
                return "input_user";
            }
        }

        // ユーザ存在チェック用
        if ($this->groupDto->getLeader_id()>0 || $this->groupDto->users) {
            $where = array();
            $where[] = "del_flg = 0";
            $userkeylist = $this->MdbUser->getUserKeyList($where);
        }

        //グループ管理者によるユーザ管理
        if (!in_array($this->groupDto->getGroup_admin_operate_flg(),array('0','1'),true)) {
            $errorList->add("Admin_Config_Share_Add_Done::error", "グループ管理者によるユーザ管理操作の設定を正しく選択してください。");
            $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
            return "input_user";
        }

        // 管理者追加
        /*
        if ($this->groupDto->getLeaders()) {
            foreach($this->groupDto->getLeaders() as $val) {
                // グループ管理者（＝ユーザ）存在チェック
                $this->groupDto->setLeader_id($val);
                if (!array_key_exists($this->groupDto->getLeader_id(), $userkeylist)) {
                    return 'error';
                }
            }
        }
        */

        // folderを作成し、folder_idを確定
        $this->DtoFolder->setParent_id('');
        $this->DtoFolder->setFolder_name($this->groupDto->folder_name);
        $this->DtoFolder->setFolder_type(1);  // folder_type 1 が共有フォルダ
        $this->DtoFolder->setUser_id(0);     // 共有フォルダは0を入れる

        // 共有フォルダ名チェック
        if (OVERWRITE_FLG) {
            if ($this->MdbFolder->getFolderNameTF($this->DtoFolder)) {
                $errorList->add("Admin_Config_Share_Add_Done::error", "同名の共有フォルダ名があるため、別の共有フォルダ名にしてください。");
                $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
                return "input";
            }
        }

        $this->MdbFolder->insertFolder($this->DtoFolder);

        // groupを作成する
        $this->groupDto->setAdmin_id($this->session->getParameter('admin_id'));
        $this->groupDto->setFolder_id($this->DtoFolder->folder_id);
        $group_id = $this->MdbGroups->add($this->groupDto);
        if ($this->groupDto->getLeaders()) {
            foreach($this->groupDto->getLeaders() as $val) {
                // ユーザと重複してた場合はユーザの方は消す
                if ($this->groupDto->getUsers()) {
                    $rs = false;
                    $rs = array_search($val, $this->groupDto->getUsers());
                    if ($rs !== false && !is_null($rs)) {
                        $ar_users = null;
                        $ar_users = $this->groupDto->getUsers();
                        unset($ar_users[$rs]);
                        $this->groupDto->setUsers($ar_users);
                    }
                }
                $where = array();
                $where[] = "user_id = '".Db_Common::escape_string($val)."'";
                $where[] = "del_flg = 0";
                // 存在しないユーザはスルー
                if (!$this->MdbUser->getExistCheck($where)) {
                    continue;
                }

                // 管理者の登録
                $this->DtoGroupsadminusers->setGroup_id($group_id);
                $this->DtoGroupsadminusers->setSection_id('0');
                $this->DtoGroupsadminusers->setUser_id($val);
                $this->MdbGroupsadminusers->insert($this->DtoGroupsadminusers);
            }
        }

        // groups_usersに追加する
        // $this->MdbGroups_users->save($group_id,$this->groupDto->users, $this->groupDto->readonly_flgs);
        $users = "";
        $users = $this->groupDto->getUsers();
        $sections = "";
        $sections = $this->groupDto->getSections();
        $user_read_flg = "";
        $user_read_flg = $this->groupDto->getReadonly_Flgs();
        $section_read_flg = "";
        $section_read_flg = $this->groupDto->getSection_readonly_flgs();
        $this->groupDto->setId($group_id);
        $saved_sections = array(); // 登録部署を格納する変数
        if ($sections) {
            $sectionkeylist = $this->MdbSection->getKeyList(); // 部署マスタ
            foreach($sections as $val) {

                // 存在しない部署を選択されたらスルー
                if (!array_key_exists($val ,$sectionkeylist)) {
                    continue;
                }

                $this->MdbGroups_users->save2(
                    $this->groupDto,
                    '0',
                    $val,
                    $section_read_flg[$val]["section_readonly_flg"],
                    1
                );
                $saved_sections[] = $val;
            }
        }
        if ($users) {
            // 部署所属ユーザ取得
            $this->DtoSectionusers->setSection_id($saved_sections);
            $sectionuseridlist = $this->SectionLogic->getSectionUserIdList($this->MdbSectionusers, $this->DtoSectionusers);
            foreach($users as $val) {
                // 存在しないユーザを選択されたらスルー
                if (!array_key_exists($val ,$userkeylist)) {
                    continue;
                }

                // 既に部署ユーザが存在している場合はスルー
                if (in_array($val ,$sectionuseridlist)) {
                    continue;
                }
                $this->MdbGroups_users->save2(
                    $this->groupDto,
                    $val,
                    '0',
                    $user_read_flg[$val]["readonly_flg"],
                    1
                );
            }
        }

        // Log
        $history_id = 113; // 113:各種設定＞グループ共有機能＞グループ追加

        $member_name = "";
        $member = array();
        $member = $this->groupDto->getUsers();
        if ($member) {
            $member_name .= "　[ユーザ]\n";
            for ($i=0;$i<count($member);$i++) {
                $this->DtoUser->setUser_id($member[$i]);
                $this->MdbUser->getUserInfo($this->DtoUser);
                $member_name .= "　".$this->DtoUser->getAccount()."（".$this->DtoUser->getUser_name() ."）\n";
            }
        }
        $member = array();
        $member = $this->groupDto->getSections();
        if ($member) {
            $member_name .= "　[部　署]\n";
            for ($i=0;$i<count($member);$i++) {
                $this->DtoSection->setSection_id($member[$i]);
                $this->MdbSection->getInfo($this->DtoSection);
                $member_name .= "　".$this->DtoSection->getSection_name()."\n";
            }
        }

        $capa = $this->groupDto->getCapacity();
        $capa = StrChange::CapacityUnit($capa);

        $leader = "";
        if ($this->groupDto->getLeader_id()) {
            $info = "";
            $info = $this->MdbUser->getInfo($this->groupDto->getLeader_id());
            if ($info) {
                $leader = "　".$info["user_name"]."（".$info["mailaddr"]."）";
            }
        }

        $memo = "";
        $memo .= "グループ名：".$this->groupDto->getName()."\n";
        $memo .= "共有フォルダ名：".$this->groupDto->getFolder_name()."\n";
        if (!defined('SHARE_SPACE_FLG') || SHARE_SPACE_FLG == 0) {
            $memo .= "容量：".$capa."\n";
        }
        if (!defined('FILE_LOCK_OPTION_FLG') || FILE_LOCK_OPTION_FLG) {
            $arry = array("無効","有効");
            $memo .= "ファイルロック機能：" . $arry[$this->groupDto->lock_flg]."\n";
            $arry = array("許可","禁止");
            $memo .= "ロック中のダウンロード：" . $arry[$this->groupDto->lock_dl_flg]."\n";
            $memo .= "ロック解除日数：".$this->groupDto->cancel_span."日後に解除"."\n";
        }
        if (API_FLG) {
            $arry = array("使用しない","使用する");
            $memo .= API_CLIENT_NAME."機能の利用：" . $arry[$this->groupDto->getApi_flg()]."\n";
        }
        $arr = array("許可","禁止");
        $memo .= "配布設定：".$arr[$this->groupDto->getSend_flg()]."\n";
        $arr = array("共有しない","共有する");
        $memo .= "履歴共有設定：".$arr[$this->groupDto->getLog_share_flg()]."\n";
        $arr = array("公開しない","公開する");
        $memo .= "メールアドレスの公開設定：".$arr[$this->groupDto->getMail_show_flg()]."\n";

        //サブ管理者による編集設定 @システム管理者のみ
        if ($this->session->getParameter("login_admin_level") >= 5) {
            $arr = array("許可","禁止");
            $memo .= "サブ管理者の編集権限設定：".$arr[$this->groupDto->getAdmin_edit_flg()]."\n";
        }
        //グループ管理者によるユーザ管理設定
        $arr = array("許可","禁止");
        $memo .= "グループ管理者によるユーザ管理設定：".$arr[$this->groupDto->getGroup_admin_operate_flg()]."\n";

        if ($leader) {
            $memo .= "グループ管理者"."\n".$leader."\n";
        }
        $memo .= "メンバー"."\n".$member_name;

        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        // セッション削除
        $this->session->removeParameter( "admin_edit_flg" );
        $this->session->removeParameter( "name" );
        $this->session->removeParameter( "folder_name" );
        $this->session->removeParameter( "del_span" );
        $this->session->removeParameter( "capacity" );
        $this->session->removeParameter( "lock_flg" );
        $this->session->removeParameter( "lock_dl_flg" );
        $this->session->removeParameter( "term_flg" );
        $this->session->removeParameter( "term_type_id" );
        $this->session->removeParameter( "start_year" );
        $this->session->removeParameter( "start_month" );
        $this->session->removeParameter( "start_day" );
        $this->session->removeParameter( "end_year" );
        $this->session->removeParameter( "end_month" );
        $this->session->removeParameter( "end_day" );
        $this->session->removeParameter( "cancel_span" );
        $this->session->removeParameter( "send_flg" );
        $this->session->removeParameter( "log_share_flg" );
        $this->session->removeParameter( "mail_show_flg" );
        $this->session->removeParameter("del_span_init");
        $this->session->removeParameter("api_flg");
        $this->session->removeParameter("GroupAddToken");

        return "success";
    }
    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoGroups($DtoGroups) {
        $this->DtoGroups = $DtoGroups;
    }
    function setMdbGroups($MdbGroups) {
        $this->MdbGroups = $MdbGroups;
    }
    function setGroupLogic($GroupLogic) {
        $this->GroupLogic = $GroupLogic;
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
    function setDtoLog ( &$DtoLog ) {
        $this->DtoLog =& $DtoLog;
    }
    function setMdbLog ( &$MdbLog ) {
        $this->MdbLog =& $MdbLog;
    }
    function setDtoGroupsadminusers($DtoGroupsadminusers) {
        $this->DtoGroupsadminusers = $DtoGroupsadminusers;
    }
    function setMdbGroupsadminusers($MdbGroupsadminusers) {
        $this->MdbGroupsadminusers = $MdbGroupsadminusers;
    }
    function setSectionLogic($SectionLogic) {
        $this->SectionLogic = $SectionLogic;
    }
    function setDtoConfigs($DtoConfigs) {
        $this->DtoConfigs = $DtoConfigs;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setDel_span_init($del_span_init) {
        $this->del_span_init =& $del_span_init;
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
    function setGroup_add_flg($group_add_flg) {
        $this->group_add_flg = $group_add_flg;
    }
    function setMdbMchoice($MdbMchoice) {
        $this->MdbMchoice = $MdbMchoice;
    }
    function setCommonList($CommonList) {
        $this->CommonList =& $CommonList;
    }

    // getter
    function getGroupList() {
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
    function getName()
    {
        return $this->groupDto->name;
    }
    function getFolder_name()
    {
        return $this->groupDto->folder_name;
    }
    function getCapacity()
    {
        return $this->groupDto->capacity;
    }
    function getLeader_array() {
        if ($this->groupDto->getLeaders()) {
            foreach($this->groupDto->getLeaders() as $val) {
                $this->DtoUser->setUser_id($val);
                $this->MdbUser->getUserInfo($this->DtoUser);
                $ary[] = array("user_id"   => $this->DtoUser->getUser_id(),
                               "user_name" => $this->DtoUser->getUser_name(),
                               "mailaddr"  => $this->DtoUser->getMailaddr());
            }
            return $ary;
        } else {
            return array();
        }
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

    function getDel_span()
    {
        if (is_null($this->del_span_init) && (string)$this->del_span_init !== '0') {
            if ($this->MdbConfigs->getValCnt("del_span") >= 1) {
                $this->MdbConfigs->getInfo($this->DtoConfigs,"del_span");
                return $this->DtoConfigs->getValue();
            } else {
                return 0;
            }
        } else {
            return $this->del_span_init;
        }
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
        if ($this->groupDto->getCancel_span() != "") {
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
    function getSend_flg() {
        return $this->groupDto->getSend_flg();
    }
    function getLog_share_flg() {
        return $this->groupDto->getLog_share_flg();
    }
    function getMail_show_flg() {
        return $this->groupDto->getMail_show_flg();
    }
    function getAdmin_edit_flg() {
        return $this->groupDto->getAdmin_edit_flg();
    }
    function getGroup_admin_operate_flg() {
        return $this->groupDto->getGroup_admin_operate_flg();
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
}
?>

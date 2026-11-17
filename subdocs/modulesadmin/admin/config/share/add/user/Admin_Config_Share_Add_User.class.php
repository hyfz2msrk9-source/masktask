<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] グループ共有機能 - グループ共有機能に関する設定 - グループ追加(権限設定)
 * @updater     Hiroko Yanai（2018/04/13）SAML認証(OneLogin)に対応。SSOやSAMLでも、設定は更新可能とする
 *
 * @package     WebFile
 * @version     3.15.2
 * @author
 * @copyright   KEY POINT
 * @access      public
 */
class Admin_Config_Share_Add_User {
    var $session;
    var $DtoGroups;
    var $MdbGroups;
    var $groupDto;
    var $DtoFolder;
    var $MdbFolder;
    var $DtoUser;
    var $MdbUser;
    var $DtoSection;
    var $MdbSection;
    var $DtoConfigs;
    var $MdbConfigs;
    var $MdbMchoice;
    var $user_array;
    var $section_array;
    var $getLeader_array;

    var $use_capacity;
    var $limit_capacity;
    var $del_span_init;

    var $CommonList;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Share_Add_Userが実行されました","Admin_Config_Share_Add_User#execute");

        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        // 定期削除機能が無効の時
        if (AUTO_DEL_FILE_STOP_FLG == 1) {
            // 削除日数を強制的に0にする
            $this->del_span_init = 0;
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

        //ファイルロック機能チェック
        if (FILE_LOCK_OPTION_FLG) {
            if (!array_key_exists($this->groupDto->getLock_flg(), $this->getLockOptions())) return "error";
            if (!array_key_exists($this->groupDto->getLock_dl_flg(), $this->getLockDlOptions())) return "error";

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

        /*
        if (SHARE_ADMIN_MUST_FLG==1 && !$this->groupDto->getLeaders()) {
            // 管理者ユーザは必須入力
            $errorList->add("Admin_Config_Share_Add_Done::error", "グループ管理者を選択してください。");
            $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
            return "input";
        }
        */

        // ユーザ存在チェック用
        if ($this->groupDto->getLeader_id()>0 || $this->groupDto->users) {
            $where = array();
            $where[] = "del_flg = 0";
            $userkeylist = $this->MdbUser->getUserKeyList($where);
        }

        /*
        //グループ管理者によるユーザ管理
        if (!in_array($this->groupDto->getGroup_admin_operate_flg(),array('0','1'),true)) {
            $errorList->add("Admin_Config_Share_Add_Done::error", "グループ管理者によるユーザ管理操作の設定を正しく選択してください。");
            $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
            return "input";
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

        if ( $this->session->getParameter("member_edit") == 1 ) {
            $array = $this->session->getParameter("leaders");
            $this->leader_array = array();
            $readonly_flgs = $this->session->getParameter("readonly_flgs");
            if (is_array($array)) {
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
            if (is_array($array)) {
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
            if (is_array($array)) {
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

        // セッションに保存
        $this->session->setParameter( "admin_edit_flg", $this->groupDto->getadmin_edit_flg() );
        $this->session->setParameter( "name", $this->groupDto->getName() );
        $this->session->setParameter( "folder_name", $this->groupDto->getFolder_name() );
        $this->session->setParameter( "del_span", $this->groupDto->getDel_span() );
        $this->session->setParameter( "capacity", round($this->groupDto->capacity/1024/1024,2) );
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
//        $this->groupDto->setCapacity(round($this->groupDto->capacity/1024/1024,2));
//        $this->session->setParameter( "groupDto_serialize", serialize($this->groupDto) );
        $this->session->setParameter( "del_span_init", $this->del_span_init);
        $this->session->setParameter( "api_flg", $this->groupDto->getApi_flg());

        $this->session->removeParameter("leaders");

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
    function setDtoConfigs($DtoConfigs) {
        $this->DtoConfigs = $DtoConfigs;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setDel_span_init($del_span_init) {
        $this->del_span_init =& $del_span_init;
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
  function Leader_array()
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

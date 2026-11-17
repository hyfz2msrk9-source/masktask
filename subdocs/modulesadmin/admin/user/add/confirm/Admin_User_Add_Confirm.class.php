<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] ユーザ管理 - ユーザ登録(確認)
 * @updater
 *
 * @package     WebFile
 * @version
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_User_Add_Confirm {
    var $session;
    var $DtoUser;
    var $userDto;
    var $MdbUser;
    var $userLogic;
    var $adminuserLogic;
    var $MdbConfigs;
    var $DtoConfigs;
    var $MdbGroups;
    var $DtoGroups;
    var $MdbGroupsusers;
    var $DtoGroupsusers;
    var $DtoSection;
    var $MdbSection;
    var $GroupsusersLogic;
    var $MdbGroupsadminusers;

    var $MdbUpload;
    var $MdbCollect;
    var $CapacityLogic;

    var $all_readonly_flg;
    var $subfolder;

    var $start_at_year;
    var $start_at_month;
    var $start_at_day;
    var $end_at_year;
    var $end_at_month;
    var $end_at_day;

    var $CommonList;
    function execute() {

        $log =& LogFactory::getLog();
        $log->debug("Admin_User_Add_Confirm","Admin_User_Add_Confirm#execute");

        // エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        $cnt = $this->userLogic->getUserActiveCont($this->DtoUser, $this->MdbUser);

        if (MAX_USER != 0) {
            if ( MAX_USER <= $cnt) {
                return "error";
            }
        }

        // グループアクセス権限入力チェック
        if (!$this->GroupsusersLogic->checkValue($this->DtoGroupsusers,$this->MdbGroups,$this->session->getParameter("login_admin_level"))) {
            return "input";
        }
        // 部署選択入力チェック
        if (!$this->adminuserLogic->checkSection_id($this->DtoGroupsusers, $this->MdbSection, $this->session->getParameter("login_admin_level"))) {
            return "input";
        }

        if ($this->DtoUser->getAuth_type() == 1) {
            // Google SSO認証ユーザであれば、google_mail_addressにaccountをセット
            $this->DtoUser->setAccount($this->DtoUser->getGoogle_mail_address());
            $this->DtoUser->setPassword("");
            $this->DtoUser->setSso_flg(1);
            $this->DtoUser->setSaml_flg(0);
        } else if ($this->DtoUser->getAuth_type() == 2) {
            // SAML認証(OneLogin)ユーザであれば、saml_mail_addressにaccountをセット
            $this->DtoUser->setAccount($this->DtoUser->getSaml_mail_address());
            $this->DtoUser->setPassword("");
            $this->DtoUser->setSso_flg(0);
            $this->DtoUser->setSaml_flg(1);
        } else {
            // パスワード認証の場合
            $this->DtoUser->setSso_flg(0);
            $this->DtoUser->setSaml_flg(0);
        }

        // 認証方式の選択チェック
        if (!$this->userLogic->checkAuth($this->DtoUser, $this->CommonList)) {
            return "input";
        }
        // 認証方式によって、アカウント重複チェック
        if (!$this->userLogic->checkAccount($this->DtoUser,$this->MdbUser)) {
            return "input";
        }
        // メールアドレス重複チェック
        if (!$this->userLogic->checkMailaddr($this->DtoUser,$this->MdbUser)) {
            return "input";
        }
        if (!$this->adminuserLogic->checkSize($this->DtoUser,$this->MdbUser, $this->CapacityLogic)) {
            return "input";
        }
        if (!$this->DtoUser->getMax_up_size()) {
            $this->DtoUser->setMax_up_size(0);
        } else if ($this->DtoUser->getMax_up_size() < 0.01) {
            $errorList->add("Admin_User_Add_Confirm","ご希望のアップロード容量を0.01MB以上で入力してください。");
            return "input";
        }
        if (!$this->adminuserLogic->checkUpSize($this->DtoUser)) {
            return "input";
        }
        // API(Synchrorer)のチェック。SSO認証とSAML認証(OneLogin)の場合は、0にする
        if (!$this->adminuserLogic->checkApi_flg($this->DtoUser, $this->MdbUser)) {
            return "input";
        }
        if (!$this->adminuserLogic->checkSend_flg($this->DtoUser)) {
            return "input";
        }
        if (!$this->adminuserLogic->checkCollect_flg($this->DtoUser)) {
            return "input";
        }
        if (!$this->adminuserLogic->checkOpenFolder_flg($this->DtoUser)) {
            return "input";
        }
        if (!$this->adminuserLogic->checkDelivery_flg($this->DtoUser)) {
            return "input";
        }
        if (!$this->adminuserLogic->checkGathering_flg($this->DtoUser)) {
            return "input";
        }

        // SSO認証とSAML認証の場合は、パスワードチェックは無し
        if ($this->DtoUser->getAuth_type() == 0) {
            // パスワード必須チェック
            if (!defined("PASSWORD_REQUIRED_FLG") || !PASSWORD_REQUIRED_FLG) {
                if (!$this->userDto->getPasswd()) {
                    $errorList->add("Admin_User_Add_Confirm::error","ご希望のパスワードを入力してください。");
                    return "input";
                }
            }
            if ($this->userDto->getPasswd()) {
                if (!$this->adminuserLogic->checkPasswordStr($this->userDto->getPasswd(),$this->MdbConfigs,$this->DtoConfigs)) {
                    return "input";
                }
                if (!$this->adminuserLogic->checkPasswordRule($this->userDto->getPasswd(),$this->MdbConfigs,$this->DtoConfigs)) {
                    return "input";
                }
                $ret = $this->adminuserLogic->checkPasswordAccountNG(
                    $this->MdbConfigs,
                    $this->DtoUser,
                    $this->userDto->getPasswd(),
                    $errorList
                );
                if ($ret === false && $errorList->isExists()) {
                    return "input";
                }
            }
        }

        // グループ所属の排他チェック？
        if (!$this->GroupsusersLogic->checkExclusiveSectionUser($this->DtoGroupsusers,$this->DtoUser,$this->MdbGroupsadminusers,$this->MdbGroupsusers) ) {
            return "input";
        }

        // 新規作成時なので、前回と同じパスワードかどうかのチェックは、行っていません。
        //有効期限を設定
        if (TERM_OF_VALIDITY) {
            //開始日のどこかが入力されている
            if ($this->start_at_year!=NULL || $this->start_at_month!=NULL || $this->start_at_day) {
                //開始日が全て入力されている
                if ($this->start_at_year!=NULL && $this->start_at_month!=NULL && $this->start_at_day) {
                    //開始日の日付が不正ではない(2/31等)
                    if (!$this->dateCheck($this->start_at_year,$this->start_at_month,$this->start_at_day)) {
                        //終了日が入力されている
                        if ($this->end_at_year != NULL) {
                            //終了日が全て入力されているかチェック
                            if ($this->end_at_year == NULL || $this->end_at_month == NULL || $this->end_at_day == NULL) {
                                $errorList->add("Admin_User_Add_Confirm::error","有効期限（終了）が正しく入力されていません。");
                                return "input";
                            }
                            $start_dt = date("Y-m-d",mktime(0,0,0,$this->start_at_month,$this->start_at_day,$this->start_at_year));
                            //開始日が終了日を越えていない
                            if ($start_dt <= date("Y-m-d",mktime(0,0,0,$this->end_at_month,$this->end_at_day,$this->end_at_year))) {
                                $this->DtoUser->setStart_dt($start_dt);
                            } else {
                                $errorList->add("Admin_User_Add_Confirm::error","有効期限（開始）が有効期限（終了）を越えています。");
                                return "input";
                            }
                        } else {
                            //全てのチェックをクリア
                            $this->DtoUser->setStart_dt(date("Y-m-d",mktime(0,0,0,$this->start_at_month,$this->start_at_day,$this->start_at_year)));
                        }
                    } else {
                        $errorList->add("Admin_User_Add_Confirm::error","有効期限（開始）の日付が正しくありません。");
                        return "input";
                    }
                } else {
                    $errorList->add("Admin_User_Add_Confirm::error","有効期限（開始）が正しく入力されていません。");
                    return "input";
                }
            }

            //終了日のどこかが入力されている
            if ($this->end_at_year!=NULL || $this->end_at_month!=NULL || $this->end_at_day) {
                //終了日が全て入力されている
                if ($this->end_at_year!=NULL && $this->end_at_month!=NULL && $this->end_at_day) {
                    //終了日の日付が不正ではない(2/31等)
                    if (!$this->dateCheck($this->end_at_year,$this->end_at_month,$this->end_at_day)) {
                        //全てのチェックをクリア
                        $this->DtoUser->setEnd_dt(date("Y-m-d",mktime(0,0,0,$this->end_at_month,$this->end_at_day,$this->end_at_year)));
                    } else {
                        $errorList->add("Admin_User_Add_Confirm::error","有効期限（終了）の日付が正しくありません。");
                        return "input";
                    }
                } else {
                    $errorList->add("Admin_User_Add_Confirm::error","有効期限（終了）が正しく入力されていません。");
                    return "input";
                }
            }
        }

        // 定期削除の個別設定日数の整数チェック
        if ($this->DtoUser->getFile_delete_limit_dt() >= 0 && $this->DtoUser->getFile_delete_limit_dt() < 1000) {
            if ($this->DtoUser->getFile_delete_limit_dt() !== '0' && (!preg_match("/^[1-9][0-9]*$/", $this->DtoUser->getFile_delete_limit_dt()))) {
                $errorList->add("Admin_User_Add_Confirm::error", "定期削除の個別設定日数は整数で入力してください。");
                return "input";
            }
        }

        // sessionに記入
        $this->session->setParameter("auth_type",$this->DtoUser->getAuth_type());
        $this->session->setParameter("account",$this->DtoUser->getAccount());
        $this->session->setParameter("google_mail_address",$this->DtoUser->getGoogle_mail_address());
        $this->session->setParameter("saml_mail_address",$this->DtoUser->getSaml_mail_address());
        $this->session->setParameter("mailaddr",$this->DtoUser->getMailaddr());
        $this->session->setParameter("user_name",$this->DtoUser->getUser_name());
        $this->session->setParameter("user_kana",$this->DtoUser->getUser_kana());
        $this->session->setParameter("password",$this->userDto->getPasswd());
        $this->session->setParameter("max_file_size",$this->DtoUser->getMax_file_size());
        $this->session->setParameter("ext_file_size",$this->DtoUser->getExt_file_size());
        $this->session->setParameter("ext_unlimited_flg",$this->DtoUser->getExt_unlimited_flg());
        $this->session->setParameter("max_up_size",$this->DtoUser->getMax_up_size());
        $this->session->setParameter("start_dt",$this->DtoUser->getStart_dt());
        $this->session->setParameter("end_dt",$this->DtoUser->getEnd_dt());
        $this->session->setParameter("memo",$this->DtoUser->getMemo());
        $this->session->setParameter("stop_flg",$this->DtoUser->getStop_flg());
        $this->session->setParameter("ad_flg",$this->DtoUser->getAd_flg());
        $this->session->setParameter("readonly_flg",$this->DtoGroupsusers->readonly_flg);
        $this->session->setParameter("section_id",$this->DtoGroupsusers->section_id);
        $this->session->setParameter("subfolder",$this->subfolder);
        $this->session->setParameter("password_change_flg",$this->DtoUser->getPassword_change_flg());
        $this->session->setParameter("api_flg",$this->DtoUser->getApi_flg());
        $this->session->setParameter("file_delete_limit_dt",$this->DtoUser->getFile_delete_limit_dt());
        $this->session->setParameter("send_flg", $this->DtoUser->getSend_flg());
        $this->session->setParameter("collect_flg", $this->DtoUser->getCollect_flg());
        $this->session->setParameter("open_folder_flg", $this->DtoUser->getOpen_folder_flg());
        $this->session->setParameter("delivery_flg",$this->DtoUser->getDelivery_flg());
        $this->session->setParameter("gathering_flg",$this->DtoUser->getGathering_flg());

        return "success";
    }

    function dateCheck($year,$month,$day) {
        $err_flg = 0;
        switch($month) {
            case 2:
                //閏年チェック
                if ($year % 400 == 0) {
                    //西暦が400で割り切れる年は閏年
                    if($day > 29) $err_flg = 1;
                } else if ($year % 100 == 0) {
                    //400で割り切れない場合、100で割り切れる年は閏年ではないので28以上ならエラー
                    if ($day > 28) $err_flg = 1;
                } else if ($year % 4 == 0) {
                    // 100で割り切れない場合、西暦が4で割り切れる年は閏年
                    if($day > 29) $err_flg = 1;
                } else {
                    // 閏年ではない
                    if($day > 28) $err_flg = 1;
                }
                break;
            case 4:
            case 6:
            case 9:
            case 11:
                if ($day > 30) $err_flg = 1;
                break;
            default:
                if ($day > 31) $err_flg = 1;
                break;
        }
        return $err_flg;
    }
    /************************************
    * setter
    *************************************/
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoUser($DtoUser) {
        $this->DtoUser = $DtoUser;
    }
    function setUserDto($userDto) {
        $this->userDto = $userDto;
    }
    function setUserLogic($userLogic) {
        $this->userLogic = $userLogic;
    }
    function setAdminuserLogic($adminuserLogic) {
        $this->adminuserLogic = $adminuserLogic;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = $MdbUser;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setDtoConfigs($DtoConfigs) {
        $this->DtoConfigs = $DtoConfigs;
    }
    function setMdbGroups($MdbGroups) {
        $this->MdbGroups = $MdbGroups;
    }
    function setDtoGroups($DtoGroups) {
        $this->DtoGroups = $DtoGroups;
    }
    function setMdbGroupsusers($MdbGroupsusers) {
        $this->MdbGroupsusers = $MdbGroupsusers;
    }
    function setDtoGroupsusers($DtoGroupsusers) {
        $this->DtoGroupsusers = $DtoGroupsusers;
    }
    function setDtoSection($DtoSection) {
        $this->DtoSection = $DtoSection;
    }
    function setMdbSection($MdbSection) {
        $this->MdbSection = $MdbSection;
    }

    function setMdbUpload($MdbUpload) {
        $this->MdbUpload = $MdbUpload;
    }
    function setMdbCollect($MdbCollect) {
        $this->MdbCollect = $MdbCollect;
    }

    function setAll_readonly_flg($all_readonly_flg) {
        $this->all_readonly_flg = intval($all_readonly_flg);
    }
    function setGroupsusersLogic(&$GroupsusersLogic) {
        $this->GroupsusersLogic = & $GroupsusersLogic;
    }
    function setSubfolder($subfolder) {
        $this->subfolder = $subfolder;
    }
    function setMdbGroupsadminusers(&$MdbGroupsadminusers) {
        $this->MdbGroupsadminusers = & $MdbGroupsadminusers;
    }
    function setCapacityLogic(&$CapacityLogic) {
        $this->CapacityLogic = & $CapacityLogic;
    }
    function setStart_at_year($start_at_year) {
        $this->start_at_year = $start_at_year;
    }
    function setStart_at_month($start_at_month) {
        $this->start_at_month = $start_at_month;
    }
    function setStart_at_day($start_at_day) {
        $this->start_at_day = $start_at_day;
    }
    function setEnd_at_year($end_at_year) {
        $this->end_at_year = $end_at_year;
    }
    function setEnd_at_month($end_at_month) {
        $this->end_at_month = $end_at_month;
    }
    function setEnd_at_day($end_at_day) {
        $this->end_at_day = $end_at_day;
    }
    function setCommonList($CommonList) {
        $this->CommonList = $CommonList;
    }
    /************************************
    * getter
    *************************************/
    function getAccount() {
        return $this->DtoUser->getAccount();
    }
    function getUser_name() {
        return $this->DtoUser->getUser_name();
    }
    function getUser_kana() {
        return $this->DtoUser->getUser_kana();
    }
    function getMailaddr() {
        return $this->DtoUser->getMailaddr();
    }
    function getMax_file_size() {
        return $this->DtoUser->getMax_file_size();
    }
    function getExt_file_size() {
        return $this->DtoUser->getExt_file_size();
    }
    function getExt_unlimited_flg() {
        return $this->DtoUser->getExt_unlimited_flg();
    }
    function getTotal_file_size() {
        return $this->DtoUser->getMax_file_size() + $this->DtoUser->getExt_file_size();
    }
    function getMax_up_size() {
        return $this->DtoUser->getMax_up_size();
    }
    function getPasswd_cont() {
        $cont = "";
        if (!$this->userDto->getPasswd()) {
            $cont = "パスワード未設定";
        } else {
            for($i=0;$i<strlen($this->userDto->getPasswd());$i++) {
                $cont .= "*";
            }
        }
        return $cont;
    }
    function getPassword_change_flg() {
        return $this->DtoUser->getPassword_change_flg();
    }
    function getStart_at_year() {
        return $this->start_at_year;
    }
    function getStart_at_month() {
        return $this->start_at_month;
    }
    function getStart_at_day() {
        return $this->start_at_day;
    }
    function getEnd_at_year() {
        return $this->end_at_year;
    }
    function getEnd_at_month() {
        return $this->end_at_month;
    }
    function getEnd_at_day() {
        return $this->end_at_day;
    }
    function getMemo() {
        return $this->DtoUser->getMemo();
    }
    function getStop_flg() {
        return preg_match( '/^[01]$/', $this->DtoUser->getStop_flg() ) ?
            $this->DtoUser->getStop_flg() : '0';
    }
    function getAd_flg() {
        return preg_match( '/^[01]$/', $this->DtoUser->getAd_flg() ) ?
            $this->DtoUser->getAd_flg() : '0';
    }
    function getPassmin() {
        $this->MdbConfigs->getInfo($this->DtoConfigs,"pass_min_num");
        return $this->DtoConfigs->getValue();
    }
    function getPassmax() {
        $this->MdbConfigs->getInfo($this->DtoConfigs,"pass_max_num");
        return $this->DtoConfigs->getValue();
    }
    function getPassmust_span() {
        return $this->MdbConfigs->getValue("user_passmust_span");
        // admin_passmust_span
    }
    function getPass_min_num() {
        return $this->MdbConfigs->getValue("pass_min_num");
    }
    function getPass_max_num() {
        return $this->MdbConfigs->getValue("pass_max_num");
    }
    function getSection_id() {
        return $this->DtoGroupsusers->getSection_id();
    }
    function getIsSection($param) {
        $data = $param['data'];
        if (!is_array($data)) return false;
        $sec = $this->DtoGroupsusers->getSection_id();
        $flg = "";
        $cmp = array(""=>0,1=>1,2=>2,0=>3);
        if (is_array($sec)) foreach( $sec as $val ) {
            if (empty($data['section_readonly_flg'])){
                // このグループに所属している部署はない
                continue;
            }
            // ユーザがこの共有グループに部署で所属していたら権限上書き（新規なのでないはず）
            if ( isset($cmp[$data['section_readonly_flg'][$val]]) &&  $cmp[$data['section_readonly_flg'][$val]] > $cmp[$flg]) $flg=$data['section_readonly_flg'][$val];
        }
        return $flg;
    }
    function getSectionKeyList () {
        if ( $this->session->getParameter("login_admin_level") < 5 ) {
            return $this->MdbSection->getKeyList("",array("subadmin_editable_flg='1'"));
            //return $this->MdbSection->getKeyList("",array("owner_admin_id=".Db_Common::escape_string($this->session->getParameter("admin_id"))));
        } else {
            return $this->MdbSection->getKeyList();
        }
    }
    function getGroupsList() {
        return $this->adminuserLogic->getGroupsList(
            $this->DtoGroups, $this->DtoGroupsusers,
            $this->MdbGroups, $this->MdbGroupsusers, $this->MdbSection, $this->MdbGroupsadminusers,
            $this->session->getParameter("login_admin_level")
        );
    }
    function getType_List() {
        return array(
            "" => '<img src="images/ico-authority-none.gif" title="なし"/>なし',
            "1" => '<img src="images/ico-authority-r.gif" title="読み出しのみ"/>読み出しのみ',
            "2" => '<img src="images/ico-authority-rw.gif" title="読み／書き"/>読み／書き',
            "0" => '<img src="images/ico-authority-rwx.gif" title="読み／書き／フォルダ操作"/>読み／書き／フォルダ操作',
        );
    }
    function getType_ListImg() {
        return array(
            "" => '<img src="images/ico-authority-none.gif" title="なし"/>',
            "1" => '<img src="images/ico-authority-r.gif" title="読み出しのみ"/>',
            "2" => '<img src="images/ico-authority-rw.gif" title="読み／書き"/>',
            "0" => '<img src="images/ico-authority-rwx.gif" title="読み／書き／フォルダ操作"/>',
        );
    }
    function getAll_readonly_flg() {
        return $this->all_readonly_flg;
    }
    function getSubfolder() {
        return ($this->subfolder=="1" ? "1":"0");
    }
    function getApi_flg() {
        return preg_match( '/^[01]$/', $this->DtoUser->getApi_flg() ) ?
            $this->DtoUser->getApi_flg() : '0';
    }
    function getFile_delete_limit_dt() {
        return $this->DtoUser->getFile_delete_limit_dt();
    }
    function getSend_flg() {
        return preg_match( '/^[01]$/', $this->DtoUser->getSend_flg() ) ?
            $this->DtoUser->getSend_flg() : '0';
    }
    function getCollect_flg() {
        return preg_match( '/^[01]$/', $this->DtoUser->getCollect_flg() ) ?
            $this->DtoUser->getCollect_flg() : '0';
    }
    function getOpen_folder_flg() {
        return preg_match( '/^[01]$/', $this->DtoUser->getOpen_folder_flg() ) ?
            $this->DtoUser->getOpen_folder_flg() : '0';
    }
    function getDelivery_flg() {
        return preg_match( '/^[01]$/', $this->DtoUser->getDelivery_flg() ) ?
            $this->DtoUser->getDelivery_flg() : '0';
    }
    function getGathering_flg() {
        return preg_match( '/^[01]$/', $this->DtoUser->getGathering_flg() ) ?
            $this->DtoUser->getGathering_flg() : '0';
    }

    function getUsage_auth_types() {
        return $this->CommonList->getUsageAuthTypes();
    }
    function getUsage_auth_types_space() {
        $usage_auth_types = $this->CommonList->getUsageAuthTypes();
        foreach ($usage_auth_types as $key => $value) {
            $usage_auth_types_space[$key] = " " . $value;
        }
        return $usage_auth_types_space;
    }
    function getAuth_type() {
        return $this->DtoUser->getAuth_type();
    }
    function getGoogle_mail_address() {
        return $this->DtoUser->getGoogle_mail_address();
    }
    function getSaml_mail_address() {
        return $this->DtoUser->getSaml_mail_address();
    }
    function getDel_span() {
        $this->MdbConfigs->getInfo($this->DtoConfigs, "del_span");
        return $this->DtoConfigs->getValue();
    }
    function getAll_auth_types() {
        return $this->CommonList->getAllAuthTypes();
    }
}
?>

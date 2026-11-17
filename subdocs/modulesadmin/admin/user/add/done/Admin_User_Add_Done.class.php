<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] ユーザ管理 - ユーザ登録(完了)
 * @updater
 *
 * @package     WebFile
 * @version
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_User_Add_Done {
    var $session;

    var $userDto;
    var $DtoUser;
    var $MdbUser;
    var $userLogic;
    var $adminuserLogic;

    var $DtoConfigs;
    var $MdbConfigs;

    var $DtoGroups;
    var $MdbGroups;

    var $DtoGroupsusers;
    var $MdbGroupsusers;
    var $GroupsusersLogic;
    var $MdbGroupsadminusers;

    var $DtoSection;
    var $MdbSection;

    var $DtoSectionusers;
    var $MdbSectionusers;

    var $DtoLog;
    var $MdbLog;

    var $MdbFolder;
    var $GroupLogic;

    var $MdbUpload;
    var $MdbCollect;
    var $CapacityLogic;

    var $all_readonly_flg;
    var $subfolder;

    var $CommonList;
    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_User_Add_Done","Admin_User_Add_Done#execute");

        $cnt = $this->userLogic->getUserActiveCont($this->DtoUser, $this->MdbUser);

        if (MAX_USER != 0) {
            if (MAX_USER <= $cnt) {
                return "error";
            }
        }

        // 必須項目チェック
        if (!$this->adminuserLogic->checkRequiredAdd($this->DtoUser)) {
            return "add_error";
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
        if (!$this->adminuserLogic->checkAuth($this->DtoUser, $this->CommonList)) {
            return "input";
        }
        // 認証方式によって、アカウント重複チェック
        if (!$this->adminuserLogic->checkAccount($this->DtoUser,$this->MdbUser)) {
            return "input";
        }
        // メールアドレス重複チェック
        if (!$this->adminuserLogic->checkMailaddr($this->DtoUser,$this->MdbUser)) {
            return "input";
        }
        $this->DtoUser->setUser_id(null);
        if (!$this->adminuserLogic->checkSize($this->DtoUser,$this->MdbUser, $this->CapacityLogic)) {
            return "input";
        }
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
        if (!$this->GroupsusersLogic->checkExclusiveSectionUser($this->DtoGroupsusers,$this->DtoUser,$this->MdbGroupsadminusers,$this->MdbGroupsusers) ) {
            return "input";
        }

        // Logのため、容量値を確保
        $max_file_size_input = "";
        $max_file_size_input = $this->DtoUser->getMax_file_size();
        $ext_file_size_input = "";
        $ext_file_size_input = $this->DtoUser->getExt_file_size();
        $max_up_size_input   = "";
        $max_up_size_input   = $this->DtoUser->getMax_up_size();

        // ユーザ登録
        $this->DtoUser->setOwner_admin_id( $this->session->getParameter("admin_id") );

        // 2012-11-19 @yano configsテーブルからセット
        $this->DtoUser->setMail_header_flg($this->MdbConfigs->getDirectValue('mail_header_flg'));
        $this->DtoUser->setMail_footer_flg($this->MdbConfigs->getDirectValue('mail_footer_flg'));
        $this->adminuserLogic->add($this->DtoUser,$this->MdbUser);

        // ユーザ部署削除
        $where = array();
        $where[] = "user_id = " . Db_Common::escape_string($this->DtoUser->getUser_id());
        $this->MdbSectionusers->deleteWhere($where);

        // 部署登録
        $log_section = "";
        $sectionkeylist = $this->getSectionKeyList();
        $sec2 = array();
        if (is_array($this->DtoGroupsusers->getSection_id())) {
            $sec2 = $this->DtoGroupsusers->getSection_id();
            foreach ($this->DtoGroupsusers->getSection_id() as $section_id) {
                // 部署登録
                $this->DtoSectionusers->setSection_id($section_id);
                $this->DtoSectionusers->setUser_id($this->DtoUser->getUser_id());
                $this->DtoSectionusers->setLeader_flg(0);
                $this->DtoSectionusers->setHistory_open_flg(0);
                $this->MdbSectionusers->insert($this->DtoSectionusers);
                // 排他制御
                $this->GroupsusersLogic->excluseSectionUesr($this->MdbGroupsusers,$section_id,$this->DtoUser->getUser_id());
                // ログ
                $log_section.= "　　".$sectionkeylist[$section_id]."\n";
            }
        }

        // グループユーザ削除
        //$this->MdbGroupsusers->delete2($this->DtoUser->getUser_id());
        $where = array();
        $where[] = "user_id = " . Db_Common::escape_string($this->DtoUser->getUser_id());
        if ($this->subfolder!="1") {
            //サブフォルダ指定なし -> トップのみ
            $where[] = "top_flg = 1";
        }
        $this->MdbGroupsusers->deleteWhere($where);

        // Log用
        $log_share_groups = "";
        $groupslist   = $this->getGroupsList();
        $type_list    = $this->getType_List();
        foreach($groupslist as $key => $val) {
            // "権限なし"以外のみ
            if ($val['readonly_flg'] !== "") {
                // グループユーザ登録
                $this->DtoGroupsusers->setGroup_id($val['id']);
                $this->DtoGroupsusers->setUser_section_id(0);
                $this->DtoGroupsusers->setUser_id($this->DtoUser->getUser_id());
                $this->DtoGroupsusers->setSection_id(0);
                $this->DtoGroupsusers->setFolder_id($val['folder_id']);
                $this->DtoGroupsusers->setTop_flg(1);
                $this->DtoGroupsusers->setReadonly_flg($val['readonly_flg']);
                $this->MdbGroupsusers->add($this->DtoGroupsusers);
                //サブフォルダも更新
                if ($this->subfolder=="1") {
                    // サブフォルダのIDを取り出す。
                    $dir = array();
                    $dir = $this->GroupLogic->makelist($this->MdbFolder,$val['folder_id']);
                    // メンバー登録
                    foreach($dir as $dkey => $dval) {
                        // 更新（追加）
                        $this->DtoGroupsusers->setTop_flg(0);
                        $this->DtoGroupsusers->setFolder_id($dval['folder_id']);
                        $this->MdbGroupsusers->add($this->DtoGroupsusers);
                    }
                }
            }
            $flg2 = null;
            $com2 = array(""=>0, "1"=>1, "2"=>2, "0"=>3);
            if (is_array($val['section_readonly_flg'])) foreach ($val['section_readonly_flg'] as $key2 => $val2) {
                if (in_array($key2, $sec2)) {
                    if ($com2[$flg2] < $com2[$val2]) {
                        $flg2 = $val2;
                    }
                }
            }
            $log_read_type = "";
            if ($flg2 === "") {
                $log_read_type = "なし";
            } elseif ($flg2 === "1") {
                $log_read_type = "読み出しのみ";
            } elseif ($flg2 === "2") {
                $log_read_type = "読み/書き";
            } elseif ($flg2 === "0") {
                $log_read_type = "読み/書き/フォルダ操作";
            } else {
                if ($val['readonly_flg'] === "") {
                    $log_read_type = "なし";
                } elseif ($val['readonly_flg'] === "1") {
                    $log_read_type = "読み出しのみ";
                } elseif ($val['readonly_flg'] === "2") {
                    $log_read_type = "読み/書き";
                } elseif ($val['readonly_flg'] === "0") {
                    $log_read_type = "読み/書き/フォルダ操作";
                }
            }
            $log_share_groups.= "　　".$val['name'].'：'.$log_read_type."\n";
        }
        if (mb_strlen($log_share_groups) == 0) {
            $log_share_groups = "　　なし\n";
        }

        // Log
        $history_id = 101; // 101:ユーザ管理＞ユーザ追加
        $memo  = "";
        $memo .= "追加ユーザ\n";
        $usage_auth_types = $this->CommonList->getUsageAuthTypes();
        if (1 < count($usage_auth_types)) {
            $memo .= "　認証方式：".$usage_auth_types[$this->DtoUser->getAuth_type()]."\n";
        }
        $memo .= "　アカウント：".$this->DtoUser->getAccount()."\n";
        if ($this->DtoUser->getPassword_change_flg() === "1") {
            $memo .= "　パスワード：初回ログイン時に変更させる"."\n";
        }
        $memo .= "　氏名：".$this->DtoUser->getUser_name()." ";
        $memo .= "(".$this->DtoUser->getUser_kana().")\n ";
        $memo .= "　メールアドレス：".$this->DtoUser->getMailaddr()."\n";
        if (EXTRA_SPACE_FLG) {
            $memo .= "　容量：\n";
            $memo .= "　　基本：".$max_file_size_input."MB\n";
            $memo .= "　　拡張：";
            if ($this->DtoUser->getExt_unlimited_flg()) {
                $memo .= "無制限\n";
            } else {
                $memo .= $ext_file_size_input."MB\n";
            }
        } else {
            $memo .= "　容量：".$max_file_size_input."MB\n";
        }
        $memo .= "　アップロード容量：".$max_up_size_input."MB\n";

        $memo .= "　定期削除の個別設定：";
        if ($this->DtoUser->getFile_delete_limit_dt()
            && $this->DtoUser->getFile_delete_limit_dt() != 0) {
            $memo .= $this->DtoUser->getFile_delete_limit_dt()."日前のファイルを削除\n";
        } else {
            $memo .= "個別の定期削除は行わない\n";
        }

        $memo .= "　配布機能：";
        if ($this->DtoUser->getSend_flg() == '1') {
            $memo .= "使用する";
        } else {
            $memo .= "使用しない";
        }
        $memo .= "\n";
        $memo .= "　引取り機能：";
        if ($this->DtoUser->getCollect_flg() == '1') {
            $memo .= "使用する";
        } else {
            $memo .= "使用しない";
        }
        $memo .= "\n";
        $memo .= "　フォルダ公開機能：";
        if ($this->DtoUser->getOpen_folder_flg() == '1') {
            $memo .= "使用する";
        } else {
            $memo .= "使用しない";
        }
        $memo .= "\n";

        if (defined("DELIVERY_FLG") && DELIVERY_FLG) {
            $memo.= "　一斉配信機能：";
            if ($this->DtoUser->getDelivery_flg() == '1') {
                $memo.= "使用する";
            } else {
                $memo.= "使用しない";
            }
            $memo.= "\n";
        }
        if (defined("GATHERING_FLG") && GATHERING_FLG) {
            $memo.= "　一斉収集機能：";
            if ($this->DtoUser->getGathering_flg() == '1') {
                $memo.= "使用する";
            } else {
                $memo.= "使用しない";
            }
            $memo.= "\n";
        }

        if (API_FLG) {
            $memo.= "　".API_CLIENT_NAME."機能の利用：";
            if ($this->DtoUser->getApi_flg() == '1') {
                $memo.= "利用する";
            } else {
                $memo.= "利用しない";
            }
            $memo.= "\n";
        }
        if (TERM_OF_VALIDITY) {
            $memo .= "　有効期限（開始）：";
            $dt = $this->DtoUser->getStart_dt();
            if ($dt != NULL) {
                list($year,$month,$day) = explode("-",$dt);
                $memo .= $year."年".abs($month)."月".abs($day)."日\n";
            } else {
                $memo .= "未設定\n";
            }

            $memo .= "　有効期限（終了）：";
            $dt = $this->DtoUser->getEnd_dt();
            if ($dt != NULL) {
                list($year,$month,$day) = explode("-",$dt);
                $memo .= $year."年".abs($month)."月".abs($day)."日\n";
            } else {
                $memo .= "未設定\n";
            }
        }
        $memo .= "　メモ：".$this->DtoUser->getMemo()."\n";
        if (defined("AD_FLG") && AD_FLG) {
            if ($this->DtoUser->getAd_flg() == '1') {
                $memo .= "　AD連携：する\n";
            } else {
                $memo .= "　AD連携：しない\n";
            }
        }
        if ($this->DtoUser->getStop_flg() == '1') {
            $memo .= "　状態：利用停止\n";
        } else {
            $memo .= "　状態：利用開始\n";
        }
        if (SHARE_GROUP_FLG) {
            $memo .= "　所属部署：\n".$log_section;
            $memo .= "　グループアクセス権限：\n".$log_share_groups;
        }
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        //sessionを削除
        $this->session->removeParameter("auth_type");
        $this->session->removeParameter("account");
        $this->session->removeParameter("google_mail_address");
        $this->session->removeParameter("saml_mail_address");
        $this->session->removeParameter("mailaddr");
        $this->session->removeParameter("user_name");
        $this->session->removeParameter("user_kana");
        $this->session->removeParameter("password");
        $this->session->removeParameter("max_file_size");
        $this->session->removeParameter("ext_file_size");
        $this->session->removeParameter("ext_unlimited_flg");
        $this->session->removeParameter("max_up_size");
        $this->session->removeParameter("start_dt");
        $this->session->removeParameter("end_dt");
        $this->session->removeParameter("memo");
        $this->session->removeParameter("stop_flg");
        $this->session->removeParameter("ad_flg");
        $this->session->removeParameter("readonly_flg");
        $this->session->removeParameter("section_id");
        $this->session->removeParameter("api_flg");
        $this->session->removeParameter("file_delete_limit_dt");
        $this->session->removeParameter("send_flg");
        $this->session->removeParameter("collect_flg");
        $this->session->removeParameter("open_folder_flg");
        $this->session->removeParameter("delivery_flg");
        $this->session->removeParameter("gathering_flg");

        return "success";
    }
    /************************************
    * setter
    *************************************/
    function setSession($session) {
        $this->session = $session;
    }
    function setUserDto($userDto) {
        $this->userDto = $userDto;
    }
    function setDtoUser($DtoUser) {
        $this->DtoUser = $DtoUser;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = $MdbUser;
    }
    function setUserLogic($userLogic) {
        $this->userLogic = $userLogic;
    }
    function setAdminuserLogic($adminuserLogic) {
        $this->adminuserLogic = $adminuserLogic;
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

    function setDtoGroupsusers($DtoGroupsusers) {
        $this->DtoGroupsusers = $DtoGroupsusers;
    }
    function setMdbGroupsusers($MdbGroupsusers) {
        $this->MdbGroupsusers = $MdbGroupsusers;
    }
    function setGroupsusersLogic(&$GroupsusersLogic) {
        $this->GroupsusersLogic = & $GroupsusersLogic;
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

    function setDtoLog($DtoLog) {
        $this->DtoLog = $DtoLog;
    }
    function setMdbLog($MdbLog) {
        $this->MdbLog = $MdbLog;
    }
    function setMdbFolder(&$MdbFolder) {
        $this->MdbFolder = & $MdbFolder;
    }
    function setGroupLogic(&$GroupLogic) {
        $this->GroupLogic = & $GroupLogic;
    }
    function setMdbGroupsadminusers(&$MdbGroupsadminusers) {
        $this->MdbGroupsadminusers = & $MdbGroupsadminusers;
    }
    function setMdbUpload(&$MdbUpload) {
        $this->MdbUpload = & $MdbUpload;
    }
    function setMdbCollect(&$MdbCollect) {
        $this->MdbCollect = & $MdbCollect;
    }
    function setCapacityLogic(&$CapacityLogic) {
        $this->CapacityLogic = & $CapacityLogic;
    }
    function setAll_readonly_flg($all_readonly_flg) {
        $this->all_readonly_flg = $all_readonly_flg;
    }
    function setSubfolder($subfolder) {
        $this->subfolder = $subfolder;
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
    function getMax_up_size() {
        return $this->DtoUser->getMax_up_size();
    }
    function getPasswd_cont() {
        $cont = "";
        for($i=0;$i<strlen($this->userDto->getPasswd());$i++) {
            $cont .= "*";
        }
        return $cont;
    }
    function getMemo() {
        return $this->DtoUser->getMemo();
    }
    function getStop_flg() {
        return $this->DtoUser->getStop_flg();
    }
    function getAd_flg() {
        return $this->DtoUser->getAd_flg();
    }
    function getPassmin()
    {
        $this->MdbConfigs->getInfo($this->DtoConfigs,"pass_min_num");
        return $this->DtoConfigs->getValue();
    }
    function getPassmax()
    {
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
    function getSectionKeyList () {
        return $this->MdbSection->getKeyList();
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
    function getSend_flg() {
        return $this->DtoUser->getSend_flg();
    }
    function getCollect_flg() {
        return $this->DtoUser->getCollect_flg();
    }
    function getOpen_folder_flg() {
        return $this->DtoUser->getOpen_folder_flg();
    }
    function getDelivery_flg() {
        return $this->DtoUser->getDelivery_flg();
    }
    function getGathering_flg() {
        return $this->DtoUser->getGathering_flg();
    }
    function getUsage_auth_types() {
        return $this->CommonList->getUsageAuthTypes();
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
}
?>

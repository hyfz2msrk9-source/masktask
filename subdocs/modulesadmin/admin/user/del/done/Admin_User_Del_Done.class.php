<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] ユーザ管理 - ユーザ削除(完了)
 * @updater     Hiroko Yanai（2019/07/26）上長を削除したら、承認待ちは全て却下する
 *
 * @package     WebFile
 * @version     3.17.0
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_User_Del_Done {
    var $session;
    var $DtoResendMail;
    var $MdbResendMail;
    var $DtoUser;
    var $MdbUser;
    var $MdbAddr;
    var $DtoAddr;
    var $adminuserLogic;
    var $DtoLog;
    var $MdbLog;
    var $MdbUpload;
    var $DtoUpload;
    var $MdbCollect;
    var $DtoCollect;
    var $MdbGroupsUsers;
    var $MdbFolder;
    var $DtoFolder;
    var $MdbTag;
    var $DtoTag;
    var $MdbAddrGroup;
    var $DtoAddrGroup;
    var $MdbAddrGroupUsers;
    var $MdbSign;
    var $DtoSign;
    var $MdbTemplate;
    var $Dtotemplate;
    var $MdbGroups;
    var $MdbConfigs;
    var $DtoConfigs;
    var $MdbUserSuperior;
    var $DtoUserSuperior;
    var $DtoSection;
    var $MdbSection;
    var $DtoSectionusers;
    var $MdbSectionusers;
    var $MdbGroupsadminusers;
    var $DtoDomain;
    var $MdbDomain;
    var $MdbReportmail;
    var $CommonList;
    var $UserLogic;
    var $MdbSuperiorSend;
    var $MdbSuperiorMulti;

    var $rejected_flg;
    var $MdbDelivery;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_User_Del_Done","Admin_User_Del_Done#execute");

        $info = $this->MdbUser->getInfo($this->DtoUser->getUser_id());
        if (!$info) {
            // 存在しないID
            return "input";
        }
        // 既に削除されている
        if ($info['del_flg'] == '1') {
            return "input";
        }

        // ユーザの所属部署を取得
        $log_section = "";
        $sectionkeylist = $this->getSectionKeyList();
        $where = array();
        $where[] = "user_id = " . Db_Common::escape_string($this->DtoUser->getUser_id());
        $list = $this->MdbSectionusers->getSectionusersList($where);
        foreach ($list as $val) {
            $log_section.= "　　".$sectionkeylist[$val['section_id']]."\n";
        }

        // Log用
        $log_share_groups = "";
        $list = $this->MdbGroups->getJoinList2($this->DtoUser->getUser_id());
        $type_list = $this->getType_List();
        foreach ($list as $key => $val) {
            if ($val['readonly_flg'] === "") {
                $log_read_type = "なし";
            } elseif ($val['readonly_flg'] == "1") {
                $log_read_type = "読み出しのみ";
            } elseif ($val['readonly_flg'] == "2") {
                $log_read_type = "読み/書き";
            } else {
                $log_read_type = "読み/書き/フォルダ操作";
            }
            $log_share_groups.= "　　".$val['name'].'：'.$log_read_type."\n";
        }
        if (mb_strlen($log_share_groups) == 0) {
            $log_share_groups = "　　なし\n";
        }

        $this->rejected_flg = $this->adminuserLogic->deleteUser(
            $this->DtoUser, $this->MdbUser
            ,$this->MdbGroupsUsers
            ,$this->MdbSectionusers
            ,$this->MdbGroupsadminusers
            ,$this->MdbUserSuperior
            ,$this->MdbUpload, $this->DtoUpload
            ,$this->MdbCollect, $this->DtoCollect
            ,$this->MdbFolder, $this->DtoFolder
            ,$this->MdbTag, $this->DtoTag
            ,$this->MdbAddr, $this->DtoAddr
            ,$this->MdbAddrGroup, $this->DtoAddrGroup
            ,$this->MdbAddrGroupUsers
            ,$this->MdbSign, $this->DtoSign
            ,$this->MdbTemplate, $this->DtoTemplate
            ,$this->MdbResendMail, $this->DtoResendMail
            ,$this->MdbDomain, $this->DtoDomain
            ,$this->MdbReportmail
            , $this->UserLogic
            , $this->DtoUserSuperior
            , $this->MdbSuperiorSend
            ,$this->MdbSuperiorMulti
            ,$this->MdbDelivery
        );

        // Logのため、容量値を確保
        $max_file_size_input = "";
        $max_file_size_input = $this->DtoUser->getMax_file_size();
        $ext_file_size_input = "";
        $ext_file_size_input = $this->DtoUser->getExt_file_size();
        $max_up_size_input = 0;
        if ($this->DtoUser->getMax_up_size()) {
            $max_up_size_input = $this->DtoUser->getMax_up_size();
        }

        // Log
        $history_id = 104; // 104:ユーザ管理＞ユーザの削除
        $memo  = "";
        $memo  = "削除ユーザ\n";
        $usage_auth_types = $this->CommonList->getAllAuthTypes();
        if (1 < count($usage_auth_types)) {
            if ($this->DtoUser->getSso_flg()) {
                $auth_type = 1;
            } elseif ($this->DtoUser->getSaml_flg()) {
                $auth_type = 2;
            } else {
                $auth_type = 0;
            }
            $memo .= "　認証方式：".$usage_auth_types[$auth_type]."\n";
        }
        $memo .= "　アカウント：".$this->DtoUser->getAccount()."\n";

        if ( TWO_STEP_AUTH_FLG) {
            $user_info = $this->MdbUser->getInfo($this->DtoUser->getUser_id());
            $two_step_auth_flg = $this->MdbConfigs->getDirectValue("two_step_auth_flg");
            $two_facotor_auth_is_enabled =
                !is_null( $user_info['two_step_auth_secret_key'] );
            $memo .= '　二要素認証機能：';
            if ($two_facotor_auth_is_enabled) {
              if ($this->DtoUser->getTwo_step_auth_disable_flg()) {
                $memo .= '使用しない';
              } else {
                $memo .= '認証アプリで使用する';
              }
            } else if ( $two_step_auth_flg == "2" ) {
                $memo .= 'メールで使用する';
            } else {
              $memo .= '使用しない';
            }
            $memo .= "\n";
        }

        $memo .= "　氏名：".$this->DtoUser->getUser_name()." ";
        $memo .= "(".$this->DtoUser->getUser_kana().")\n ";
        $memo .= "　メールアドレス：".$this->DtoUser->getMailaddr()."\n";
        if (EXTRA_SPACE_FLG) {
            $memo .= "　容量：\n";
            $memo .= "　　基本：".($max_file_size_input / 1024 / 1024)."MB\n";
            $memo .= "　　拡張：";
            if ($this->DtoUser->getExt_unlimited_flg()) {
                $memo .= "無制限\n";
            } else {
                $memo .= ($ext_file_size_input / 1024 / 1024)."MB\n";
            }
        } else {
            $memo .= "　容量：".($max_file_size_input / 1024 / 1024)."MB\n";
        }
        $memo .= "　アップロード容量：".($max_up_size_input / 1024 / 1024)."MB\n";

        $memo .= "　定期削除の個別設定：";
        if ($this->DtoUser->getFile_delete_limit_dt()
            && $this->DtoUser->getFile_delete_limit_dt() != 0) {
            $memo .= $this->DtoUser->getFile_delete_limit_dt()."日前のファイルを削除\n";
        } else {
            $memo .= "個別の定期削除は行わない\n";
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
        $memo.= "　配布機能：";
        if ($this->DtoUser->getSend_flg() == '1') {
            $memo.= "使用する";
        } else {
            $memo.= "使用しない";
        }
        $memo.= "\n";
        $memo.= "　引取り機能：";
        if ($this->DtoUser->getCollect_flg() == '1') {
            $memo.= "使用する";
        } else {
            $memo.= "使用しない";
        }
        $memo.= "\n";
        $memo.= "　フォルダ公開機能：";
        if ($this->DtoUser->getOpen_folder_flg() == '1') {
            $memo.= "使用する";
        } else {
            $memo.= "使用しない";
        }
        $memo.= "\n";
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


        if (TERM_OF_VALIDITY) {
            $dt = $this->DtoUser->getStart_dt();
            $memo .= "　有効期限（開始）：";
            if ($dt) {
                    list($year,$month,$day) = explode("-",$dt);
                    $memo .= $year."年".abs($month)."月".abs($day)."日\n";
            } else {
                    $memo .= "未設定\n";
            }

            $dt = $this->DtoUser->getEnd_dt();
            $memo .= "　有効期限（終了）：";
            if ($dt) {
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
        if ($this->MdbConfigs->getDirectValue("user_account_lock_cnt") > 0) {
          if ($this->DtoUser->getAccount_lock_flg() == '1') {
              $memo .= "　アカウントロック：アカウントロック中";
          } else {
              $memo .= "　アカウントロック：アカウントロック解除中";
          }
          $memo.= "\n";
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

        return "success";
    }
    /************************************
    * setter
    *************************************/
    function setSession($session) {
        $this->session = $session;
    }
    function setMdbResendMail(&$MdbResendMail) {
        $this->MdbResendMail =& $MdbResendMail;
    }
    function setDtoResendMail(&$DtoResendMail) {
        $this->DtoResendMail =& $DtoResendMail;
    }
    function setDtoUser($DtoUser) {
        $this->DtoUser = $DtoUser;
    }
    function setAdminuserLogic($adminuserLogic) {
        $this->adminuserLogic = $adminuserLogic;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = $MdbUser;
    }
    function setMdbAddr($MdbAddr) {
        $this->MdbAddr = $MdbAddr;
    }
    function setDtoAddr ( &$DtoAddr ) {
        $this->DtoAddr =& $DtoAddr;
    }
    function setDtoLog ( &$DtoLog ) {
        $this->DtoLog =& $DtoLog;
    }
    function setMdbLog ( &$MdbLog ) {
        $this->MdbLog =& $MdbLog;
    }
    function setMdbUpload($MdbUpload) {
        $this->MdbUpload = $MdbUpload;
    }
    function setDtoUpload($DtoUpload) {
        $this->DtoUpload = $DtoUpload;
    }
    function setMdbCollect($MdbCollect) {
        $this->MdbCollect = $MdbCollect;
    }
    function setDtoCollect($DtoCollect) {
        $this->DtoCollect = $DtoCollect;
    }
    function setMdbGroupsUsers($MdbGroupsUsers) {
        $this->MdbGroupsUsers = $MdbGroupsUsers;
    }
    function setMdbFolder($MdbFolder) {
        $this->MdbFolder = $MdbFolder;
    }
    function setDtoFolder($DtoFolder) {
        $this->DtoFolder = $DtoFolder;
    }
    function setMdbTag($MdbTag) {
        $this->MdbTag = $MdbTag;
    }
    function setDtoTag($DtoTag) {
        $this->DtoTag = $DtoTag;
    }
    function setMdbAddrGroup($MdbAddrGroup) {
        $this->MdbAddrGroup = $MdbAddrGroup;
    }
    function setDtoAddrGroup($DtoAddrGroup) {
        $this->DtoAddrGroup = $DtoAddrGroup;
    }
    function setMdbAddrGroupUsers($MdbAddrGroupUsers) {
        $this->MdbAddrGroupUsers = $MdbAddrGroupUsers;
    }
    function setMdbSign($MdbSign) {
        $this->MdbSign = $MdbSign;
    }
    function setDtoSign($DtoSign) {
        $this->DtoSign = $DtoSign;
    }
    function setMdbTemplate($MdbTemplate) {
        $this->MdbTemplate = $MdbTemplate;
    }
    function setDtoTemplate($DtoTemplate) {
        $this->DtoTemplate = $DtoTemplate;
    }
    function setMdbGroups($MdbGroups) {
        $this->MdbGroups = $MdbGroups;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setDtoConfigs($DtoConfigs) {
        $this->DtoConfigs = $DtoConfigs;
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
    function setMdbGroupsadminusers($MdbGroupsadminusers) {
        $this->MdbGroupsadminusers = $MdbGroupsadminusers;
    }
    function setMdbUserSuperior($MdbUserSuperior) {
        $this->MdbUserSuperior = & $MdbUserSuperior;
    }
    function setDtoUserSuperior($DtoUserSuperior) {
        $this->DtoUserSuperior = & $DtoUserSuperior;
    }
    function setMdbDomain($MdbDomain) {
        $this->MdbDomain = & $MdbDomain;
    }
    function setDtoDomain($DtoDomain) {
        $this->DtoDomain = & $DtoDomain;
    }
    function setMdbReportmail($MdbReportmail) {
        $this->MdbReportmail = $MdbReportmail;
    }
    function setCommonList($CommonList) {
        $this->CommonList =& $CommonList;
    }
    function setUserLogic($UserLogic) {
        $this->UserLogic = $UserLogic;
    }
    function setMdbSuperiorSend($MdbSuperiorSend) {
        $this->MdbSuperiorSend = $MdbSuperiorSend;
    }
    function setMdbSuperiorMulti($MdbSuperiorMulti) {
        $this->MdbSuperiorMulti = $MdbSuperiorMulti;
    }
    function setMdbDelivery($MdbDelivery) {
        $this->MdbDelivery = $MdbDelivery;
    }

    // getter
    function getSectionKeyList () {
        return $this->MdbSection->getKeyList();
    }
    function getType_List() {
        return array("" => "権限なし", "1" => "RO", "2" => "RW", "0" => "+RW");
    }
    function getRejected_flg(){
        return $this->rejected_flg;
    }
}
?>

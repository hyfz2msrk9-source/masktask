<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] ユーザ管理 - ユーザ編集(確認)
 * @updater     Hiroko Yanai（2018/04/11）SAML認証(OneLogin)に対応
 *
 * @package     WebFile
 * @version     3.15.2
 * @author
 * @copyright   KeyPoint
 * @access      public
 */

class Admin_User_Edit_Confirm {
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

    var $DtoSectionusers;
    var $MdbSectionusers;

    var $MdbUserSuperior;
    var $DtoUserSuperior;

    var $MdbUpload;
    var $MdbCollect;
    var $CapacityLogic;

    var $MdbAdminuser;
    var $DtoAdminuser;
    var $AdminuserDto;
    var $AdminadminLogic;

    var $all_readonly_flg;
    var $subfolder;

    var $start_at_year;
    var $start_at_month;
    var $start_at_day;

    var $end_at_year;
    var $end_at_month;
    var $end_at_day;


    //Log用
    var $log_auth_type;
    var $log_account;
    var $log_google_mail_address;
    var $log_saml_mail_address;
    var $log_mailaddr;
    var $log_user_name;
    var $log_user_kana;
    var $log_max_file_size;
    var $log_ext_file_size;
    var $log_ext_unlimited_flg;
    var $log_max_up_size;
    var $log_memo;
    var $log_stop_flg;
    var $log_ad_flg;
    var $log_account_lock_flg;
    var $log_section;
    var $log_start_dt;
    var $log_end_dt;
    var $log_owner_admin_id;
    var $log_password_change_flg;
    var $log_api_flg;
    var $log_file_delete_limit_dt;
    var $log_send_flg;
    var $log_collect_flg;
    var $log_open_folder_flg;
    var $log_delivery_flg;
    var $log_gathering_flg;

    var $CommonList;
    var $TemplateLogic;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_User_Edit_Confirm","Admin_User_Edit_Confirm#execute");

        // エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        // 対象ユーザがアクティブユーザかのチェック
        $active_user = null;
        $info = $this->getUserInfo();
        $active_user = $this->userLogic->checkActiveUser($info);

        if (!$info) {
            //存在しないID
            return "error";
        }
        // 既に削除されている
        if ($info['del_flg'] == '1') {
            return "error";
        }
        // 編集権限なし
        if ($this->session->getParameter("login_admin_level") == "3"  &&  $info['owner_admin_id'] != $this->session->getParameter("admin_id")) {
            $errorList->add("Admin_User_Edit_Confirm::error","このアカウントで追加したユーザではないため、編集できません。");
            return "input";
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
        if (!$this->adminuserLogic->checkSize($this->DtoUser,$this->MdbUser, $this->CapacityLogic,$active_user)) {
            return "input";
        }
        if (!$this->DtoUser->getMax_up_size()) {
            $this->DtoUser->setMax_up_size(0);
        } else if ($this->DtoUser->getMax_up_size() < 0.01) {
            $errorList->add("Admin_User_Edit_Confirm","ご希望のアップロード容量を0.01MB以上で入力してください。");
            return "input";
        }
        if (!$this->adminuserLogic->checkUpSize($this->DtoUser)) {
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
        // API(Synchrorer)、二要素認証、AD連携のチェック。SSO認証とSAML認証(OneLogin)の場合は、0にする
        if (!$this->adminuserLogic->checkApi_flg($this->DtoUser, $this->MdbUser)) {
            return "input";
        }
        if (!$this->adminuserLogic->checkTwo_step_auth_disable_flg($this->DtoUser) ) {
            return "input";
        }
        if (!$this->adminuserLogic->checkAd_flg($this->DtoUser)) {
            return "input";
        }
        // 次回ログイン時のパスワード変更、ログイン情報再送のチェック。SSO認証とSAML認証(OneLogin)の場合は、0にする
        if (!$this->adminuserLogic->checkPassword_change_flg($this->DtoUser)) {
            return "input";
        }
        if (!$this->adminuserLogic->checkReinfo($this->DtoUser, $this->TemplateLogic)) {
            return "input";
        }
        if ($this->DtoUser->getAuth_type() == 0 && $this->DtoUser->getReinfo() == 0) {
            // パスワード認証なのに
            // ログイン情報再送(パスワードを初期化)しない場合は、パスワードルールをチェックする
            if ($this->log_auth_type != 0 && !$this->userDto->getPasswd()) {
                // SSO認証かSAML認証->パスワード認証
                $errorList->add("Admin_User_Edit_Confirm","ご希望のパスワードを入力してください。");
                return "input";
            }

            if ($this->userDto->getPasswd()) {
                if (!$this->adminuserLogic->checkPasswordStr($this->userDto->getPasswd(),$this->MdbConfigs,$this->DtoConfigs)) {
                    return "input";
                }
                if (!$this->adminuserLogic->checkPasswordRule($this->userDto->getPasswd(),$this->MdbConfigs,$this->DtoConfigs)) {
                    return "input";
                }
                // 管理画面なので、前回と同じパスワードかどうかのチェックは、行っていません。

                // アカウントと同じじゃないかどうか
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

        if (!$this->GroupsusersLogic->checkExclusiveSectionUser($this->DtoGroupsusers,$this->DtoUser,$this->MdbGroupsadminusers,$this->MdbGroupsusers) ) {
            return "input";
        }

        //有効期限を設定
        if (TERM_OF_VALIDITY) {
            $start_dt_flg = false;
            $end_dt_flg = false;
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
                            $start_dt_flg = true;
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
                            $start_dt_flg = true;
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
                        $end_dt_flg = true;

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

        //上長承認設定で尚かつ停止にする場合は上長かチェック
        if (SUPERIOR_OPTION_FLG && $this->DtoUser->getStop_flg()) {
            $where = array("0" => "superior_id = '".Db_Common::escape_string($this->DtoUser->getUser_id())."'");
            $this->MdbUserSuperior->getUserSuperiorListWhere($this->DtoUserSuperior, $where);
            if (count($this->DtoUserSuperior->getUsersuperiorList()) > 0) {
                $errorList->add("Admin_User_Edit_Confirm::error", "上長に設定されている為、状態を停止することはできません。");
                return "input";
            }
        }

        /*
        // ユーザ管理者のは、自分のadmin_idをセットする
        if ($this->session->getParameter("login_admin_level") == "3") {
            $this->DtoUser->setOwner_admin_id( $this->session->getParameter("admin_id") );
        }
        // 管理者IDの数字チェック
        if (!is_numeric($this->DtoUser->getOwner_admin_id())) {
            return "error";
        }
        // 管理者が存在するかチェック
        $admin_info = array();
        $admin_info = $this->MdbAdminuser->getAdminuserInfo2($this->DtoUser->getOwner_admin_id());
        if (!$admin_info["admin_id"]) {
            //存在しないID
            return "error";
        }
        */

        // アクティブユーザがMAX_USER以上の場合
        if (MAX_USER != 0) {
            $cnt = $this->userLogic->getUserActiveCont($this->DtoUser,$this->MdbUser);
            if (MAX_USER <= $cnt) {
               // 有効期間の設定あり
               if (TERM_OF_VALIDITY) {
                   // 新しくする期間
                   if ( $start_dt_flg ) {
                       $start_dt = date("Y-m-d",mktime(0,0,0,$this->start_at_month,$this->start_at_day,$this->start_at_year));
                   }
                   if ( $end_dt_flg ) {
                       $end_dt = date("Y-m-d",mktime(0,0,0,$this->end_at_month,$this->end_at_day,$this->end_at_year));
                   }
                   // 以前の期間設定が期間外
                   $before_dt_outside_flg = false;
                   if ( ($this->log_start_dt != NULL) && ($this->log_end_dt != NULL)
                       && ( $this->log_start_dt >= date("Y-m-d") || $this->log_end_dt <= date("Y-m-d") ) ) {
                       $before_dt_outside_flg = true;
                   } elseif ( ($this->log_start_dt != NULL ) && $this->log_start_dt >= date("Y-m-d") ) {
                         $before_dt_outside_flg = true;
                   } elseif ( ($this->log_end_dt != NULL ) && $this->log_end_dt <= date("Y-m-d") ) {
                       $before_dt_outside_flg = true;
                   }
                   // ->利用
                   if ( $this->DtoUser->getStop_flg() != "1" ) {
                       // 停止->利用
                       if ($this->log_stop_flg == '1') {
                           // 新しく設定する期間が 期限外(ただし未来をのぞく)以外はNG
                           if ( $start_dt_flg && $end_dt_flg
                               && $start_dt>=date("Y-m-d") && $end_dt<=date("Y-m-d") ) {
                               // 有効期限が未来
                           } elseif ( $active_user && $start_dt_flg && $start_dt>=date("Y-m-d") ) {
                               $errorList->add("Admin_User_Edit_Confirm::error", "アクティブユーザ数がライセンス数を超過しています。利用中や有効期限内、未来の有効期限へ変更できません。");
                               return "input";
                               // 有効期限が過去
                           } elseif ( $end_dt_flg && $end_dt<=date("Y-m-d") ) {
                               // 有効期限が期限内
                           } else {
                               $errorList->add("Admin_User_Edit_Confirm::error", "アクティブユーザ数がライセンス数を超過しています。利用中や有効期限内、未来の有効期限へ変更できません。");
                               return "input";
                           }
                           // 利用->利用
                       } else {
                           // 期間外->
                           if ( $before_dt_outside_flg ) {
                               // 期間外->なし :NG
                               if ( !$start_dt_flg && !$end_dt_flg ) {
                                   $errorList->add("Admin_User_Edit_Confirm::error", "アクティブユーザ数がライセンス数を超過しています。利用中や有効期限内、未来の有効期限へ変更できません。");
                                   return "input";
                                   // 期間外->内 : NG
                               } elseif ($start_dt_flg && $end_dt_flg
                                       && $start_dt<=date("Y-m-d") && $end_dt>=date("Y-m-d") ) {
                                   $errorList->add("Admin_User_Edit_Confirm::error", "アクティブユーザ数がライセンス数を超過しています。利用中や有効期限内、未来の有効期限へ変更できません。");
                                   return "input";
                               } elseif ( $end_dt_flg == false && $start_dt_flg  && $start_dt<=date("Y-m-d") ) {
                                   $errorList->add("Admin_User_Edit_Confirm::error", "アクティブユーザ数がライセンス数を超過しています。利用中や有効期限内、未来の有効期限へ変更できません。");
                                   return "input";
                               } elseif ( $start_dt_flg == false && $end_dt_flg && $end_dt>=date("Y-m-d") ) {
                                   $errorList->add("Admin_User_Edit_Confirm::error", "アクティブユーザ数がライセンス数を超過しています。利用中や有効期限内、未来の有効期限へ変更できません。");
                                   return "input";
                               }
                           } else {
                               // 有効期限が未来
                               if ( $active_user && $start_dt_flg && $start_dt>=date("Y-m-d") ) {
                                   $errorList->add("Admin_User_Edit_Confirm::error", "アクティブユーザ数がライセンス数を超過しています。利用中や有効期限内、未来の有効期限へ変更できません。");
                                   return "input";
                               }
                           }
                       }
                   }//$this->DtoUser->getStop_flg() != 1

                   // 有効期間の設定なし
               } else {
                   // 停止->利用
                   if ( $this->log_stop_flg == '1'  && $this->DtoUser->getStop_flg() == '0' ) {
                       $errorList->add("Admin_User_Edit_Confirm::error", "アクティブユーザ数がライセンス数を超過しています。状態を変更できません。");
                       return "input";
                   }
               }//if (TERM_OF_VALIDITY)

            }
        }//if (MAX_USER)

        // 定期削除の個別設定日数の整数チェック
        if ($this->DtoUser->getFile_delete_limit_dt() >= 0 && $this->DtoUser->getFile_delete_limit_dt() < 1000) {
            if ($this->DtoUser->getFile_delete_limit_dt() !== '0' && (!preg_match("/^[1-9][0-9]*$/", $this->DtoUser->getFile_delete_limit_dt()))) {
                $errorList->add("Admin_User_Edit_Confirm::error", "定期削除の個別設定日数は整数で入力してください。");
                return "input";
            }
        }

        //sessionに記入
        $this->session->setParameter("user_id",$this->DtoUser->getUser_id());
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
        $this->session->setParameter("account_lock_flg",$this->DtoUser->getAccount_lock_flg());
        $this->session->setParameter("readonly_flg",$this->DtoGroupsusers->readonly_flg);
        $this->session->setParameter("section_id",$this->DtoGroupsusers->getSection_id());
        $this->session->setParameter("password_change_flg",$this->DtoUser->getPassword_change_flg());
        $this->session->setParameter("reinfo",$this->DtoUser->getReinfo());
        //Dtoはrequestの為、既存User情報をセット
        $this->session->setParameter("owner_admin_id",$info['owner_admin_id']);
        $this->session->setParameter("api_flg",$this->DtoUser->getApi_flg());
        $this->session->setParameter("file_delete_limit_dt",$this->DtoUser->getFile_delete_limit_dt());
        $this->session->setParameter("send_flg", $this->DtoUser->getSend_flg());
        $this->session->setParameter("collect_flg", $this->DtoUser->getCollect_flg());
        $this->session->setParameter("open_folder_flg", $this->DtoUser->getOpen_folder_flg());
        $this->session->setParameter("delivery_flg",$this->DtoUser->getDelivery_flg());
        $this->session->setParameter("gathering_flg",$this->DtoUser->getGathering_flg());
        $this->session->setParameter("two_step_auth_disable_flg",$this->DtoUser->getTwo_step_auth_disable_flg());

        //Log
        $this->session->setParameter("log_auth_type",$this->log_auth_type);
        $this->session->setParameter("log_account", $this->log_account);
        $this->session->setParameter("log_google_mail_address",$this->log_google_mail_address);
        $this->session->setParameter("log_saml_mail_address",$this->log_saml_mail_address);
        $this->session->setParameter("log_mailaddr", $this->log_mailaddr);
        $this->session->setParameter("log_user_name", $this->log_user_name);
        $this->session->setParameter("log_user_kana", $this->log_user_kana);
        $this->session->setParameter("log_max_file_size", $this->log_max_file_size);
        $this->session->setParameter("log_ext_file_size", $this->log_ext_file_size);
        $this->session->setParameter("log_ext_unlimited_flg", $this->log_ext_unlimited_flg);
        $this->session->setParameter("log_max_up_size", $this->log_max_up_size);
        $this->session->setParameter("log_memo", $this->log_memo);
        $this->session->setParameter("log_stop_flg", $this->log_stop_flg);
        $this->session->setParameter("log_ad_flg", $this->log_ad_flg);
        $this->session->setParameter("log_account_lock_flg", $this->log_account_lock_flg);
        $this->session->setParameter("log_section", $this->log_section);
        $this->session->setParameter("log_start_dt",$this->log_start_dt);
        $this->session->setParameter("log_end_dt",$this->log_end_dt);
        $this->session->setParameter("log_owner_admin_id",$this->log_owner_admin_id);
        $this->session->setParameter("log_password_change_flg",$this->log_password_change_flg);
        $this->session->setParameter("log_api_flg",$this->log_api_flg);
        $this->session->setParameter("log_file_delete_limit_dt",$this->log_file_delete_limit_dt);
        $this->session->setParameter("log_send_flg",$this->log_send_flg);
        $this->session->setParameter("log_collect_flg",$this->log_collect_flg);
        $this->session->setParameter("log_open_folder_flg",$this->log_open_folder_flg);
        $this->session->setParameter("log_delivery_flg",$this->log_delivery_flg);
        $this->session->setParameter("log_gathering_flg",$this->log_gathering_flg);

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
    function setMdbUser($MdbUser) {
        $this->MdbUser = $MdbUser;
    }
    function setUserLogic($userLogic) {
        $this->userLogic = $userLogic;
    }
    function setAdminuserLogic($adminuserLogic) {
        $this->adminuserLogic = $adminuserLogic;
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

    function setGroupsusersLogic(& $GroupsusersLogic) {
        $this->GroupsusersLogic = & $GroupsusersLogic;
    }
    function setMdbGroupsadminusers(&$MdbGroupsadminusers) {
        $this->MdbGroupsadminusers = & $MdbGroupsadminusers;
    }

    function setDtoSectionusers($DtoSectionusers) {
        $this->DtoSectionusers = $DtoSectionusers;
    }
    function setMdbSectionusers($MdbSectionusers) {
        $this->MdbSectionusers = $MdbSectionusers;
    }

    function setDtoUserSuperior($DtoUserSuperior) {
        $this->DtoUserSuperior = & $DtoUserSuperior;
    }
    function setMdbUserSuperior($MdbUserSuperior) {
        $this->MdbUserSuperior = & $MdbUserSuperior;
    }

    function setMdbUpload($MdbUpload) {
        $this->MdbUpload = & $MdbUpload;
    }
    function setMdbCollect($MdbCollect) {
        $this->MdbCollect = & $MdbCollect;
    }
    function setCapacityLogic($CapacityLogic) {
        $this->CapacityLogic = & $CapacityLogic;
    }

    function setAll_readonly_flg($all_readonly_flg) {
        $this->all_readonly_flg = intval($all_readonly_flg);
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

    // Log
    function setLog_auth_type($log_auth_type) {
        return $this->log_auth_type = $log_auth_type;
    }
    function setLog_account($log_account) {
        return $this->log_account = $log_account;
    }
    function setLog_google_mail_address($log_google_mail_address) {
        return $this->log_google_mail_address = $log_google_mail_address;
    }
    function setLog_saml_mail_address($log_google_mail_address) {
        return $this->log_saml_mail_address = $log_google_mail_address;
    }
    function setLog_user_name($log_user_name) {
        return $this->log_user_name = $log_user_name;
    }
    function setLog_user_kana($log_user_kana) {
        return $this->log_user_kana = $log_user_kana;
    }
    function setLog_mailaddr($log_mailaddr) {
        return $this->log_mailaddr = $log_mailaddr;
    }
    function setLog_max_file_size($log_max_file_size) {
        return $this->log_max_file_size = $log_max_file_size;
    }
    function setLog_ext_file_size($log_ext_file_size) {
        return $this->log_ext_file_size = $log_ext_file_size;
    }
    function setLog_ext_unlimited_flg($log_ext_unlimited_flg) {
        return $this->log_ext_unlimited_flg = $log_ext_unlimited_flg;
    }
    function setLog_max_up_size($log_max_up_size) {
        return $this->log_max_up_size = $log_max_up_size;
    }
    function setLog_start_dt($log_start_dt) {
        return $this->log_start_dt = $log_start_dt;
    }
    function setLog_end_dt($log_end_dt) {
        return $this->log_end_dt = $log_end_dt;
    }
    function setLog_memo($log_memo) {
        return $this->log_memo = $log_memo;
    }
    function setLog_stop_flg($log_stop_flg) {
        return $this->log_stop_flg = $log_stop_flg;
    }
    function setLog_ad_flg($log_ad_flg) {
        return $this->log_ad_flg = $log_ad_flg;
    }
    function setLog_account_lock_flg($log_account_lock_flg) {
        return $this->log_account_lock_flg = $log_account_lock_flg;
    }
    function setLog_section($log_section) {
        return $this->log_section = $log_section;
    }
    function setLog_owner_admin_id($log_owner_admin_id) {
        return $this->log_owner_admin_id = $log_owner_admin_id;
    }
    function setLog_password_change_flg($log_password_change_flg) {
        return $this->log_password_change_flg = $log_password_change_flg;
    }
    function setSubfolder($subfolder) {
        $this->subfolder = $subfolder;
    }
    function setLog_api_flg($log_api_flg) {
        $this->log_api_flg = $log_api_flg;
    }
    function setLog_file_delete_limit_dt($log_file_delete_limit_dt) {
        $this->log_file_delete_limit_dt = $log_file_delete_limit_dt;
    }
    function setLog_send_flg($log_send_flg) {
        return $this->log_send_flg = $log_send_flg;
    }
    function setLog_collect_flg($log_collect_flg) {
        return $this->log_collect_flg = $log_collect_flg;
    }
    function setLog_open_folder_flg($log_open_folder_flg) {
        return $this->log_open_folder_flg = $log_open_folder_flg;
    }
    function setLog_delivery_flg($log_delivery_flg) {
        return $this->log_delivery_flg = $log_delivery_flg;
    }
    function setLog_gathering_flg($log_gathering_flg) {
        return $this->log_gathering_flg = $log_gathering_flg;
    }
    function setMdbAdminuser(&$MdbAdminuser) {
        $this->MdbAdminuser = & $MdbAdminuser;
    }
    function setDtoAdminuser($DtoAdminuser) {
        $this->DtoAdminuser = & $DtoAdminuser;
    }
    function setAdminuserDto($AdminUserDto) {
        $this->AdminuserDto = & $AdminUserDto;
    }
    function setAdminadminLogic($AdminadminLogic) {
        $this->AdminadminLogic = & $AdminadminLogic;
    }

    function setCommonList($CommonList) {
        $this->CommonList =& $CommonList;
    }
    function setTemplateLogic($TemplateLogic) {
        $this->TemplateLogic = $TemplateLogic;
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
        if (!$this->DtoUser->getMax_up_size()) {
            return "0";
        } else {
            return $this->DtoUser->getMax_up_size();
        }
    }
    function getPasswd() {
        return $this->userDto->getPasswd();
    }
    function getPasswd_cont() {
        $cont = "";
        for($i=0;$i<strlen($this->userDto->getPasswd());$i++) {
            $cont .= "*";
        }
        if ($cont) {
            return $cont;
        } else {
            return "修正なし";
        }
    }
    function getPassword_change_flg() {
        return $this->DtoUser->getPassword_change_flg();
    }
    function getReinfo() {
        return $this->DtoUser->getReinfo();
    }
    function getUser_id() {
        return $this->DtoUser->getUser_id();
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
            $this->DtoUser->getStop_flg() :
            $this->getUserInfoValue( 'stop_flg' );
    }
    function getAd_flg() {
        return preg_match( '/^[01]$/', $this->DtoUser->getAd_flg() ) ?
            $this->DtoUser->getAd_flg() :
            $this->getUserInfoValue( 'ad_flg' );
    }
    function getOwner_admin_list() {
        // 管理者リストの取得
        $list = $this->AdminadminLogic->getSelectList($this->MdbAdminuser);
        return $list;
    }
    function getFile_delete_limit_dt() {
        if (!$this->DtoUser->getFile_delete_limit_dt()) {
            return "0";
        } else {
            return $this->DtoUser->getFile_delete_limit_dt();
        }
    }

    // Log
    function getLog_auth_type() {
        return $this->log_auth_type;
    }
    function getLog_account() {
        return $this->log_account;
    }
    function getLog_google_mail_address() {
        return $this->log_google_mail_address;
    }
    function getLog_saml_mail_address() {
        return $this->log_saml_mail_address;
    }
    function getLog_user_name() {
        return $this->log_user_name;
    }
    function getLog_user_kana() {
        return $this->log_user_kana;
    }
    function getLog_mailaddr() {
        return $this->log_mailaddr;
    }
    function getLog_max_file_size() {
        return $this->log_max_file_size;
    }
    function getLog_ext_file_size() {
        return $this->log_ext_file_size;
    }
    function getLog_ext_unlimited_flg() {
        return $this->log_ext_unlimited_flg;
    }
    function getLog_max_up_size() {
        if (!$this->log_max_up_size) {
            return "0";
        } else {
            return $this->log_max_up_size;
        }
    }
    function getLog_start_dt() {
        return $this->log_start_dt;
    }
    function getLog_end_dt() {
        return $this->log_end_dt;
    }
    function getLog_memo() {
        return $this->log_memo;
    }
    function getLog_stop_flg() {
        return $this->log_stop_flg;
    }
    function getLog_ad_flg() {
        return $this->log_ad_flg;
    }
    function getLog_account_lock_flg() {
        return $this->log_account_lock_flg;
    }
    function getLog_password_change_flg()
    {
        return $this->log_password_change_flg;
    }
    function getLog_section() {
        return $this->log_section;
    }

    function getPassmin() {
        $this->MdbConfigs->getInfo($this->DtoConfigs,'pass_min_num');
        return $this->DtoConfigs->getValue();
    }
    function getPassmax() {
        $this->MdbConfigs->getInfo($this->DtoConfigs,"pass_max_num");
        return $this->DtoConfigs->getValue();
    }
    function getSection_id() {
        return $this->DtoGroupsusers->getSection_id();
    }

    /**
     * 共有グループの権限を取得する
     * @param array $param 共有グループの情報。$param["data"]["section_readonly_flg"] = array(グループに所属する部署1のid => 権限, 部署2のid => 権限);
     * @return int|"" 権限なしなら"",読み権限で1,書き権限で2,フォルダ操作で0
     */
    function getIsSection($param)
    {
      $data = $param['data'];
      if (!is_array($data)) return false;
      $sec = $this->DtoGroupsusers->getSection_id();
      $flg = "";
      $cmp = array("" => 0, 1 => 1, 2 => 2, 0 => 3); // array(inputのvalue => 権限の強さ);
      if (is_array($sec)) foreach ($sec as $val) {
        if (empty($data['section_readonly_flg'])) {
          // このグループに所属している部署はない
          continue;
        }

        // $data["section_readonly_flg"] = array(グループに所属している部署ID => 権限情報("",0,1,2のどれか));
        if (!isset($data['section_readonly_flg'][$val])) {
          // このグループにこの部署は所属していない
          continue;
        }

        $section_auth = $data['section_readonly_flg'][$val];
        // 今までチェックした部署での権限より上の権限なら上書き
        if ($cmp[$section_auth] > $cmp[$flg]) {
          $flg = $section_auth;
        }
      }
      return $flg;
    }
    function getSectionKeyList () {
        if ( $this->session->getParameter("login_admin_level") < 5 ) {
            return $this->MdbSection->getKeyList("",array("subadmin_editable_flg='1'"));
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
    function getAccount_lock_flg() {
        return $this->DtoUser->getAccount_lock_flg();
    }
    function getSubfolder() {
        return ($this->subfolder=="1" ? "1":"0");
    }
    function getUser_account_lock_cnt() {
        return $this->MdbConfigs->getDirectValue("user_account_lock_cnt");
    }
    function getLog_owner_admin_id() {
        return $this->log_owner_admin_id;
    }
    function getOwner_admin_id() {
        $admin_info = $this->MdbAdminuser->getAdminuserInfo2($this->DtoUser->getOwner_admin_id());
        return $admin_info["admin_id"];
    }
    function getOwner_admin_account() {
        // ユーザ管理者のは、自分のadmin_idをセットする
        if ($this->session->getParameter("login_admin_level") == "3") {
            $this->DtoUser->setOwner_admin_id( $this->session->getParameter("admin_id") );
        }

        $admin_info = $this->MdbAdminuser->getAdminuserInfo2($this->DtoUser->getOwner_admin_id());
        return $admin_info["account"];
    }

    function getLog_api_flg() {
        return $this->log_api_flg;
    }
    function getApi_flg() {
        return preg_match( '/^[01]$/', $this->DtoUser->getApi_flg() ) ?
            $this->DtoUser->getApi_flg() :
            $this->getUserInfoValue( 'api_flg' );
    }
    function getLog_file_delete_limit_dt() {
        return $this->log_file_delete_limit_dt;
    }
    function getSend_flg() {
        return preg_match( '/^[01]$/', $this->DtoUser->getSend_flg() ) ?
            $this->DtoUser->getSend_flg() :
            $this->getUserInfoValue( 'send_flg' );
    }
    function getLog_send_flg() {
        return $this->log_send_flg;
    }
    function getCollect_flg() {
        return preg_match( '/^[01]$/', $this->DtoUser->getCollect_flg() ) ?
            $this->DtoUser->getCollect_flg() :
            $this->getUserInfoValue( 'collect_flg' );
    }
    function getLog_collect_flg() {
        return $this->log_collect_flg;
    }
    function getOpen_folder_flg() {
        return preg_match( '/^[01]$/', $this->DtoUser->getOpen_folder_flg() ) ?
            $this->DtoUser->getOpen_folder_flg() :
            $this->getUserInfoValue( 'open_folder_flg' );
    }
    function getLog_open_folder_flg() {
        return $this->log_open_folder_flg;
    }
    function getDelivery_flg() {
        return preg_match( '/^[01]$/', $this->DtoUser->getDelivery_flg() ) ?
            $this->DtoUser->getDelivery_flg() :
            $this->getUserInfoValue( 'delivery_flg' );
    }
    function getLog_delivery_flg() {
        return $this->log_delivery_flg;
    }
    function getGathering_flg() {
        return preg_match( '/^[01]$/', $this->DtoUser->getGathering_flg() ) ?
            $this->DtoUser->getGathering_flg() :
            $this->getUserInfoValue( 'gathering_flg' );
    }
    function getLog_gathering_flg() {
        return $this->log_gathering_flg;
    }
    function getTwo_step_auth_disable_flg() {
        return $this->DtoUser->getTwo_step_auth_disable_flg() === '1';
    }
    function getTwo_step_auth_is_enabled() {
        return !is_null( $this->getUserInfoValue( 'two_step_auth_secret_key' ) );
    }
    function getTwo_step_auth_flg() {
        $this->MdbConfigs->getInfo($this->DtoConfigs,"two_step_auth_flg");
        return $this->DtoConfigs->getValue();
    }

    /**
     * ユーザ情報を取得する
     * @return array
     */
    function getUserInfo() {
        // 既に取得していればそれを返す
        if ( !isset( $this->userInfo ) ) {
            $this->userInfo =
                $this->MdbUser->getInfo( $this->DtoUser->getUser_id() );
        }
        return $this->userInfo;
    }

    /**
     * キーからユーザ情報の値を取得する
     * @param  string $key
     * @return mixed
     */
    function getUserInfoValue( $key ) {
        $info = $this->getUserInfo();
        return $info[$key];
    }

    function getAll_auth_types() {
        return $this->CommonList->getAllAuthTypes();
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
    function getPassword_required_flg()
    {
        return PASSWORD_REQUIRED_FLG;
    }
}
?>

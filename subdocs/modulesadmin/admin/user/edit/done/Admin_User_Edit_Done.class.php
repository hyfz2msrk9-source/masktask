<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] ユーザ管理 - ユーザ編集(完了)
 * @updater     Hiroko Yanai（2018/04/11）SAML認証(OneLogin)に対応
 *
 * @package     WebFile
 * @version     3.15.2
 * @author
 * @copyright   KeyPoint
 * @access      public
 */

class Admin_User_Edit_Done {
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

    var $MdbGroupsusers;
    var $DtoGroupsusers;
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

    var $MdbAdminuser;
    var $DtoAdminuser;
    var $AdminuserDto;
    var $AdminadminLogic;

    var $MdbUserSuperior;

    var $all_readonly_flg;
    var $subfolder;

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
    var $log_file_delete_limit_dt;
    var $log_send_flg;
    var $log_collect_flg;
    var $log_open_folder_flg;
    var $log_delivery_flg;
    var $log_gathering_flg;

    var $CommonList;
    var $TemplateLogic;
    var $SuperiorLogic;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_User_Edit_Done","Admin_User_Edit_Done#execute");

        // log_owner_admin_idを引き継ぐ
        $this->setLog_owner_admin_id( $this->session->getParameter("log_owner_admin_id") );

        // 必須項目チェック
        if (!$this->adminuserLogic->checkRequiredEdit($this->DtoUser)) {
            return "error";
        }
        // アクティブユーザかのチェック
        $active_user = null;
        $user_info = $this->MdbUser->getInfo($this->DtoUser->getUser_id());
        $active_user = $this->userLogic->checkActiveUser($user_info);

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

        if (!$this->GroupsusersLogic->checkExclusiveSectionUser($this->DtoGroupsusers,$this->DtoUser,$this->MdbGroupsadminusers,$this->MdbGroupsusers) ) {
            return "input";
        }

        // エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        // アクティブユーザがMAX_USER以上の場合
        if (MAX_USER != 0) {
            $cnt = $this->userLogic->getUserActiveCont($this->DtoUser,$this->MdbUser);
            if (MAX_USER <= $cnt) {

                // 有効期間の設定あり
                if (TERM_OF_VALIDITY) {
                    // 新しくする期間
                    if ( $this->DtoUser->getStart_dt() != NULL ) {
                        list($start_at_year,$start_at_month,$start_at_day) = explode("-",$this->DtoUser->getStart_dt());
                        $start_dt = date("Y-m-d",mktime(0,0,0,$start_at_month,$start_at_day,$start_at_year));
                        $start_dt_flg = true;
                    }
                    if ( $this->DtoUser->getEnd_dt() != NULL ) {
                        list($end_at_year,$end_at_month,$end_at_day) = explode("-",$this->DtoUser->getEnd_dt());
                        $end_dt = date("Y-m-d",mktime(0,0,0,$end_at_month,$end_at_day,$end_at_year));
                        $end_dt_flg = true;
                    }
                    // 以前の期間設定が期間外
                    $before_dt_outside_flg = false;
                    if ( ($this->session->getParameter("log_start_dt") != NULL) && ($this->session->getParameter("log_end_dt") != NULL)
                        && ( $this->session->getParameter("log_start_dt") >= date("Y-m-d") || $this->session->getParameter("log_end_dt") <= date("Y-m-d") ) ) {
                            $before_dt_outside_flg = true;
                    } elseif ( ($this->session->getParameter("log_start_dt") != NULL ) && $this->session->getParameter("log_start_dt") >= date("Y-m-d") ) {
                        $before_dt_outside_flg = true;
                    } elseif ( ($this->session->getParameter("log_end_dt") != NULL ) && $this->session->getParameter("log_end_dt") <= date("Y-m-d") ) {
                        $before_dt_outside_flg = true;
                    }

                    // ->利用
                    if ( $this->DtoUser->getStop_flg() != 1 ) {
                        // 停止->利用
                        if ($this->log_stop_flg == '1') {
                            // 新しく設定する期間が 期限外(ただし未来をのぞく)以外はNG
                            if ( $start_dt_flg && $end_dt_flg
                                && $start_dt>=date("Y-m-d") && $end_dt<=date("Y-m-d") ) {
                            // 有効期限が未来
                            } elseif ( $active_user && $start_dt_flg && $start_dt>=date("Y-m-d") ) {
                                $errorList->add("Admin_User_Edit_Done::error", "アクティブユーザ数がライセンス数を超過しています。利用中や有効期限内、未来の有効期限へ変更できません。");
                                return "notActive";
                            // 有効期限が過去
                            } elseif ( $end_dt_flg && $end_dt<=date("Y-m-d") ) {
                            // 有効期限が期限内
                            } else {
                                $errorList->add("Admin_User_Edit_Done::error", "アクティブユーザ数がライセンス数を超過しています。利用中や有効期限内、未来の有効期限へ変更できません。");
                                return "notActive";
                            }
                        // 利用->利用
                        } else {
                            // 期間外->
                            if ( $before_dt_outside_flg ) {
                                // 期間外->なし :NG
                                if ( !$start_dt_flg && !$end_dt_flg ) {
                                    $errorList->add("Admin_User_Edit_Done::error", "アクティブユーザ数がライセンス数を超過しています。利用中や有効期限内、未来の有効期限へ変更できません。");
                                    return "notActive";
                                // 期間外->内 : NG
                                } elseif ($start_dt_flg && $end_dt_flg
                                        && $start_dt<=date("Y-m-d") && $end_dt>=date("Y-m-d") ) {
                                    $errorList->add("Admin_User_Edit_Done::error", "アクティブユーザ数がライセンス数を超過しています。利用中や有効期限内、未来の有効期限へ変更できません。");
                                    return "notActive";
                                } elseif ( $end_dt_flg == false && $start_dt_flg  && $start_dt<=date("Y-m-d") ) {
                                    $errorList->add("Admin_User_Edit_Done::error", "アクティブユーザ数がライセンス数を超過しています。利用中や有効期限内、未来の有効期限へ変更できません。");
                                    return "notActive";
                                } elseif ( $start_dt_flg == false && $end_dt_flg && $end_dt>=date("Y-m-d") ) {
                                    $errorList->add("Admin_User_Edit_Done::error", "アクティブユーザ数がライセンス数を超過しています。利用中や有効期限内、未来の有効期限へ変更できません。");
                                    return "notActive";
                                }
                            } else {
                                // 有効期限が未来
                                if ( $active_user && $start_dt_flg && $start_dt>=date("Y-m-d") ) {
                                    $errorList->add("Admin_User_Edit_Done::error", "アクティブユーザ数がライセンス数を超過しています。利用中や有効期限内、未来の有効期限へ変更できません。");
                                    return "input";
                                }
                            }
                        }
                    }//$this->DtoUser->getStop_flg() != 1

                // 有効期間の設定なし
                } else {
                    // 停止->利用
                    if ( $this->log_stop_flg == '1'  && $this->DtoUser->getStop_flg() == '0' ) {
                        $errorList->add("Admin_User_Edit_Done::error", "アクティブユーザ数がライセンス数を超過しています。状態を変更できません。");
                        return "notActive";
                    }
                }//if (TERM_OF_VALIDITY)

            }// MAX_USER <= $cnt
        }// MAX_USER

        // Logのため、容量値を確保
        $max_file_size_input = "";
        $max_file_size_input = $this->DtoUser->getMax_file_size();
        $ext_file_size_input = "";
        $ext_file_size_input = $this->DtoUser->getExt_file_size();
        $max_up_size_input = "0";
        $max_up_size_input = $this->DtoUser->getMax_up_size();

        //send_flg変更かどうか
        if ($this->DtoUser->getSend_flg() == '' || $this->DtoUser->getSend_flg() == null) {
            $this->DtoUser->getSend_flg($this->session->getParameter('log_send_flg'));
        }
        //collect_flg変更かどうか
        if ($this->DtoUser->getCollect_flg() == '' || $this->DtoUser->getCollect_flg() == null) {
            $this->DtoUser->getCollect_flg($this->session->getParameter('log_collect_flg'));
        }
        //open_folder_flg変更かどうか
        if ($this->DtoUser->getOpen_folder_flg() == '' || $this->DtoUser->getOpen_folder_flg() == null) {
            $this->DtoUser->getOpen_folder_flg($this->session->getParameter('log_open_folder_flg'));
        }
        if (defined("DELIVERY_FLG") && DELIVERY_FLG) {
            //delivery_flg変更かどうか
            if ($this->DtoUser->getDelivery_flg() == '' || $this->DtoUser->getDelivery_flg() == null) {
                $this->DtoUser->setDelivery_flg($this->session->getParameter('log_delivery_flg'));
            }
        }
        if (defined("GATHERING_FLG") && GATHERING_FLG) {
            //gathering_flg変更かどうか
            if ($this->DtoUser->getGathering_flg() == '' || $this->DtoUser->getGathering_flg() == null) {
                $this->DtoUser->setGathering_flg($this->session->getParameter('log_gathering_flg'));
            }
        }
        if (AD_FLG) {
            //ad_flg変更かどうか
            if ($this->DtoUser->getAd_flg() == '' || $this->DtoUser->getAd_flg() == null) {
                if ($this->DtoUser->getAuth_type() == 0) {
                    // パスワード認証なら、logのを使う
                    $this->DtoUser->setAd_flg($this->session->getParameter('log_ad_flg'));
                }
            }
        }

        if (API_FLG) {
            //api_flg変更かどうか
            if ($this->DtoUser->getApi_flg() == '' || $this->DtoUser->getApi_flg() == null) {
                $this->DtoUser->setApi_flg($this->session->getParameter('log_api_flg'));
            }
        }
        //stop_flg変更かどうか
        if ($this->DtoUser->getStop_flg() == '' || $this->DtoUser->getStop_flg() == null) {
            $this->DtoUser->setStop_flg($this->session->getParameter('log_stop_flg'));
        }
        if ($this->MdbConfigs->getDirectValue("user_account_lock_cnt") > 0) {
            //変更ない場合はセットしない 2010/2/1
            //account_lock_flg変更かどうか
            //if ($this->DtoUser->getAccount_lock_flg() == '' || $this->DtoUser->getAccount_lock_flg() == null) {
            //    $this->DtoUser->setAccount_lock_flg($this->session->getParameter('log_account_lock_flg'));
            //}
            // パスワード変更アリ
            if ($this->DtoUser->getPassword()) {
                //アカウントロックを強制的にOFF
                $this->DtoUser->setAccount_lock_flg("0");
            }
        }

        // 二要素認証を無効化するかどうか
        $this->DtoUser->setTwo_step_auth_disable_flg(
            TWO_STEP_AUTH_FLG &&
            $this->DtoUser->getTwo_step_auth_disable_flg() === '1'
        );

        // can_login_without_superior は上長承認の設定から変更するので、ここでは元の値をそのまま使う
        $this->DtoUser->setCan_login_without_superior($user_info['can_login_without_superior']);

        // ユーザ編集
        $this->DtoUser->setOwner_admin_id($user_info["owner_admin_id"]);
        if (!$user_info["owner_admin_id"]) { $this->DtoUser->setOwner_admin_id(1); }
        $this->adminuserLogic->edit($this->DtoUser,$this->MdbUser);

        // 部署登録
        $old_sections = $this->MdbSectionusers->getSectionusersList(["user_id = " . Db_Common::escape_string($this->DtoUser->getUser_id())]) ?: array();
        
        $groupsList = $this->getGroupsList();//管理者としての所属情報など
        foreach($groupsList as $key => $val) {
            $groupsForLog[$val['id']] = $val; //idをキーとして、管理者の情報を取得
            $groupsForLog[$val['id']]['readonly_flg'] = null; //メンバーとしての権限が入力値になってしまっているので、一旦リセット;
        }
        $old_groups_tmp   = $this->MdbGroupsusers->getHasGroupsEx($this->DtoUser->getUser_id());
        foreach ($old_groups_tmp as $val) {
            $groupsForLog[$val['group_id']]['readonly_flg'] = $val['readonly_flg'];
            // DtoGroupsusersのreadonly_flgが入力値に引っ張られているので、DBから現状を取
        }
        $sectionkeylist = $this->getSectionKeyList(); //存在する部署idに対する部署名のリスト
        $sec2 = array();
        if (is_array($this->DtoGroupsusers->getSection_id())) {
            $sec2 = $this->DtoGroupsusers->getSection_id();
            foreach ($this->DtoGroupsusers->getSection_id() as $section_id) {
                $where = array();
                $where[] = "user_id=".Db_Common::escape_string($this->DtoUser->getUser_id());
                $where[] = "section_id=".Db_Common::escape_string($section_id);
                // チェック
                if ( $this->MdbSectionusers->getCount($where) < 1 ) {
                    // 部署登録
                    $this->DtoSectionusers->setSection_id($section_id);
                    $this->DtoSectionusers->setUser_id($this->DtoUser->getUser_id());
                    $this->DtoSectionusers->setLeader_flg(0);
                    $this->DtoSectionusers->setHistory_open_flg(0);
                    $this->MdbSectionusers->insert($this->DtoSectionusers);
                }
                // 排他制御
                $this->GroupsusersLogic->excluseSectionUesr($this->MdbGroupsusers,$section_id,$this->DtoUser->getUser_id());
            }
        }

        // ユーザ部署削除
        $where = array();
        $where[] = "user_id = " . Db_Common::escape_string($this->DtoUser->getUser_id());
        $sec3 = $sec2;
        // 管理者管理を使用している
        if (defined("ADMIN_MANAGER_FLG") && ADMIN_MANAGER_FLG) {
            // 編集権限の無い部署から削除しない
            if ( $this->session->getParameter("login_admin_level") < 5 ) {
                $sections = $this->MdbSectionusers->getNotEditableSections($this->DtoUser->getUser_id());
                foreach( $sections as $val ) {
                    $sec3[] = $val["section_id"];
                }
            }
        }
        if (count($sec3)>0) {
            $where[] = "section_id NOT IN (".Db_Common::escape_string(implode(",",$sec3)).")";
        }
        $this->MdbSectionusers->deleteWhere($where);

        $new_sections = $this->MdbSectionusers->getSectionusersList(["user_id = " . Db_Common::escape_string($this->DtoUser->getUser_id())]) ?: array();

        // グループユーザ削除
        //$this->MdbGroupsusers->delete2($this->DtoUser->getUser_id());
        $where = array();
        $where[] = "user_id = " . Db_Common::escape_string($this->DtoUser->getUser_id());
        if (defined("ADMIN_MANAGER_FLG") && ADMIN_MANAGER_FLG) {
            if ($this->subfolder!="1") {
                //サブフォルダ指定なし -> トップのみ
                $where[] = "top_flg = 1";
            }
            // 編集権限の無いグループから削除しない
            if ( $this->session->getParameter("login_admin_level") < 5 ) {
                $groups = $this->MdbGroupsusers->getNotEditableGroups($this->DtoUser->getUser_id());
                $group_ids = array();
                foreach( $groups as $val ) {
                    $group_ids[] = $val["group_id"];
                }
                if (count($group_ids)>0) {
                    $where[] = "group_id NOT IN (".Db_Common::escape_string(implode(",",$group_ids)).")";
                }
            }
        }
        $this->MdbGroupsusers->deleteWhere($where);
        $new_groups   = $this->getGroupsList();

        // Log用
        foreach($new_groups as $key => $val) {
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

                $dir = array();
                $folder_list = array();
                //サブフォルダも更新
                if ($this->subfolder=="1") {
                    // サブフォルダのIDを取り出す。
                    $dir = $this->GroupLogic->makelist($this->MdbFolder,$val['folder_id']);
                    // メンバー登録
                    foreach($dir as $dkey => $dval) {
                        // 更新（追加）
                        $this->DtoGroupsusers->setTop_flg(0);
                        $this->DtoGroupsusers->setFolder_id($dval['folder_id']);
                        $this->MdbGroupsusers->add($this->DtoGroupsusers);
                        $folder_list[] = $val['folder_id'];
                    }

                }
                //読み取りならロック解除
                if (FILE_LOCK_OPTION_FLG && $val['readonly_flg'] === '1') {
                    if ($folder_list) {
                        $this->MdbUpload->cancelLock($val['folder_id'],$this->DtoUser->getUser_id(),$folder_list);
                    } else {
                        $this->MdbUpload->cancelLock($val['folder_id'],$this->DtoUser->getUser_id());
                    }
                }

            } else {
                //削除時はサブフォルダも消す
                $this->MdbGroupsusers->delete2($val['id'],$this->DtoUser->getUser_id());
                // 権限がない場合、持ち出しキャンセル
                $this->SuperiorLogic->autoRevertExternalizationForFolder($val['folder_id']);
                //ロック解除
                if (FILE_LOCK_OPTION_FLG) {
                    $dir = array();
                    $dir = $this->GroupLogic->makelist($this->MdbFolder,$val['folder_id']);
                    $folder_list = array();
                    if ($dir) foreach($dir as $folder) {
                            $folder_list[] = $folder['folder_id'];
                        }
                    $this->MdbUpload->cancelLock($val['folder_id'],$this->DtoUser->getUser_id(),$folder_list);
                }
            }
            $groupsForLog[$val['id']]['new_readonly_flg'] = $val['readonly_flg'];
            $groupsForLog[$val['id']]['new_section_readonly_flg'] = $val['section_readonly_flg'];
        }

        //ログイン情報を送る
        $reinfo_log = "";
        if ($this->DtoUser->getReinfo() == 1) {
            $container   = &DIContainerFactory::getContainer();
            $actionChain = &$container->getComponent("ActionChain");
            $errorList   = &$actionChain->getCurErrorList();
            $info_mail_body = $this->TemplateLogic->get_template_file_contents("", "user_inform_body.txt");
            if ($info_mail_body) {
                //パスワード生成
                $password = Password::resetPassword($this->MdbConfigs->getDirectValue('pass_min_num'),$this->MdbConfigs->getDirectValue('pass_max_num'),$this->MdbConfigs->getDirectValue('pass_type_id'));
                $this->DtoUser->setPassword($password);

                //メールを送る
                $mail = new MailSend();
                $ret = $mail->User_Inform_Mail($this->DtoUser,$info_mail_body);

                if (PEAR::isError($ret)) {
                    $errorList->add("Admin_User_Edit_Done::error", "ログイン情報の送信に失敗しました。しばらく時間をおいてから、再度お試しください。");
                    $reinfo_log = "　ログイン情報送信：失敗";
                } else {
                    $data = array();
                    //$data['password'] = sha1($password);
                    $data['password'] = Password::hash($password);
                    $data['password_change_dt'] = date('Y-m-d');

                    //アカウントロックを解除する
                    if ($this->DtoUser->getAccount_lock_flg()) {
                        $data['account_lock_flg'] = 0;
                        $data['login_failuer_num'] = 0;
                        $data['login_failuer_dt'] = null;
                    }
                    $this->MdbUser->updateByData($this->DtoUser,$data);
                    $reinfo_log = "　ログイン情報送信：成功";
                }
            } else {
                $errorList->add("Admin_User_Edit_Done::error", "ログイン情報の送信に失敗しました。テンプレートが保存されていません。");
                $reinfo_log = "　ログイン情報送信：失敗（テンプレート未保存）";
            }
        }

        // Log
        $history_id = 103; // 103:ユーザ管理＞ユーザ情報の編集
        $memo  = "";
        $memo  = "編集ユーザ\n";
        if ($this->DtoUser->getAuth_type() != $this->session->getParameter("log_auth_type")) {
            $usage_auth_type = $this->CommonList->getUsageAuthTypes();
            if (1 < count($usage_auth_type)) {
                $memo .= "　認証方式：".$usage_auth_type[$this->session->getParameter("log_auth_type")];
                $memo .= " → ".$usage_auth_type[$this->DtoUser->getAuth_type()]."\n";
            }
        }
        if ( $this->DtoUser->getAccount() != $this->session->getParameter("log_account")) {
            $memo .= "　アカウント：".$this->session->getParameter("log_account");
            $memo .= " → ".$this->DtoUser->getAccount()."\n";
        } else {
            $memo .= "　アカウント：".$this->DtoUser->getAccount()."\n";
        }
        if ($this->DtoUser->getAuth_type() == 0) {
            if ( $this->DtoUser->getPassword() != $this->session->getParameter("password")) {
                $memo .= "　パスワード：******→******"."\n";
            } else {
                $memo .= "　パスワード：******"."\n";
            }
            if ($this->DtoUser->getPassword_change_flg()) {
                $memo .= "　次回ログイン時にパスワードを変更する"."\n";
            }
        }
        if ($this->DtoUser->getReinfo()) {
            $memo .= $reinfo_log."\n";
        }
        if ( TWO_STEP_AUTH_FLG ) {
            $two_step_auth_is_enabled = !is_null($user_info['two_step_auth_secret_key']);
            $two_step_auth_flg = $this->MdbConfigs->getDirectValue("two_step_auth_flg");
            if ($two_step_auth_flg == 1) {
                if ($two_step_auth_is_enabled) {
                    $two_step_auth_log_before = '認証アプリで使用する';
                } else {
                    $two_step_auth_log_before = '使用しない';
                }
            } else if ($two_step_auth_flg == 2) {
                $two_step_auth_log_before = 'メールで使用する';
            } else {
                $two_step_auth_log_before = '使用しない';
            }

            $two_step_auth_log_after = ($two_step_auth_is_enabled && $this->DtoUser->getTwo_step_auth_disable_flg()) ? '→使用しない' : '';
            $memo .= '　二要素認証機能：' . $two_step_auth_log_before . $two_step_auth_log_after . "\n";
        }
        if ( $this->DtoUser->getUser_name() != $this->session->getParameter("log_user_name")) {
            $memo .= "　氏名：".$this->session->getParameter("log_user_name");
            $memo .= " → ".$this->DtoUser->getUser_name()." ";
        } else {
            $memo .= "　氏名：".$this->DtoUser->getUser_name()." ";
        }
        if ( $this->DtoUser->getUser_kana() != $this->session->getParameter("log_user_kana")) {
            $memo .= "(".$this->session->getParameter("log_user_kana");
            $memo .= " → ".$this->DtoUser->getUser_kana().")\n";
        } else {
            $memo .= "(".$this->DtoUser->getUser_kana().")\n";
        }
        if ( $this->DtoUser->getMailaddr() != $this->session->getParameter("log_mailaddr")) {
            $memo .= "　メールアドレス：".$this->session->getParameter("log_mailaddr");
            $memo .= " → ".$this->DtoUser->getMailaddr()."\n";
        } else {
            $memo .= "　メールアドレス：".$this->DtoUser->getMailaddr()."\n";
        }
        if (EXTRA_SPACE_FLG) {
            $memo .= "　容量：\n";
            if ( $max_file_size_input != $this->session->getParameter("log_max_file_size")) {
                $memo .= "　　基本：".$this->session->getParameter("log_max_file_size");
                $memo .= " → ".$max_file_size_input."MB\n";
            } else {
                $memo .= "　　基本：".$max_file_size_input."MB\n";
            }
            if ( $ext_file_size_input != $this->session->getParameter("log_ext_file_size")
                 || $this->DtoUser->getExt_unlimited_flg() != $this->session->getParameter("log_ext_unlimited_flg")) {
                $memo .= "　　拡張：";
                if ($this->session->getParameter("log_ext_unlimited_flg")) {
                    $memo .= "無制限";
                } else {
                    $memo .= $this->session->getParameter("log_ext_file_size");
                }
                $memo .= " → ";
                if ($this->DtoUser->getExt_unlimited_flg()) {
                    $memo .= "無制限\n";
                } else {
                    $memo .= $ext_file_size_input."MB\n";
                }
            } else {
                $memo .= "　　拡張：";
                if ($this->DtoUser->getExt_unlimited_flg()) {
                    $memo .= "無制限\n";
                } else {
                    $memo .= $ext_file_size_input."MB\n";
                }
            }
        } else {
            if ( $max_file_size_input != $this->session->getParameter("log_max_file_size")) {
                $memo .= "　容量：".$this->session->getParameter("log_max_file_size");
                $memo .= " → ".$max_file_size_input."MB\n";
            } else {
                $memo .= "　容量：".$max_file_size_input."MB\n";
            }
        }

        if ( $max_up_size_input != $this->session->getParameter("log_max_up_size")) {
            $memo .= "　アップロード容量：".$this->session->getParameter("log_max_up_size");
            $memo .= " → ".$max_up_size_input."MB\n";
        } else {
            $memo .= "　アップロード容量：".$max_up_size_input."MB\n";
        }

        if ( $this->DtoUser->getFile_delete_limit_dt() != $this->session->getParameter("log_file_delete_limit_dt")) {
            $memo .= "　定期削除の個別設定：\n　　";
            if ($this->session->getParameter("log_file_delete_limit_dt") == 0) {
                $memo .= "個別の定期削除は行わない";
            } else {
                $memo .= $this->session->getParameter("log_file_delete_limit_dt")."日前のファイルを削除";
            }
            $memo .= " → ";
            if ($this->DtoUser->getFile_delete_limit_dt() == 0) {
                $memo .= "個別の定期削除は行わない\n";
            } else {
                $memo .= $this->DtoUser->getFile_delete_limit_dt()."日前のファイルを削除\n";
            }
        } else {
            if ($this->DtoUser->getFile_delete_limit_dt() == 0) {
                $memo .= "　定期削除の個別設定：個別の定期削除は行わない\n";
            } else {
                $memo .= "　定期削除の個別設定：".$this->DtoUser->getFile_delete_limit_dt()."日前のファイルを削除\n";
            }
        }
        if ($this->session->getParameter("log_send_flg") == '1') {
            $memo .= "　配布機能：使用する";
        } else {
            $memo .= "　配布機能：使用しない";
        }
        if ((int)$this->DtoUser->getSend_flg() != $this->session->getParameter("log_send_flg")) {
            if ($this->DtoUser->getSend_flg() == '1') {
                $memo .= " → 使用する\n";
            } else {
                $memo .= " → 使用しない\n";
            }
        } else {
            $memo .= "\n";
        }
        if ($this->session->getParameter("log_collect_flg") == '1') {
            $memo .= "　引取り機能：使用する";
        } else {
            $memo .= "　引取り機能：使用しない";
        }
        if ((int)$this->DtoUser->getCollect_flg() != $this->session->getParameter("log_collect_flg")) {
            if ($this->DtoUser->getCollect_flg() == '1') {
                $memo .= " → 使用する\n";
            } else {
                $memo .= " → 使用しない\n";
            }
        } else {
            $memo .= "\n";
        }
        if ($this->session->getParameter("log_open_folder_flg") == '1') {
            $memo .= "　フォルダ公開機能：使用する";
        } else {
            $memo .= "　フォルダ公開機能：使用しない";
        }
        if ((int)$this->DtoUser->getOpen_folder_flg() != $this->session->getParameter("log_open_folder_flg")) {
            if ($this->DtoUser->getOpen_folder_flg() == '1') {
                $memo .= " → 使用する\n";
            } else {
                $memo .= " → 使用しない\n";
            }
        } else {
            $memo .= "\n";
        }

        if (defined("DELIVERY_FLG") && DELIVERY_FLG) {
            if ($this->session->getParameter("log_delivery_flg") == '1') {
                $memo .= "　一斉配信機能：使用する";
            } else {
                $memo .= "　一斉配信機能：使用しない";
            }
            if ((int)$this->DtoUser->getDelivery_flg() != $this->session->getParameter("log_delivery_flg")) {
                if ($this->DtoUser->getDelivery_flg() == '1') {
                    $memo .= " → 使用する\n";
                } else {
                    $memo .= " → 使用しない\n";
                }
            } else {
                $memo .= "\n";
            }
        }
        if (defined("GATHERING_FLG") && GATHERING_FLG) {
            if ($this->session->getParameter("log_gathering_flg") == '1') {
                $memo .= "　一斉収集機能：使用する";
            } else {
                $memo .= "　一斉収集機能：使用しない";
            }
            if ((int)$this->DtoUser->getGathering_flg() != $this->session->getParameter("log_gathering_flg")) {
                if ($this->DtoUser->getGathering_flg() == '1') {
                    $memo .= " → 使用する\n";
                } else {
                    $memo .= " → 使用しない\n";
                }
            } else {
                $memo .= "\n";
            }
        }

        if (API_FLG) {
            if ($this->session->getParameter("log_api_flg") == '1') {
                $memo .= "　".API_CLIENT_NAME."機能の利用：利用する";
            } else {
                $memo .= "　".API_CLIENT_NAME."機能の利用：利用しない";
            }
            if ((int)$this->DtoUser->getApi_flg() != $this->session->getParameter("log_api_flg")) {
                if ($this->DtoUser->getApi_flg() == '1') {
                    $memo .= " → 利用する\n";
                } else {
                    $memo .= " → 利用しない\n";
                }
            } else {
                $memo .= "\n";
            }
        }
        if (TERM_OF_VALIDITY) {
            $year = NULL;
            $month = NULL;
            $day = NULL;
            $memo .= "　有効期限（開始）：";
            $dt = $this->session->getParameter("log_start_dt");
            if ( $this->DtoUser->getStart_dt() != $dt) {
                if ($dt != NULL) {
                    list($year,$month,$day) = explode("-",$this->session->getParameter("log_start_dt"));
                    $memo .= $year."年".abs($month)."月".abs($day)."日";
                } else {
                    $memo .= "未設定";
                }
                $memo .= " → ";
                $dt = $this->DtoUser->getStart_dt();
                if ($dt != NULL) {
                    list($year,$month,$day) = explode("-",$dt);
                    $memo .= $year."年".abs($month)."月".abs($day)."日\n";
                } else {
                    $memo .= "未設定\n";
                }
            } else {
                if ($this->DtoUser->getStart_dt() == NULL) {
                    $memo .= "未設定\n";
                } else {
                    list($year,$month,$day) = explode("-",$this->DtoUser->getStart_dt());
                    $memo .= $year."年".abs($month)."月".abs($day)."日\n";
                }
            }

            $year = NULL;
            $month = NULL;
            $day = NULL;
            $memo .= "　有効期限（終了）：";
            $dt = $this->session->getParameter("log_end_dt");
            if ( $this->DtoUser->getEnd_dt() != $dt) {
                if ($dt != NULL) {
                    list($year,$month,$day) = explode("-",$dt);
                    $memo .= $year."年".abs($month)."月".abs($day)."日";
                } else {
                    $memo .= "未設定";
                }
                $memo .= " → ";
                $dt = $this->DtoUser->getEnd_dt();
                if ($dt != NULL) {
                    list($year,$month,$day) = explode("-",$dt);
                    $memo .= $year."年".abs($month)."月".abs($day)."日\n";
                } else {
                    $memo .= "未設定\n";
                }
            } else {
                if ($this->DtoUser->getEnd_dt() == NULL) {
                    $memo .= "未設定\n";
                } else {
                    list($year,$month,$day) = explode("-",$this->DtoUser->getEnd_dt());
                    $memo .= $year."年".abs($month)."月".abs($day)."日\n";
                }
            }
        }

        if ( $this->DtoUser->getMemo() != $this->session->getParameter("log_memo")) {
            $memo .= "　メモ：".$this->session->getParameter("log_memo");
            $memo .= " → ".$this->DtoUser->getMemo()."\n";
        } else {
            $memo .= "　メモ：".$this->DtoUser->getMemo()."\n";
        }

        if (defined("ADMIN_MANAGER_FLG") && ADMIN_MANAGER_FLG) {
            // 管理者リストの取得（変更機能はない）
            $admin_list = $this->AdminadminLogic->getSelectList($this->MdbAdminuser);
            $memo .= "　管理者：" . $admin_list[$this->DtoUser->getOwner_admin_id()] . "\n";
        }

        if (defined("AD_FLG") && AD_FLG) {
            if ($this->session->getParameter("log_ad_flg") == '1') {
                $memo .= "　AD連携：する";
            } else {
                $memo .= "　AD連携：しない";
            }
            if ((int)$this->DtoUser->getAd_flg() != $this->session->getParameter("log_ad_flg")) {
                if ($this->DtoUser->getAd_flg()==1) {
                    $memo .= " → する\n";
                } else {
                    $memo .= " → しない\n";
                }
            } else {
                $memo .= "\n";
            }
        }
        if ($this->session->getParameter("log_stop_flg") == '1') {
            $memo .= "　状態：停止中";
        } else {
            $memo .= "　状態：利用中";
        }
        if ((int)$this->DtoUser->getStop_flg() != $this->session->getParameter("log_stop_flg")) {
            if ( $this->DtoUser->getStop_flg() == 1) {
                $memo .= " → 停止中\n";
            } else {
                $memo .= " → 利用中\n";
            }
        } else {
            $memo .= "\n";
        }
        // アカウントロック
        if ($this->MdbConfigs->getDirectValue("user_account_lock_cnt") > 0) {
            if ($this->session->getParameter("log_account_lock_flg") == '1') {
                $memo .= "　アカウントロック：アカウントロック中";
                if ($this->DtoUser->getAccount_lock_flg() == '0') {
                    $memo .= " → アカウントロックを解除する";
                }
            } else {
                $memo .= "　アカウントロック：アカウントロック解除中";
            }
            $memo.= "\n";
        }

        // 所属部署、グループアクセス権限
        if (SHARE_GROUP_FLG ) {
            $memo .= "　所属部署：\n";
            $diff_sections = array_diff(array_column($new_sections, 'section_id'), array_column($old_sections, 'section_id'));
            $old_section_text = "";
            if ($diff_sections) {
                foreach ($old_sections as $section) {
                    $old_section_text .= "　　" . $sectionkeylist[$section['section_id']] . "\n";
                }
                $memo .= $old_section_text ?: "　　なし\n";
                $memo .= $old_section_text . " 　→ \n";
            }
            $new_section_text = "";
            foreach ($new_sections as $section) {
                $new_section_text .= "　　" . $sectionkeylist[$section['section_id']] . "\n";
            }
            $memo .= $new_section_text ?: "　　なし\n";

            $memo .= "　グループアクセス権限：\n";
            $log_share_groups = "";
            $user_id = $this->DtoUser->getUser_id();
            foreach($groupsForLog as $group){
                $auth = $this->getGroupAuth($user_id, $group, $old_sections, $new_sections);
                if( $auth['before'] ===  $auth['after']  && $auth['before']  ===  '権限なし') {
                    continue; // 権限なしで変化がなければ、ログに出力しない
                }
                $log_share_groups .= "　　" . $group['name'] . ': ' . $auth['before'];
                if($auth['before'] !== $auth['after']){
                    $log_share_groups .=' → ' . $auth['after'];
                } 
                $log_share_groups .= "\n";
            }
            $memo .= $log_share_groups ?: "　　なし\n";
            if ($this->subfolder == "1") {
                $memo .= "\n　（サブフォルダも更新）" . "\n";
            }
        }

        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        //sessionを削除
        $this->session->removeParameter("user_id");
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
        $this->session->removeParameter("account_lock_flg");
        $this->session->removeParameter("readonly_flg");
        $this->session->removeParameter("section_id");
        $this->session->removeParameter("password_change_flg");
        $this->session->removeParameter("reinfo");
        $this->session->removeParameter("api_flg");
        $this->session->removeParameter("file_delete_limit_dt");
        $this->session->removeParameter("send_flg");
        $this->session->removeParameter("collect_flg");
        $this->session->removeParameter("open_folder_flg");
        $this->session->removeParameter("delivery_flg");
        $this->session->removeParameter("gathering_flg");
        $this->session->removeParameter("two_step_auth_disable_flg");

        $this->session->removeParameter("log_auth_type");
        $this->session->removeParameter("log_account");
        $this->session->removeParameter("log_google_mail_address");
        $this->session->removeParameter("log_saml_mail_address");
        $this->session->removeParameter("log_mailaddr");
        $this->session->removeParameter("log_user_name");
        $this->session->removeParameter("log_user_kana");
        $this->session->removeParameter("log_max_file_size");
        $this->session->removeParameter("log_ext_file_size");
        $this->session->removeParameter("log_ext_unlimited_flg");
        $this->session->removeParameter("log_max_up_size");
        $this->session->removeParameter("log_memo");
        $this->session->removeParameter("log_stop_flg");
        $this->session->removeParameter("log_ad_flg");
        $this->session->removeParameter("log_account_lock_flg");
        $this->session->removeParameter("log_password_change_flg");
        $this->session->removeParameter("log_api_flg");
        $this->session->removeParameter("log_file_delete_limit_dt");
        $this->session->removeParameter("log_send_flg");
        $this->session->removeParameter("log_collect_flg");
        $this->session->removeParameter("log_open_folder_flg");
        $this->session->removeParameter("log_delivery_flg");
        $this->session->removeParameter("log_gathering_flg");

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

    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setDtoConfigs($DtoConfigs) {
        $this->DtoConfigs = $DtoConfigs;
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

    function setDtoLog ( &$DtoLog ) {
        $this->DtoLog =& $DtoLog;
    }
    function setMdbLog ( &$MdbLog ) {
        $this->MdbLog =& $MdbLog;
    }

    function setAll_readonly_flg($all_readonly_flg) {
        $this->all_readonly_flg = $all_readonly_flg;
    }
    function setSubfolder($subfolder) {
        $this->subfolder = $subfolder;
    }
    function setMdbFolder(&$MdbFolder) {
        $this->MdbFolder = & $MdbFolder;
    }
    function setGroupLogic(&$GroupLogic) {
        $this->GroupLogic = & $GroupLogic;
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
    function setMdbUserSuperior($MdbUserSuperior) {
        $this->MdbUserSuperior = & $MdbUserSuperior;
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
    function setMdbGroupsadminusers(&$MdbGroupsadminusers) {
        $this->MdbGroupsadminusers = & $MdbGroupsadminusers;
    }
    function setLog_owner_admin_id($log_owner_admin_id) {
        return $this->log_owner_admin_id = $log_owner_admin_id;
    }
    function setCommonList($CommonList) {
        $this->CommonList =& $CommonList;
    }
    function setTemplateLogic($TemplateLogic) {
        $this->TemplateLogic = $TemplateLogic;
    }
    function setSuperiorLogic($SuperiorLogic)
    {
        $this->SuperiorLogic = $SuperiorLogic;
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
    function getUser_id() {
        return $this->DtoUser->getUser_id();
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
    function getLog_memo() {
        return $this->log_memo;
    }
    function getLog_stop_flg() {
        return $this->log_stop_flg;
    }
    function getLog_ad_flg() {
        return $this->log_ad_flg;
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
    function getAccount_lock_flg() {
        return $this->DtoUser->getAccount_lock_flg();
    }
    function getSubfolder() {
        return ($this->subfolder=="1" ? "1":"0");
    }
    function getLog_file_delete_limit_dt() {
        return $this->log_file_delete_limit_dt;
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
    function getLog_send_flg() {
        return $this->log_send_flg;
    }
    function getLog_collect_flg() {
        return $this->log_collect_flg;
    }
    function getLog_open_folder_flg() {
        return $this->log_open_folder_flg;
    }
    function getLog_delivery_flg() {
        return $this->log_delivery_flg;
    }
    function getLog_gathering_flg() {
        return $this->log_gathering_flg;
    }
    function getLog_owner_admin_id() {
        return $this->log_owner_admin_id;
    }
    function getAll_auth_types() {
        return $this->CommonList->getAllAuthTypes();
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

    /**
    * グループへの所属権限情報を取得する
    * @param ｓｔｒｉｎｇ $user_id ユーザID
    * @param array $group グループ情報。そのグループに権限のある管理者や部署の情報を含む
    * @param array $old_sections 変更前にユーザが所属していた部署
    * @param array $new_sections 変更後にユーザが所属する部署
    * @return string 権限名
    */
    function getGroupAuth($user_id, $group, $old_sections, $new_sections) {
        // 部署-グループ間の権限設定はユーザ変更で変わらないので、部署については所属している部署の変更だけ考えればいい。
        $auth['before'] = match (true) {
             in_array((int)$user_id, array_keys($group['leader_flg']))  => "管理者権限",
                    $group['readonly_flg'] === "1" => "読み出しのみ",
                    $group['readonly_flg'] === "2" => "読み/書き",
                    $group['readonly_flg'] === "0" => "読み/書き/フォルダ操作",
                    $group['readonly_flg'] === "" => "権限なし",
                    // readonly_flg=nullの時は、部署で一番強い権限を返す
                    default => $this->getMaxAuth($group['section_readonly_flg'], $old_sections),
                };
        
        $auth['after'] = match (true) {
            in_array((int)$user_id, array_keys($group['leader_flg']))  => "管理者権限", //ユーザ編集画面で変更されることはない
                    $group['new_readonly_flg'] === "1" => "読み出しのみ",
                    $group['new_readonly_flg'] === "2" => "読み/書き",
                    $group['new_readonly_flg'] === "0" => "読み/書き/フォルダ操作",
                    $group['new_readonly_flg'] === "" => "権限なし",
                    // new_readonly_flg=nullの時は、部署で一番強い権限を返す
                    default => $this->getMaxAuth($group['new_section_readonly_flg'], $new_sections)
        };
        return $auth;
    }

    /**
     * 部署としての権限で一番強いものを取得する
     * @param array $sectionAuth グループに対して権限がある部署情報（キーが部署ID、値が権限）
     * @param array $mySections ユーザの所属部署
     * @return string 権限の文字列
     */
    function getMaxAuth($sectionAuth, $mySections){
        $maxAuth = "権限なし";
        foreach ($mySections as $section) {
            if(!isset($sectionAuth[(int)$section["section_id"]])) {
                // 己のグループに所属していない部署は無視
                continue;
            }
            if($sectionAuth[(int)$section["section_id"]] === "0") {
                // 最上位権限なので、これを返す
                return "読み/書き/フォルダ操作";
            } elseif ($sectionAuth[(int)$section["section_id"]]=== "2") {
                $maxAuth = "読み/書き";
            } elseif ($sectionAuth[(int)$section["section_id"]]=== "1" && $maxAuth !== "読み/書き") {
                $maxAuth = "読み出しのみ";
            }
        }
        return $maxAuth;
    }
}
?>

<?php
require_once( MODULE_DIR . "/common/common.php");

class Exec_Adadduser {
    var $session;

    var $DtoUser;
    var $MdbUser;
    var $adminuserLogic;
    var $DtoLog;
    var $MdbLog;
    var $MdbGroups;
    var $MdbGroupsusers;
    var $MdbSectionusers;
    var $MdbSection;
    var $DtoSection;
    var $MdbUpload;
    var $MdbCollect;
    var $CapacityLogic;

    var $error;
    var $add_cnt;
    var $add_users;
    var $edit_cnt;
    var $edit_users;
    var $restart_cnt;
    var $restart_users;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_User_Add_Done","Admin_User_Add_Done#execute");

        // 接続アカウント情報
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();
		if (!defined("AD_ADMIN_USER") || AD_ADMIN_USER == "") {
		    $this->DtoLog->setUser_id("");
		    $this->DtoLog->setAdmin_id( 1 );
		    $this->DtoLog->setFile_id("");
		    $this->DtoLog->setHistory_id(169);
		    $this->DtoLog->setMemo("[定期実行]\nADの管理者アカウントを入力してください。");
		    $this->MdbLog->Logging($this->DtoLog);
            return "input";
        }
		else if (!defined("AD_ADMIN_PASS") || AD_ADMIN_PASS == "") {
		    $this->DtoLog->setUser_id("");
		    $this->DtoLog->setAdmin_id( 1 );
		    $this->DtoLog->setFile_id("");
		    $this->DtoLog->setHistory_id(169);
		    $this->DtoLog->setMemo("[定期実行]\nADの管理者パスワードを入力してください。");
		    $this->MdbLog->Logging($this->DtoLog);
            return "input";
        }

        // AD接続
        // AD認証
        $this->add_users = array();
        $this->add_cnt = 0;
        $this->edit_users = array();
        $this->edit_cnt = 0;
        $this->restart_users = array();
        $this->restart_cnt = 0;
        $ad_error = "";
        $ad_error = $this->getAd_info();
        if ($ad_error == "error") {
            return "error";
        }

        //$this->addError($this->_account_error);
        //return false;

        // Logのため、容量値を確保

        // ユーザ登録
//        $this->adminuserLogic->add($this->DtoUser,$this->MdbUser);

        // Log
        $history_id = 169; // 169:ユーザ管理＞ADユーザ一括登録
        $cnt = 0;
        $add_cnt = 0;
        $edit_cnt = 0;
        $restart_cnt = 0;
        $memo = "[定期実行]\n";
        $memo.= $this->makeLog();

        /*
        foreach( $this->add_users as $val ) {
            $memo .= $this->makeLog( "add", $cnt, $add_cnt );
            $cnt++;
            $add_cnt++;
        }
        foreach( $this->edit_users as $val ) {
            $memo .= $this->makeLog( "edit", $cnt, $edit_cnt );
            $cnt++;
            $edit_cnt++;
        }
        foreach( $this->restart_users as $val ) {
            $memo .= $this->makeLog( "restart", $cnt, $restart_cnt );
            $cnt++;
            $restart_cnt++;
        }
        if ($cnt <= 0) {
            $memo = "追加ユーザ\n0人";
        }
        */

        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( 1 );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);



        return "success";
    }

    function makeLog() {
        $memo = "";
        if ($this->add_cnt && $this->add_cnt > 0) {
            $memo.= "追加ユーザ：".$this->add_cnt."人";
            if ($this->add_users) {
                foreach($this->add_users as $val) {
                    $memo.= "\n";
                    $memo.= $val;
                }
            }
        }
        if ($this->edit_cnt &&  $this->edit_cnt > 0) {
            if ($memo) { $memo.= "==========================================\n"; }
            $memo.= "編集ユーザ：".$this->edit_cnt."人";
            if ($this->edit_users) {
                foreach($this->edit_users as $val) {
                    $memo.= "\n";
                    $memo.= $val;
                }
            }
        }

        return $memo;
    }

    function getAd_info() {

        // ADに接続 --------------------
        for( $a = 0; $a < 2; $a++ ) {
		$ad_proto = 0;
            if ($a == 0) {
                $host = AD_HOST_1;
                $port = AD_PORT_1;
                $ad_dc = AD_DC_1;
                if(defined("AD_PROTO_1") && AD_PROTO_1){
                    $ad_proto = AD_PROTO_1;
                }
            } elseif ($a == 1) {
                if (!defined("AD_HOST_2") || !AD_HOST_2) { break; } // 予備サーバがなければ終わり
                $host = AD_HOST_2;
                $port = AD_PORT_2;
                $ad_dc = AD_DC_2;
                if(defined("AD_PROTO_2") && AD_PROTO_2){
                    $ad_proto = AD_PROTO_2;
                }
            }
            if($ad_proto == 1){
                $protocol = "ldaps";
            }else{
                $protocol = "ldap";
            }
            $ds = ldap_connect($protocol . "://" . $host . ':' . $port);

            // baseDN
            $dc= "";
            $ary_dc = "";
            $ary_dc = preg_split("/\./", $ad_dc);
            foreach ($ary_dc as $val) {
                if ($dc) {
                    $dc.= ",DC=".$val;
                } else {
                    $dc = "DC=".$val;
                }
            }
            
            if ($ds) {
                // もし管理者アカウントの設定がなければユーザのアカウントで接続してみる
                /*
                if ((!defined("AD_ADMIN_USER") || !AD_ADMIN_USER) || (!defined("AD_ADMIN_PASS") || !AD_ADMIN_PASS)) {
                    $this->error = "ADの設定が正しくありません。";
                    return "error";
                } else {
                    $ad_admin_user = AD_ADMIN_USER;
                    $ad_admin_pass = AD_ADMIN_PASS;
                }*/

                // 接続アカウント
                if (!defined("AD_ADMIN_USER") || AD_ADMIN_USER == "" || !defined("AD_ADMIN_PASS") || AD_ADMIN_PASS == "") {
					$this->DtoLog->setUser_id("");
					$this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
					$this->DtoLog->setFile_id("");
					$this->DtoLog->setHistory_id(169);
					$this->DtoLog->setMemo("[定期実行]\nADの設定が正しくありません。");
					$this->MdbLog->Logging($this->DtoLog);
                    return "error";
                }

                $ad_admin_user = AD_ADMIN_USER;
                $ad_admin_pass = AD_ADMIN_PASS;

                // バインド（関連付け→成功しないと検索できません）
                ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
                if (AD_SERVER_TYPE == 'AD') {
                    $bind_account = $ad_admin_user."@".$ad_dc;
                } else {
                    if (ADMIN_OU != '') {
                        $bind_account = "cn=". AD_ADMIN_USER.",ou=". ADMIN_OU .',' .$dc;
                    } else {
                        $bind_account = "cn=". AD_ADMIN_USER.",".$dc;
                    }
                }
                $bind_rs = @ldap_bind($ds, $bind_account, $ad_admin_pass);
                // バインド成功なら終わり
                if ($bind_rs) { break; }
            }
        }
        if (!$ds) {
            $this->error = "ADに接続できませんでした。";       // 接続エラー
            return "error";
        }
        // -----------------------------

        // USERの検索（keyはアカウント）
        $ldap_filter = null;
        if (file_exists(MODULE_DIR."/common/adwhere.php")) {
            require_once(MODULE_DIR."/common/adwhere.php");
            $ldap_filter = GlobalAdArray::getFilter();
        }
        $fields = array('memberof','sAMAccountName','mail','cn','userAccountControl','displayname','lockoutTime');
        if ($ldap_filter) { $user_filter = $ldap_filter; } else { $user_filter = array("(samaccountname=*)"); }

        // 指定のOUを検索
        $user_info_list = array();
        if (defined("AD_USER_OU") && AD_USER_OU) {
            $user_ou = preg_split("/\,/",AD_USER_OU);
            foreach($user_ou as $u) {
                $ou = "";
                $ary_ou = "";
                $ary_ou = preg_split("/\//", $u);
                foreach ($ary_ou as $val) {
                    if ($ou) {
                        $ou = "OU=".$val.",".$ou;
                    } else {
                        $ou = "OU=".$val;
                    }
                }
                $search_dn = $ou.",".$dc;
                $user_search_rs = "";
                foreach($user_filter as $filter_str) {
                    $user_search_rs = @ldap_search($ds,$search_dn,"(&".$filter_str."(objectClass=user))",$fields); // 検索
                    if ($user_search_rs) {
                        $user_info_list[] = ldap_get_entries($ds, $user_search_rs); // 検索結果
                    }
                }
            }
        }

        // ユーザを発見できなかったエラー
        if (!$user_info_list) {
            $this->error = "ADにユーザが登録されていません。";
            return "error";
        }

        // debug 2012-02-15
        $debug_data = "";
        // debug 2012-02-15
        $this->add_cnt = 0;
        $ad_account_list = null;
        foreach($user_info_list as $key => $user_info) {
            if (isset($user_info["count"]) && $user_info["count"]>0) {

                if (defined("MAX_USER") && MAX_USER) {
                    $max_loop = MAX_USER - $this->MdbUser->getActiveUsers();
                } else {
                    $max_loop = "unlimited";
                }
                if( $max_loop > 0 || $max_loop=="unlimited") {
                    foreach( $user_info as $ui_key => $ui_val ) {
                        //if( $this->add_cnt >= $max_loop ) break;
                        // ユーザの情報を取得
                        if (isset($ui_val["samaccountname"][0])) {
                            $account = $ui_val["samaccountname"][0];
                        } else {
                            //アカウント名が無い
                            continue;
                        }

                        // OKでもNGでも良いのでアカウントを全部拾う
                        if ($ad_account_list) {
                            $ad_account_list.= ",'".Db_Common::escape_string($account)."'";
                        } else {
                            $ad_account_list = "'".Db_Common::escape_string($account)."'";
                        }

                        // debug 2012-02-15
                        $debug_data.= "\n";
                        $debug_data.= '"'.date("Y-m-d H:i:s").'",';
                        $debug_data.= '"cron",';
                        $debug_data.= '"'.$account.'",';
                        $debug_data.= '"'.$ui_val["displayname"][0].'",';
                        $debug_data.= '"'.$ui_val["mail"][0].'",';
                        $debug_data.= '"'.$ui_val["dn"].'",';
                        // debug 2012-02-15

                        // 名前がないエラー
                        if (isset($ui_val["displayname"])) {
                            $user_name = $ui_val["displayname"][0];
                        } else {
                            //return "ad_noname_error";

                            // debug 2012-02-15
                            $debug_data.= '"no name",';
                            // debug 2012-02-15

                            continue;
                        }
                        // メアドがないエラー
                        if (isset($ui_val["mail"])) {
                            $user_mail = $ui_val["mail"][0];
                            // 2014/10/23 katsuhara 新gTLD対応 アルファベットを2文字以上に
                            if (!preg_match("/^[a-zA-Z0-9\"\._\?\+\/-]+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,}|[0-9]{1,3})(\]?)$/", $user_mail)) {

                                // debug 2012-02-15
                                $debug_data.= '"mail error",';
                                // debug 2012-02-15

                                continue;
                            }

                        } else {
                            //return "ad_nomail_error";

                            // debug 2012-02-15
                            $debug_data.= '"no mail",';
                            // debug 2012-02-15

                            continue;
                        }

                        // アカウントが無効か否か
                        $stop_flg = 0;
                        $debug_stop = 0;  // debug 2012-02-15
                        $disabled = $ui_val['useraccountcontrol'][0] & 0x0002;
                        if ($disabled===2) {
                            $stop_flg = 1;
                            $debug_stop = 1;  // debug 2012-02-15
                        }

                        // アカウント重複チェック
                        if ($this->MdbUser->getExistCheck(array("LOWER(account)='".Db_Common::escape_string($account)."'",
                                                                "ad_flg='0'",
                                                                "del_flg='0'"))) {
                            $this->err_msg[] = "アカウント:".$account."は、アカウントが重複しているため登録できません。";

                            // debug 2012-02-15
                            $debug_data.= '"account double",';
                            // debug 2012-02-15

                            continue;
                        }

                        // メアド重複チェック
                        if ($this->MdbUser->getExistCheck(array("LOWER(account)<>'".Db_Common::escape_string($account)."'",
                                                                "LOWER(mailaddr)='".Db_Common::escape_string($user_mail)."'",
                                                                "del_flg='0'"))) {
                            $this->err_msg[] = "アカウント:".$account."は、メールアドレスが重複しているため登録できません。";

                            // debug 2012-02-15
                            $debug_data.= '"mail double",';
                            // debug 2012-02-15

                            continue;
                        }

                        // 既存のアカウント情報をゲット
                        $user_id = null;
                        $user_id = $this->MdbUser->getUserIdByAccount( $account );
                        if ($user_id) {
                            $this->DtoUser->setUser_id( $user_id );
                            $this->MdbUser->getUserInfo( $this->DtoUser );
                        } else {
                            $this->DtoUser->setUser_id(null);
                        }
                        if ($user_id) {
                            $type = "edit";
                            // AD側が停止じゃないとき
                            if (!$stop_flg) {
                                // WF側が停止だったら
                                if ($this->DtoUser->getStop_flg()){
                                    // アカウント復活あり
                                    if (defined("AD_PACKAGE_RESTART") && AD_PACKAGE_RESTART == 1) {
                                        $type = "restart";

                                        // debug 2012-02-15
                                        $debug_data.= '"","restart",';
                                        // debug 2012-02-15

                                    // アカウント復活なし
                                    } else {
                                        $this->err_msg[] = "アカウント:".$account."は、WebFile側で利用が停止されているため更新できません。";

                                        // debug 2012-02-15
                                        $debug_data.= '"stop error",';
                                        // debug 2012-02-15

                                        continue;
                                    }
                                }
                            }
                        } else {
                            $type = "add";     // データがないので新規登録

                            // debug 2012-02-15
                            $debug_data.= '"","add",';
                            // debug 2012-02-15

                        }

                        // debug 2012-02-15
                        if ($type == "edit") {
                            $debug_data.= '"","edit",';
                        }
                        // debug 2012-02-15

                        // 容量チェックの前に現情報を保存（ログ用）
                        $now_ext_size = 0;
                        $now_ext_unlimited_flg = 0;
                        $now_max_file_size = 0;
                        $now_max_up_size = 0;
                        if ($type == "edit" || $type == "restart") {
                            $now_ext_size = $this->DtoUser->getExt_file_size();
                            $now_ext_unlimited_flg = $this->DtoUser->getExt_unlimited_flg();
                            $now_max_file_size = $this->DtoUser->getMax_file_size();
                            $now_max_up_size = $this->DtoUser->getMax_up_size();
                        }

			/*
                        // 容量チェック
                        if( defined("AD_EXTRA_FILE_SIZE") && AD_EXTRA_FILE_SIZE > 0 ) {
                            $this->DtoUser->setExt_file_size( AD_EXTRA_FILE_SIZE );
                        } else {
                            $this->DtoUser->setExt_file_size( 0 );
                        }
                        if( defined("AD_EXTRA_FILE_SIZE") && AD_EXTRA_FILE_SIZE == -1 ) {
                            $this->DtoUser->setExt_unlimited_flg( 1 );
                        } else {
                            $this->DtoUser->setExt_unlimited_flg( 0 );
                        }
                        if( defined("AD_MAX_FILE_SIZE") && AD_MAX_FILE_SIZE > 0 ) {
                            $this->DtoUser->setMax_file_size( AD_MAX_FILE_SIZE );
                        } else {
                            $this->DtoUser->setMax_file_size( 0 );
                        }
                        if( defined("AD_MAX_UP_SIZE") && AD_MAX_UP_SIZE > 0 ) {
                            $this->DtoUser->setMax_up_size( AD_MAX_UP_SIZE );
                        } else {
                            $this->DtoUser->setMax_up_size( 0 );
                        }
                        if (!$this->adminuserLogic->checkSize($this->DtoUser,$this->MdbUser,$this->CapacityLogic)) {

                            // debug 2012-02-15
                            $debug_data.= '"size over",';
                            // debug 2012-02-15

                            continue;
                        }
			*/

                        // debug 2012-02-15
                        $debug_data.= '"","'.$debug_stop.'",';
                        // debug 2012-02-15

                        // 登録 ------------------------
                        if ($type == "add") {
                            // 容量チェック
                            if( defined("AD_EXTRA_FILE_SIZE") && AD_EXTRA_FILE_SIZE > 0 ) {
                                $this->DtoUser->setExt_file_size( AD_EXTRA_FILE_SIZE );
                            } else {
                                $this->DtoUser->setExt_file_size( 0 );
                            }
                            if( defined("AD_EXTRA_FILE_SIZE") && AD_EXTRA_FILE_SIZE == -1 ) {
                                $this->DtoUser->setExt_unlimited_flg( 1 );
                            } else {
                                $this->DtoUser->setExt_unlimited_flg( 0 );
                            }
                            if( defined("AD_MAX_FILE_SIZE") && AD_MAX_FILE_SIZE > 0 ) {
                                $this->DtoUser->setMax_file_size( AD_MAX_FILE_SIZE );
                            } else {
                                $this->DtoUser->setMax_file_size( 0 );
                            }
                            if( defined("AD_MAX_UP_SIZE") && AD_MAX_UP_SIZE > 0 ) {
                                $this->DtoUser->setMax_up_size( AD_MAX_UP_SIZE );
                            } else {
                                $this->DtoUser->setMax_up_size( 0 );
                            }
                            if (!$this->adminuserLogic->checkSize($this->DtoUser,$this->MdbUser,$this->CapacityLogic)) {
                                $this->err_msg[] = "アカウント:".$account."は、個人総容量を超えるため登録できません";

                                // debug 2012-02-15
                                $debug_data.= '"size over",';
                                // debug 2012-02-15

                                continue;
                            }

			    // 値をセット
                            $this->DtoUser->setAccount($account);
                            $this->DtoUser->setMailaddr($user_mail);
                            $this->DtoUser->setPassword("");
                            $this->DtoUser->setMax_file_size(round($this->DtoUser->getMax_file_size()*1024*1024,0)+0);
                            if ($this->DtoUser->getExt_unlimited_flg()) {
                                $this->DtoUser->setExt_file_size(0);
                                $this->DtoUser->setExt_unlimited_flg(1);
                            } else {
                                $this->DtoUser->setExt_file_size(round($this->DtoUser->getExt_file_size()*1024*1024,0)+0);
                                $this->DtoUser->setExt_unlimited_flg(0);
                            }
                            $this->DtoUser->setMax_up_size(round($this->DtoUser->getMax_up_size()*1024*1024,0)+0);
                            $this->DtoUser->setDemo_flg(0);
                            $this->DtoUser->setDel_flg(0);
                            $this->DtoUser->setLast_login_dt("");
                            $this->DtoUser->setIn_dt(date("Y-m-d H:i:s"));
                            $this->DtoUser->setCourse_id(0);
                            $this->DtoUser->setCategory_id(0);
                            $this->DtoUser->setSpouse_flg(0);
                            $this->DtoUser->setChildren_flg(0);
                            $this->DtoUser->setHouse_id(0);
                            $this->DtoUser->setError(0);
                            $this->DtoUser->setUser_name($user_name);
                            $this->DtoUser->setUser_kana("");
                            $this->DtoUser->setMemo("");
                            $this->DtoUser->setMail_header_flg(0);
                            $this->DtoUser->setMail_footer_flg(0);
                            $this->DtoUser->setPassword_change_dt(date("Y-m-d H:i:s"));
                            $this->DtoUser->setStart_dt("");
                            $this->DtoUser->setEnd_dt("");
                            $this->DtoUser->setStop_flg($stop_flg);
                            $this->DtoUser->setAd_flg(1);
                            $this->DtoUser->setAfferiate_id(0);
                            $this->DtoUser->setOwner_admin_id(1);
                            $this->DtoUser->setFile_delete_limit_dt(0);
                            $this->DtoUser->setSso_flg(0);
                            $this->DtoUser->setSaml_flg(0);
                            $this->MdbUser->insertUser($this->DtoUser); // ★登録

                            // ログ用
                            $memo = "";
                            $memo.= "　アカウント：".$this->DtoUser->getAccount()."\n";
                            $memo.= "　氏名：".$this->DtoUser->getUser_name()."\n";
                            $memo.= "　メールアドレス：".$this->DtoUser->getMailaddr()."\n";
                            if (EXTRA_SPACE_FLG) {
                                $memo.= "　容量：\n";
                                $memo.= "　　基本：".StrChange::CapacityUnit($this->DtoUser->getMax_file_size())."\n";
                                if ($this->DtoUser->getExt_unlimited_flg() == 1) {
                                    $memo.= "　　拡張：無制限"."\n";
                                } else {
                                    $memo.= "　　拡張：".StrChange::CapacityUnit($this->DtoUser->getExt_file_size())."\n";
                                }
                            } else {
                                $memo.= "　容量：".StrChange::CapacityUnit($this->DtoUser->getMax_file_size())."\n";
                            }
                            $memo.= "　アップロード容量：".StrChange::CapacityUnit($this->DtoUser->getMax_up_size())."\n";
                            $memo.= "　AD連携：する"."\n";
                            if ($this->DtoUser->getStop_flg()) {
                                $memo.= "　状態：停止中"."\n";
                            } else {
                                $memo.= "　状態：利用開始"."\n";
                            }
                            $this->add_users[] = $memo;
                            $this->add_cnt++;

                        // 更新
                        } elseif ($type == "edit" || $type == "restart") {
                            // ログ用に保管
                            $now_mail = $this->DtoUser->getMailaddr();
                            $now_user_name = $this->DtoUser->getUser_name();
                            $now_stop_flg = $this->DtoUser->getStop_flg();

                            $this->DtoUser->setAccount($account);
                            $this->DtoUser->setMailaddr($user_mail);
			    /*
                            // 割当て容量、拡張容量、アップロード容量は、編集しない

                            $this->DtoUser->setMax_file_size(round($this->DtoUser->getMax_file_size()*1024*1024,0)+0);
                            if ($this->DtoUser->getExt_unlimited_flg()) {
                                $this->DtoUser->setExt_file_size(0);
                                $this->DtoUser->setExt_unlimited_flg(1);
                            } else {
                                $this->DtoUser->setExt_file_size(round($this->DtoUser->getExt_file_size()*1024*1024,0)+0);
                                $this->DtoUser->setExt_unlimited_flg(0);
                            }
                            $this->DtoUser->setMax_up_size(round($this->DtoUser->getMax_up_size()*1024*1024,0)+0);
			    */
			    $this->DtoUser->setMax_file_size($now_max_file_size);
                            $this->DtoUser->setExt_file_size($now_ext_size);
                            $this->DtoUser->setExt_unlimited_flg($now_ext_unlimited_flg);
                            $this->DtoUser->setMax_up_size($now_max_up_size);

                            $this->DtoUser->setUser_name($user_name);
                            $this->DtoUser->setStop_flg($stop_flg);
                            $this->DtoUser->setAd_flg(1);

                            // 変更がなければ更新しない ＆　ログのメモ
                            $update_flg = 0;
                            $memo = "";
                            $memo.= "　アカウント：".$this->DtoUser->getAccount()."\n";
                            if ($now_user_name <> $this->DtoUser->getUser_name()) {
                                $update_flg = 1;
                                $memo.= "　氏名：".$now_user_name."→".$this->DtoUser->getUser_name()."\n";
                            }
                            if ($now_mail <> $this->DtoUser->getMailaddr()) {
                                $update_flg = 1;
                                $memo.= "　メールアドレス：".$now_mail."→".$this->DtoUser->getMailaddr()."\n";
                            }
                            if (EXTRA_SPACE_FLG) {
                                if ($now_max_file_size <> $this->DtoUser->getMax_file_size()) {
                                    $update_flg = 1;
                                } elseif ($now_ext_unlimited_flg <> $this->DtoUser->getExt_unlimited_flg()) {
                                    $update_flg = 1;
                                } elseif ($now_ext_size <> $this->DtoUser->getExt_file_size()) {
                                    $update_flg = 1;
                                }

                                $memo.= "　容量：\n";
                                $memo.= "　　基本：".StrChange::CapacityUnit($this->DtoUser->getMax_file_size())."\n";
                                if ($this->DtoUser->getExt_unlimited_flg() == 1) {
                                    $memo.= "　　拡張：無制限"."\n";
                                } else {
                                    $memo.= "　　拡張：".StrChange::CapacityUnit($this->DtoUser->getExt_file_size())."\n";
                                }
                            } else {
                                if ($now_max_file_size <> $this->DtoUser->getMax_file_size()) {
                                    $update_flg = 1;
                                    $memo.= "　容量：".StrChange::CapacityUnit($now_max_file_size)."→".StrChange::CapacityUnit($this->DtoUser->getMax_file_size())."\n";
                                }
                            }
                            if ($now_max_up_size <> $this->DtoUser->getMax_up_size()) {
                                $update_flg = 1;
                                $memo.= "　アップロード容量：".StrChange::CapacityUnit($now_max_up_size)."→".StrChange::CapacityUnit($this->DtoUser->getMax_up_size())."\n";
                            }
                            if ($now_stop_flg <> $this->DtoUser->getStop_flg()) {
                                $update_flg = 1;
                                if ($now_stop_flg) {
                                    $memo.= "　状態：停止中→利用する"."\n";
                                } else {
                                    $memo.= "　状態：利用中→停止"."\n";
                                }
                            }

                            // 更新するときだけ更新
                            if ($update_flg) {
                                $this->MdbUser->editUserByAdmin($this->DtoUser); // ★更新

                                // ログのメモ保管
                                $this->edit_users[] = $memo;
                                $this->edit_cnt++;
                                if ($type=="restart") {
                                    //$this->restart_users[] = $memo;
                                    $this->restart_cnt++;
                                }
                            }
                        }


                    }   // foreach
                }
            }
        }

        // ADから取得できなかったアカウントはstop_flgを立てる
        if ($ad_account_list) {
            $mdb = new MDB_QueryTool(DNS);

            $mdb->reset();
            $mdb->setTable("USER");

            $sql = "SELECT `account` FROM `USER` ";
            $sql.= "WHERE `ad_flg`=1 AND  `del_flg`=0 AND `stop_flg`=0 AND `account` NOT IN (".$ad_account_list.");";
            $rs = $mdb->execute($sql);

            // debug 2012-02-15
            $debug_sql = '';
            $debug_sql.= '========================================'."\n";
            $debug_sql.= 'date:'.date("Y-m-d H:i:s")."\n";
            $debug_sql.= 'sql1:'.$sql."\n";
            // debug 2012-02-15

            if ($rs) {
                $memo = ""; $stop_cnt=0;
                foreach($rs as $key => $val) {
                    $memo.= "　アカウント：".$val["account"]."\n";
                    $this->edit_cnt++;
                    $stop_cnt++;

                    // debug 2012-02-15
                    $debug_stop_account.= $val["account"].'---';
                    // debug 2012-02-15

                }
                $memo = "※AD側にアカウントが見つかりませんでした：\n".$memo;
                $this->edit_users[] = $memo;

                $mdb->reset();
                $mdb->setTable("USER");

                $sql = "UPDATE `USER` SET `stop_flg` = '1' ";
                $sql.= "WHERE `ad_flg` = 1 AND  `del_flg` =0 AND `account` NOT IN (".$ad_account_list.");";
                $mdb->execute($sql);

                // debug 2012-02-15
                $debug_sql.= 'sql2:'.$sql."\n";
                $debug_sql.= 'account:'.$debug_stop_account."\n";
                // debug 2012-02-15

            }
        }

        // debug 2012-02-15
        $debug_fp1 = fopen(LOG_DIR."/addalluser.csv","a+");
        $dtit = "\n\n".'"date","cron","account","namae","mail","ou","err1","type","err2","stop"';
        fwrite($debug_fp1,$dtit.$debug_data);
        fclose($debug_fp1);

        $debug_fp2 = fopen(LOG_DIR."/accountlist.log","a+");
        fwrite($debug_fp2,$debug_sql);
        fclose($debug_fp2);
        // debug 2012-02-15

        return "success";
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
    function setMdbUser($MdbUser) {
        $this->MdbUser = $MdbUser;
    }
    function setAdminuserLogic($adminuserLogic) {
        $this->adminuserLogic = $adminuserLogic;
    }
    function setDtoLog($DtoLog) {
        $this->DtoLog = $DtoLog;
    }
    function setMdbLog($MdbLog) {
        $this->MdbLog = $MdbLog;
    }
    function setMdbGroupsusers($MdbGroupsusers) {
        $this->MdbGroupsusers = $MdbGroupsusers;
    }
    function setMdbGroups($MdbGroups) {
        $this->MdbGroups = $MdbGroups;
    }
    function setMdbSectionusers($MdbSectionusers) {
        $this->MdbSectionusers = $MdbSectionusers;
    }
    function setMdbSection($MdbSection){
        $this->MdbSection = $MdbSection;
    }
    function setMdbUpload($MdbUpload){
        $this->MdbUplaod = $MdbUpload;
    }
    function setMdbCollect($MdbCollect) {
        $this->MdbCollect = $MdbCollect;
    }
    function setCapacityLogic($CapacityLogic) {
        $this->CapacityLogic = $CapacityLogic;
    }

    /************************************
    * getter
    *************************************/
    function getError() {
        return $this->error;
    }
    function getAdd_cnt() {
        return $this->add_cnt;
    }
    function getAdd_users() {
        return $this->add_users;
    }
    function getEdit_cnt() {
        return $this->edit_cnt;
    }
    function getEdit_users() {
        return $this->edit_users;
    }
    function getRestart_cnt() {
        return $this->restart_cnt;
    }
    function getRestart_users() {
        return $this->restart_users;
    }
    function getEdit_and_restart_cnt() {
        return $this->edit_cnt + $this->restart_cnt;
    }
    function getUser_capacity() {
        return 1;
    }
}
?>

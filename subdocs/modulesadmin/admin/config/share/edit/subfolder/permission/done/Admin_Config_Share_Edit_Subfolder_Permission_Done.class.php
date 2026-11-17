<?php

require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Share_Edit_Subfolder_Permission_Done {

    // maple:action
    var $session;
    var $DtoUser;
    var $MdbUser;
    var $DtoFolder;
    var $MdbFolder;
    var $DtoLog;
    var $MdbLog;
    var $MdbSection;
    var $MdbSectionusers;
    var $ShareLogic;
    var $FolderLogic;
    var $DtoGroups;
    var $MdbGroups;
    var $MdbUpload;
    var $SuperiorLogic;

    // request
    var $data_id;
    var $old_power_flg;
    var $PowerEditToken;
    var $sub_folder_id;
    var $power_flg;
    var $add_flg;
    var $subfolder;

    var $power_flg_section;
    var $power_flg_user;

    // inner
    var $top_flg;

    function execute() {

        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Share_Edit_Subfolder_Permission_Doneが実行されました","Admin_Config_Share_Edit_Subfolder_Permission_Done#execute");

        //Tokenチェック
        if($this->PowerEditToken != $this->session->getParameter("PowerEditToken")) {
            echo "不正なアクセスを検知しました。";
            exit();
        }

        //print_r($_REQUEST);

        //--------------------------------------
        //前準備
        //--------------------------------------
        //セッション情報取得
        $group_id = $this->session->getParameter("id");
        $sub_folder_id = $this->session->getParameter("sub_folder_id");

        //フォルダ情報取得
        $this->DtoFolder->setFolder_id($sub_folder_id);
        $this->MdbFolder->getFolderInfo2($this->DtoFolder);
        //グループ情報取得
        $this->DtoGroups->setId($group_id);
        $this->MdbGroups->getInfo($this->DtoGroups);

        //グループのフォルダIDと一致している場合はトップディレクトリ
        $this->top_flg = ( $this->DtoGroups->getFolder_id()==$this->DtoFolder->getFolder_id() ? 1 : 0 );

        //--------------------------------------
        //入力チェック
        //--------------------------------------
        if($this->data_id=="all") {
            //一括編集
            if ($this->power_flg_section !== "0" &&
                $this->power_flg_section !== "1" &&
                $this->power_flg_section !== "2" &&
                $this->power_flg_section !== "-1") {
                echo "正しいデータが送信されていません。";
                exit();
            }
            if ($this->power_flg_user !== "0" &&
                $this->power_flg_user !== "1" &&
                $this->power_flg_user !== "2" &&
                $this->power_flg_user !== "-1") {
                echo "正しいデータが送信されていません。";
                exit();
            }

            if ($this->top_flg) {
                if ($this->power_flg_section == "-1" ||
                    $this->power_flg_user == "-1") {
                    echo "親フォルダから権限を削除する場合は、編集画面でメンバーを再設定してください。";
                    exit();
                }
            }

        } else {
            //編集
            //データチェック
            if(!is_array($this->data_id) || !is_array($this->old_power_flg)) {
                echo "正しいデータが送信されていません。";
                exit();
            }

            $this->section_id = array();
            $this->user_id = array();
            $this->power_flg = array();
            $this->data_cnt = 0;
            $userkeylist    = $this->MdbUser->getKeyList();    // ユーザキーリスト
            $sectionkeylist = $this->MdbSection->getKeyList(); // 部署キーリスト
            foreach($this->data_id as $key => $val) {
                if(preg_match("/^row([0-9]+)_([0-9]+)$/",$val,$reg)){
                    $this->section_id[$key] = $reg[1];
                    $this->user_id[$key] = $reg[2];
                }
                else {
                    echo "正しいデータが送信されていません。";
                    exit();
                }

                if ($_REQUEST['power_flg_'.$val] !== "0" &&
                    $_REQUEST['power_flg_'.$val] !== "1" &&
                    $_REQUEST['power_flg_'.$val] !== "2" &&
                    $_REQUEST['power_flg_'.$val] !== "-1") {
                    echo "正しいデータが送信されていません。";
                    exit();
                }

                if ($this->top_flg) {
                    if ($_REQUEST['power_flg_'.$val] == "-1") {
                        echo "親フォルダから権限を削除する場合は、編集画面でメンバーを再設定してください。";
                        exit();
                    }
                }

                // 所属する部署に所属しているユーザは-2にしてみる
                if ($_REQUEST['power_flg_'.$val] >= 0 && $reg[2]) {
                    // 既に登録の部署メンバーとして所属している
                    if ($this->MdbSectionusers->checkEntryUser($group_id,$reg[2])) {
                        $this->power_flg[$key] = -2;

                    } else {
                        // 所属部署のメンバーではないとき
                        foreach($this->data_id as $key2 => $val2) {
                            // これから所属する部署に所属してないかチェック
                            if(preg_match("/^row([0-9]+)_([0-9]+)$/",$val2,$reg2)){
                                if ($reg2[1] && $_REQUEST['power_flg_'.$val2] >= 0) {
                                    $where = array("section_id=".Db_Common::escape_string($reg2[1]),
                                                   "user_id=".Db_Common::escape_string($reg[2]));
                                    if ($this->MdbSectionusers->getExistCheck($where)) {
                                        $this->power_flg[$key] = -2;
                                    } else {
                                        $this->power_flg[$key] = $_REQUEST['power_flg_'.$val];
                                        $this->data_cnt ++;
                                    }
                                } else {
                                    $this->power_flg[$key] = $_REQUEST['power_flg_'.$val];
                                    $this->data_cnt ++;
                                }
                            } else {
                                $this->power_flg[$key] = $_REQUEST['power_flg_'.$val];
                                $this->data_cnt ++;
                            }
                        }
                    }

                } else {
                    $this->power_flg[$key] = $_REQUEST['power_flg_'.$val];
                    $this->data_cnt ++;
                }

                if (preg_match("/[^0-9]/",$this->section_id[$key]) ||
                    preg_match("/[^0-9]/",$this->user_id[$key]) ||
                    !preg_match("/^-?[0-9]$/",$this->power_flg[$key]) ||
                    ($this->section_id[$key]>0 && $this->power_flg[$key] == -1 && $this->top_flg == 1) ||
                    !preg_match("/^-?[0-9]$/",$this->old_power_flg[$key])) {
                    echo "正しいデータが送信されていません。";
                    exit();
                }
            }
        }

        if($this->data_id !="all") {
            // 全部-2のエラーだったらエラー
            $key_err = null;
            $ary_err = array();
            $ary_err = array_diff_assoc($this->power_flg,$this->old_power_flg);
            if ($ary_err) {
                for($errcnt=0; $errcnt<5; $errcnt++) {
                    $errkey = $errcnt;
                    if ($errcnt == 4) { $errkey == -1; }
                    $key_err = array_search($errkey,$ary_err);
                    if ($key_err >= 0) {
                        break;
                    }
                }
            }
            if ($key_err == -1) {
                echo "選択したユーザは、部署メンバーとしてグループに所属しています。";
                exit();
            }
        }

        //---------------------------------------
        //前準備
        //---------------------------------------
        //データ更新の準備
        $setting_sub_folder_id = $this->ShareLogic->getPowerSettingFolder($sub_folder_id,$group_id);
        if($setting_sub_folder_id != $sub_folder_id) {
            //該当フォルダに権限設定データがない場合。
            //→親フォルダの設定をコピー
            $this->ShareLogic->copyPowerSetting($setting_sub_folder_id,$sub_folder_id,$this->top_flg,$group_id);
        }
        //→0,0を作成
        $this->FolderLogic->initPowerSetting($sub_folder_id,$this->top_flg,$group_id);

        //--------------------------------------
        //データ更新
        //--------------------------------------
        $history_id = 0;
        $memo = "";
        if($this->data_id=="all") {
            //一括編集
            $this->FolderLogic->setAllPowerListEx($sub_folder_id,$this->power_flg_section,$this->power_flg_user,$this->top_flg,$group_id,$this->subfolder);
            //サブフォルダの更新
            if($this->subfolder=='1') {
                $this->ShareLogic->setAllPowerToSubfolder($sub_folder_id,$group_id);
            }
            // // 環境分離ONで、権限を削除した時
            if (USE_ENV_SEPARATION_FLG && ($this->power_flg_section == -1 || $this->power_flg_user == -1)) {
                $this->SuperiorLogic->autoRevertExternalizationForFolder($sub_folder_id);
            }

            // ログ
            $history_id = 76;
            $memo.= "グループ名"."：".$this->DtoGroups->getName()."\n";
            $memo.= "フォルダ名"."：".$this->DtoFolder->getFolder_name();
            if($this->subfolder=='1') {
                $memo.= "（サブフォルダ：更新）";
            }
            $memo.= "\n";
            $memo.= "権限：";
            $memo.= "\n　 [部署] " . $this->getPowerShortNameExt($this->power_flg_section);
            $memo.= "\n　 [ユーザ] " . $this->getPowerShortNameExt($this->power_flg_user);

        } else {
            //編集
            //ロック解除
            if(FILE_LOCK_OPTION_FLG) {
                $this->FolderLogic->updateLockFile($sub_folder_id,$this->section_id, $this->user_id,$this->power_flg,$group_id, $this->subfolder);
            }
            //データ更新
            $this->FolderLogic->setPowerlist($sub_folder_id,$this->section_id,$this->user_id,$this->power_flg,$this->top_flg,$group_id);
            //サブフォルダの更新
            if ($this->subfolder == '1') {
                $this->ShareLogic->setPowerToSubfolder($sub_folder_id, $this->section_id, $this->user_id, $this->power_flg, $group_id);
            }
            // 環境分離ONで、権限を削除した時
            if (USE_ENV_SEPARATION_FLG && in_array(-1, $this->power_flg)) {
                $this->SuperiorLogic->autoRevertExternalizationForFolder($sub_folder_id);
            }

            // ログ
            $history_id = 75;
            $memo_section = "";
            $memo_user    = "";
            foreach ($this->section_id as $key => $val) {

                // 権限
                $memo_privilege = "：" . "  ";
                if ($this->old_power_flg[$key] !== $this->power_flg[$key]) {
                    $memo_privilege.= $this->getPowerShortNameExt($this->old_power_flg[$key]);
                    $memo_privilege.= " → ";
                }
                $memo_privilege.= $this->getPowerShortNameExt($this->power_flg[$key]);
                // 部署・ユーザ
                if (isset($sectionkeylist[$this->section_id[$key]])) {
                    // 部署
                    $memo_section.= $sectionkeylist[$this->section_id[$key]] . $memo_privilege . "\n";
                } else if (isset($userkeylist[$this->user_id[$key]])) {
                    // ユーザ
                    $memo_user.= $userkeylist[$this->user_id[$key]] . $memo_privilege . "\n";
                }
            }
            if ($memo_section !== "") {
                $memo_section = "[" . "部署" . "]\n" . $memo_section;
            }
            if ($memo_user !== "") {
                $memo_user = "[" . "ユーザ" . "]\n" . $memo_user;
            }

            $memo.= "グループ名"."：".$this->DtoGroups->getName()."\n";
            $memo.= "フォルダ名"."：".$this->DtoFolder->getFolder_name();
            if($this->subfolder=='1') {
                $memo.= "（サブフォルダ：更新）";
            }
            $memo.= "\n";
            $memo.= $memo_section . $memo_user;
        }

        if ($memo !== "") {
            $this->DtoLog->setUser_id("");
            $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
            $this->DtoLog->setFile_id("");
            $this->DtoLog->setShare_id($group_id);
            $this->DtoLog->setHistory_id($history_id);
            $this->DtoLog->setMemo($memo);
            $this->MdbLog->Logging($this->DtoLog);
        }
        exit();
    }

    // Setter
    function setSession(&$session) {
        $this->session = & $session;
    }
    function setDtoUser(&$DtoUser) {
        $this->DtoUser = & $DtoUser;
    }
    function setMdbUser(&$MdbUser) {
        $this->MdbUser = & $MdbUser;
    }
    function setDtoFolder(&$DtoFolder) {
        $this->DtoFolder = & $DtoFolder;
    }
    function setMdbFolder(&$MdbFolder) {
        $this->MdbFolder = & $MdbFolder;
    }
    function setDtoLog(&$DtoLog) {
        $this->DtoLog = & $DtoLog;
    }
    function setMdbLog(&$MdbLog) {
        $this->MdbLog = & $MdbLog;
    }
    function setMdbSection(&$MdbSection) {
        $this->MdbSection = & $MdbSection;
    }
    function setMdbSectionusers($MdbSectionusers) {
        $this->MdbSectionusers = $MdbSectionusers;
    }
    function setShareLogic(&$ShareLogic) {
        $this->ShareLogic = & $ShareLogic;
    }
    function setFolderLogic(&$FolderLogic) {
        $this->FolderLogic = & $FolderLogic;
    }
    function setDtoGroups(&$DtoGroups) {
        $this->DtoGroups = & $DtoGroups;
    }
    function setMdbGroups(&$MdbGroups) {
        $this->MdbGroups = & $MdbGroups;
    }

    function setData_id($data_id){
        $this->data_id = $data_id;
    }
    function setOld_power_flg($old_power_flg) {
        $this->old_power_flg = $old_power_flg;
    }
    function setPowerEditToken($powerEditToken) {
        $this->PowerEditToken = $powerEditToken;
    }
    function setPower_flg($power_flg) {
        $this->power_flg = $power_flg;
    }
    function setPower_flg_section($power_flg_section) {
        $this->power_flg_section = $power_flg_section;
    }
    function setPower_flg_user($power_flg_user) {
        $this->power_flg_user = $power_flg_user;
    }
    function setAdd_flg($add_flg) {
        $this->add_flg = $add_flg;
    }
    function setSubfolder($subfolder) {
        $this->subfolder = $subfolder;
    }

    // Getter
    //権限文言取得
    function getPowerShortName($param) {
        $power_flg = $param['id'];
        switch($power_flg) {
            case '0': return "読み出しのみ";
            case '1': return "読み／書き";
            case '2': return "読み／書き／フォルダ操作";
            case '3': return "管理者";
            default: return "なし";
        }
    }

    // 権限文言取得（ログ記録用）
    function getPowerShortNameExt($power_flg) {
        $name = "";
        if ($power_flg == -1 && $this->add_flg) {
            $name = "追加しない";
        }else if ($power_flg == -1 && $this->top_flg) {
            $name = "削除";
        } else {
            $name = $this->getPowerShortName(array("id"=>$power_flg));
        }
        return $name;
    }
    function setMdbUpload($MdbUpload)
    {
        $this->MdbUpload = $MdbUpload;
    }
    function setSuperiorLogic($SuperiorLogic)
    {
        $this->SuperiorLogic = $SuperiorLogic;
    }
}
?>

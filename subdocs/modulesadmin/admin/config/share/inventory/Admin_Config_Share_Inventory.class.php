<?php
require_once(MODULE_DIR . '/common/common.php');

class Admin_Config_Share_Inventory {

    var $session;
    var $MdbGroups_users;
    var $DtoGroups;
    var $MdbGroups;
    var $MdbGroups_admin_users;
    var $DtoFolder;
    var $MdbFolder;
    var $DtoUser;
    var $MdbUser;
    var $DtoSection;
    var $MdbSection;
    var $MdbSectionusers;
    var $DtoAdminUser;

    var $DtoLog;
    var $MdbLog;

    function execute() {

        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Share_Inventoryが実行されました", "Admin_Config_Share_Inventory#execute");

        //エラー用
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        //一件以上あるかどうかチェック
        if( $this->MdbGroups_users->isSetting()>0 ){
            $errorList->add("Admin_Config_Share_Inventory::error", "グループのアクセス権限は設定されていません。");
            return "error";
        }
        /* 2012-11-06 @yano add comment
        //CSV用ヘッダー出力
        $browser = new CrossBrowser();
        $file_name = $browser->FilenameExchange("全グループのアクセス権限-".date("Ymd").".csv");
        $browser->DownloadHeader($file_name,0,"application/octet-stream");
        */

        // 2012-11-06@yano header出力とデータ出力が間に合わないので変数に入れて出力するよう修正
        $data = "";

        //CSVヘッダー行
        //$this->_outputline("グループ名","フォルダパス","部署名","アカウント名","名前(漢字)","メールアドレス","header");
        $data .= $this->_outputline("グループ名","フォルダパス","部署名","アカウント名","名前(漢字)","メールアドレス","header");

        //グループリスト
        $where = array();
        if($this->DtoAdminUser->getLogin_admin_level() < 5) {
            $where[] = "admin_edit_flg = '1'";
        }
        $this->MdbGroups->getList($this->DtoGroups,"name asc", $where);
        $groups_list = $this->DtoGroups->getGroupsList();

        //グループ毎ループ
        if($groups_list && is_array($groups_list)) foreach($groups_list as $group_val) {
            //グループ処理
            //$this->_subFolderLoop($group_val['id'], $group_val['name'], $group_val['folder_id'], "", 1);
            $data .= $this->_subFolderLoop($group_val['id'], $group_val['name'], $group_val['folder_id'], "", 1);
        }

        //CSV用ヘッダー出力
        $browser = new CrossBrowser();
        $file_name = $browser->FilenameExchange("全グループのアクセス権限-".date("Ymd").".csv");
        $browser->DownloadHeader($file_name, strlen($data), "application/octet-stream");
        // 2012-11-06 @yano 出力
        echo $data;

        // Log
        $history_id = 138; // 138:各種設定＞グループ共有機能設定＞全グループのアクセス権限
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $memo = "全グループについてアクセス権限とユーザ情報のエクスポート\n";
        $memo.= "グループ：" . count($groups_list) . "件\n";
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);
        unset($groups_list);
        exit;
    }

    //************************************************************
    // logic
    //************************************************************
    function _subFolderLoop($group_id, $group_name, $folder_id, $folder_path, $top_flg) {

        //---------------------------------------------
        // 当フォルダのフォルダパスを生成
        //---------------------------------------------
        //フォルダ情報
        $this->DtoFolder->setFolder_id($folder_id);
        $this->MdbFolder->setFolderInfo($this->DtoFolder);

        if($top_flg) $folder_path="";
        $folder_path .= "/".$this->DtoFolder->getFolder_name();

        // 2013-01-23@uchida 出力用
        $data = "";

        //---------------------------------------------
        // 当フォルダがトップフォルダ時は管理者出力
        //---------------------------------------------
        if($top_flg) {
            //グループのトップフォルダ

            //管理者
            $admin_list = $this->MdbGroups_admin_users->getLeaderWhere($group_id);
            if($admin_list && is_array($admin_list)) foreach($admin_list as $admin_val) {

                //ユーザ情報
                $this->DtoUser->setUser_id($admin_val['user_id']);
                $this->MdbUser->getUserInfo($this->DtoUser);
                //出力
                $data .= $this->_outputline($group_name, "", "", $this->DtoUser->getAccount(), $this->DtoUser->getUser_name(), $this->DtoUser->getMailaddr(), 3);
            }
            unset($admin_list);
        }

        //---------------------------------------------
        // 当フォルダの権限の出力
        //---------------------------------------------
        //権限リスト
        $power_list = $this->MdbGroups_users->getUserlistEx($group_id,$folder_id,"section_name asc, user_name asc");

        // 2012-11-06@yano data 返却に変更
        // 2013-01-23@uchida mod 管理者出力の上にもっていく
        //$data = "";

        //権限ループ

        if($power_list && is_array($power_list)) foreach($power_list as $power_val) {
            //出力
            //$this->_outputline($group_name, $folder_path, $power_val['section_name'], $power_val['account'], $power_val['user_name'], $power_val['mailaddr'], $power_val['readonly_flg']);
            $data .= $this->_outputline($group_name, $folder_path, $power_val['section_name'], $power_val['account'], $power_val['user_name'], $power_val['mailaddr'], $power_val['readonly_flg']);
        }
        unset($power_list);

        //---------------------------------------------
        // 当フォルダのサブフォルダ
        //---------------------------------------------
        //サブフォルダリスト
        $folder_list = $this->MdbFolder->childrenList(0,$folder_id);
        //サブフォルダループ
        if($folder_list && is_array($folder_list)) foreach($folder_list as $folder_val) {
            //再帰呼び出し
            $data .= $this->_subFolderLoop($group_id, $group_name, $folder_val['folder_id'], $folder_path, 0);
        }
        unset($folder_list);

        return $data;
    }

    //************************************************************
    // utility
    //************************************************************
    function _outputline($group_name, $folder_path, $section_name, $account, $user_name, $mailaddr, $readonly_flg) {
        // 出力フォーマット
        // グループ名,フォルダパス,部署名,名前(漢字),メールアドレス),権限
        $line  = $this->_csv_quoted($group_name);
        $line .= ",".$this->_csv_quoted($folder_path);
        $line .= ",".$this->_csv_quoted($section_name);
        $line .= ",".$this->_csv_quoted($account);
        $line .= ",".$this->_csv_quoted($user_name);
        $line .= ",".$this->_csv_quoted($mailaddr);
        $line .= ",".$this->_csv_quoted($this->_flgName($readonly_flg));
        $line .= "\r\n";
        return mb_convert_encoding($line,"sjis-win");
    }
    function _flgName($readonly_flg) {
        switch($readonly_flg) {
        case 'header':
            return '権限';
        case 1:
        case '1':
            return '読み出しのみ';
        case 2:
        case '2':
            return '読み／書き';
        case 3:
        case '3':
            return 'グループ管理者';
        case 0:
        case '0':
            return '読み／書き／フォルダ操作';
        }
    }
    function _csv_quoted($value)
    {
        if (! is_string($value)) return $value;
        $value = preg_replace('/"/', '""', $value);
        if (preg_match('/["\r\n,]/', $value) > 0) {
            $value = '"' . $value . '"';
        }
        return $value;
    }

    //************************************************************
    // setter
    //************************************************************
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoGroups($DtoGroups) {
        $this->DtoGroups = $DtoGroups;
    }
    function setMdbGroups($MdbGroups) {
        $this->MdbGroups = $MdbGroups;
    }
    function setDtoSection($DtoSection) {
        $this->DtoSection = $DtoSection;
    }
    function setMdbSection($MdbSection) {
        $this->MdbSection = $MdbSection;
    }
    function setDtoUser($DtoUser) {
        $this->DtoUser = $DtoUser;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = $MdbUser;
    }
    function setMdbGroups_users($MdbGroups_users) {
        $this->MdbGroups_users = $MdbGroups_users;
    }
    function setMdbGroups_admin_users($MdbGroups_admin_users) {
        $this->MdbGroups_admin_users = $MdbGroups_admin_users;
    }
    function setDtoFolder($DtoFolder) {
        $this->DtoFolder = $DtoFolder;
    }
    function setMdbFolder($MdbFolder) {
        $this->MdbFolder = $MdbFolder;
    }
    function setMdbSectionusers(&$MdbSectionusers) {
        $this->MdbSectionusers = &$MdbSectionusers;
    }
    function setDtoLog($DtoLog){
        $this->DtoLog = $DtoLog;
    }
    function setMdbLog($MdbLog){
        $this->MdbLog = $MdbLog;
    }
    function setDtoAdminUser($DtoAdminUser) {
        $this->DtoAdminUser = $DtoAdminUser;
    }

    //************************************************************
    // getter
    //************************************************************
}
?>

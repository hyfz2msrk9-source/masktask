<?php
require_once( MODULE_DIR . "/common/common.php");

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * [機能説明] 共有フォルダのロックファイルの解除-確認-完了
 */
class Admin_Config_Share_Unlock_Done {
    var $session;
    var $DtoUpload;
    var $MdbUpload;
    var $MdbFolder;
    var $MdbGroups;
    var $MdbUser;
    var $DtoUser;
    var $MdbAdminUser;
    var $DtoAdminUser;
    var $GroupDto;
    var $DtoGroups;
    var $DtoLog;
    var $MdbLog;
    var $AdminUserLogic;

    var $file_id;

    var $file_list_back;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Share_Unlock_Doneが実行されました","Admin_Config_Share_Unlock_Done#execute");

        //エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        if (!$this->file_list_back) {
            $this->session->removeParameter('query');
            $this->session->removeParameter('qtype');
            $this->session->removeParameter('page');
            $this->session->removeParameter('sortname');
            $this->session->removeParameter('sortorder');
            $this->session->removeParameter('rp');
            $this->GroupDto->setPage(0);
        }

        // ファイルロック機能のチェック
        if(!FILE_LOCK_OPTION_FLG){
            $errorList->add("Admin_Config_Share_Unlock_Done::error", "ファイルロック機能が有効ではありません。");
            return "error";
        }

        // ファイルID
        if( is_null($this->DtoUpload->getFile_id())  || !preg_match("/^[0-9]+$/", $this->DtoUpload->getFile_id())){
            $errorList->add("Admin_Config_Share_Unlock_Done::error", "不正な操作が行われました。");
            return "error";
        }

        // ファイル情報を取得
        $this->MdbUpload->setUploadInfo2($this->DtoUpload);
        $lock_user_info = $this->MdbUser->getInfo($this->DtoUpload->getLock_user_id());

        // ロックがかかっているかどうか
        if (!$this->DtoUpload->getLock_flg() || $this->DtoUpload->getDel_flg()) {
            $errorList->add("Admin_Config_Share_Unlock_Done::error", "ファイルは削除されたか、既にロックが解除されています。");
            return "error";
        }

        // 共有グループのファイルチェック
        if($this->DtoUpload->getShare_flg() != 1){
            $errorList->add("Admin_Config_Share_Unlock_Done::error", "ファイルが正しくありません。");
            return "error";
        }

        // 共有グループ情報
        $folder_list = $this->MdbFolder->parentList(0,$this->DtoUpload->getFolder_id());
        // パス
        $f = "";
        $folder_info = "";
        for($i=0; $i < count($folder_list); $i++){
            $folder_info = $this->MdbFolder->setFolderInfo_direct($folder_list[$i]);
            if( $f != ""){
                $f = $folder_info["folder_name"]."/".$f;
            }else{
                $f = $folder_info["folder_name"];
            }
        }

        // 共有グループのID
        $share_group_id = array_pop($folder_list);
        $where[] = "folder_id = ". Db_Common::escape_string($share_group_id);
        $this->MdbGroups->getInfoWhere($this->DtoGroups,$where);

        //ファイルのサブ管理者チェック
        if(!$this->AdminUserLogic->checkAdminGroup($this->DtoAdminUser, $this->DtoGroups)) {
            $errorList->add("Admin_Config_Share_Unlock_Done::error", "権限がありません。再度、ロックファイル一覧よりファイルを選択してください。");
            return "error";
        }

        // ロック解除
        $this->DtoUpload->setLock_flg(0);
        $this->DtoUpload->setLock_user_id(0);
        $this->DtoUpload->setLock_dt(NULL);
        $this->MdbUpload->updateOverwrite($this->DtoUpload);

        // ログ(管理者側)
        // Log
        $history_id = 172; // 172:グループ共有に関する設定>ロックファイル一覧>ファイルのロック解除
        $memo  = "";
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
        $this->DtoLog->setGroup_id($this->DtoGroups->getId());
        $this->DtoLog->setFile_id($this->DtoUpload->getFile_id());
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $memo  = 'グループ名：';
        $memo .= " ".$this->DtoGroups->getName() ."\n";
        $memo .= 'ファイル名：';
        $memo .= " /".$f."/".$this->DtoUpload->getFile_name()."\n";
        $memo .= 'ロックユーザ：';
        $memo .= " ".$lock_user_info["user_name"]."(".$lock_user_info["account"].")\n";
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        // ログ(ユーザ側)
        // Log
        $history_id = 1017; // 1017:ファイルのロック解除
        $memo  = "";
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id("");
        $this->DtoLog->setGroup_id($this->DtoGroups->getId());
        $this->DtoLog->setFile_id($this->DtoUpload->getFile_id());
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->DtoLog->setShare_id($share_group_id);
        $memo .= '管理者がロック解除しました。\n';
        $memo .= 'グループ名：';
        $memo .= " ".$this->DtoGroups->getName() ."\n";
        $memo .= 'ファイル名：';
        $memo .= " /".$f."/".$this->DtoUpload->getFile_name()."\n";
        $memo .= 'ロックユーザ：';
        $memo .= " ".$lock_user_info["user_name"]."(".$lock_user_info["account"].")\n";
        $admin_info = $this->MdbAdminUser->getAdminuserInfo2( $this->session->getParameter('admin_id') );
        $memo .= 'ロック解除した管理者：';
        $memo .= $admin_info["account"];

        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        // 元のロックユーザにメール通知
        $mail = new MailSend();
        // 共有フォルダ権限があるユーザ
        $ret = $mail->Unlock_Mail($this->DtoUser,$this->MdbUser,$this->DtoGroups," /".$f,$this->DtoUpload->getFile_name(),$lock_user_info["user_id"],$this->DtoUpload->getMemo());

        return "success";
    }

    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoUpload($DtoUpload){
        $this->DtoUpload = $DtoUpload;
    }
    function setMdbUpload($MdbUpload){
        $this->MdbUpload = $MdbUpload;
    }
    function setMdbFolder($MdbFolder){
        $this->MdbFolder = $MdbFolder;
    }
    function setMdbGroups($MdbGroups){
        $this->MdbGroups = $MdbGroups;
    }
    function setGroupDto($GroupDto){
        $this->GroupDto = $GroupDto;
    }
    function setDtoGroups($DtoGroups){
        $this->DtoGroups = $DtoGroups;
    }
    function setDtoUser($DtoUser){
        $this->DtoUser = $DtoUser;
    }
    function setMdbUser($MdbUser){
        $this->MdbUser = $MdbUser;
    }
    function setDtoAdminUser($DtoAdminUser){
        $this->DtoAdminUser = $DtoAdminUser;
    }
    function setMdbAdminUser($MdbAdminUser){
        $this->MdbAdminUser = $MdbAdminUser;
    }
    function setDtoLog($DtoLog){
        $this->DtoLog = $DtoLog;
    }
    function setMdbLog($MdbLog){
        $this->MdbLog = $MdbLog;
    }
    function setAdminUserLogic($AdminUserLogic) {
        $this->AdminUserLogic = $AdminUserLogic;
    }

}
/*
 * Local Variables:
 * mode: php
 * coding: utf-8-unix
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * indent-tabs-mode: nil
 * End:
 */
?>

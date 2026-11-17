<?php
require_once( MODULE_DIR . "/common/common.php");

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * [機能説明] 共有フォルダのロックファイルの解除-確認
 */
class Admin_Config_Share_Unlock_Confirm {
    var $session;
    var $DtoUpload;
    var $MdbUpload;
    var $MdbFolder;
    var $MdbGroups;
    var $DtoGroups;
    var $MdbUser;
    var $GroupDto;
    var $DtoAdminUser;
    var $AdminUserLogic;

    var $file_id;
    var $group_name;
    var $folder_path;
    var $file_name;
    var $file_size;
    var $user_name;
    var $lock_user_name;
    var $lock_dt;

    var $file_list_back;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Share_Unlock_Confirmが実行されました","Admin_Config_Share_Unlock_Confirm#execute");

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
            $errorList->add("Admin_Config_Share_Unlock_Confirm::error", "ファイルロック機能が有効ではありません。");
            return "error";
        }

        // ファイルID
        if( is_null($this->DtoUpload->getFile_id()) || !preg_match("/^[0-9]+$/", $this->DtoUpload->getFile_id())){
            $errorList->add("Admin_Config_Share_Unlock_Confirm::error", "不正な操作が行われました。");
            return "error";
        }

        // ファイル情報を取得
        $this->MdbUpload->setUploadInfo2($this->DtoUpload);

        // 解除済か削除済のときはエラーに
        // ロックがかかっているかどうか
        if (!$this->DtoUpload->getLock_flg() || $this->DtoUpload->getDel_flg()) {
            $errorList->add("Admin_Config_Share_Unlock_Confirm::error", "ファイルは削除されたか、既にロックが解除されています。");
            return "error";
        }

        // 共有グループのファイルチェック
        if($this->DtoUpload->getShare_flg() != 1){
            $errorList->add("Admin_Config_Share_Unlock_Confirm::error", "ファイルが正しくありません。");
            return "error";
        }

        // ファイルID
        $this->file_id = $this->DtoUpload->getFile_id();
        // ファイルサイズ
        $size = $this->DtoUpload->getFile_size();
        $this->file_size = round( $size/1024/1024,2 );
        // ファイル名
        $this->file_name = $this->DtoUpload->getFile_name();
        // フォルダパス
        $folder_list = $this->MdbFolder->parentList(0,$this->DtoUpload->getFolder_id());
        $f = "";
        $folder_info = "";
        $parent_folder_id = "";
        for($i=0; $i < count($folder_list); $i++){
            $folder_info = $this->MdbFolder->setFolderInfo_direct($folder_list[$i]);
            if( $f != ""){
                $f = $folder_info["folder_name"]."/".$f;
            }else{
                $f = $folder_info["folder_name"];
            }
            $parent_folder_id = $folder_list[$i];
        }
        $this->folder_path = "/".$f;
        // 共有グループ名
        $glist = $this->MdbGroups->getInfoByFolder($parent_folder_id);
        if(is_array($glist) && $glist) {
            $this->DtoGroups->setGroupsInfo(array_shift($glist));
        } else {
            $errorList->add("Admin_Config_Share_Unlock_Confirm::error", "グループ情報が取得できませんでした。");
            return "error";
        }

         //ファイルのサブ管理者チェック
        if(!$this->AdminUserLogic->checkAdminGroup($this->DtoAdminUser, $this->DtoGroups)) {
            $errorList->add("Admin_Config_Share_Unlock_Confirm::error", "権限がありません。再度、ロックファイル一覧よりファイルを選択してください。");
            return "error";
        }

        $this->group_name = $this->DtoGroups->getName();
        // ファイルアップロードユーザ名
        $user = $this->MdbUser->getInfo($this->DtoUpload->getUser_id());
        $this->user_name = $user["user_name"]."(".$user["account"].")";
        // ロックユーザ名
        $lock_user = $this->MdbUser->getInfo($this->DtoUpload->getLock_user_id());
        $this->lock_user_name = $lock_user["user_name"]."(".$lock_user["account"].")";
        // ロック日時
        $this->lock_dt = $this->DtoUpload->getLock_dt();

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
    function setDtoGroups($DtoGroups){
        $this->DtoGroups = $DtoGroups;
    }
    function setGroupDto($GroupDto){
        $this->GroupDto = $GroupDto;
    }
    function setMdbUser($MdbUser){
        $this->MdbUser = $MdbUser;
    }
    function setDtoAdminUser($DtoAdminUser) {
        $this->DtoAdminUser = $DtoAdminUser;
    }
    function setAdminUserLogic($AdminUserLogic) {
        $this->AdminUserLogic = $AdminUserLogic;
    }

    // getter
    function getFile_id(){
        return $this->file_id;
    }
    function getGroup_name(){
        return $this->group_name;
    }
    function getFolder_path(){
        return $this->folder_path;
    }
    function getFile_name(){
        return $this->file_name;
    }
    function getFile_size(){
        return $this->file_size;
    }
    function getUser_name(){
        return $this->user_name;
    }
    function getLock_user_name(){
        return $this->lock_user_name;
    }
    function getLock_dt(){
        return $this->lock_dt;
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

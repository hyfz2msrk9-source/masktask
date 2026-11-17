<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Share_Access_Edit_Delete {
    var $session;
    var $DtoGroups;
    var $MdbGroups;
    var $DtoGroupsaccesses;
    var $MdbGroupsaccesses;
    var $GroupsaccessesDto;
    var $GroupsaccessesLogic;
    var $DtoLog;
    var $MdbLog;
    var $DtoAdminUser;
    var $AdminUserLogic;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Share_Access_Edit_Deleteが実行されました","Admin_Config_Share_Access_Edit_Delete#execute");

        //エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        // パラメータチェック
        if (!is_numeric($this->DtoGroups->getId())) {
            return "error";
        }

        // グループ情報の取得
        $this->MdbGroups->getInfo($this->DtoGroups);

        if (!$this->DtoGroups->getId()) {
            // 存在しないグループの選択
            return "error";
        }

        //管理者チェック
        if(!$this->AdminUserLogic->checkAdminGroup($this->DtoAdminUser, $this->DtoGroups)) {
            $errorList->add("Admin_Config_Share_Access_Edit_Delete::error", "権限がありません。再度、一覧からグループを選択してください。");
            return "error";
        }

        // Log
        $history_id = 176; // 176:グループ共有に関する設定>IP制限>IPアドレス削除
        $list = $this->getAccessList();
        $memo = "";

        $this->DtoGroupsaccesses->setGroup_id($this->DtoGroups->getId());
        if($this->DtoGroupsaccesses->getAccess_id() && is_array($this->DtoGroupsaccesses->getAccess_id())) {
            foreach($this->DtoGroupsaccesses->getAccess_id() as $val){
                if(array_key_exists($val, $list)){
                    $memo .= $list[$val] . "\n";
                }
            }
        } else {
            $errorList->add("Admin_Config_Share_Access_Edit_Delete::error", "削除するIPアドレスを選択してください。");
            return "input";
        }

        //削除
        $this->GroupsaccessesLogic->deleteGroupsaccesses($this->DtoGroupsaccesses, $this->MdbGroupsaccesses);

        // メモがあれば残す
        if($memo){

            $group_memo = "";
            if($this->DtoGroupsaccesses->getGroup_id()) {
                // グループ情報の取得
                $this->DtoGroups->setId($this->DtoGroupsaccesses->getGroup_id());
                $this->MdbGroups->getInfo($this->DtoGroups);
                $group_memo .= "グループ名：". $this->DtoGroups->getName() . "\n";
            }

            $memo = $group_memo."IPアドレス：".$memo;
            $this->DtoLog->setUser_id("");
            $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
            $this->DtoLog->setFile_id("");
            $this->DtoLog->setHistory_id($history_id);
            $this->DtoLog->setMemo($memo);
            $this->MdbLog->Logging($this->DtoLog);
        }

        return "success";
    }

    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoGroups($DtoGroups){
        $this->DtoGroups = $DtoGroups;
    }
    function setMdbGroups($MdbGroups) {
        $this->MdbGroups = $MdbGroups;
    }
    function setDtoGroupsaccesses($DtoGroupsaccesses) {
        $this->DtoGroupsaccesses = $DtoGroupsaccesses;
    }
    function setMdbGroupsaccesses($MdbGroupsaccesses) {
        $this->MdbGroupsaccesses = $MdbGroupsaccesses;
    }
    function setGroupsaccessesDto($GroupsaccessesDto) {
        $this->GroupsaccessesDto = $GroupsaccessesDto;
    }
    function setGroupsaccessesLogic($GroupsaccessesLogic) {
        $this->GroupsaccessesLogic = $GroupsaccessesLogic;
    }
    function setDtoLog ( &$DtoLog ) {
        $this->DtoLog =& $DtoLog;
    }
    function setMdbLog ( &$MdbLog ) {
        $this->MdbLog =& $MdbLog;
    }
    function setDtoAdminUser($DtoAdminUser) {
        $this->DtoAdminUser = $DtoAdminUser;
    }
    function setAdminUserLogic($AdminUserLogic) {
        $this->AdminUserLogic = $AdminUserLogic;
    }

    // getter

    // IPアドレス
    function getIp_addr(){
      return $this->DtoGroupsaccesses->getIp_addr();
    }
    // IPアドレス一覧取得
    function getAccessList(){
        return $this->GroupsaccessesLogic->getAccessKeyList($this->MdbGroupsaccesses, $this->DtoGroups->getId());
    }

}
?>

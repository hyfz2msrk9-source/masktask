<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Domain_Add {

    var $Session;
    var $DtoDomain;
    var $MdbDomain;
    var $DtoUser;
    var $MdbUser;
    var $DomainLogic;
    var $DtoLog;
    var $MdbLog;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Domain_Addが実行されました","Admin_Config_Domain_Add#execute");

        // エラーリスト初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        // リストチェック
        if (!array_key_exists($this->DtoDomain->getList_type(), $this->getList_Type_Options())) {
            $errorList->add("Admin_Config_Domain_Add::error", "不正なパラメータが入力されています。");
            return "input";
        }

        // ドメイン整合性チェック
        if (!$this->DomainLogic->checkDomain($this->DtoDomain->getDomain_name())) {
            $errorList->add("Admin_Config_Domain_Add::error", "ドメインを正しく入力してください。");
            return "input";
        }

        // ドメイン文字長チェック
        if ( strlen($this->DtoDomain->getDomain_name()) > 255 ) {
            $errorList->add("Admin_Config_Domain_Add::error", "ドメインが255文字を超えています。255文字以内で入力してください。");
            return "input";
        }

        // ドメイン存在チェック
        if ($this->DomainLogic->existsDomain($this->MdbDomain, $this->DtoDomain->getList_type(), $this->DtoDomain->getDomain_name(), $this->DtoDomain->getUser_id())) {
            $errorList->add("Admin_Config_Domain_Add::error", "入力したドメインは、既に存在しています。");
            return "input";
        }
        /*
        // 共通ドメインの場合、DB内の同じドメインをクリアする（ドメイン重複防止）
        if ($this->DtoDomain->getUser_id() == "0") {
            $where = array();
            $where[] = "list_type = " . Db_Common::escape_string($this->DtoDomain->getList_type());
            $where[] = "domain_name = '" . Db_Common::escape_string($this->DtoDomain->getDomain_name()) . "'";
            $this->MdbDomain->deleteWhere($where);
        }
        */

        $this->MdbUser->getUserInfo($this->DtoUser);
        if ($this->DtoUser->getDel_flg()) {
            $errorList->add("Admin_Config_Domain_Add::error", "このユーザは既に削除されています。");
            return "input";
        }

        // 登録
        $this->MdbDomain->insert($this->DtoDomain);

        // Log
        $history_id = 155;
        $memo  = "";
        if($this->DtoDomain->getUser_id() =="0") {
            $kind = "共通設定";
        } else {
            $kind = "個人設定";
        }
        if($this->DtoDomain->getList_type()=="0") {
            $type = "ホワイトリスト";
        } else {
            $type = "ブラックリスト";
        }
        $memo .= "[".$kind."＞".$type."]\n";
        if($this->DtoDomain->getUser_id() !="0") {
            $memo .= "アカウント：".$this->DtoUser->getAccount()."\n";
            $memo .= "ユーザ名：".$this->DtoUser->getUser_name()."(".$this->DtoUser->getUser_kana().")\n";
        }
        $memo .= "ドメイン：". $this->DtoDomain->getDomain_name() . "\n";

        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id( $this->Session->getParameter('admin_id') );
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        return "success";
    }
    // setter
    function setSession(&$obj) {
        $this->Session = &$obj;
    }
    function setDtoDomain($DtoDomain){
        $this->DtoDomain = $DtoDomain;
    }
    function setMdbDomain($MdbDomain) {
        $this->MdbDomain = $MdbDomain;
    }
    function setDomainLogic($DomainLogic) {
        $this->DomainLogic = $DomainLogic;
    }
    function setDtoUser($DtoUser) {
        $this->DtoUser = $DtoUser;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = $MdbUser;
    }
    function setDtoLog ( &$DtoLog ) {
        $this->DtoLog =& $DtoLog;
    }
    function setMdbLog ( &$MdbLog ) {
        $this->MdbLog =& $MdbLog;
    }


    // getter
    function getList_Type_Options() {
        return GlobalArray::getList_Type_Options();
    }
    function getDomain_id() {
        return $this->DtoDomain->getDomain_id();
    }
    function getDomain_name() {
        return $this->DtoDomain->getDomain_name();
    }
}
?>
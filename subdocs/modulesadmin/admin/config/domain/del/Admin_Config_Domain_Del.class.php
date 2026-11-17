<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Domain_Del {

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
        $log->debug("Admin_Config_Domain_Delが実行されました","Admin_Config_Domain_Del#execute");

        // エラーリスト初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        // リストチェック
        if (!array_key_exists($this->DtoDomain->getList_type(), $this->getList_Type_Options())) {
            $errorList->add("Admin_Config_Domain_Del::error", "不正なパラメータが入力されています。");
            return "input";
        }

        // ドメイン数値チェック
        $domain_id = $this->DtoDomain->getDomain_id();
        $domain_id = is_array($domain_id) ? $domain_id : array($domain_id);
        $domain_id = array_filter($domain_id, "is_numeric");
        if (!$domain_id) {
            $errorList->add("Admin_Config_Domain_Del::error", "不正なパラメータが入力されています。");
            return "input";
        }

        // ユーザ情報
        $this->MdbUser->getUserInfo($this->DtoUser);

        // Log
        $ids = $this->DtoDomain->getDomain_id();
        if(is_array($ids)) {
            $history_id = 156;
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
            foreach($ids as $val) {
                $dat = $this->MdbDomain->getDomainInfoEx($val);
                $memo .= "ドメイン：". @$dat['domain_name'] . "\n";
            }

            $this->DtoLog->setUser_id("");
            $this->DtoLog->setAdmin_id( $this->Session->getParameter('admin_id') );
            $this->DtoLog->setFile_id("");
            $this->DtoLog->setHistory_id($history_id);
            $this->DtoLog->setMemo($memo);
            $this->MdbLog->Logging($this->DtoLog);
        }

        // 削除
        $this->DtoDomain->setDomain_id($domain_id);
        $this->MdbDomain->deleteMultiple($this->DtoDomain);

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
    function setDtoUser($DtoUser) {
        $this->DtoUser = $DtoUser;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = $MdbUser;
    }
    function setDomainLogic($DomainLogic) {
        $this->DomainLogic = $DomainLogic;
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
}
?>
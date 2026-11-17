<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Fileformat_Add {
    var $session;
    var $DtoFileformat;
    var $MdbFileformat;
    var $FileformatLogic;
    var $DtoLog;
    var $MdbLog;

  function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Filedelが実行されました","Admin_Config_Fileforma_Addt#execute");

        // エラーリスト初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        // リストチェック
        if (!array_key_exists($this->DtoFileformat->getList_type(), $this->getList_Type_Options())) {
            $errorList->add("Admin_Config_Fileformat_Add::error", "不正なパラメータが入力されています。");
            return "input";
        }

        // 整合性
        if (!$this->FileformatLogic->checkFileformat($this->DtoFileformat->getFormat_name())) {
            $errorList->add("Admin_Config_Fileformat_Add::error", "ファイル形式を正しく入力してください。");
            return "input";
        }

        // 文字長
        if (strlen($this->DtoFileformat->getFormat_name()) > 255) {
            $errorList->add("Admin_Config_Fileformat_Add::error", "ファイル形式が255文字を超えています。255文字以内で入力してください。");
            return "input";
        }
        // 存在チェック
        if ($this->FileformatLogic->existsFileformat($this->DtoFileformat->getList_type(), $this->DtoFileformat->getFormat_name())) {
            $errorList->add("Admin_Config_Domain_Add::error", "入力したファイル形式は、既に存在しています。");
            return "input";
        }

        // 登録
        $this->MdbFileformat->add($this->DtoFileformat);

        // ログ
        $history_id = 196; // 各種設定>ファイル形式に関する設定>ファイル形式の追加
        $memo = "";
        if($this->DtoFileformat->getList_type() == "0") {
            $type = "ホワイトリスト";
        } else {
            $type = "ブラックリスト";
        }
        $memo .= "[" . $type ."]\n";
        $memo .= "ファイル形式：" . $this->DtoFileformat->getFormat_name() . "\n";

        $this->DtoLog->setUser_id('');
        $this->DtoLog->setAdmin_id($this->session->getParameter("admin_id"));
        $this->DtoLog->setFile_id('');
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        return "success";
    }

    // setter
    function setSession(&$obj) {
        $this->session = &$obj;
    }
    function setDtoFileformat($DtoFileformat) {
        $this->DtoFileformat = $DtoFileformat;
    }
    function setMdbFileformat($MdbFileformat) {
        $this->MdbFileformat = $MdbFileformat;
    }
    function setFileformatLogic($FileformatLogic) {
        $this->FileformatLogic = $FileformatLogic;
    }
    function setDtoLog($DtoLog) {
        $this->DtoLog = $DtoLog;
    }
    function setMdbLog($MdbLog) {
        $this->MdbLog = $MdbLog;
    }

    // getter
    function getList_Type_Options() {
        return GlobalArray::getList_Type_Options();
    }
    function getFormat_id() {
        return $this->DtoFileformat->getFormat_id();
    }
    function getFormat_name() {
        return $this->DtoFileformat->getFormat_name();
    }
}
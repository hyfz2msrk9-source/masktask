<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Fileformat_Del {
    var $session;
    var $DtoFileformat;
    var $MdbFileformat;
    var $FileformatLogic;
    var $DtoLog;
    var $MdbLog;

    function execute()  {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Filedelが実行されました","Admin_Config_Fileforma_Addt#execute");

        // エラーリスト初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        // リストチェック
        if (!array_key_exists($this->DtoFileformat->getList_type(), $this->getList_Type_Options())) {
            $errorList->add("Admin_Config_Fileformat_Del::error", "不正なパラメータが入力されています。");
            return "input";
        }

        $format_id = $this->DtoFileformat->getFormat_id();
        $format_id = is_array($format_id) ? $format_id : array($format_id);
        $format_id = array_filter($format_id, "is_numeric");
        if (!$format_id) {
            $errorList->add("Admin_Config_Formatfile_Del::error", "不正なパラメータが入力されています。");
            return "input";
        }

        // log
        if(is_array($this->DtoFileformat->getFormat_id())) {
            $history_id = 197;
            $memo = "";
            if ($this->DtoFileformat->getList_type() == "0") {
                $type = "ホワイトリスト";
            } else {
                $type = "ブラックリスト";
            }
            $memo .= "[".$type."]\n";
            foreach($format_id as $val) {
                $format_info = $this->MdbFileformat->getFileformatInfo($val);
                $memo .= "ファイル形式：" . $format_info['format_name'] . "\n";
            }

            $this->DtoLog->setUser_id('');
            $this->DtoLog->setAdmin_id($this->session->getParameter("admin_id"));
            $this->DtoLog->setFile_id('');
            $this->DtoLog->setHistory_id($history_id);
            $this->DtoLog->setMemo($memo);
            $this->MdbLog->Logging($this->DtoLog);
        }

        // 削除
        $this->DtoFileformat->setFormat_id($format_id);
        $this->MdbFileformat->deleteMultiple($this->DtoFileformat);


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
}
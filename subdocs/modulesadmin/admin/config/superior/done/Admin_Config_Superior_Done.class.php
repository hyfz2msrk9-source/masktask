<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Superior_Done {

    var $session;

    var $DtoConfigs;
    var $MdbConfigs;

    var $DtoLog;
    var $MdbLog;
    var $token;

    var $superior_file_check_type;

    function execute(){

        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Superior_Doneが実行されました", "Admin_Config_Superior_Done#execute");

        $superior_error_response = function ($message) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => $message,
                'token_name' => 'SuperiorEditToken',
                'token_value' => $this->token->getValue('SuperiorEditToken')
            ]);
            exit;
        };

        // 上長承認でのファイルチェック方法フラグ 正当性チェック
        if (!array_key_exists($this->superior_file_check_type, $this->getSuperior_File_Check_Type_Options())) {
            $superior_error_response("不正な値が入力されました");
        }

        // 上長承認でのファイルチェック方法フラグ更新
        $this->MdbConfigs->setValue('superior_file_check_type', $this->superior_file_check_type);

        // ログ
        $history_id = 112;  // 112:各種設定＞機能設定
        $memo = "";

        if ($this->superior_file_check_type != $this->session->getParameter("superior_file_check_type")) {
            switch ($this->superior_file_check_type) {
                case 1:
                    $memo .= "承認時にダウンロードを必須にする\n";
                    break;
                 case 2:
                    $memo .= "承認時にプレビューまたはダウンロードを必須にする\n";
                    break;
                default:
                    $memo .= "ファイルチェック機能を無効にする\n";
                    break;
            }
        }

        if($memo){
            $memo = "[セキュリティ＞上長承認に関する設定]\n". $memo;
            $this->DtoLog->setUser_id("");
            $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
			$this->DtoLog->setFile_id("");
			$this->DtoLog->setHistory_id($history_id);
			$this->DtoLog->setMemo($memo);
			$this->MdbLog->Logging($this->DtoLog);
        }

        // sessionを削除
        $this->session->removeParameter("superior_file_check_type");

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success'
        ]);
        exit;
    }

    /************************************
    * setter
    *************************************/

    function setSession($session) {
		$this->session = $session;
	}
	function setDtoConfigs($DtoConfigs){
		$this->DtoConfigs = $DtoConfigs;
	}
	function setMdbConfigs($MdbConfigs) {
		$this->MdbConfigs = $MdbConfigs;
	}
	function setDtoLog (&$DtoLog) {
		$this->DtoLog =& $DtoLog;
	}
	function setMdbLog ($MdbLog) {
		$this->MdbLog = $MdbLog;
	}

    function setSuperior_file_check_type($superior_file_check_type)
    {
        $this->superior_file_check_type = $superior_file_check_type;
    }
    function setToken($token) {
        $this->token = $token;
    }

    /************************************
    * getter
    *************************************/

    function getSuperior_File_Check_Type_Options()
    {
        return GlobalArray::getSuperior_File_Check_Type_Options();
    }

    function getSuperior_file_check_type()
    {
        return $this->MdbConfigs->getDirectValue("superior_file_check_type");
    }

}

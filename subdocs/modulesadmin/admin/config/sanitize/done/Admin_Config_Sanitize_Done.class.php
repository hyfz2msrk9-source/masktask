<?php
require_once(MODULE_DIR . "/common/common.php");

class Admin_Config_Sanitize_Done
{

    var $session;

    var $DtoConfigs;
    var $MdbConfigs;

    var $DtoLog;
    var $MdbLog;
    var $token;

    var $remove_unsanitized_file;

    function execute()
    {

        $log = &LogFactory::getLog();
        $log->debug("Admin_Config_Sanitize_Doneが実行されました", "Admin_Config_Sanitize_Done#execute");

        $sanitize_error_response = function ($message) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => $message,
                'token_name' => 'SanitizeEditToken',
                'token_value' => $this->token->getValue('SanitizeEditToken')
            ]);
            exit;
        };

        if (!FILE_SANITIZE_FLG) {
            $sanitize_error_response("ファイルの無害化機能は現在、使用できません。");
        }

        if (!SHOW_SANITIZATION_SETTINGS_FLG) {
            $sanitize_error_response("ファイルの無害化に関する設定は利用できません。");
        }

        // フラグ 正当性チェック
        if (!array_key_exists($this->remove_unsanitized_file, $this->getRemove_unsanitized_fileOptions())) {
            $sanitize_error_response("不正な値が入力されました。");
        }

        // フラグ更新
        $this->MdbConfigs->setValue('remove_unsanitized_file', $this->remove_unsanitized_file);

        // ログ
        $history_id = 112;  // 112:各種設定＞機能設定
        $memo = "";

        if ($this->remove_unsanitized_file != $this->session->getParameter("remove_unsanitized_file")) {
            switch ($this->remove_unsanitized_file) {
                case 1:
                    $memo .= "無害化前のファイル： 保持しない\n";
                    break;

                default:
                    $memo .= "無害化前のファイル： 保持する\n";
                    break;
            }
        }

        if ($memo) {
            $memo = "[セキュリティ＞ファイルの無害化に関する設定]\n" . $memo;
            $this->DtoLog->setUser_id("");
            $this->DtoLog->setAdmin_id($this->session->getParameter('admin_id'));
            $this->DtoLog->setFile_id("");
            $this->DtoLog->setHistory_id($history_id);
            $this->DtoLog->setMemo($memo);
            $this->MdbLog->Logging($this->DtoLog);
        }

        // sessionを削除
        $this->session->removeParameter("remove_unsanitized_file");

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success'
        ]);
        exit;
    }

    /************************************
     * setter
     *************************************/

    function setSession($session)
    {
        $this->session = $session;
    }
    function setDtoConfigs($DtoConfigs)
    {
        $this->DtoConfigs = $DtoConfigs;
    }
    function setMdbConfigs($MdbConfigs)
    {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setDtoLog(&$DtoLog)
    {
        $this->DtoLog = &$DtoLog;
    }
    function setMdbLog($MdbLog)
    {
        $this->MdbLog = $MdbLog;
    }

    function setremove_unsanitized_file($remove_unsanitized_file)
    {
        $this->remove_unsanitized_file = $remove_unsanitized_file;
    }
    function setToken($token) {
        $this->token = $token;
    }

    /************************************
     * getter
     *************************************/

    function getRemove_unsanitized_fileOptions()
    {
        return GlobalArray::getRemove_unsanitized_fileOptions();
    }

    function getRemove_unsanitized_file()
    {
        return $this->MdbConfigs->getDirectValue("remove_unsanitized_file");
    }

    function getSubTitle(){
        return "ファイルの無害化に関する設定";
    }
}

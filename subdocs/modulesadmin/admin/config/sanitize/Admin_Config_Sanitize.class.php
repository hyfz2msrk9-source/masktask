<?php
require_once(MODULE_DIR . "/common/common.php");

class Admin_Config_Sanitize
{

    var $session;
    var $MdbConfigs;
    var $token;

    function execute()
    {
        $log = &LogFactory::getLog();
        $log->debug(__CLASS__ . "が実行されました", __CLASS__ . "#execute");

        //エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        if (!FILE_SANITIZE_FLG) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'ファイルの無害化機能は現在、使用できません。'
            ]);
            exit;
        }

        if (!SHOW_SANITIZATION_SETTINGS_FLG) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'ファイルの無害化に関する設定は利用できません。'
            ]);
            exit;
        }

        // sessionにセット
        $this->session->setParameter("remove_unsanitized_file", $this->MdbConfigs->getDirectValue("remove_unsanitized_file"));

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'token_name' => 'SanitizeEditToken',
            'token_value' => $this->token->getValue('SanitizeEditToken')
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
    function setMdbConfigs($MdbConfigs)
    {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setToken($token) {
        $this->token = $token;
    }
}

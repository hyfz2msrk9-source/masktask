<?php
require_once( MODULE_DIR . "/common/common.php");

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
 * [機能説明] 各種設定 - システム設定 - Outlook アドインに関する設定
 * @package     WebFile
 * @version     4.6.0
 * @copyright   KEY POINT
 * @access      public
 */
class Admin_Config_Outlook {
    var $session;
    var $MdbConfigs;

    function execute() {
        $this->log =& LogFactory::getLog();
        $this->log->debug('Admin_Config_Outlookが実行されました','Admin_Config_Outlook#execute');

        // エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent('ActionChain');
        $errorList   = &$actionChain->getCurErrorList();

        // Outlookアドインが使用できない場合
        if (!OUTLOOK_ADDIN_FLG) {
            $errorList->add('Outlook::error', 'この機能は、現在ご利用頂けません。','Sorry, this function cannot be used now.');
            return $this->_return('error');
        }

        return $this->_return('success');
    }

    private function _return($result)
    {
        $this->log->debug('Admin_Config_Outlookが '.$result.' で終了しました','Admin_Config_Outlook#execute');
        return $result;
    }


    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = &$MdbConfigs;
    }
    function hasSamlSettings()
    {
        return boolval($this->MdbConfigs->getDirectValue('saml_authentication_saml_endpoint'));
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

<?php
require_once( MODULE_DIR . "/common/common.php");

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
 * [機能説明] 各種設定 - システム設定 - API(Synchrorer)に関する設定
 * @updater     Hiroko Yanai（2018/04/13）SAML認証(OneLogin)に対応。SSOやSAMLでも、設定は更新可能とする
 *
 * @package     WebFile
 * @version     3.15.2
 * @author      Satoshi Yasuda（2013/08/28）
 * @copyright   KEY POINT
 * @access      public
 */
class Admin_Config_Api {
    var $log;
    var $session;
    var $MdbConfigs;

    var $CommonList;

    function execute() {
        $this->log =& LogFactory::getLog();
        $this->log->debug('Admin_Config_Apiが実行されました','Admin_Config_Api#execute');

        // エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent('ActionChain');
        $errorList   = &$actionChain->getCurErrorList();

        // APIが使用できない場合
        if (!API_FLG) {
            $errorList->add('api::error', 'この機能は、現在ご利用頂けません。','Sorry, this function cannot be used now.');
            return $this->_return('error');
        }


        return $this->_return('success');
    }

    private function _return($result)
    {
        $this->log->debug('Admin_Config_Apiが '.$result.' で終了しました','Admin_Config_Api#execute');
        return $result;
    }


    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setCommonList($CommonList) {
        $this->CommonList =& $CommonList;
    }

    // getter
    function getApi_Flg_Options() {
        return GlobalArray::getApi_Flg_Options();
    }
    function getApi_flg() {
        return $this->MdbConfigs->getDirectValue("api_flg");
    }
    function getAuth_type_notice_main() {
        $str = '';
        $usage_auth_types = $this->CommonList->getUsageAuthTypes();
        if (isset($usage_auth_types[1]) OR isset($usage_auth_types[2])) {
            unset($usage_auth_types[0]);
            $str = implode('、', $usage_auth_types);
            $str = '<br /><span>※この設定は、' . $str . 'のユーザには適用されません。</span>';
        }
        return $str;
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

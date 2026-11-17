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
class Admin_Config_Outlook_Download {
    /**
     * @var Wf_Admin_Config_Outlook_Logo_Logic
     */
    public $OutlookLogoResizer;

    var $MdbConfigs;

    function execute() {
        $this->log =& LogFactory::getLog();
        $this->log->debug('Admin_Config_Outlook_Downloadが実行されました','Admin_Config_Outlook_Download#execute');

        // エラー初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent('ActionChain');
        $errorList   = &$actionChain->getCurErrorList();

        // Outlookアドインが使用できない場合
        if (!OUTLOOK_ADDIN_FLG) {
            $errorList->add('Admin_Config_Outlook_Download::error', 'この機能は、現在ご利用頂けません。', 'Sorry, this function cannot be used now.');
            return $this->_return('error');
        }

        // ロゴ画像がなければ作成
        $this->generateLogoImages();

        // マニフェストファイルを生成
        $outlook_addin_path = OUTLOOK_ADDIN_MANIFEST_FILE_PATH;
        if(!file_exists($outlook_addin_path)){
            $errorList->add('Admin_Config_Outlook_Download::error', 'マニフェストファイルが読み込めませんでした。管理者にお問合せください。', 'Manifest file could not be read. Please contact the administrator.');
            return $this->_return('error');
        }
        $manifest = file_get_contents($outlook_addin_path);
        $pattern = array('[##APP_URL##]', '[##SYS_NAME##]', '[##ASSETS_PATH##]');
        $replace = array(APP_URL . APP_PATH, SYS_NAME, '/images/outlook_addin');
        if (SAML_FLG) {
            // SSOのURLを取得
            $sso_url = $this->MdbConfigs->getDirectValue('saml_authentication_saml_endpoint');
            if (!$sso_url) {
                $errorList->add('Admin_Config_Outlook_Download::error', '「各種設定> その他設定> SAML認証に関する設定」からSAML認証の設定をしてください。');
                return $this->_return('error');
            }
            $parsed_url = parse_url($sso_url);
            $sso_domain = $parsed_url['scheme'] . '://' . $parsed_url['host'];
            array_push($pattern, '[##API_SAML_IDP_URL##]');
            array_push($replace, $sso_domain);
        }
        $manifest = str_replace($pattern, $replace, $manifest);

        // マニフェストファイルのエンコードはUTF-8で良い
        $size = strlen($manifest);
        $browser = new CrossBrowser();
        $filename = str_replace(" ", "_", SYS_NAME) . "_for_outlook.xml"; // 日本語が入ってても動く
        $browser->DownloadHeader($filename, $size, "application/octet-stream");
        echo $manifest;
        exit();
    }

    private function _return($result)
    {
        $this->log->debug('Admin_Config_Outlook_Downloadが ' . $result . ' で終了しました', 'Admin_Config_Outlook_Download#execute');
        return $result;
    }

    function getFunctionName()
    {
        return "Outlook アドインに関する設定";
    }
    function getPreviousAction()
    {
        return "admin_config_outlook";
    }

    public function setOutlookLogoResizer($OutlookLogoResizer)
    {
        $this->OutlookLogoResizer = $OutlookLogoResizer;
    }

    private const OUTLOOK_LOGO_PATH = WF_PATH . "/htdocs/images/outlook_addin/";
    private const OUTLOOK_LOGO_ORG = self::OUTLOOK_LOGO_PATH . "icon-128-org.gif";

    /**
     * 全サイズのロゴを生成
     * @return void
     * @throws Exception
     */
    private function generateLogoImages(){
        $this->generateLogoImage(self::OUTLOOK_LOGO_ORG, self::OUTLOOK_LOGO_PATH . $this->OutlookLogoResizer::OUTLOOK_LOGO_128, 128, 128);
        $this->generateLogoImage(self::OUTLOOK_LOGO_ORG, self::OUTLOOK_LOGO_PATH . $this->OutlookLogoResizer::OUTLOOK_LOGO_80, 80, 80);
        $this->generateLogoImage(self::OUTLOOK_LOGO_ORG, self::OUTLOOK_LOGO_PATH . $this->OutlookLogoResizer::OUTLOOK_LOGO_64, 64, 64);
        $this->generateLogoImage(self::OUTLOOK_LOGO_ORG, self::OUTLOOK_LOGO_PATH . $this->OutlookLogoResizer::OUTLOOK_LOGO_32, 32, 32);
        $this->generateLogoImage(self::OUTLOOK_LOGO_ORG, self::OUTLOOK_LOGO_PATH . $this->OutlookLogoResizer::OUTLOOK_LOGO_16, 16, 16);
    }

    /**
     * ロゴを作成
     *
     * @param $src_path
     * @param $dist_path
     * @param $width
     * @param $height
     * @return void
     * @throws Exception
     */
    private function generateLogoImage($src_path, $dist_path, $width, $height) {
        // ロゴがアップロードされていない場合は作成する
        if (!file_exists($dist_path)) {
            $this->OutlookLogoResizer->execute($src_path, $dist_path, $width, $height);
            @chmod($dist_path, 0644);
        }
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = & $MdbConfigs;
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

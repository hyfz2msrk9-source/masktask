<?php
require_once(MODULE_DIR . "/common/common.php");

class Admin_Config_Logo_Done
{
    var $session;
    var $DtoLog;
    var $MdbLog;
    var $MdbConfigs;
    /**
     * @var Wf_Admin_Config_Outlook_Logo_Logic
     */
    var $OutlookLogoResizer;

    var $LogoFile;
    var $all_en_flg;


    function execute()
    {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Logo_Doneが実行されました", "Admin_Config_User_Done#execute");

        // エラー初期化
        $container = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList = &$actionChain->getCurErrorList();

        if (!CHANGE_LOGO_FLG) {
            $errorList->add("Admin_Config_Logo_Done::error", "この機能は、現在ご利用頂けません。", "Sorry, this function cannot be used now. ");
            return "error2";
        }

        if (LOGO_PREFIX && !OUTLOOK_ADDIN_FLG) {
            $errorList->add("Admin_Config_Logo_Done::error", "この機能は、現在ご利用頂けません。", "Sorry, this function cannot be used now. ");
            return "error2";
        }
        

        $size_list = $this->LogoFile->getFilesize();
        $name_list = $this->LogoFile->getOriginalName();
        $type_list = $this->LogoFile->getMimeType();
        $tmpname_list = $this->LogoFile->getTmpName();
        $error_list = $this->LogoFile->getError();
        $data = array();
        $size_sum = 0;

        /**
         * Validation
         */
        if (is_array($size_list)) {
            foreach ($size_list as $key => $val) {
                if (strlen($name_list[$key]) >= 255) {
                    $no = $key + 1;
                    $errorList->add('Admin_Config_Logo_Done::error',
                        '上から' . $no . '番目の' . $name_list[$key] . '、ファイル名が長すぎます。参照ファイルを確認してください。',
                        'The file (' . $name_list[$key] . ') too long.'
                    );
                } else if ($val > 0) {
                    $data[$key]["name"] = $name_list[$key];
                    $data[$key]["type"] = $type_list[$key];
                    $data[$key]["size"] = $val;
                    $data[$key]["tmp"] = $tmpname_list[$key];
                    $log->debug("getOriginalName", $name_list[$key]);
                    $log->debug("getMimeType", $type_list[$key]);
                    $log->debug("getFilesize", $val);
                    $log->debug("getTmpName", $tmpname_list[$key]);
                    $size_sum = $size_sum + $val;
                } else if ($error_list[$key] == 0) {
                    $no = $key + 1;
                    $errorList->add('Admin_Config_Logo_Done::error',
                        '上から' . $no . '番目の' . $name_list[$key] . 'ファイルが空です。参照ファイルを確認してください。',
                        'The file (' . $name_list[$key] . ') is empty.'
                    );
                }
            }
        }

        // ファイルの存在確認
        if (!$data) {
            $errorList->add('Admin_Config_Logo_Done::error',
                "アップロードするファイルが存在しません。確認してください。",
                "Specify the file."
            );
        }


        if ($errorList->isExists()) {
            return "input";
        }

        // Fileを置き換え
        // アップロードされているファイルのみを書き換える
        // 全てが揃ってなくてもいい
        if ($this->all_en_flg == 1) {
            if (array_key_exists('0', $data)) {
                // 管理側のロゴを差替
                $logo_path = self::ADMIN_LOGO_PATH;
                move_uploaded_file($data[0]["tmp"], $logo_path);
                @chmod($logo_path, 0644);

                // wf側
                copy($logo_path, WF_PATH . "/htdocs/images-en/logo.gif");
                copy($logo_path, WF_PATH . "/htdocs/images/logo.gif");
                if (file_exists(WF_PATH . "/htdocs/images-cn") && CHINESE_FLG == 1) {
                    copy($logo_path, WF_PATH . "/htdocs/images-cn/logo.gif");
                    copy($logo_path, WF_PATH . "/htdocs/images-tcn/logo.gif");
                }
            }

            $outlook_key = $this->getOutlookKey();
            $has_outlook_file = array_key_exists($outlook_key, $data);
            if ($has_outlook_file) {
                // outlookのロゴを差替
                // ファイルを128x128, 80x80, 64x64, 32x32, 16x16にリサイズ
                $this->resizeOutlookLogos($data[$outlook_key]["tmp"]);
            }
        } else {
            foreach ($data as $key => $val) {
                $path = $this->getPath($key);
                if ($path === "") {
                    return "error";
                }

                if ($path === self::OUTLOOK_LOGO_PATH){
                    // outlookのロゴを差替
                    // ファイルを128x128, 80x80, 64x64, 32x32, 16x16にリサイズ
                    $this->resizeOutlookLogos($data[$key]["tmp"]);
                } else {
                    // WebFileのロゴを差し替え
                    move_uploaded_file($data[$key]["tmp"], $path);
                    @chmod($path, 0644);
                }
            }
        }

        // Log
        $history_id = 112; // 112:各種設定＞機能設定
        $memo = "[システム＞ロゴに関する設定]\n";
        $this->DtoLog->setAdmin_id("");
        $this->DtoLog->setAdmin_id($this->session->getParameter('admin_id'));
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success'
        ]);
        exit;
    }

    // setter
    function setSession(&$session)
    {
        $this->session =& $session;
    }

    function setLogoFile(&$LogoFile)
    {
        $this->LogoFile =& $LogoFile;
    }

    function setDtoLog(&$DtoLog)
    {
        $this->DtoLog =& $DtoLog;
    }

    function setMdbLog(&$MdbLog)
    {
        $this->MdbLog =& $MdbLog;
    }

    function setMdbConfigs(&$MdbConfigs)
    {
        $this->MdbConfigs =& $MdbConfigs;
    }

    function setOutlookLogoResizer($OutlookLogoResizer)
    {
        $this->OutlookLogoResizer = $OutlookLogoResizer;
    }

    function setAll_en_flg($all_en_flg)
    {
        $this->all_en_flg = $all_en_flg;
    }


    function getFile_id()
    {
        return $this->DtoUpload->getfile_id();
    }

    function getFolder_id()
    {
        return $this->DtoUpload->getFolder_id();
    }

    function getSends_flg()
    {
        return $this->MdbConfigs->getValue("sends_flg");
    }

    function getCn_images()
    {
        return file_exists(WF_PATH . "/htdocs/images-cn") && CHINESE_FLG;
    }

    function getTcn_images()
    {
        return file_exists(WF_PATH . "/htdocs/images-tcn") && CHINESE_FLG;
    }

    private const EN_LOGO_PATH = WF_PATH . "/htdocs/images-en/logo.gif";
    private const JP_LOGO_PATH = WF_PATH . "/htdocs/images/logo.gif";
    private const CN_LOGO_PATH = WF_PATH . "/htdocs/images-cn/logo.gif";
    private const TCN_LOGO_PATH = WF_PATH . "/htdocs/images-tcn/logo.gif";
    private const ADMIN_LOGO_PATH = BASE_DIR . "/htdocs/images/logo.gif";
    private const OUTLOOK_LOGO_PATH = WF_PATH . "/htdocs/images/outlook_addin/";

    /**
     * ファイルのパスを取得
     *
     * @param $key
     * @return string
     */
    private function getPath($key): string
    {
        if (CHINESE_FLG == 1) {
            switch ($key) {
                case 0 : // 英語版
                    $path = self::EN_LOGO_PATH;
                    break;
                case 1 : // 日本語版
                    $path = self::JP_LOGO_PATH;
                    break;
                case 2 : // 中国語簡体字
                    $path = self::CN_LOGO_PATH;
                    break;
                case 3 : // 中国語繁体字
                    $path = self::TCN_LOGO_PATH;
                    break;
                case 4 : // 管理側
                    $path = self::ADMIN_LOGO_PATH;
                    break;
                case 5 : // Outlook
                    $path = self::OUTLOOK_LOGO_PATH;
                    break;
                default:
                    $path = "";
            }
        } else {
            switch ($key) {
                case 0 : // 英語版
                    $path = self::EN_LOGO_PATH;
                    break;
                case 1 : // 日本語版
                    $path = self::JP_LOGO_PATH;
                    break;
                case 2 : // 管理側
                    $path = self::ADMIN_LOGO_PATH;
                    break;
                case 3 : // Outlook
                    $path = self::OUTLOOK_LOGO_PATH;
                    break;
                default:
                    $path = "";
            }
        }

        return $path;
    }

    /**
     * Outlookのキーを取得
     * フラグによって順番が変わるため
     *
     * @return int
     */
    private function getOutlookKey(): int
    {
        if ($this->all_en_flg == 1) {
            return 1;
        }

        if (CHINESE_FLG == 1) {
            return 5;
        }

        return 3;
    }

    /**
     * Outlook用のロゴをサイズ違いで生成
     *
     * @param $tmp
     * @return void
     * @throws Exception
     */
    private function resizeOutlookLogos($tmp): void
    {
        $this->OutlookLogoResizer->execute($tmp, self::OUTLOOK_LOGO_PATH . $this->OutlookLogoResizer::OUTLOOK_LOGO_128 , 128, 128);
        $this->OutlookLogoResizer->execute($tmp, self::OUTLOOK_LOGO_PATH . $this->OutlookLogoResizer::OUTLOOK_LOGO_80 , 80, 80);
        $this->OutlookLogoResizer->execute($tmp, self::OUTLOOK_LOGO_PATH . $this->OutlookLogoResizer::OUTLOOK_LOGO_64 , 64, 64);
        $this->OutlookLogoResizer->execute($tmp, self::OUTLOOK_LOGO_PATH . $this->OutlookLogoResizer::OUTLOOK_LOGO_32 , 32, 32);
        $this->OutlookLogoResizer->execute($tmp, self::OUTLOOK_LOGO_PATH . $this->OutlookLogoResizer::OUTLOOK_LOGO_16 , 16, 16);
        @chmod(self::OUTLOOK_LOGO_PATH . $this->OutlookLogoResizer::OUTLOOK_LOGO_128, 0644);
        @chmod(self::OUTLOOK_LOGO_PATH . $this->OutlookLogoResizer::OUTLOOK_LOGO_80, 0644);
        @chmod(self::OUTLOOK_LOGO_PATH . $this->OutlookLogoResizer::OUTLOOK_LOGO_64, 0644);
        @chmod(self::OUTLOOK_LOGO_PATH . $this->OutlookLogoResizer::OUTLOOK_LOGO_32, 0644);
        @chmod(self::OUTLOOK_LOGO_PATH . $this->OutlookLogoResizer::OUTLOOK_LOGO_16, 0644);
    }
    function getOutlookImgPath() {
      if (file_exists(WF_PATH."/htdocs/images/outlook_addin/icon-80.png")) {
          return APP_PATH . "/images/outlook_addin/icon-80.png";
      }
      return APP_PATH . "/images/outlook_addin/icon-128-org.gif";
    }
    //ロゴの取得
    function getLogoByLanguage($lang)
    {
        $postfix = "";
        if (in_array($lang, ['en', 'cn', 'tcn'])) { //他言語
            $postfix  = "-" . $lang;
        }
        if (file_exists(WF_PATH . "/htdocs/images" . $postfix . "/" . LOGO_PREFIX . "logo.gif")) {
            return APP_PATH . "/images" . $postfix . "/" . LOGO_PREFIX . "logo.gif";
        } else {
            return APP_PATH . "/images" . $postfix . "/default_logo.gif";
        }
    }
    function getLogo()
    {
        return $this->getLogoByLanguage("");
    }
    function getLogoEn()
    {
        return $this->getLogoByLanguage("en");
    }
    function getLogoCn()
    {
        return $this->getLogoByLanguage("cn");
    }
    function getLogoTcn()
    {
        return $this->getLogoByLanguage("tcn");
    }
}

?>

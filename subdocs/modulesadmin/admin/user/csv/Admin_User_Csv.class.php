<?php
require_once(MODULE_DIR . '/common/common.php');
/**
 * [機能説明] ユーザ管理 - CSVファイルで追加
 * @updater     Hiroko Yanai（2018/04/12）SAML認証(OneLogin)に対応
 *
 * @package     WebFile
 * @version     3.15.2
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_User_Csv {

    var $session;
    var $MdbConfigs;
    var $CommonList;
    var $usage_auth_types;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_User_Csvが実行されました", "Admin_User_Csv#execute");

        $this->usage_auth_types = $this->CommonList->getUsageAuthTypes();
        return "success";
    }
    //************************************************************
    // setter
    //************************************************************
    function setSession($session) {
        $this->session = $session;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setCommonList($CommonList) {
        $this->CommonList =& $CommonList;
    }
    //************************************************************
    // getter
    //************************************************************
    function getUsage_auth_types() {
        return $this->usage_auth_types;
    }
    function getUsage_auth_types_html() {
        if (count($this->usage_auth_types) == 3) {
            $str = '<span class="strong" style="color:#FF0000;">「' . $this->usage_auth_types[0] . '」</span>、';
            $str .= '<span class="strong" style="color:#FF0000;">「' . $this->usage_auth_types[1] . '」</span>、もしくは';
            $str .= '<span class="strong" style="color:#FF0000;">「' . $this->usage_auth_types[2] . '」</span>';
            return $str;
        } elseif (count($this->usage_auth_types) == 2) {
            $str = '<span class="strong" style="color:#FF0000;">「';
            $str .= implode('」</span>、もしくは<span class="strong" style="color:#FF0000;">「', $this->usage_auth_types);
            $str .= '」</span>';
            return $str;
        }
        // 1種類の場合は、認証方式を表示しない
    }
    function getAccount_html() {
        if (1 < count($this->usage_auth_types)) {
            $str = '';
            foreach ($this->usage_auth_types as $key => $value) {
                if ($key == 1) {
                    $str .= '・<span class="strong">' . $value . '</span>・・・<span class="strong" style="color:#FF0000;">Googleアカウント(メールアドレス)を半角英数字255文字以内</span>で入力してください。<br />';
                } elseif ($key == 2) {
                    $str .= '・<span class="strong">' . $value . '</span>・・・<span class="strong" style="color:#FF0000;">メールアドレスを半角英数字255文字以内</span>で入力してください。<br />';
                } else {
                    $str .= '・<span class="strong">' . $value . '</span>・・・<span class="strong" style="color:#FF0000;">半角英数字2-200文字以内</span>で入力してください。<br />';
                }
            }
            return $str;
        } else {
            if (isset($this->usage_auth_types[1])) {
                $str = '<span class="strong" style="color:#FF0000;">' . $this->usage_auth_types[1] . 'に使用するGoogleアカウント(メールアドレス)を半角英数字255文字以内</span>';
            } elseif (isset($this->usage_auth_types[2])) {
                $str = '<span class="strong" style="color:#FF0000;">' . $this->usage_auth_types[2] . 'に使用するメールアドレスを半角英数字255文字以内</span>';
            } else {
                $str = '<span class="strong" style="color:#FF0000;">半角英数字2-200文字以内</span>';
            }
            return $str;
        }
    }
    function getPassword_html() {
        if (1 < count($this->usage_auth_types)) {
            // パスワード認証を使わない場合は、呼ばれない
            $str = '';
            foreach ($this->usage_auth_types as $key => $value) {
                if ($key != 0) {
                    $str .= '・<span class="strong">' . $value . '</span>・・・パスワードは無効になりますので、<span class="strong" style="color:#FF0000;">空白</span>にしてください。<br />';
                } else {
                    $str .= '・<span class="strong">' . $value . '</span>・・・<span class="strong" style="color:#FF0000;">4文字以上、16文字以内</span>で入力してください。<br />';
                }
            }
            return $str;
        }
        // 1種類の場合は、html側でやるので呼ばれない
    }
    function getPassword_change_flg_html() {
        if (1 < count($this->usage_auth_types)) {
            // パスワード認証を使わない場合は、呼ばれない
            $str = '';
            foreach ($this->usage_auth_types as $key => $value) {
                if ($key != 0) {
                    $str .= '・<span class="strong">' . $value . '</span>・・・初期パスワード変更は無効になりますので、<span class="strong" style="color:#FF0000;">「0」</span>にしてください。<br />';
                } else {
                    $str .= '・<span class="strong">' . $value . '</span>・・・初期パスワード変更を設定する場合は<span class="strong" style="color:#FF0000;">「1」</span>、設定しない場合は<span class="strong" style="color:#FF0000;">「0」</span>を入力してください。<br />';
                }
            }
            return $str;
        }
        // 1種類の場合は、html側でやるので呼ばれない
    }
    function getApi_flg_html() {
        // Synchrorer
        if (1 < count($this->usage_auth_types)) {
            // パスワード認証を使わない場合は、呼ばれない
            $str = '';
            foreach ($this->usage_auth_types as $key => $value) {
                if ($key != 0) {
                    $str .= '・<span class="strong">' . $value . '</span>・・・' . API_CLIENT_NAME . '機能は利用できませんので、<span class="strong" style="color:#FF0000;">「利用しない」</span>もしくは<span class="strong" style="color:#FF0000;">0</span>を入力してください。<br />';
                } else {
                    $str .= '・<span class="strong">' . $value . '</span>・・・' . API_CLIENT_NAME . '機能を利用する場合は<span class="strong" style="color:#FF0000;">「利用する」</span>もしくは<span class="strong" style="color:#FF0000;">「1」</span>を、';
                    $str .= '利用しない場合は<span class="strong" style="color:#FF0000;">「利用しない」</span>もしくは<span class="strong" style="color:#FF0000;">「0」</span>を入力してください。<br />';
                }
            }
            return $str;
        }
        // 1種類の場合は、html側でやるので呼ばれない
    }

}
?>

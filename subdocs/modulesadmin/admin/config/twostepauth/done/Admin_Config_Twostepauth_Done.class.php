<?php

require_once( dirname( __FILE__ ).'/../Admin_Config_Twostepauth_Base.class.php' );
/**
 * [機能説明] 各種設定 - セキュリティ設定 - 二要素認証に関する設定(完了)
 * @updater     Hiroko Yanai（2018/04/13）SAML認証(OneLogin)に対応。SSOやSAMLでも、設定は更新可能とする
 *
 * @package     WebFile
 * @version     3.15.2
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_Config_Twostepauth_Done extends Admin_Config_Twostepauth_Base
{
    var $DtoLog;
    var $MdbLog;
    var $MdbTwostepauthbrowser;
    var $MdbUser;
    var $MdbAdminTwostepauth;
    var $MdbBackupcode;
    var $MdbAdminTwostepauthBrowser;

    // response
    var $two_step_auth_flg;
    var $two_step_auth_confirm_span;
    var $admin_two_step_auth_span;
    var $admin_two_step_auth_type;
    
    var $CommonList;
    var $token;

    function execute()
    {
        $twostepauth_error_response = function ($message) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => $message,
                'token_name' => 'TwoStepAuthConfigEditToken',
                'token_value' => $this->token->getValue('TwoStepAuthConfigEditToken')
            ]);
            exit;
        };
        $this->beforeExecute();
        if ( $this->hasError() ) return 'error';
        $userLog = '';
        if (TWO_STEP_AUTH_FLG) {
            //バリデーションチェック
            $errorMessage = $this->getValidationErrorMessage(true);
            if ($errorMessage !== '') {
                $twostepauth_error_response($errorMessage);
            }

            // 設定を保存
            foreach ( array(
                'two_step_auth_flg',
                'two_step_auth_confirm_span',
            ) as $name ) {
                $this->MdbConfigs->setValue( $name, $this->$name );
            }

            // 二要素認証機能(アプリ)が有効から無効になった場合
            if (
                ($this->session->getParameter( 'two_step_auth_flg' ) === '1' &&
                $this->two_step_auth_flg === '0') ||
                ($this->session->getParameter( 'two_step_auth_flg' ) === '1' &&
                $this->two_step_auth_flg === '2')
            ) {
                // すべてのユーザの二要素認証の秘密鍵を削除
                $this->MdbUser->removeTwoStepAuthSecretKeyForAllUsers();

                // すべてのブラウザごとの二要素認証の保持を削除
                $this->MdbTwostepauthbrowser->removeAll();
            }

            // 二要素認証機能(メール)が有効から無効になった場合
            if (
                ($this->session->getParameter( 'two_step_auth_flg' ) === '2' &&
                $this->two_step_auth_flg === '0') ||
                ($this->session->getParameter( 'two_step_auth_flg' ) === '2' &&
                $this->two_step_auth_flg === '1')
            ) {
                // すべてのユーザの二要素認証の秘密鍵を削除
                $this->MdbUser->removeTwoStepAuthConfirmationCodeForAllUsers();

                // すべてのブラウザごとの二要素認証の保持を削除
                $this->MdbTwostepauthbrowser->removeAll();
            }
            // ログ生成
            if (
                $this->two_step_auth_flg !==
                $this->session->getParameter('two_step_auth_flg')
            ) {
                if ($this->two_step_auth_flg === '1') {
                    $userLog .= '　二要素認証機能：認証アプリで使用する'
                    . "\n";
                } else if ($this->two_step_auth_flg === '2') {
                    $userLog .= '　二要素認証機能：メールで使用する'
                    . "\n";
                } else {
                    $userLog .= '　二要素認証機能：使用しない'
                    . "\n";
                }
            }
            if (
                $this->two_step_auth_confirm_span !==
                $this->session->getParameter('two_step_auth_confirm_span')
            ) {
                $userLog .= '　二要素認証の確認頻度：'
                . $this->session->getParameter('two_step_auth_confirm_span') . '日'
                . '→'
                . $this->two_step_auth_confirm_span . '日'
                . "\n";
            }
            if ($userLog !== "") {
                $userLog = "【ユーザ側設定】\n" . $userLog;
            }
        }
        //管理者側二要素認証の設定
        $adminLog = '';
        if ($this->session->getParameter('login_admin_level') >= 5 && ADMIN_TWO_STEP_AUTH_FLG) {
            //バリデーションチェック
            $errorMessage = $this->getValidationErrorMessage(false);
            if ($errorMessage !== '') {
                $twostepauth_error_response($errorMessage);
            }
            $beforeUpdateSettings = [
                'admin_two_step_auth_type' => $this->MdbConfigs->getDirectValue("admin_two_step_auth_type"),
                'admin_two_step_auth_span' => $this->MdbConfigs->getDirectValue("admin_two_step_auth_span")
            ];
            //値をDBに保存
            $this->MdbConfigs->setValue('admin_two_step_auth_span', $this->admin_two_step_auth_span);
            $this->MdbConfigs->setValue('admin_two_step_auth_type', $this->admin_two_step_auth_type);
            $saveSettings = [
                'admin_two_step_auth_type' => $this->admin_two_step_auth_type,
                'admin_two_step_auth_span' => $this->admin_two_step_auth_span,
            ];
            $changedSettings = array_diff_assoc($saveSettings, $beforeUpdateSettings);
            //二要素認証の方式を変更した際に全管理者アカウントのkeyやcodeを削除する
            if (isset($changedSettings["admin_two_step_auth_type"])) {
                if ($changedSettings["admin_two_step_auth_type"] === '0') {
                    //無効に変更した場合どちらも削除する
                    $this->MdbAdminTwostepauth->resetCode();
                    $this->MdbAdminTwostepauth->resetEmail();
                    $this->MdbAdminTwostepauth->resetKey();
                    $admin_id = $this->session->getParameter("login_admin_id");
                    //今までのバックアップコードを削除する
                    $this->MdbBackupcode->remove($admin_id);        
                    //ブラウザの情報を削除する
                    $this->MdbAdminTwostepauthBrowser->removeAll();
                }
                if ($changedSettings["admin_two_step_auth_type"] === '1') {
                    //認証アプリの利用に変更した場合メールのコードを削除
                    $this->MdbAdminTwostepauth->resetCode();
                    $this->MdbAdminTwostepauth->resetEmail();
                    //ブラウザの情報を削除する
                    $this->MdbAdminTwostepauthBrowser->removeAll();
                }
                if ($changedSettings["admin_two_step_auth_type"] === '2') {
                    //メールの利用に変更した場合アプリのキーを削除
                    $this->MdbAdminTwostepauth->resetKey();
                    //ブラウザの情報を削除する
                    $this->MdbAdminTwostepauthBrowser->removeAll();
                }
            }

            //Log
            if (count($changedSettings) !== 0)  $adminLog .= "【管理者アカウント】\n";
            if (isset($changedSettings["admin_two_step_auth_type"])) {
                $adminLog .= match ($changedSettings["admin_two_step_auth_type"]) {
                    '1' => '　二要素認証機能：認証アプリで利用する',
                    '2' => '　二要素認証機能：メールで利用する',
                    default => '　二要素認証機能：利用しない',
                };
                $adminLog .= "\n";
            }
            if (isset($changedSettings["admin_two_step_auth_span"])) {
                $adminLog .= '　二要素認証の確認頻度：';
                if ($beforeUpdateSettings["admin_two_step_auth_span"] !== '') $adminLog .= $beforeUpdateSettings["admin_two_step_auth_span"] . '日 →';
                $adminLog .= $changedSettings["admin_two_step_auth_span"] . '日';
                $adminLog .= "\n";
            }
        }
        if ($adminLog || $userLog) {
            $this->insertLog($userLog . $adminLog);
        }

        // セッションの元の状態の記録を削除
        $this->session->removeParameter( 'two_step_auth_flg' );
        $this->session->removeParameter( 'two_step_auth_confirm_span' );

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success'
        ]);
        exit;
    }

    /**
     * ログを保存する
     * @param  string $memo
     */
    function insertLog( $memo )
    {
        if ( !is_string( $memo ) || $memo === '' ) return;

        $this->DtoLog->setUser_id( '' );
        $this->DtoLog->setAdmin_id( $this->session->getParameter( 'admin_id' ) );
        $this->DtoLog->setFile_id( '' );
        $this->DtoLog->setHistory_id( 112 );
        $this->DtoLog->setMemo( $memo );
        $this->MdbLog->Logging( $this->DtoLog );
    }
    /**
     * バリデーションチェック系のエラーメッセージを取得する
     *
     * @param boolean $isUserSetting true:ユーザ側の設定 false:管理者アカウントの設定
     * @return string $errorMessage エラーメッセージ
     */
    private function getValidationErrorMessage($isUserSetting)
    {
        $errorMessage = '';
        $flg = $isUserSetting ?  $this->two_step_auth_flg : $this->admin_two_step_auth_type;
        $span = $isUserSetting ? $this->two_step_auth_confirm_span : $this->admin_two_step_auth_span;
        $side = $isUserSetting ? 'ユーザ側の設定：' : '管理者アカウントの設定：';
        if (!preg_match('/^[012]$/', $flg)) {
            $errorMessage = $side . "二要素認証機能に不正な入力が見つかりました。\n";
        }
        if (!$span && $span !== '0') {
            $errorMessage = $side . "二要素認証の確認頻度が入力されていません。\n";
        }
        if (!preg_match('/^\d{1,4}$/', $span)) {
            $errorMessage = $side . "二要素認証の確認頻度は4桁以内の整数で入力してください。\n";
        }
        return $errorMessage;
    }

    /**
     * setter
     */
    function setDtoLog( $DtoLog )
    {
        $this->DtoLog = $DtoLog;
    }

    function setMdbLog( $MdbLog )
    {
        $this->MdbLog = $MdbLog;
    }

    function setMdbTwostepauthbrowser( $MdbTwostepauthbrowser )
    {
        $this->MdbTwostepauthbrowser = $MdbTwostepauthbrowser;
    }

    function setMdbUser( $MdbUser )
    {
        $this->MdbUser = $MdbUser;
    }
    function setMdbBackupcode($MdbBackupcode)
    {
        $this->MdbBackupcode = $MdbBackupcode;
    }


    function setTwo_step_auth_flg( $two_step_auth_flg )
    {
        $this->two_step_auth_flg = $two_step_auth_flg;
    }

    function setTwo_step_auth_confirm_span( $two_step_auth_confirm_span )
    {
        $this->two_step_auth_confirm_span = $two_step_auth_confirm_span;
    }

    function setCommonList($CommonList) {
        $this->CommonList =& $CommonList;
    }
    function setMdbAdminTwostepauth($MdbAdminTwostepauth)
    {
        $this->MdbAdminTwostepauth = $MdbAdminTwostepauth;
    }
    function setAdmin_two_step_auth_span($admin_two_step_auth_span)
    {
        $this->admin_two_step_auth_span = $admin_two_step_auth_span;
    }
    function setAdmin_two_step_auth_type($admin_two_step_auth_type)
    {
        $this->admin_two_step_auth_type = $admin_two_step_auth_type;
    }
    function setMdbAdminTwostepauthBrowser($MdbAdminTwostepauthBrowser)
    {
        $this->MdbAdminTwostepauthBrowser = $MdbAdminTwostepauthBrowser;
    }
    function setToken($token) {
        $this->token = $token;
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
    function getAdmin_two_step_auth_span()
    {
        return  $this->admin_two_step_auth_span;
    }
    function getAdmin_two_step_auth_type()
    {
        return $this->admin_two_step_auth_type;
    }
    function getTwo_step_auth_flg_options()
    {
        return GlobalArray::getTwo_Step_Auth_Flg_Options();
    }
    function getAdmin_Two_step_auth_flg_options()
    {
        return GlobalArray::getTwo_Step_Auth_Flg_Options();
    }
}

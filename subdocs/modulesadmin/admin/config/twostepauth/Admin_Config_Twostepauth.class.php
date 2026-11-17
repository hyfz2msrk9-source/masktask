<?php

require_once( dirname( __FILE__ ).'/Admin_Config_Twostepauth_Base.class.php' );
/**
 * [機能説明] 各種設定 - セキュリティ設定 - 二要素認証に関する設定
 * @updater     Hiroko Yanai（2018/04/13）SAML認証(OneLogin)に対応。SSOやSAMLでも、設定は更新可能とする
 *
 * @package     WebFile
 * @version     3.15.2
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_Config_Twostepauth extends Admin_Config_Twostepauth_Base
{
    var $CommonList;
    var $token;
    function execute()
    {
        $this->beforeExecute();

        if ( $this->hasError() ) return 'error';

        // ログのメモ作成時に利用するのでセッションに保存
        $this->session->setParameter(
            'two_step_auth_flg',
            $this->getTwo_step_auth_flg()
        );
        $this->session->setParameter(
            'two_step_auth_confirm_span',
            $this->getTwo_step_auth_confirm_span()
        );

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'token_name' => 'TwoStepAuthConfigEditToken',
            'token_value' => $this->token->getValue('TwoStepAuthConfigEditToken')
        ]);
        exit;
    }


    function setCommonList($CommonList) {
        $this->CommonList =& $CommonList;
    }
    function setToken($token) {
        $this->token = $token;
    }

    //エラー画面用
    function getPreviousAction()
    {
        //戻るボタンのリンク先
        return "admin_config_security";
    }
    function getFunctionName()
    {
        //エラー画面のタイトル
        return 'セキュリティ設定 (二要素認証に関する設定) : エラー';
    }
}

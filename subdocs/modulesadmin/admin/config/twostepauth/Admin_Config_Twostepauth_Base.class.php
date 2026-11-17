<?php

require_once( MODULE_DIR.'/common/common.php' );
/**
 * [機能説明] 各種設定 - セキュリティ設定 - 二要素認証に関する設定(ベースクラス)
 * @updater
 *
 * @package     WebFile
 * @version     3.13.0以前
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_Config_Twostepauth_Base
{
    var $session;
    var $DtoConfigs;
    var $MdbConfigs;

    function beforeExecute()
    {
        // ログへ実行を記録
        $actionName = get_class( $this );
        $log =& LogFactory::getLog();
        $log->debug( "{$actionName}が実行されました", "$actionName#execute" );
    }

    /**
     * エラーがあるかどうか
     * @return boolean
     */
    function hasError()
    {
        $errorName = get_class( $this ).'::error';

        if (!TWO_STEP_AUTH_FLG && !ADMIN_TWO_STEP_AUTH_FLG) {
            $this->getErrorList()->add(
                $errorName,
                '二要素認証機能は、現在ご利用いただけません。',
                'Sorry, Two-step authentication function cannot be used now.'
            );
            return true;
        }

        return false;
    }

    /**
     * 設定の値を取得する
     * @param  string $name
     * @return string
     */
    function getConfigValue( $name )
    {
        $this->MdbConfigs->getInfo( $this->DtoConfigs, $name );
        return $this->DtoConfigs->getValue();
    }

    /**
     * ErrorListを取得する
     * @return Object
     */
    function getErrorList()
    {
        if ( !isset( $this->_errorList ) ) {
            $container        = &DIContainerFactory::getContainer();
            $actionChain      = &$container->getComponent( 'ActionChain' );
            $this->_errorList = &$actionChain->getCurErrorList();
        }
        return $this->_errorList;
    }

    /**
     * setter
     */
    function setSession( $session )
    {
        $this->session = $session;
    }

    function setDtoConfigs( $DtoConfigs )
    {
        $this->DtoConfigs = $DtoConfigs;
    }

    function setMdbConfigs( $MdbConfigs )
    {
        $this->MdbConfigs = $MdbConfigs;
    }

    /**
     * getter
     */
    function getTwo_step_auth_flg()
    {
        return
            isset( $this->two_step_auth_flg ) &&
            preg_match( '/^[012]$/', $this->two_step_auth_flg ) ?
            $this->two_step_auth_flg :
            $this->getConfigValue( 'two_step_auth_flg' );
    }

    function getTwo_step_auth_confirm_span()
    {
        if ( !isset( $this->two_step_auth_confirm_span ) ) {
            return $this->getConfigValue( 'two_step_auth_confirm_span' );
        }

        return filter_var(
            $this->two_step_auth_confirm_span,
            FILTER_VALIDATE_INT,
            array( 'options' => array(
                'default'   => $this->getConfigValue( 'two_step_auth_confirm_span' ),
                'min_range' => 0,
                'max_range' => 9999,
            ) )
        );
    }
   
    function getTwo_step_auth_flg_options()
    {
        return GlobalArray::getTwo_Step_Auth_Flg_Options();
    }

    function getAdmin_Two_step_auth_flg_options()
    {

        return GlobalArray::getTwo_Step_Auth_Flg_Options();
    }
    function getAdmin_id()
    {
        return $this->session->getParameter('login_admin_id');
    }
    function getAdmin_two_step_auth_span()
    {
        return $this->MdbConfigs->getDirectValue("admin_two_step_auth_span");
    }
    function getAdmin_two_step_auth_type()
    {
        return $this->MdbConfigs->getDirectValue("admin_two_step_auth_type");
    }
}

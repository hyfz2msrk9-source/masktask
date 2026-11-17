<?php
/**
 * [機能説明] 各種設定 - セキュリティ設定 - パスワードに関する設定
 * @updater     Hiroko Yanai（2018/09/28）passmust_span -> user_passmust_span
 * @updater     Hiroko Yanai（2018/10/01）admin_passmust_span の設定
 *
 * @package     WebFile
 * @version     3.16.0
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Pass {
     var $session;
     var $DtoConfigs;
     var $MdbConfigs;
     var $CommonList;
     var $token;

     function execute() {
        $log = &LogFactory::getLog();
        $log->debug("Admin_Config_Passが実行されました", "Admin_Config_Pass#execute");
        $this->MdbConfigs->getList($this->DtoConfigs);

        $list = $this->DtoConfigs->getConfigslist();

        //sessionに記入
        $this->session->setParameter("reissue_flg",               $list['reissue_flg']);
        $this->session->setParameter("user_passmust_span",        $list['user_passmust_span']);
        $this->session->setParameter("pass_min_num",              $list['pass_min_num']);
        $this->session->setParameter("pass_max_num",              $list['pass_max_num']);
        $this->session->setParameter("pass_type_id",              $list['pass_type_id']);
        $this->session->setParameter("smspass_min_num",           $list['smspass_min_num']);
        $this->session->setParameter("smspass_max_num",           $list['smspass_max_num']);
        $this->session->setParameter("smspass_type_id",           $list['smspass_type_id']);
        $this->session->setParameter("pass_differ_flg",           $list['pass_differ_flg']);
        $this->session->setParameter("pass_account_ng_flg",       $list['pass_account_ng_flg']);

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'token_name' => 'PassEditToken',
            'token_value' => $this->token->getValue('PassEditToken')
        ]);
        exit;
     }
     // setter
     function setSession($session) {
         $this->session = $session;
     }
     function setDtoConfigs($DtoConfigs){
         $this->DtoConfigs = $DtoConfigs;
     }
     function setMdbConfigs($MdbConfigs) {
         $this->MdbConfigs = $MdbConfigs;
     }
     function setCommonList($CommonList) {
         $this->CommonList =& $CommonList;
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

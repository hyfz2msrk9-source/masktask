<?php
  /************************************************************
   * last update :
   ************************************************************/
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Section_Form_Import {
    var $session;
    var $DtoSection;
    var $MdbSection;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Section_Form_Importが実行されました","Admin_Config_Section_Form_Import#execute");

        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        // 部署情報の取得と存在確認
        $this->MdbSection->getInfo($this->DtoSection);
        if (!$this->DtoSection->getSection_name()) {
            $errorList->add("Admin_Config_Section_Form_Import::error", "部署情報を取得できませんでした。");
            $errorList->add("Admin_Config_Section_Form_Import::error", "部署は既に削除されている可能性があります。");
            return "error";
        }
        // サブ管理者の編集権限確認
        if( $this->session->getParameter("login_admin_level") < 5  &&  $this->DtoSection->getSubadmin_editable_flg() === "0" ) {
            $errorList->add("Admin_Config_Section_Form_Import::error", "この部署グループの編集を許可されていません。");
            return "error";
        }
        $this->session->setParameter('section_section_name',$this->DtoSection->getSection_name());
        
        return "success";
    }
    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoSection($DtoSection) {
        $this->DtoSection = $DtoSection;
    }
    function setMdbSection($MdbSection) {
        $this->MdbSection = $MdbSection;
    }

    // getter
    function getSection_id() {
        return $this->DtoSection->getSection_id();
    }
    function getSection_name() {
        return $this->DtoSection->getSection_name();
    }
}
?>
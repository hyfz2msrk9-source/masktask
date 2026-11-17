<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Template_Editen_Confirm {
    var $template_name_en;
    var $session;
    var $TemplateLogic;
    var $naiyo;
    var $naiyo_sub;
    var $hmsg_sub;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Template_Editen_Confirmが実行されました","Admin_Template_Editen_Confirm#execute");
        if (!$this->TemplateLogic->checkTemplate($this->template_name_en,  $this->naiyo)) {
            return "input";
        }
        return "success";
    }
    function setSession($session) {
        $this->session = $session;
    }
    function setTemplateLogic($TemplateLogic) {
        $this->TemplateLogic = $TemplateLogic;
    }
    function setTemplate_name_en($template_name_en) {
        $this->template_name_en = $template_name_en;
    }
    function setNaiyo($naiyo) {
        $this->naiyo = $naiyo;
        $this->session->setParameter("template_naiyo",$this->naiyo);
    }
    function setHmsg_sub($hmsg_sub){
        $this->hmsg_sub = $hmsg_sub;
        $this->session->setParameter("template_hmsg_sub",$this->hmsg_sub);
    }
    function setNaiyo_sub($naiyo_sub) {
        $this->naiyo_sub = $naiyo_sub;
        $this->session->setParameter("template_naiyo_sub",$this->naiyo_sub);
    }
    function getNaiyo() {
        return $this->naiyo;
    }
    function getNaiyo_sub() {
        return $this->naiyo_sub;
    }
    function getHmsgSub(){
        return $this->hmsg_sub;
    }
    function getTemplate_name_en() {
        return $this->template_name_en;
    }
    function getName() {
        return $this->TemplateLogic->getTitleName($this->template_name_en);
    }
    function getHmsgName(){
        return $this->TemplateLogic->getHmsgName($this->hmsg_sub);
    }
    function getTemplate_type() {
        if ($this->TemplateLogic->checkInsertTemplate($this->template_name_en)) {
            return $this->template_name_en;
        }
        return false;
    }
    function getConfirmNaiyo() {
        if ($this->TemplateLogic->checkInsertTemplate($this->template_name_en)) {
            return StrChange::ConfirmTemplate(htmlspecialchars($this->naiyo),  $this->template_name_en);
        } else {
            return htmlspecialchars($this->naiyo);
        }
    }
}
?>

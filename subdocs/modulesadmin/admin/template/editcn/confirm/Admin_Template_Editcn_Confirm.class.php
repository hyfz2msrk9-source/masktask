<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Template_Editcn_Confirm {
    var $template_name_cn;
    var $session;
    var $TemplateLogic;
    var $naiyo;
    var $naiyo_sub;
    var $hmsg_sub;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Template_Editcn_Confirmが実行されました","Admin_Template_Editcn_Confirm#execute");
        if (!$this->TemplateLogic->checkTemplate($this->template_name_cn,  $this->naiyo)) {
            return "input";
        }

        // 中国語版が使用可能かチェック
        if( !defined("CHINESE_FLG")  ||  CHINESE_FLG == 0 ) {
            return "error";
        }

        return "success";
    }
    function setSession($session) {
        $this->session = $session;
    }
    function setTemplateLogic($TemplateLogic) {
        $this->TemplateLogic = $TemplateLogic;
    }
    function setTemplate_name_cn($template_name_cn) {
        $this->template_name_cn = $template_name_cn;
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
    function getTemplate_name_cn() {
        return $this->template_name_cn;
    }
    function getName() {
        return $this->TemplateLogic->getTitleName($this->template_name_cn);
    }
    function getHmsgName(){
        return $this->TemplateLogic->getHmsgName($this->hmsg_sub);
    }
    function getTemplate_type() {
        if ($this->TemplateLogic->checkInsertTemplate($this->template_name_cn)) {
            return $this->template_name_cn;
        }
        return false;
    }
    function getConfirmNaiyo() {
        if ($this->TemplateLogic->checkInsertTemplate($this->template_name_cn)) {
            return StrChange::ConfirmTemplate(htmlspecialchars($this->naiyo),  $this->template_name_cn);
        } else {
            return htmlspecialchars($this->naiyo);
        }
    }
}
?>

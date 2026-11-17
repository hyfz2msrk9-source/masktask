<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Template_Edittcn {
    var $session;
    var $listCommon;
    var $TemplateLogic;
    var $template_name_tcn;
    var $template_edit_back;
    var $naiyo;
    var $naiyo_sub;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Template_Edittcnが実行されました","Admin_Template_Edittcn#execute");

        // 中国語版が使用可能かチェック
        if( !defined("CHINESE_FLG")  ||  CHINESE_FLG == 0 ) {
            return "error";
        }

        if ($this->template_edit_back) {
            $this->naiyo = $this->session->getParameter("template_naiyo");
            $this->hmsg_sub  = $this->session->getParameter("template_hmsg_sub");
            if($this->hmsg_sub =="0"){
                $this->naiyo_sub = "";
            }else{
                $this->naiyo_sub = $this->session->getParameter("template_naiyo_sub");
            }
        }else {
            $this->naiyo = $this->TemplateLogic->getTemplateToTxt($this->template_name_tcn,"tcn");
            $this->naiyo_sub = "";
            $this->hmsg_sub  = "";
        }
        return "success";
    }
    function setSession($session) {
        $this->session = $session;
    }
    function setListCommon($listCommon){
        $this->listCommon = $listCommon;
    }
    function setTemplateLogic($TemplateLogic) {
        $this->TemplateLogic = $TemplateLogic;
    }
    function setTemplate_name_tcn($template_name_tcn) {
        $this->template_name_tcn = $template_name_tcn;
    }
    function setTemplate_edit_back($template_edit_back) {
        $this->template_edit_back = $template_edit_back;
    }
    function getNaiyo() {
        return $this->naiyo;
    }
    function getNaiyo_sub(){
        return $this->naiyo_sub;
    }
    function getHmsgSub(){
        return $this->hmsg_sub;
    }
    function getTemplate_name_tcn() {
        return $this->template_name_tcn;
    }
    function getTemplate_edit_back() {
        return $this->template_edit_back;
    }
    function getName() {
        return $this->TemplateLogic->getTitleName($this->template_name_tcn);
    }
    // header用 冒頭文セレクトボックス
    function getHmsgOption(){
        return $this->listCommon->getHmsgOption();
    }
    function getHmsgName(){
        return $this->TemplateLogic->getHmsgName($this->hmsg_sub);
    }
    function getTemplate_type() {
        if ($this->TemplateLogic->checkInsertTemplate($this->template_name_tcn)) {
            return $this->template_name_tcn;
        }
        return false;
    }
}
?>

<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Share_Sectionusers {
    var $session;

    var $DtoUser;
    var $MdbUser;

    var $DtoSectionusers;
    var $MdbSectionusers;

    var $SectionLogic;

    // request
    var $section_ids;
    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Share_Sectionusersが実行されました","Admin_Config_Share_Sectionusers#execute");

        $response = new stdClass;
        $response = array();
        if (is_array($this->section_ids)) {
            // 部署IDの重複を削除
            $this->section_ids = array_unique($this->section_ids);

            $i=0;
            foreach ($this->section_ids as $section_id) {
                $response[$i]['section_id'] = htmlspecialchars($section_id);
                $response[$i]['users']   = array();
                $this->DtoSectionusers->setSection_id((int)$section_id);
                // ユーザIDの配列を取得
                if ($user_ids = $this->SectionLogic->getSectionUserIdList($this->MdbSectionusers, $this->DtoSectionusers)) {
                    // ユーザ一覧を取得
                    $where = array();
                    $where[] = "user_id in (" . Db_Common::escape_string(implode(",", $user_ids)) . ")";
                    $list = $this->MdbUser->getUserKeyList($where);
                    foreach ($list as $val) {
                        $response[$i]['users'][] = array(
                                                         "user_id" => $val['user_id'],
                                                         "user_name" => $val['user_name'],
                                                         "mailaddr" => $val['mailaddr']
                                                         );
                    }
                }
                $i++;
            }
        }
        exit(json_encode($response, JSON_UNESCAPED_UNICODE));
    }
    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoUser($DtoUser){
        $this->DtoUser = $DtoUser;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = $MdbUser;
    }
    function setDtoSectionusers($DtoSectionusers){
        $this->DtoSectionusers = $DtoSectionusers;
    }
    function setMdbSectionusers($MdbSectionusers) {
        $this->MdbSectionusers = $MdbSectionusers;
    }
    function setSectionLogic($SectionLogic) {
        $this->SectionLogic = $SectionLogic;
    }
    function setSection_ids($section_ids) {
        $this->section_ids = $section_ids;
    }
    // getter
}
?>
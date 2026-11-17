<?php
require_once( MODULE_DIR . "/common/common.php");
/**
 * [機能説明] ユーザ管理 - ユーザ一覧
 * @updater     Hiroko Yanai（2018/04/09）SAML認証(OneLogin)に対応
 *
 * @package     WebFile
 * @version     3.15.2
 * @author
 * @copyright   KeyPoint
 * @access      public
 */
class Admin_User {
    var $session;
    var $MdbAdminuser;
    var $MdbUser;
    var $DtoUser;
    var $UserDto;
    var $UserLogic;
    var $User2Logic;
    var $MdbCollect;
    var $MdbUpload;
    var $DtoUpload;
    var $MdbFolder;
    var $DtoFolder;
    var $PageLink;
    var $user_list_back;
    var $user_list2;
    var $MdbGroups;
    var $CapacityLogic;
    var $MdbConfigs;
    var $MdbAdminTwostepauth;
    /** @var \Wf_Kpnews_Logic */
    var $KpnewsLogic;

    var $all_size;
    var $groups_uploaded_size;
    var $all_ext_size;
    var $active_users_capacity;
    var $nondeleteuser_size;
    var $CommonList;
    var $unreadNewsId = 0;

    function execute()
    {
        $log =& LogFactory::getLog();
        $log->debug("Admin_User executed.","Admin_User#execute");
        if (!$this->user_list_back) {

            if ($this->session->getParameter("login_admin_account") == "") {
                $info = $this->MdbAdminuser->getAdminuserInfo2($this->session->getParameter("admin_id"));
                $this->session->setParameter("login_admin_account", $info["account"]);
                $this->session->setParameter("login_admin_level", $info["level"]);
                $adminTwoStepAuthInfo = $this->MdbAdminTwostepauth->getAdminTwostepauthInfo($this->session->getParameter("admin_id"));
                $this->session->setParameter("login_admin_two_step_auth_email", $adminTwoStepAuthInfo['email']);
                $admin_two_step_auth_type = $this->MdbConfigs->getDirectValue('admin_two_step_auth_type');
                $this->session->setParameter("admin_two_step_auth_type", $admin_two_step_auth_type);
            }

            $this->session->removeParameter('query');
            $this->session->removeParameter('qtype');
            $this->session->removeParameter('page');
            $this->session->removeParameter('sortname');
            $this->session->removeParameter('sortorder');
            $this->session->removeParameter('rp');
            $this->UserDto->setPage(0);

            if(!$this->session ->getParameter("unread_news_shown") && $this->MdbConfigs->getDirectValue('admin_kp_news_flg')){
                // ログイン直後で運営からのお知らせが有効なら、未読ニュースIDをセット
                $this->unreadNewsId = $this->KpnewsLogic->getFirstUnreadNewsId($this->session->getParameter("admin_id"));
                $this->session ->setParameter("unread_news_shown", true);
            }
        }

        // 基本使用量の取得
        $this->all_size = $this->MdbUpload->getAllUserCapacity()+$this->MdbCollect->getUpMaxSize_all();

        // 拡張使用量の取得
        //$this->all_ext_size = $this->UserLogic->getAll_ext_size($this->MdbUser, $this->MdbUpload, $this->MdbCollect);
        $this->all_ext_size = $this->CapacityLogic->getExtra_usedAll();

        // 共有使用量の取得
        $this->groups_uploaded_size = $this->MdbUpload->getAllUserShareCapacity();

        // アクティブユーザ割当済み容量
        $this->active_users_capacity = $this->CapacityLogic->getBasic_assignAll();

        // 割り当て済み総容量（全ユーザ 停止ユーザ、）
        $this->nondeleteuser_size = $this->MdbUser->getNonDeleteUser_size();

        return "success";
    }

    /*
     * setter
     */
    function setSession(&$session) {
        $this->session =& $session;
    }
    function setMdbAdminuser(&$MdbAdminuser) {
        $this->MdbAdminuser = & $MdbAdminuser;
    }
    function setMdbUser(&$MdbUser) {
        $this->MdbUser = & $MdbUser;
    }
    function setDtoUser($DtoUser) {
        $this->DtoUser = & $DtoUser;
    }
    function setUserDto($UserDto) {
        $this->UserDto = & $UserDto;
    }
    function setUserLogic($UserLogic) {
        $this->UserLogic = & $UserLogic;
    }
    function setUser2Logic($User2Logic) {
        $this->User2Logic = & $User2Logic;
    }
    function setMdbCollect($MdbCollect) {
        $this->MdbCollect = & $MdbCollect;
    }
    function setMdbUpload($MdbUpload) {
        $this->MdbUpload = & $MdbUpload;
    }
    function setDtoUpload($DtoUpload) {
        $this->DtoUpload = & $DtoUpload;
    }
    function setMdbFolder($MdbFolder) {
        $this->MdbFolder = & $MdbFolder;
    }
    function setDtoFolder($DtoFolder) {
        $this->DtoFolder = & $DtoFolder;
    }
    function setPageLink($PageLink) {
        $this->PageLink = & $PageLink;
    }
    function setUser_list_back($user_list_back) {
        $this->user_list_back = $user_list_back;
    }
    function setMdbGroups($MdbGroups) {
        $this->MdbGroups = & $MdbGroups;
    }
    function setCapacityLogic($CapacityLogic) {
        $this->CapacityLogic = & $CapacityLogic;
    }
    function setMdbConfigs($MdbConfigs) {
        $this->MdbConfigs = $MdbConfigs;
    }
    function setCommonList($CommonList) {
        $this->CommonList =& $CommonList;
    }
    function setMdbAdminTwostepauth($MdbAdminTwostepauth)
    {
        $this->MdbAdminTwostepauth = &$MdbAdminTwostepauth;
    }
    function setKpnewsLogic($KpnewsLogic)
    {
        $this->KpnewsLogic = $KpnewsLogic;
    }

    /*
     * setter
     */
    // ユーザ登録人数    
    function getUserNum() {
        // Mdbの関数がActiveUserだがdel_flgしかみてない
        return $this->MdbUser->getActiveUser();
    }
    // 最大登録可能人数
    function getMaxUser() {
        return MAX_USER;
    }
    // アクティブユーザ数
    function getActiveUserNum() {
      return $this->User2Logic->getUserActiveCont($this->DtoUser,$this->MdbUser);
    }
    // API利用ユーザ数
    function getApiUserCount() {
        return $this->MdbUser->getApiUserCount();
    }

    // 容量アラート状態かどうか
    function getIsCapacityAlert() {
        return (($this->active_users_capacity + $this->all_ext_size) >= $this->getSystem_max()) ? true : false;
    }
    // 契約容量
    function getSystem_max() {
        return SYS_MAX_SIZE*1024*1024*1024;
    }
    // アクティブユーザ (未ログインユーザ)割当容量
    function getActive_users_capacity() {
        return $this->active_users_capacity;
    }
    // 拡張(空き)容量 (契約容量-アクティブユーザ割当容量-拡張使用済み)
    function getExt_free_capacity() {
        return $this->getSystem_max() - $this->getActive_users_capacity() - $this->all_ext_size;
    }
    // 使用容量（拡張使用容量除く）
    function getUploaded_size() {
        return $this->all_size - $this->all_ext_size;
    }
    // 拡張使用容量
    function getExt_uploaded_size() {
        return $this->all_ext_size;
    }
    // 個人容量空き容量 (契約容量-アクティブユーザ割当容量)
    function getParsonal_free_capacity()
    {
        $allCapacity = SYS_MAX_SIZE * 1024 * 1024 * 1024;
        $usedCapacity = $this->getActive_users_capacity();
        return $allCapacity - $usedCapacity;
    }
    
    function getNon_deleteuser_size() {
        return $this->nondeleteuser_size;
    }

    // 共有グループ：契約容量
    function getGroups_size() {
        return SHARE_MAX_CAPACITY*1024*1024*1024;
    }
    // 共有グループ：割り当て済み容量
    function getGroups_assigned_capacity() {
        $size = $this->MdbGroups->getUseCapacity();
        return $size;
    }
    // 共有グループ：使用済み
    function getGroups_uploaded_size() {
        return $this->groups_uploaded_size;
    }
    // 共有グループ：共有容量の空き容量
    function getGroups_free_capacity()
    {
        $allCapacity = SHARE_MAX_CAPACITY * 1024 * 1024 * 1024;
        $usedCapacity = $this->MdbGroups->getUseCapacity();
        return $allCapacity - $usedCapacity;
    }
    
    function getUsage_auth_types() {
        return $this->CommonList->getUsageAuthTypes();
    }
    /*
     * 未読のニュースがあれば一件取得して表示。なければfalse
    * @return array|false 未読のニュースがあればそのデータ、なければfalse
    */
    function getUnreadNewsId(){
        return $this->unreadNewsId;
    }
}
?>

<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Archive_Detail {
    var $session;

    var $DtoUser;
    var $MdbUser;

    var $DtoArchive;
    var $MdbArchive;
    var $DtoArchiveAddr;
    var $MdbArchiveAddr;
    var $DtoArchiveFile;
    var $MdbArchiveFile;
    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Archive_Detailが実行されました","Admin_Archive_Detail#execute");

        // エラーリスト初期化
        $container   = &DIContainerFactory::getContainer();
        $actionChain = &$container->getComponent("ActionChain");
        $errorList   = &$actionChain->getCurErrorList();

        // アーカイブ情報
        if (!$this->MdbArchive->setInfo($this->DtoArchive)) {
            $errorList->add("Admin_Archive_Detail::error", "アーカイブ情報の取得に失敗しました。");
            return "error";
        }
        // アーカイブアドレス情報
        $this->MdbArchiveAddr->setInfo2($this->DtoArchiveAddr);
        // アーカイブファイル情報
        $this->MdbArchiveFile->setInfo2($this->DtoArchiveFile);
        // ユーザ情報
        $this->DtoUser->setUser_id($this->DtoArchive->getUser_id());
        $this->MdbUser->getUserInfo($this->DtoUser);

        return "success";
    }
    // setter
    function setSession($session) {
        $this->session = $session;
    }
    function setDtoUser($DtoUser) {
        $this->DtoUser = $DtoUser;
    }
    function setMdbUser($MdbUser) {
        $this->MdbUser = $MdbUser;
    }
    function setDtoArchive($DtoArchive) {
        $this->DtoArchive = $DtoArchive;
    }
    function setMdbArchive($MdbArchive) {
        $this->MdbArchive = $MdbArchive;
    }
    function setDtoArchiveAddr($DtoArchiveAddr) {
        $this->DtoArchiveAddr = $DtoArchiveAddr;
    }
    function setMdbArchiveAddr($MdbArchiveAddr) {
        $this->MdbArchiveAddr = $MdbArchiveAddr;
    }
    function setDtoArchiveFile($DtoArchiveFile) {
        $this->DtoArchiveFile = $DtoArchiveFile;
    }
    function setMdbArchiveFile($MdbArchiveFile) {
        $this->MdbArchiveFile = $MdbArchiveFile;
    }

    // getter
    function getArchive_Type_Options() {
        return GlobalArray::getArchive_Type_Options();
    }
    function getMail_Type_Flg_Options() {
        return GlobalArray::getMail_Type_Flg_Options();
    }

    function getUser_id() {
        return $this->DtoArchive->getUser_id();
    }
    function getArchive_type() {
        return $this->DtoArchive->getArchive_type();
    }
    function getMailer_id() {
        return $this->DtoArchive->getMailer_id();
    }
    function getFolder_name() {
        return $this->DtoArchive->getFolder_name();
    }
    function getFolder_flg() {
        return $this->DtoArchive->getFolder_flg();
    }
    function getGroup_id() {
        return $this->DtoArchive->getGroup_id();
    }
    function getSubject() {
        return $this->DtoArchive->getSubject();
    }
    function getBody() {
        return $this->DtoArchive->getBody();
    }
    function getBody_str() {
        $data = array(
            "name"     => $this->DtoArchiveAddr->getTo_name(),
            "mail"     => $this->DtoArchiveAddr->getTo_mail(),
            "dl_limit" => $this->DtoArchive->getDl_limit_dt(),
            "up_limit" => $this->DtoArchive->getUp_limit_dt(),
            "addr_list" => "[宛先一覧]",
            "file_list" => "[配布ファイル一覧]",
            "名前" => $this->DtoArchiveAddr->getTo_name(), //ユーザ側(ja)のTerms::NAME
            "メールアドレス"     => $this->DtoArchiveAddr->getTo_mail(), //ユーザ側(ja)のTerms::MAIL_ADDRESS
            "Email address"     => $this->DtoArchiveAddr->getTo_mail() //ユーザ側(en)のTerms::MAIL_ADDRESS
            //英語版のTerms::NAMEは"name"なので不要
        );
        return StrChange::Archive($this->DtoArchive->getBody(), $data);
    }
    function getIn_dt() {
        return $this->DtoArchive->getIn_dt();
    }
    function getChecksum() {
        return $this->DtoArchive->getChecksum();
    }
    function getDl_time() {
        return $this->DtoArchive->getDl_time();
    }
    function getMax_dl_time() {
        return $this->DtoArchive->getMax_dl_time();
    }
    function getDl_dt() {
        return $this->DtoArchive->getDl_dt();
    }
    function getDl_limit_dt() {
        return $this->DtoArchive->getDl_limit_dt();
    }
    function getCollect_pass_flg() {
        return $this->DtoArchive->getCollect_pass_flg();
    }
    function getCollect_pass() {
        return $this->DtoArchive->getCollect_pass();
    }
    function getZip_pass_flg() {
        return $this->DtoArchive->getZip_pass_flg();
    }
    function getZip_pass() {
        return $this->DtoArchive->getZip_pass();
    }
    function getOpen_flg() {
        return $this->DtoArchive->getOpen_flg();
    }
    function getDownload_flg() {
        return $this->DtoArchive->getDownload_flg();
    }
    function getStop_flg() {
        return $this->DtoArchive->getStop_flg();
    }
    function getCreated() {
        return $this->DtoArchive->getCreated();
    }
    function getModified() {
        return $this->DtoArchive->getModified();
    }

    function getTo_mail() {
        return $this->DtoArchiveAddr->getTo_mail();
    }
    function getTo_name() {
        return $this->DtoArchiveAddr->getTo_name();
    }
    function getMail_type_flg() {
        return $this->DtoArchiveAddr->getMail_type_flg();
    }

    function getArchive_file_id() {
        return $this->DtoArchiveFile->getArchive_file_id();
    }
    function getFile_name() {
        return $this->DtoArchiveFile->getFile_name();
    }
    function getFile_size() {
        return $this->DtoArchiveFile->getFile_size();
    }
    function getFile_size_str() {
        return StrChange::CapacityUnit($this->DtoArchiveFile->getFile_size());
    }
    function getFile_in_dt() {
        return $this->DtoArchiveFile->getIn_dt();
    }
    function getFile_folder_name() {
        return $this->DtoArchiveFile->getFolder_name();
    }

    function getAccount() {
        return $this->DtoUser->getAccount();
    }
    function getUser_name() {
        return $this->DtoUser->getUser_name();
    }

    function getSsl_flg () {
        return preg_match('/^https/i', APP_URL);
    }
    function getDl_limit_str() {
        if ($this->DtoArchive->getDl_limit_dt()) {
            return $this->DtoArchive->getDl_limit_Dt();
        } else {
            return '無期限';
        }
    }
    function getMax_dl_time_str() {
        if ($this->DtoArchive->getMax_dl_time() >= 9999) {
            return "無制限";
        } else {
            return $this->DtoArchive->getMax_dl_time() . '回';
        }

    }
    function getDl_pass_flg_str() {
        if (!$this->DtoArchive->getDl_pass_flg()) {
            return "OFF";
        } else {
            if($this->DtoArchive->getDl_pass_flg() == 1) {
                return 'ワンタイムパスワード';
            } elseif($this->DtoArchive->getDl_pass_flg() == 2) {
                return '指定パスワード';
            } elseif($this->DtoArchive->getDl_pass_flg() == 4) {
                $str = 'ワンタイムパスワード（別の宛先）';
                if (trim($this->DtoArchiveAddr->getTo_mail())) {
                      $str = $str."\n　".$this->DtoArchiveAddr->getTo_name()."(".$this->DtoArchiveAddr->getTo_mail().")：".$this->DtoArchiveAddr->getTo_sms();
                }
                return $str;
            }
        }
    }
    function getZip_pass_flg_str() {
        if ($this->DtoArchive->getZip_pass_flg()) {
            return "ON";
        } else {
            return "OFF";
        }
    }
    function getOpen_flg_str() {
        if ($this->DtoArchive->getOpen_flg()) {
            return '行う';
        } else {
            return '行わない';
        }
    }
    function getDownload_flg_str() {
        if ($this->DtoArchive->getDownload_flg()) {
            return '行う';
        } else {
            return '行わない';
        }
    }
    function getUp_limit_str() {
        if ($this->DtoArchive->getUp_limit_dt()) {
            return $this->DtoArchive->getUp_limit_Dt();
        } else {
            return '無期限';
        }
    }
    function getCollect_pass_flg_str() {
        if (!$this->DtoArchive->getCollect_pass_flg()) {
            return "OFF";
        } else {
            if($this->DtoArchive->getCollect_pass_flg() == 1) {
                return 'ワンタイムパスワード';
            } elseif($this->DtoArchive->getCollect_pass_flg() == 2) {
                return '指定パスワード';
            }
        }
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

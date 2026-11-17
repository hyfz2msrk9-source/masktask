<?php
require_once(MODULE_DIR . '/common/common.php');

class Admin_User_Export_Capacity
{
    var $session;
    var $DtoUser;
    var $DtoLog;
    var $MdbUser;
    var $MdbUpload;
    var $MdbCollect;
    var $MdbLog;

    function execute()
    {
        $log = &LogFactory::getLog();
        $log->debug("Admin_User_Export_Capacityが実行されました", "Admin_User_Export_Capacity#execute");

        if ($this->session->getParameter("login_admin_level") == "3") {
            $admin_id = $this->session->getParameter("admin_id");
        } else {
            $admin_id = 0;
        }

        // ユーザ一覧
        $where = null;
        if ($admin_id) {
            $where[] = "owner_admin_id=" . Db_Common::escape_string($admin_id);
        }
        $this->MdbUser->getUserList2($this->DtoUser, $where);

        // CSVの中身
        if ($this->DtoUser->getUserList()) {
            $title = '"アカウント","氏名","利用容量","保管","収集"' . "\n";
            $cont = null;
            foreach ($this->DtoUser->getUserList() as $key => $val) {
                $up_size = 0;
                $up_size_t = 0;
                $col_size = 0;
                $col_size_t = 0;
                $all_size = 0;
                $all_size_t = 0;

                $up_size = $this->MdbUpload->getUserCapacity($val["user_id"]);
                $col_size = $this->MdbCollect->getUserCapacity($val["user_id"]);

                if (!$up_size) {
                    $up_size = 0;
                } else {
                    $up_size_t = StrChange::CapacityUnit($up_size);
                }
                if (!$col_size) {
                    $col_size = 0;
                } else {
                    $col_size_t = StrChange::CapacityUnit($col_size);
                }

                $all_size = $up_size + $col_size;
                $all_size_t = StrChange::CapacityUnit($all_size);

                $cont .= '"' . $val["account"] . '","';
                $cont .= $val["user_name"] . '","';
                $cont .= $all_size_t . 'B","';
                $cont .= $up_size_t . 'B","';
                $cont .= $col_size_t . 'B"' . "\n";
            }
            $cont = $title . $cont;
        } else {
            $cont = "ユーザが登録されていません";
        }

        // 出力
        $file_name = "usercapacity.csv";
        $data = mb_convert_encoding($cont, "SJIS-win", "UTF-8");
        $size = strlen($data);
        $browser = new CrossBrowser();
        $browser->DownloadHeader($file_name, $size, "application/octet-stream");
        echo $data;

        // Log
        $history_id = 198; // 198:ユーザ管理＞ユーザー利用容量のエクスポート
        $memo = "";
        $this->DtoLog->setUser_id("");
        $this->DtoLog->setAdmin_id($this->session->getParameter('admin_id'));
        $this->DtoLog->setFile_id("");
        $this->DtoLog->setHistory_id($history_id);
        $this->DtoLog->setMemo($memo);
        $this->MdbLog->Logging($this->DtoLog);

        exit;
    }

    //************************************************************
    // setter
    //************************************************************
    function setSession($session)
    {
        $this->session = $session;
    }
    function setDtoUser($DtoUser)
    {
        $this->DtoUser = $DtoUser;
    }
    function setDtoLog($DtoLog)
    {
        $this->DtoLog = $DtoLog;
    }
    function setMdbUser($MdbUser)
    {
        $this->MdbUser = $MdbUser;
    }
    function setMdbUpload($MdbUpload)
    {
        $this->MdbUpload = $MdbUpload;
    }
    function setMdbCollect($MdbCollect)
    {
        $this->MdbCollect = $MdbCollect;
    }
    function setMdbLog($MdbLog)
    {
        $this->MdbLog = $MdbLog;
    }

    //************************************************************
    // getter
    //************************************************************



}

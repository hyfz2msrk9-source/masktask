<?php
require_once( MODULE_DIR . "/common/common.php");

class Admin_Config_Clientauth_Done {
    var $session;
    var $DtoConfigs;
    var $MdbConfigs;
    var $DtoLog;
    var $MdbLog;
    var $token;

    var $client_auth_admin;
    var $client_auth_user;
    var $client_admin_ip_flg;
    var $client_user_ip_flg;
    var $client_auth_api_flg;

    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Admin_Config_Clientauth_Doneが実行されました","Admin_Config_Clientauth_Done#execute");

        $client_error_response = function ($message) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => $message,
                'token_name' => 'ClientEditToken',
                'token_value' => $this->token->getValue('ClientEditToken')
            ]);
            exit;
        };

        // チェック
        if(!CLIENT_AUTH_FLG) {
            $client_error_response("この機能をご利用頂くためには、クライアント認証オプションが必要です。");
        }

        if(($this->client_auth_admin != "0" && $this->client_auth_admin != "1") ||
            ($this->client_auth_user != "0" && $this->client_auth_user != "1") ||
            ($this->client_admin_ip_flg != NULL && $this->client_admin_ip_flg != "1") ||
            ( $this->client_user_ip_flg != NULL &&  $this->client_user_ip_flg != "1")) {
            $client_error_response("不正な値が入力されました");
        }

        // API用
        if (!API_FLG) {
            // デフォルト(許可を設定)
            $this->client_auth_api_flg = 0;
        } else {
            // ユーザ側のクライアント認証がOFFならAPIもOFF
            if ($this->client_auth_user == 0) {
                $this->client_auth_api_flg = 0;
            } else {
                // 入力チェック
                if (!preg_match("/^[0-1]{0,1}$/", $this->client_auth_api_flg)) {
                    $client_error_response("不正な値が入力されました");
                }
            }
        }

        $this->MdbConfigs->setValue('client_auth_admin',$this->client_auth_admin);
        $this->MdbConfigs->setValue('client_auth_user', $this->client_auth_user);
        if($this->client_auth_admin) {
            if($this->client_admin_ip_flg == NULL) {
                $this->client_admin_ip_flg = "0";
            }
            $this->MdbConfigs->setValue('client_admin_ip_flg', $this->client_admin_ip_flg);
        } else {
            $this->MdbConfigs->setValue('client_admin_ip_flg', 0);
        }
        if($this->client_auth_user) {
            if($this->client_user_ip_flg == NULL) {
                $this->client_user_ip_flg = "0";
            }
            $this->MdbConfigs->setValue('client_user_ip_flg', $this->client_user_ip_flg);
        } else {
            $this->MdbConfigs->setValue('client_user_ip_flg', 0);
        }

        // API
        $this->MdbConfigs->setValue('client_auth_api_flg', $this->client_auth_api_flg);

        // Log
        $history_id = 112; // 112:各種設定＞機能設定
        $memo  = "";
        $memo .= "管理者画面アクセス制限：";
        if($this->client_auth_admin == "1") {
            $memo .= "利用する\n";
        } else {
            $memo .= "利用しない\n";
        }
        if($this->client_admin_ip_flg == "1") {
            $memo .= "「アクセス制限に関する設定」でアクセス許可されたIPは、クライアント認証を行わない\n";
        }

        $memo .= "ユーザ画面アクセス制限：";
        if($this->client_auth_user == "1") {
            $memo .= "利用する\n";
        } else {
            $memo .= "利用しない\n";
        }
        if($this->client_user_ip_flg == "1") {
            $memo .= "「アクセス制限に関する設定」でアクセス許可されたIPは、クライアント認証を行わない\n";
        }
        if (API_FLG) {
            $memo .= API_CLIENT_NAME."の認証設定：";
            $memo .= (($this->client_auth_api_flg) ? "拒否する" : "許可する") ."\n";
        }

        if($memo) {
            $memo = "[セキュリティ＞クライアント認証に関する設定]\n".$memo;
            $this->DtoLog->setUser_id("");
            $this->DtoLog->setAdmin_id( $this->session->getParameter('admin_id') );
            $this->DtoLog->setFile_id("");
            $this->DtoLog->setHistory_id($history_id);
            $this->DtoLog->setMemo($memo);
            $this->MdbLog->Logging($this->DtoLog);
        }

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success'
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
    function setDtoLog ( &$DtoLog ) {
        $this->DtoLog =& $DtoLog;
    }
    function setMdbLog ( &$MdbLog ) {
        $this->MdbLog =& $MdbLog;
    }

    function setClient_auth_admin($client_auth_admin) {
        $this->client_auth_admin = $client_auth_admin;
    }
    function setClient_auth_user($client_auth_user) {
        $this->client_auth_user = $client_auth_user;
    }
    function setClient_admin_ip_flg($client_admin_ip_flg) {
        $this->client_admin_ip_flg = $client_admin_ip_flg;
    }
    function setClient_user_ip_flg($client_user_ip_flg) {
        $this->client_user_ip_flg = $client_user_ip_flg;
    }
    function setClient_auth_api_flg($client_auth_api_flg)
    {
        $this->client_auth_api_flg = $client_auth_api_flg;
    }
    function setToken ($token) {
        $this->token = $token;
    }

    // getter
    function getClientAuthOptions() {
        return GlobalArray::getClientAuthOptions();
    }
    function getClientAuthAdmin() {
        return $this->client_auth_admin;
    }
    function getClientAuthUser() {
        return $this->client_auth_user;
    }
}
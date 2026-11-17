<?php
  /**
   * Proxy クラス
   * @auth   Chihiro Yano
   */

class Proxy {
    /**
    * IPアドレス取得
    * @updater  Hiroko Yanai（2018/10/09）IPアドレス取得時に、Proxyサーバの有無を考慮
    *
    * @access   public
    * @version  3.16.0
    * @author   Chihiro Yano
    * @param    なし
    * @return   string  ip address
    **/
    function getIpAddress()
    {
        // 取得値は、上から下へ上書きする。下ほど有用。
        $proxy = array(
            'HTTP_X_FORWARDED_FOR', // プロキシサーバ経由の生IP
            'HTTP_SP_HOST',         // ホスト情報
            'HTTP_CLIENT_IP',
            'HTTP_FORWARDED',       // プロキシサーバの情報や生IP
            'HTTP_PC_REMOTE_ADDR',
            'REMOTE_ADDR'
        );

        // 2018/10/09@yanai IPアドレス取得時に、WebサーバがProxy構成かを考慮
        if (REVERSE_PROXY_FLG == 0) {
            // リバースプロキシを使わないので、$_SERVER["REMOTE_ADDR"]優先
            if (isset($_SERVER["REMOTE_ADDR"])) {
                $ip = $_SERVER["REMOTE_ADDR"];
                if (Proxy::is_ipaddr($ip)) {
                    return $ip;
                }
            }
        } else if (REVERSE_PROXY_FLG == 1) {
            // リバースプロキシを使うので、$_SERVER["HTTP_X_FORWARDED_FOR"]優先
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
                if (strstr($ip, ",")) { // HTTP_X_FORWARDED_FOR が複数
                    $ip_tmp = explode(",", $ip);
                    while( $val2 = array_pop($ip_tmp) ) {
                        $val2 = trim($val2);
                        if (Proxy::is_ipaddr($val2)) {
                            return $val2;
                        }
                    }
                } else {
                    if (Proxy::is_ipaddr($ip)) {
                        // HTTP_X_FORWARDED_FOR が単独
                        return $ip;
                    }
                }
            }
        }
        // ↑で取れない場合や、REVERSE_PROXY_FLGの値がおかしい場合は、従来の方法で取得する

// -> 2010/07/20 M.Okumura HTTP_X_FORWARDED FOR 対応
//        foreach ($proxy as $val) {
//            $ip = '';
//            if (isset($_SERVER[$val])) {
//                $ip = trim($_SERVER[$val]);
//            }
//            if (empty($ip)) continue;
//            if (!Proxy::is_ipaddr($ip)) continue;  // IPアドレス体系か？
//            return $ip;
//        }
// ---
        foreach ($proxy as $val) {
            $ip = '';
            if (isset($_SERVER[$val])) {
                $ip = trim($_SERVER[$val]);
            }
            if (empty($ip)) continue;
            if (Proxy::is_ipaddr($ip)) {
                return $ip;
            } else if($val == "HTTP_X_FORWARDED_FOR" && strstr($ip, ",")) {
                $ip_tmp = explode(",", $ip);
                while( $val2 = array_pop($ip_tmp) ) {
                    $val2 = trim($val2);
                    if (Proxy::is_ipaddr($val2)) {
                        return $val2;
                    }
                }
            }
        }
// <- 2010/07/20 M.Okumura HTTP_X_FORWARDED FOR 対応
        return '';
    }

    /************************************************************
     * IPアドレスかどうかの判定
     * @auth     Chihiro Yano
     * @param    str ip address
     * @return   bool
     ************************************************************/
    function is_ipaddr($ip) {
//        $res = ip2long($ip);
//        //before PHP5.0.0 return -1
//        return ($res == -1 || $res == false) ? false : $res;
        if(preg_match("/^(\d|[01]?\d\d|2[0-4]\d|25[0-5])\.(\d|[01]?\d\d|2[0-4]\d|25[0-5])\.(\d|[01]?\d\d|2[0-4]\d|25[0-5])\.(\d|[01]?\d\d|2[0-4]\d|25[0-5])$/",$ip)) {
           return true;
        }
        return false;
    }

    /************************************************************
     * 指定した範囲内にIPアドレスが存在するか(IPアドレスの妥当性チェックは行っていません)
     * @auth     Yusuke Numata
     * @param    str range
     *               = ip/subnet mask：'192.168.1.0/24'
     *               = from-to       ：'192.168.1.0-192.168.1.255'
     *               = ip            ：'192.168.1.0'
     *           str ip
     * @return   bool
     ************************************************************/
    function exists_ipaddr($range, $ip){
        // from-to
        if (strchr($range, "-")) {
            $ip = ip2long($ip);
            $range = explode("-",$range, 2);
            $range[0] = ip2long($range[0]);
            $range[1] = ip2long($range[1]);
            return ($range[0] <= $ip && $ip <= $range[1]) ? true : false;
        // ip/subnet mask
        } else if (strchr($range, "/")) {
            list($addr, $mask_bit) = explode("/", $range, 2);
            $ip_long = ip2long($addr) >> (32 - $mask_bit);
            $p_ip_long = ip2long($ip) >> (32 - $mask_bit);
            return ($p_ip_long == $ip_long) ? true : false;
        // ip
        } else {
            return ($range === $ip) ? true : false;
        }
    }

    /************************************************************
     * IPアドレスの妥当性チェック
     * @auth     Yusuke Numata
     * @param    str ip
     *               = ip/subnet mask：'192.168.1.0/24'
     *               = from-to       ：'192.168.1.0-192.168.1.255'
     *               = ip            ：'192.168.1.0'
     * @return   bool
     ************************************************************/
    function check_ipaddr($ip){
        // from-to
        if (strchr($ip, "-")) {
            list($ip_from, $ip_to) = explode("-", $ip, 2);
            if (Net_IPv4::validateIP($ip_from) && Net_IPv4::validateIP($ip_to)) {
                $ip_from = ip2long($ip_from);
                $ip_to   = ip2long($ip_to);
                if ($ip_from <= $ip_to) {
                    return true;
                }
            }
        // ip/subnet mask
        } else if (strchr($ip, "/")) {
            $ip = Net_IPv4::parseAddress($ip);
            if (! PEAR::isError($ip)) {
                if (Net_IPv4::validateIP($ip->ip) && Net_IPv4::validateNetMask($ip->netmask)) {
                    return true;
                }
            }
        // ip
        } else {
            if (Net_IPv4::validateIP($ip)) {
                return true;
            }
        }
        return false;
    }
}
?>

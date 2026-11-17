<?php
  /**
   * パスワード生成　クラス
   * @auth   Masateru Ohnishi
   */

require_once MAPLE_DIR.'/lib/fguillot/password.php';

# パスワードハッシュのアルゴリズム
if (! defined("PASSWORD_ALGO")) {
    define("PASSWORD_ALGO", PASSWORD_DEFAULT);
}
# パスワードハッシュのコスト（大きいほどサーバへ負荷がかかるので注意）
if (! defined("PASSWORD_COST")) {
    define("PASSWORD_COST", 12);
}

# パスワード暗号化の秘密鍵
if (! defined("CRYPT_SECRET")) {
    define("CRYPT_SECRET", "WebFile_Secret");
}

class Password {

    /************************************************************
     * PC用のパスワード生成
     * @auth       Masateru Ohnishi
     * @pram     int    パスワードの長さ
     * @return   str    password
     ************************************************************/
    static function makePassword( $len ) {
        $random = new String_Random();
        return $random->getRandRegex('[a-zA-Z0-9]{'.$len.'}');
    }

    /************************************************************
     * PC用のパスワード生成
     * @auth     Masateru Ohnishi
     * @update   2018/07/26 @yano 先頭に記号があればshuffleするよう修正
     * @parm     int    パスワードの長さ(min)
     * @parm     int    パスワードの長さ(max)
     * @parm     int    文字種
     * @return   str    password
     ************************************************************/
    static function resetPassword( $min, $max, $type_id ) {
        $pass = "";
        $max = rand($min,$max);
        $random = new String_Random();

        switch($type_id) {
        case 1:
            $pass = $random->getRandRegex('[a-zA-Z0-9]{'.$min.','.$max.'}');
            break;
        case 2:
            $pass1 = $random->getRandRegex('[a-zA-Z0-9]{'.$min.','.$max.'}');
            $pass2 = Password::getRandSymbol($max); //記号
            $pass = substr($pass2, 0, 1).substr($pass1,0,1).str_shuffle($pass1.$pass2);
            $pass = substr($pass, 0, $max);
            break;
        case 3:
            $pass = $random->getRandRegex('[0-9]{'.$min.','.$max.'}');
            break;
        case 4:
            $pass1 = $random->getRandRegex('[a-zA-Z]{'.$min.','.$max.'}');
            $pass2 = $random->getRandRegex('[0-9]{'.$min.','.$max.'}');
            $pass3 = Password::getRandSymbol($max); //記号
            $pass = substr($pass1,0,1).substr($pass3,0,1).substr($pass2,0,1).str_shuffle($pass1.$pass2.$pass3);
            $pass = substr($pass,0,$max);
            $pass = str_shuffle($pass);
            break;
        case 5:
        case 6:
        case 7:
            $pass1 = $random->getRandRegex('[a-z]{'.$min.','.$max.'}');
            $pass2 = $random->getRandRegex('[A-Z]{'.$min.','.$max.'}');
            $pass3 = $random->getRandRegex('[0-9]{'.$min.','.$max.'}');
            $pass4 = Password::getRandSymbol($max); //記号
            $pass = substr($pass2,0,1).substr($pass3,0,1).substr($pass1,0,1).substr($pass4,0,1).str_shuffle($pass1.$pass2.$pass3.$pass4);
            $pass = substr($pass,0,$max);
            $pass = str_shuffle($pass);
            break;
        }
        // 2018/07/26 @yano先頭の記号をなくす
        $pass = Password::removeHeadSymbol($pass);
        // 紛らわしい文字を取り除く
        $pass = Password::removeAmbiguousCharacter($pass, $type_id);
        return $pass;
    }

    /**
     * ワンタイムパスワード用のコードを生成する
     *
     * ログイン用パスワードの文字種設定とは分け、入力時に見間違いにくい文字だけを使う。
     *
     * @return string ワンタイムパスワード
     */
    static function resetOneTimePassword()
    {
        $chars = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
        $length = 6;
        $max = strlen($chars) - 1;
        $pass = '';

        for ($i = 0; $i < $length; $i++) {
            // OTP用途のため、mt_rand/randではなく random_int で文字を選ぶ
            $pass .= $chars[random_int(0, $max)];
        }

        return $pass;
    }

    /**
     * ワンタイムパスワード入力値を照合用に整える
     *
     * @param  string $password 入力されたワンタイムパスワード
     * @return string 正規化したワンタイムパスワード
     */
    static function normalizeOneTimePassword($password)
    {
        return strtoupper(trim($password));
    }

    /**
     * [機能説明] ワンタイムパスワード有効期限チェック
     * @param  string|null $issuedAt 発行日時
     * @return boolean true: 期限内 / false: 期限切れまたは発行日時不正
     */
    static function isOneTimePasswordWithinLimit($issuedAt)
    {
        if (!$issuedAt) return false;

        $issuedTimestamp = strtotime($issuedAt);
        if ($issuedTimestamp === false) return false;

        return time() < $issuedTimestamp + ONE_TIME_PASSWORD_LIMIT_MINUTES * 60;
    }

    /**
     * [機能説明] 先頭に記号があれば、shuffleさせる
     * @auther  Chihiro Yano
     * @updater
     * @param   string パスワード文字列
     * @returnm string shuffleさせたパスワード文字列
     **/
    static private function removeHeadSymbol($pass)
    {
        // 先頭に記号がある場合はshuffle
        if (preg_match("/^[!-\/:-@\[-`\{-\~]/", $pass, $matches)) {
            $pass = str_shuffle($pass);
            return Password::removeHeadSymbol($pass);
        } else {
            return $pass;
        }
    }

    static private function getRandSymbol( $len )
    {
        //「$ \ { }」は利用できないようにする (自動送信時のpreg_replaceで欠落を防止)
        // 「|」は「1」「l」「I」「!」などと、「"」「`」は「'」と、「,」は「.」と、「;」は「:」と紛らわしいので利用できないようにする
        $symbol = array('!', '#', '%', '&', '\'', '(', ')', '*', '+', '-', '.', '/',
                        ':', '<', '=', '>', '?', '@', '[', ']', '^', '_', '~');
        $str = "";
        for ($i = 0; $i < $len; $i++) {
            $key = array_rand($symbol);
            $str .= $symbol[$key];
        }
        return $str;
    }

    /**
     * [機能説明] 紛らわしい文字を別の文字に置換する
     * @auther  Hiroko Yanai
     * @updater
     * @param   string パスワード文字列の候補
     * @param   int パスワードルール(文字種)
     * @return  string 新しいパスワード文字列の候補
     **/
    static private function removeAmbiguousCharacter($password = "", $type_id = 1)
    {
        switch ($type_id) {
            case 1: // 半角英数字のみ
            case 2: // 半角英数字記号のみ
                // 大文字のO(オー)を数字の0に
                $password = str_replace('O', '0', $password);
                // 小文字のo(オー)を数字の0に
                $password = str_replace('o', '0', $password);

                // 大文字のI(アイ)を小文字のi(アイ)に
                $password = str_replace('I', 'i', $password);
                // 小文字のl(エル)を小文字のi(アイ)に
                $password = str_replace('l', 'i', $password);
                break;
            case 3: // 半角数字のみ
                break;
            case 4: // 半角英数字記号 かつ 英数字を混ぜる
            case 5: // 半角英数字記号 かつ 英字の大文字小文字を混ぜる
            case 6: // 半角英字大文字、小文字、数字、記号を3種を混ぜる
            case 7: // 半角英数字記号 かつ 英字・数字・記号を混ぜる
                // 大文字のO(オー)が数字の0と
                // 小文字のo(オー)が数字の0と
                // 大文字のI(アイ)が小文字のi(アイ)や数字の1と
                // 小文字のl(エル)が小文字のi(アイ)や数字の1と
                // 紛らわしいので同種の別の文字にする
                $alpha_upper = implode(range('A','Z'));
                $alpha_upper = str_replace('O', '', $alpha_upper);
                $alpha_upper = str_replace('I', '', $alpha_upper);
                $password = str_replace('O', substr(str_shuffle($alpha_upper), 0, 1), $password);
                $password = str_replace('I', substr(str_shuffle($alpha_upper), 0, 1), $password);

                $alpha_lower = implode(range('a','z'));
                $alpha_lower = str_replace('o', '', $alpha_lower);
                $alpha_lower = str_replace('l', '', $alpha_lower);
                $password = str_replace('o', substr(str_shuffle($alpha_lower), 0, 1), $password);
                $password = str_replace('l', substr(str_shuffle($alpha_lower), 0, 1), $password);
                break;
        }
        return $password;
    }

    /**
     * [機能説明] パスワードハッシュ検証
     * @param        str     $password  入力パスワード文字列
     * @param        str     $hash      パスワードハッシュ
     * @return       boolean true: 一致 / false: 不一致
     */
    public static function verify($password, $hash) {
        $isValid = password_verify($password, $hash);
        // 古いパスワード形式(sha1)でのチェック
        if (!$isValid) {
            $isValid = sha1($password) === $hash;
        }
        return $isValid;
    }

    /**
     * [機能説明] パスワードハッシュ取得
     * @param        str     $hash      パスワードハッシュ
     * @param        str     $algo      暗号化アルゴリズム
     * @param        array   $options   暗号化オプション
     * @return       boolean true: 一致 / false: 不一致
     */
    public static function hash($password, $algo=PASSWORD_ALGO, $options=array("cost" => PASSWORD_COST)) {
        $hash = password_hash($password, $algo, $options);
        if ($hash === false) {
            $hash = sha1($password);
        }
        return $hash;
    }
    
    /**
     * [機能説明] パスワードハッシュ更新が必要か
     * @param        str     $hash  パスワードハッシュ
     * @param        str     $algo      暗号化アルゴリズム
     * @param        array   $options   暗号化オプション
     * @return       boolean true: 必要 / false: 不要
     */
    public static function needsRehash($hash, $algo=PASSWORD_ALGO, $options=array("cost" => PASSWORD_COST)) {
        return password_needs_rehash($hash, $algo, $options);
    }

    /**
     * [機能説明] AES 暗号化
     * @param        str     $data      文字列
     * @param        str     $secret    秘密鍵
     * @return       str     $result    暗号化された文字列
     */
    public static function encrypt($data, $secret=CRYPT_SECRET) {
        $result = false;
        if (extension_loaded('openssl') && function_exists("openssl_encrypt")) {

            // PHP5.3.3以上のみivフィールドを使用可
            $use_iv = version_compare(PHP_VERSION, '5.3.3') >= 0 ? 1 : 0;

            $salt_size  = 40;
            $iv_size    = 16;
            $data_size = $salt_size;
            if ($use_iv) {
                $data_size+= $iv_size;
            }
            
            $salt = openssl_random_pseudo_bytes($iv_size);
            
            $salted = '';
            $dx = '';
            
            while (strlen($salted) < $data_size) {
                $dx = hash('sha256', $dx.$secret.$salt, true);
                $salted .= $dx;
            }
  
            $key = substr($salted, 0, $salt_size);
            if ($use_iv) {
                $iv  = substr($salted, $salt_size, $iv_size);
                $encrypted_data = openssl_encrypt($data, 'AES-256-CBC', $key, true, $iv);
            } else {
                $encrypted_data = openssl_encrypt($data, 'AES-256-CBC', $key, true);
            }
            
            $result = base64_encode($use_iv . $salt . $encrypted_data);
        }
        if ($result === false) {
            $result = $data;
        }
        return $result;
    }
    
    /**
     * [機能説明] AES 復号化
     * @param        str     $data      文字列
     * @param        str     $secret    秘密鍵
     * @return       str     $result    復号された文字列
     */
    public static function decrypt($edata, $secret=CRYPT_SECRET) {
        $result = false;
        if (extension_loaded('openssl') && function_exists("openssl_decrypt")) {

            $salt_size  = 40;
            $iv_size    = 16;
            
            $data = base64_decode($edata);
            $use_iv = substr($data, 0, 1);
            $salt = substr($data, 1, $iv_size);
            $ct = substr($data, $iv_size+1);
            
            $rounds = 3;
            $data00 = $secret.$salt;
            $hash = array();
            $hash[0] = hash('sha256', $data00, true);
            $result = $hash[0];
            for ($i = 1; $i < $rounds; $i++) {
                $hash[$i] = hash('sha256', $hash[$i - 1].$data00, true);
                $result .= $hash[$i];
            }
            
            $key = substr($result, 0, $salt_size);
            if ($use_iv) {
                $iv  = substr($result, $salt_size, $iv_size);
                $result = openssl_decrypt($ct, 'AES-256-CBC', $key, true, $iv);
            } else {
                $result = openssl_decrypt($ct, 'AES-256-CBC', $key, true);
            }
        }
        if ($result === false) {
            $result = $edata;
        }
        return $result;
    }
}
?>

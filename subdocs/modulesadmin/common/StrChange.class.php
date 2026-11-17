<?php
class StrChange {

    static function Afferiate($body, $parts) {
        $pattern[0] = "/\[##name##\]/i";
        $pattern[1] = "/\[##mail##\]/i";
        $pattern[2] = "/\[##url##\]/i";

        if (is_array($parts)) {
                $replace = $parts;
        } else {
                if ($parts == "web") {
                $replace[0] = '<span class="alert">[お名前]</span>';
                $replace[1] = '<span class="alert">[メールアドレス]</span>';
                $replace[2] = '<span class="alert">[登録URL]</span>';
                }
        }
        return preg_replace($pattern, $replace, $body);
    }
    static function user_support($body,$parts) {
        $pattern[0] = "/\[##name##\]/i";
        $pattern[1] = "/\[##name_kana##\]/i";
        $pattern[2] = "/\[##account##\]/i";
        $pattern[3] = "/\[##mailaddress##\]/i";
        $pattern[4] = "/\[##end_dt##\]/i";
        $pattern[5] = "/\[##user_agent##\]/i";
        $pattern[6] = "/\[##host##\]/i";
        $pattern[7] = "/\[##toiawase##\]/i";

        $body = preg_replace($pattern, $parts, $body);
        return str_replace("\r", "\n", str_replace("\r\n", "\n", $body));
    }
    /************************************************************
     * テンプレートの取得
     *
     * @param   string    テンプレートファイル名
     * @param   object    TemplateLogic
     * @return  string    変換後本文
     ************************************************************/
    static function GetTpl($tpl_name, $TemplateLogic) {
        return $TemplateLogic->get_template_file_contents("", $tpl_name);
    }
    /************************************************************
     * メールのテンプレートから、[##body##]への入れ込み
     * @param   string    変換前本文
     * @param   string    テンプレートファイル名
     * @param   object    TemplateLogic
     * @return  string    変換後本文
     ************************************************************/
    static function TplMail($body,$tpl_name, $TemplateLogic) {
        $tpl = $TemplateLogic->get_template_file_contents("", $tpl_name);
        return preg_replace("/\[##body##\]/i", $body, $tpl);
    }
    /************************************************************
     * メールのテンプレートから、[##header##]への入れ込み
     * @updater Hiroko Yanai（2019/08/28）通知の言語を考慮
     * @param   string    変換前本文
     * @param   string    テンプレートヘッダーファイル名
     * @param   boolean   ヘッダーがいるかとうか 0:OFF 1:ON
     * @param   string    言語(Rejection_Mail_By_Admin()のように、必要な場合のみ使う)
     * @param   object    TemplateLogic
     * @return  string    変換後本文
     ************************************************************/
    static function TplHeader($body, $header_name, $flg, $entry_use, $TemplateLogic)
    {
        if ($flg) {
            $tpl = $TemplateLogic->get_template_file_contents($entry_use, $header_name);
        }else {
            $tpl = "";
        }
        return preg_replace("/\[##header##\]/i", $tpl, $body);
    }
    /************************************************************
     * メールのテンプレートから、[##hmsg##]への入れ込み
     * @param   string    変換前本文
     * @param   string    テンプレートヘッダーmsgファイル名
     * @param   boolean   ヘッダーがいるかとうか 0:OFF 1:ON
     * @param   object    TemplateLogic
     * @return  string    変換後本文
     ************************************************************/
    static function TplHmsg($body,$hmsg_name,$flg, $TemplateLogic) {
        if ($flg) {
            $tpl = $TemplateLogic->get_template_file_contents("", $hmsg_name);
        }else {
            $tpl = "";
        }
        return preg_replace("/\[##hmsg##\]/i", $tpl, $body);
    }
    /************************************************************
     * メールのテンプレートから、[##footer##]への入れ込み
     * @updater Hiroko Yanai（2019/08/28）通知の言語を考慮
     * @param   string    変換前本文
     * @param   string    テンプレートフッターファイル名
     * @param   boolean   フッターがいるかとうか 0:OFF 1:ON
     * @param   string    言語(Rejection_Mail_By_Admin()のように、必要な場合のみ使う)
     * @param   object    TemplateLogic
     * @return  string    変換後本文
     ************************************************************/
    static function TplFooter($body, $footer_name, $flg, $entry_use, $TemplateLogic)
    {
        if ($flg) {
            $tpl = $TemplateLogic->get_template_file_contents($entry_use, $footer_name);
        }else {
            $tpl = "";
        }
        return preg_replace("/\[##footer##\]/i", $tpl, $body);
    }
    /************************************************************
     * メールのテンプレートから、[##ad....##]への入れ込み
     * @param   string    変換前本文
     * @param   bool      広告ON/OFFフラグ
     * @param   int       種類
     * @param   object    TemplateLogic
     * @return  string    変換後本文
     ************************************************************/
    static function AdMail($body,$ad_flg,$mail_kind_id, $TemplateLogic) {
        $pattern[0] = "/\[##ad##\]/i";
        if ($ad_flg) {
            //広告TPLの読み込み
            $replace[0] = StrChange::GetTpl("ad.txt", $TemplateLogic);
            $body = preg_replace($pattern,$replace, $body);
            $pattern = array();
            $replace = array();
            $pattern[0] = "/\[##ad_header##\]/i";
            $pattern[1] = "/\[##ad_1##\]/i";
            $pattern[2] = "/\[##ad_separater##\]/i";
            $pattern[3] = "/\[##ad_2##\]/i";
            $pattern[4] = "/\[##ad_footer##\]/i";
            //メール広告の埋め込み チョウ
            $ad_1 = StrChange::getMailAd(1,$mail_kind_id);
            $ad_2 = StrChange::getMailAd(2,$mail_kind_id);
            if ($ad_1 || $ad_2) {
                $replace[0] = $TemplateLogic->get_template_file_contents("", "ad_header.txt");
                if ($ad_1) {
                    $replace[1] = $ad_1."\n";
                }else {
                    $replace[1] = "";
                }
                if ($ad_1 && $ad_2) {
                    $replace[2] = $TemplateLogic->get_template_file_contents("", "ad_separater.txt");
                }else {
                    $replace[2] = "";
                }
                if ($ad_2) {
                    $replace[3] = $ad_2."\n";
                }else {
                    $replace[3] = "";
                }
                $replace[4] = $TemplateLogic->get_template_file_contents("", "ad_footer.txt");
            }else {
                 $replace = array("","","","","");
            }
        } else {
            $replace = array("");
        }

        return preg_replace($pattern,$replace, $body);
    }

    static function HistoryMemo($body) {
        $pattern = "/\[##(.*)##\]/i";
    $replace = "<b>\${1}</b>";
    return preg_replace($pattern,$replace, $body);
    }
    /**************************************************************
     *管理者用メール送信　
     **************************************************************/
     static function Adminmail($body, $parts) {
         $pattern[0] = "/\[##user_name##\]/i";
         $pattern[1] = "/\[##user_kana##\]/i";
         $pattern[2] = "/\[##mailaddr##\]/i";
         $pattern[3] = "/\[##last_login_dt##\]/i";
         $pattern[4] = "/\[##in_dt##\]/i";
         $pattern[5] = "/\[##course##\]/i";
         $pattern[6] = "/\[##sex##\]/i";
         $pattern[7] = "/\[##birth_dt##\]/i";
         $pattern[8] = "/\[##job##\]/i";
         $pattern[9] = "/\[##category##\]/i";
         $pattern[10] = "/\[##work_pref##\]/i";
         $pattern[11] = "/\[##spouse##\]/i";
         $pattern[12] = "/\[##children##\]/i";
         $pattern[13] = "/\[##house##\]/i";
         $pattern[14] = "/\[##pref##\]/i";
         $pattern[15] = "/\[##addr##\]/i";
         $pattern[16] = "/\[##tel##\]/i";
         if( is_array($parts) ) {
            $replace = $parts;
         } else {
             if ($parts == "web") {
                 $replace[0] = '<span class="alert">お名前</span>';
                 $replace[1] = '<span class="alert">お名前(カナ)</span>';
                 $replace[2] = '<span class="alert">メールアドレス</span>';
                 $replace[3] = '<span class="alert">最後ログイン日</span>';
                 $replace[4] = '<span class="alert">登録日</span>';
                 $replace[5] = '<span class="alert">コース</span>';
                 $replace[6] = '<span class="alert">性別</span>';
                 $replace[7] = '<span class="alert">生年月日</span>';
                 $replace[8] = '<span class="alert">職業</span>';
                 $replace[9] = '<span class="alert">業種</span>';
                 $replace[10] = '<span class="alert">勤務先の都道府県</span>';
                 $replace[11] = '<span class="alert">配偶者情報</span>';
                 $replace[12] = '<span class="alert">子供情報</span>';
                 $replace[13] = '<span class="alert">住居情報</span>';
                 $replace[14] = '<span class="alert">お住まいの都道府県</span>';
                 $replace[15] = '<span class="alert">住所</span>';
                 $replace[16] = '<span class="alert">電話番号</span>';
             } elseif ($parts == "mail") {
                 $replace[0] = '[お名前]';
                 $replace[1] = '[お名前(カナ)]';
                 $replace[2] = '[メールアドレス]';
                 $replace[3] = '[最後ログイン日]';
                 $replace[4] = '[登録日]';
                 $replace[5] = '[コース]';
                 $replace[6] = '[性別]';
                 $replace[7] = '[生年月日]';
                 $replace[8] = '[職業]';
                 $replace[9] = '[業種]';
                 $replace[10] = '[勤務先の都道府県]';
                 $replace[11] = '[配偶者情報]';
                 $replace[12] = '[子供情報]';
                 $replace[13] = '[住居情報]';
                 $replace[14] = '[お住まいの都道府県]';
                 $replace[15] = '[住所]';
                 $replace[16] = '[電話番号]';
             }
         }
         return preg_replace($pattern, $replace, $body);
     }

     /*************************************************************
     * 配信するメール広告の取得
     ************************************************************/
     static function getMailAd($mailad_location_id,$mail_kind_id) {
         $mailad = new MailAdTable(DNS);
         //送信可能なIDリストを取得
         $mailad->reset();
         $mailad->setWhere("stop_flg = '0'");
         $mailad->addWhere("mailad_location_id = '".$mailad_location_id."'");
         $mailad->addwhere("show_sum < show_limit");
         $mailad->addWhere("NOW( ) BETWEEN start_dt AND end_dt");
         $list = $mailad->getAll();
         $id_list = array();
         if ($list) {
            foreach ($list as $key => $val) {
                $id_list[] = $val["mail_ad_id"];
            }
         }
         //ランダムで配信広告ID取得
         if ($id_list) {
            $st = new String_Random();
            $st->addPattern('A', $id_list);
            $id = $st->getRandPattern('A');
            //メールログの記入
            $mailadviewlog = new MailAdViewLogTable(DNS);
            $mailadlog = array(
                                "mail_ad_id" => $id,
                                "mail_kind_id" => $mail_kind_id
                               );
            $mailadviewlog->reset();
            $mailadviewlog->save($mailadlog);
            $mailad->reset();
            $data = array();
            $data = $mailad->get($id);
            $update = array(
                             "mail_ad_id" => $data["mail_ad_id"],
                             "show_sum"   => $data["show_sum"]+1
                            );
            $mailad->save($update);
            return $data["naiyo"];
         }else {
            return "";
         }
     }
     /************************************************************
      * K M G の容量単位換算
      ************************************************************/
     static function CapacityUnit($capacity) {
         if(  $capacity < 1000 ) {
             return round($capacity);
         } elseif( $capacity < 1000000 ) {
             return number_format( $capacity/1024,2)."K";
         } elseif( $capacity < 1000000000 ) {
             return number_format( $capacity/1024/1024,2)."M";
         } else {
             return number_format( $capacity/1024/1024/1024,1)."G";
         }
     }

    /************************************************************
     * LIKE検索のアンダーバーをエスケープする
     *
     * LIKE検索で特殊な記号を以下のようにエスケープ
     * 「\」は「\\\\」
     * 「%」は「\\%」
     * 「_」は「\\_」
     * @param   string    文字
     * @return  string    エスケープ後文字
     ************************************************************/
    static function mysql_like_escape($str) {
        //「\」を「\\」、「%」を「\%」、「_」を「\_」にエスケープする
        $escaped_mysql_like_str = addcslashes($str, "%_\\"); //
        // Db_Common::escape_stringでエスケープされるのは「 NUL (ASCII 0)」「\n」「\r」「\」「'」「"」
        return Db_Common::escape_string($escaped_mysql_like_str);
     }

    /************************************************************
     * SJIS-winからのコンバートがOKかどうかを調べる
     *
     * @param   string    文字
     * @return  boolean
     ************************************************************/
     static function checkSjisWin($string) {
	 $str1 = mb_convert_encoding($string,"UTF-8","AUTO");
	 if ($string == $str1) {
	     return false;
	 }
	 $str2 = mb_convert_encoding($string,"UTF-8","sjis-win");
	 $str3 = mb_convert_encoding($str2,"sjis-win","UTF-8");
	 if ($string == $str3) {
	     return true;
	 }
	 return false;
     }

     static function Archive($body, $data=array()) {
         $i = 0;
         $pattern = array();
         $replace = array();
         foreach ($data as $key => $val) {
             $pattern[$i] = "/\[##" . $key . "##\]/i";
             $replace[$i] = $val;
             $i++;
         }
         return preg_replace($pattern, $replace, $body);
     }

     /**********************************************************
      * Evi 容量警告メール置換
      **********************************************************/
     static function EviAutonotice($body,$parts) {
         $pattern[0] = "/\[##percentage##\]/i";
         $pattern[1] = "/\[##autonotice##\]/i";
         $pattern[2] = "/\[##autodelete##\]/i";
         $body = preg_replace($pattern, $parts, $body);
         return str_replace("\r", "\n", str_replace("\r\n", "\n", $body));
     }

     /************************************************************
      * Evi:削除とエクスポートのタイムアウト通知
      ************************************************************/
     static function evi_timeout_mail($parts,$body) {
         $pattern[0] = "/\[##start_time##\]/i";
         $pattern[1] = "/\[##operation##\]/i";
         return preg_replace($pattern, $parts, $body);
     }

     /************************************************************
      * Evi:エクスポート完了
      ************************************************************/
     static function evi_export_mail($parts,$body) {
         $pattern[0] = "/\[##start_time##\]/i";
         $pattern[1] = "/\[##file_name##\]/i";
         return preg_replace($pattern, $parts, $body);
     }

     /**********************************************************
      * Evi アーカイブ保存エラーメール置換
      **********************************************************/
     static function evi_export_save_error_mail($body,$parts) {
         $pattern[0] = "/\[##error_file##\]/i";
         $pattern[1] = "/\[##percentage##\]/i";
         $pattern[2] = "/\[##autodelete##\]/i";
         $body = preg_replace($pattern, $parts, $body);
         return str_replace("\r", "\n", str_replace("\r\n", "\n", $body));
     }

     /************************************************************
      * Evi:エクスポート失敗通知
      ************************************************************/
     static function evi_exportfailure_mail($parts,$body) {
         $pattern[0] = "/\[##file_name##\]/i";
         $pattern[1] = "/\[##error_msg##\]/i";
         return preg_replace($pattern, $parts, $body);
     }
     /************************************************************
      * ユーザ情報通知メール
      ************************************************************/
     static function user_inform_mail($parts,$body) {
         $pattern[0] = "/\[##system_name##\]/i";
         $pattern[1] = "/\[##user_name##\]/i";
         $pattern[2] = "/\[##account##\]/i";
         $pattern[3] = "/\[##password##\]/i";
         $pattern[4] = "/\[##login_url##\]/i";
         return preg_replace($pattern, $parts, $body);
     }

     /************************************************************
      * テンプレート 確認画面用 置換
      ************************************************************/
     static function ConfirmTemplate($body,$type="download") {
         $parts[] = '<span class="alert">お名前</span>';
         $parts[] = '<span class="alert">メールアドレス</span>';
         $pattern[] = "/\[##name##\]/i";
         $pattern[] = "/\[##mail##\]/i";
         if ($type=="download") {
             $parts[] = '<span class="alert">ダウンロードURL</span>';
             $parts[] = '<span class="alert">ダウンロード有効期限</span>';
             $parts[] = '';
             $parts[] = '';
             $parts[] = '<span class="alert">宛先一覧</span>';
             $parts[] = '<span class="alert">配布ファイル名一覧</span>';
             $pattern[] = "/\[##url##\]/i";
             $pattern[] = "/\[##dl_limit##\]/i";
             $pattern[] = "/\[##upload##\]/i";
             $pattern[] = "/\[##up_limit##\]/i";
             $pattern[] = "/\[##addr_list##\]/i";
             $pattern[] = "/\[##file_list##\]/i";
         }
         if ($type=="upload") {
             $parts[] = '<span class="alert">アップロードURL</span>';
             $parts[] = '<span class="alert">アップロード有効期限</span>';
             $parts[] = '';
             $parts[] = '';
             $parts[] = '<span class="alert">宛先一覧</span>';
             $pattern[] = "/\[##upload##\]/i";
             $pattern[] = "/\[##up_limit##\]/i";
             $pattern[] = "/\[##url##\]/i";
             $pattern[] = "/\[##dl_limit##\]/i";
             $pattern[] = "/\[##addr_list##\]/i";
         }
         if ($type=="sendcollect") {
             $parts[] = '<span class="alert">ダウンロードURL</span>';
             $parts[] = '<span class="alert">ダウンロード有効期限</span>';
             $parts[] = '<span class="alert">アップロードURL</span>';
             $parts[] = '<span class="alert">アップロード有効期限</span>';
             $parts[] = '<span class="alert">宛先一覧</span>';
             $parts[] = '<span class="alert">配布ファイル名一覧</span>';
             $pattern[] = "/\[##url##\]/i";
             $pattern[] = "/\[##dl_limit##\]/i";
             $pattern[] = "/\[##upload##\]/i";
             $pattern[] = "/\[##up_limit##\]/i";
             $pattern[] = "/\[##addr_list##\]/i";
             $pattern[] = "/\[##file_list##\]/i";
         }
         if ($type=="download_onetime_password" || $type=="upload_onetime_password" || $type=="download_onetime_password_sms" || $type=="upload_onetime_password_sms") {
             $parts[] = '<span class="alert">ワンタイムパスワード</span>';
             $parts[] = '<span class="alert">有効期限</span>';
             $pattern[] = "/\[##password##\]/i";
             $pattern[] = "/\[##expiration##\]/i";
         }
         if ($type=="confirmation_code") {
            $parts[] = '<span class="alert">二要素認証の確認コード</span>';
            $parts[] = '<span class="alert">有効期限</span>';
            $pattern[] = "/\[##password##\]/i";
            $pattern[] = "/\[##expiration##\]/i";
        }
         return preg_replace($pattern, $parts, $body);
     }

    /***
     * CSV出力用に文字列をエスケープした上で、ダブルクォートで囲む
     * @version 3.13.1
     * @author Yusuke Numata（2016/11/25） 最終更新：Yusuke Numata（2016/11/25）
     * @param string $string 変換対象の文字列
     * @return string   変換後の文字列
     ***/
    static function quoteCSV($string) {
        return '"' . str_replace('"', '""', $string) . '"';
    }

    /**
    * 連続ログイン通知メール置換
    * @updater  Hiroko Yanai（2018/01/29） SYS_NAMEを追加
    *
    * @access   public
    * @author   Hiroko Yanai（2017/12/08）
    * @param    string $body 本文
    * @param    array $parts 挿入する値
    * @return   なし
    **/
    static function ContinuousLogin ($body,$parts) {
        $pattern[0] = "/\[##config_min##\]/i";
        $pattern[1] = "/\[##config_num##\]/i";
        $pattern[2] = "/\[##account##\]/i";
        $pattern[3] = "/\[##level##\]/i";
        $pattern[4] = "/\[##last_login_try_dt##\]/i";
        $pattern[5] = "/\[##login_try_log##\]/i";
        $pattern[6] = "/\[##sys_name##\]/i";
        $body = preg_replace($pattern, $parts, $body);
        return str_replace("\r", "\n", str_replace("\r\n", "\n", $body));
    }
    
    // templateの変数を実データに置き換えたときの文字数
    static function getSmsValidStrCount($template) {
        $smsValidStrCount= mb_strlen($template); 
        $log = LogFactory::getLog();
        if(strpos($template,'[##name##]') !== false){
            // [##name##]→10文字
            $smsValidStrCount = $smsValidStrCount - 10;
        }
        
        if(strpos($template,'[##url##]') !== false){
            //[##url##]→9文字、 ショートURL（https://ans.la/y8mRNS）→21文字
            $smsValidStrCount = $smsValidStrCount + ( 21 - 9 ); 
        }

        if(strpos($template,'[##dl_limit##]') !== false){
            // 有効期限（2020/05/30 12:02）→16文字、[##dl_limit##]→14文字　
            $smsValidStrCount = $smsValidStrCount + ( 16 - 14 );
        }
        
        if(strpos($template,'[##password##]') !== false){
            // SMSパスワードの最大10文字で判定
            $smsValidStrCount = $smsValidStrCount + (10 - 14);
        }

        if(strpos($template,'[##sysname##]') !== false){
            // システム名を反映
            $smsValidStrCount = $smsValidStrCount + (mb_strlen(SYS_NAME) - 13);
        }
        // [##expiration##]と有効期限（2026/07/28 14:08）は同じ16文字
        return $smsValidStrCount;
    }

    /**
     * ワンタイムパスワードの有効期限を通知用の日時に変換する
     *
     * @param  string $issued_at 発行日時
     * @return string 有効期限
     */
    static function getOneTimePasswordExpiration($issued_at)
    {
        return date("Y/m/d H:i", strtotime($issued_at) + ONE_TIME_PASSWORD_LIMIT_MINUTES * 60);
    }
    /**
     * 日本語の年月日と曜日を日付から取得する
     *
     * @param string $date 変換前の形式
     * @param string $format 変換後の形式
     * @param string $weekdayFlg 曜日を返すならtrue
     * @return string 日本語1文字の曜日
     */
    static function getWeekdayFromDateJP($date, $format = "Y-m-d H:i:s", $weekdayFlg = false)
    {
        $convertDate = date($format, strtotime($date));
        if ($weekdayFlg === true) {
            $dayOfWeek = [0 => '日', 1 => '月', 2 => '火', 3 => '水', 4 => '木', 5 => '金', 6 => '土'];
            $weekDay = $dayOfWeek[date('w', strtotime($date))];
            return $convertDate . "({$weekDay})";
        }
        return $convertDate;
    }
}
?>

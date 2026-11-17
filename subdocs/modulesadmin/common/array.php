<?php

class GlobalArray
{
    static function getReissue_Flg_Options()
    {
        return array( 1 => "パスワード再発行機能を有効にする",
                      0 => "パスワード再発行機能を無効にする"
                      );
    }
    static function getReissue_Check_Options()
    {
        return array( 1 => "画面で、登録がないことを通知する",
                      0 => "メールで、登録がないことを通知する",
                      2 => "画面・メールともに通知しない"
                      );
    }
    static function getOpenfolders_Flg_Options()
    {
        return array( 1 => "フォルダ公開機能を有効にする　　",
                      0 => "フォルダ公開機能を無効にする　　"
                      );
    }
    static function getThumbnail_Flg_Options()
    {
        return array( 1 => "サムネイルを表示する　　　　　　",
                      0 => "サムネイルを表示しない　　　　　"
                      );
    }
    static function getOpen_Subfolder_Options()
    {
        return array( 1 => "サブフォルダを公開する　　　　　　",
                      0 => "サブフォルダを公開しない　　　　　"
                      );
    }
    static function getMailer_Flg_Options()
    {
        return array( 1 => "メーラからの送信機能を有効にする",
                      0 => "メーラからの送信機能を無効にする"
                      );
    }
    static function getSample_Temp_Flg_Options()
    {
        return array( 0 => "サンプル文を初期表示しない　　　",
                      1 => "サンプル文を初期表示する　　　　"
                      );
    }
    static function getDefault_Pass_Flg_Options()
    {
        $arr = array( 1 => "ワンタイムパスワードロックを必ず設定する　",
                      4 => "ワンタイムパスワードロック（別の宛先）を必ず設定する",
                      2 => "指定パスワードロックを必ず設定する",
                      3 => "パスワードロックを必ず設定する",
                      0 => "パスワードロックを自由に設定する"
                      );
        if(!defined('SMS_PASS_FLG') || SMS_PASS_FLG == "0"){
            unset($arr[4]);
        }
        return $arr;
    }
    static function getZip_Pass_Flg_Options()
    {
        return array(
                     0 => "ZIPパスワード設定を無効にする",
                     1 => "ZIPパスワードをファイルに必ず設定する",
                     2 => "ZIPパスワードは任意で設定する"
                      );
    }
    static function getDefault_OpenPass_Flg_Options()
    {
        return array( 1 => "ワンタイムパスワードロックを必ず設定する　",
                      2 => "指定パスワードロックを必ず設定する",
                      3 => "パスワードロックを必ず設定する",
                      0 => "パスワードロックを自由に設定する"
                      );
    }
    static function getPass_Differ_Flg_Options()
    {
        return array( 1 => "前回と同じパスワードを不可にする　",
                      0 => "前回と同じパスワードを許可する　"
                      );
    }
    static function getPass_Account_NG_Flg_Options()
    {
        return array( 1 => "アカウントと同じパスワードを不可にする　",
                      0 => "アカウントと同じパスワードを許可する　"
                      );
    }
    static function getCollect_Folder_Flg_Options()
    {
        $options[1] = "全てのフォルダを指定可能にする";
        if (SHARE_GROUP_FLG) {
            $options[2] = "個人フォルダのみ指定可能にする";
            $options[3] = "共有フォルダのみ指定可能にする";
        }
        $options[0] = "フォルダ指定を不可にする";
        return $options;
    }
    static function getView_History_Ip_Flg_Options()
    {
        return array( 0 => "IPアドレスを表示する　",
                      1 => "IPアドレスを表示しない　"
                      );
    }
    static function getUpload_Return_Flg_Options()
    {
        return array( 0 => "アップロード完了画面を表示する　",
                      1 => "ファイル一覧へ戻る　　　　　　　"
                      );
    }
    static function getSends_Flg_Options()
    {
        return array( 1 => "配布機能を有効にする　　",
                      0 => "配布機能を無効にする　　"
                      );
    }
    static function getFile_Move_Flg_Options()
    {
        return array( 1 => "移動機能を有効にする　　",
                      0 => "移動機能を無効にする　　"
                      );
    }
    static function getDefault_CollectPass_Flg_Options()
    {
        return array( 1 => "ワンタイムパスワードロックを必ず設定する　",
                      2 => "指定パスワードロックを必ず設定する",
                      3 => "パスワードロックを必ず設定する",
                      0 => "パスワードロックを自由に設定する"
                      );
    }
    static function getCollect_Mailer_Flg_Options()
    {
        return array( 1 => "メーラーからの引取り送信機能を有効にする",
                      0 => "メーラーからの引取り送信機能を無効にする"
                      );
    }
    static function getArea_Flg_Options()
    {
        return array( 0 => "制限しない",
                      1 => "制限する"
                      );
    }
    static function getAccess_Flg_Options()
    {
        return array( 0 => "全てのIPよりアクセス可能",
                      1 => "登録IPアドレスのみアクセス可能"
                      );
    }
    static function getShare_Search_Flg_Options()
    {
        return array( 0 => "共有フォルダ検索を無効にする",
                      1 => "共有フォルダ検索を有効にする"
                      );
    }
    static function getFolder_Dl_Flg_Options()
    {
        return array( 0 => "ファイルの一括ダウンロードを無効にする",
                      1 => "ファイルの一括ダウンロードを有効にする"
                      );
    }
    static function getSendCollect_Flg_Options()
    {
        return array( 0 => "引取り（収集）同時送信機能を無効にする",
                      1 => "引取り（収集）同時送信機能を有効にする"
                      );
    }
    static function getSendcollect_Mailer_Flg_Options()
    {
        return array( 0 => "メーラーからの引取り（収集）同時送信機能を無効にする",
                      1 => "メーラーからの引取り（収集）同時送信機能を有効にする"
                      );
    }
    static function getAccessArea_Options()
    {
        return array( 1 => "管理者",
                      2 => "ユーザ",
                      3 => "引取・ダウンロード",
                      4 => "API",
                      );
    }
    static function getAccountlock_Mail_Flg_Options()
    {
        return array( 1 => "メール通知を有効にする",
                      0 => "メール通知を無効にする"
                      );
    }
    static function getAccountlock_Mail_Type_Options()
    {
        return array( 1 => "アカウントロックされたユーザ",
                      2 => "管理者"
                      );
    }
    /*
    static function getArchive_Mode_Flg_Options()
    {
        return array( 1 => "配布ダウンロード・収集アップロード時にアーカイブを行う",
                      2 => "ファイルアップロード時にアーカイブを行う"
                      );
    }
    */
    static function getRegular_Export_Flg_Options()
    {
        return array( 0 => "定期エクスポートを実行しない",
                      1 => "週初めに前週のエクスポートファイルを作成する",
                      2 => "月初めに前月のエクスポートファイルを作成する"
                      );
    }
    static function getArchive_Type_Options()
    {
        return array(
                      1 => "配布",
                      2 => "収集",
                      3 => "フォルダ公開",
                      4 => "配布(メーラ)",
                      5 => "フォルダ公開(メーラ)",
                      6 => "アップロード",
                      7 => "アップロード(API)",
                      8 => "アップロード(Uploader)",
                      9 => "無害化",
                      10 => "一斉収集"
                     );
    }
    static function getMail_Type_Flg_Options()
    {
        return array(
                      0 => "宛先",
                      1 => "Cc",
                      2 => "Bcc"
                     );
    }
    static function getMeter_Flg_Options()
    {
        return array( 1 => "メーターを表示する　　　　　　",
                      0 => "メーターを表示しない　　　　　"
                      );
    }
    // 2018/06/25@yanai フォルダツリーのスクロールバー
    static function getFolder_Tree_Scroll_Bar_Flg_Options()
    {
        return array(
                     0 => "スクロールバーを表示しない",
                     1 => "スクロールバーを表示する",
                     );
    }
    // 2018/06/26@yanai フォルダのフルパス
    static function getFolder_Full_Path_Flg_Options()
    {
        return array(
                     0 => "表示しない",
                     1 => "表示する",
                     );
    }
    // 2018/06/29@yanai フォルダの表示
    static function getFolder_Display_Inline_Flg_Options()
    {
        return array(
                     0 => "表示しない",
                     1 => "表示する",
                     );
    }
    // 2018/06/27@yanai ファイルの登録者
    static function getUpload_User_Name_Flg_Options()
    {
        return array(
                     0 => "表示しない",
                     1 => "表示する",
                     );
    }
    static function getMailer_Default_Flg_Options()
    {
        return array( 0 => "配布時のデフォルト機能にしない",
                      1 => "配布時のデフォルト機能にする"
                      );
    }
    static function getOpenfolders_Mailer_Flg_Options()
    {
        return array( 1 => "メーラからの送信機能を有効にする",
                      0 => "メーラからの送信機能を無効にする"
                      );
    }
    static function getOpenfolders_Mailer_Default_Flg_Options()
    {
        return array( 0 => "フォルダ公開時のデフォルト機能にしない",
                      1 => "フォルダ公開時のデフォルト機能にする"
                      );
    }
    static function getDefault_Sender_setting_flg_Option()
    {
        return array( 0 => "送信者（差出人）設定機能を無効にする",
                      1 => "送信者（差出人）設定機能を有効にする",
                      );
    }
    static function getList_Type_Options()
    {
        return array(
                      0 => "ホワイトリスト",
                      1 => "ブラックリスト"
                     );
    }
    static function getList_Type_Name_Options()
    {
        return array(
                      0 => "ホワイトリストを利用する",
                      1 => "ブラックリストを利用する"
                     );
    }
    static function getView_Common_Address_Book_Flg_Options()
    {
        return array(
                      0 => "共通アドレス帳を表示する",
                      1 => "共通アドレス帳を表示しない"
                    );
    }
    static function getView_Dept_Address_Book_Flg_Options()
    {
        return array(
                      1 => "部署別アドレス帳を表示する",
                      0 => "部署別アドレス帳を表示しない",                    
                    );
    }
    static function getFile_Lock_Options()
    {
        return array(
                     0 => "ファイルロック機能を無効にする",
                     1 => "ファイルロック機能を有効にする"
                     );
    }
    static function getFile_Lock_Download_Options()
    {
        return array(
                     0 => "ファイルロック中のダウンロードを許可する",
                     1 => "ファイルロック中のダウンロードを禁止する"
                     );
    }
    static function getStart_time_Options()
    {
        return array(
                     0 => "利用しない",
                     1 => "利用する",
                     2 => "ユーザ任意"
                     );
    }

    static function getManual_Dl_Options()
    {
        return array(
                     0 => "表示しない",
                     1 => "表示する",
                     );
    }
    static function getSend_Wait_Options()
    {
        return array(
                     0 => "利用しない",
                     1 => "利用する",
                     );
    }
    static function getApi_Flg_Options() {
        return array(
            0 => "利用しない",
            1 => "利用する",
        );
    }
    static function getClientAuthOptions() {
        return array(
                     0 => "クライアント認証によるアクセス制限を利用しない",
                     1 => "クライアント認証によるアクセス制限を利用する"
                  );
    }

    static function getClientAuthApiOptions() {
        return array(
            0 => API_CLIENT_NAME."の認証を許可する",
            1 => API_CLIENT_NAME."の認証を拒否する"
        );
    }

    static function getDelivery_Flg_Options()
    {
        return array(
                      1 => "一斉配信機能を有効にする",
                      0 => "一斉配信機能を無効にする"
                      );
    }
    static function getDefault_DeliveryPass_Flg_Options()
    {
        return array(
                      1 => "ワンタイムパスワードロックを必ず設定する　",
                      2 => "指定パスワードロックを必ず設定する",
                      3 => "パスワードロックを必ず設定する",
                      0 => "パスワードロックを自由に設定する"
                      );
    }
    static function getZip_DeliveryPass_Flg_Options()
    {
        return array(
                      0 => "ZIPパスワード設定を無効にする",
                      1 => "ZIPパスワードをファイルに必ず設定する",
                      2 => "ZIPパスワードは任意で設定する"
                      );
    }
    static function getDefault_Auto_Del_Flg_Options()
    {
        return array(
            0 => "ファイルの自動削除を任意で設定する（デフォルト：行わない）",
            1 => "ファイルの自動削除を任意で設定する（デフォルト：行う）",
            2 => "ファイルの自動削除を必須にする"
        );
    }
    static function getPassword_Notice_Flg_Options()
    {
        return array(
                      0 => "メール通知を無効にする",
                      1 => "メール通知を有効にする",
                      );
    }
    static function getDelivery_Sample_Temp_Flg_Options()
    {
        return array(
                      0 => "サンプル文を初期表示しない",
                      1 => "サンプル文を初期表示する"
                      );
    }

    static function getTwo_Step_Auth_Flg_Options()
    {
        return array(
            2 => 'メールでの二要素認証機能を利用する',
            1 => '認証アプリでの二要素認証機能を利用する',
            0 => '二要素認証機能を利用しない',
        );
    }

    static function getSuperior_File_Check_Type_Options()
    {
        return array(
            0 => 'ファイルチェック機能を無効にする',
            1 => '承認時にダウンロードを必須にする',
            2 => '承認時にプレビューまたはダウンロードを必須にする'
        );
    }

    static function getRemove_unsanitized_fileOptions()
    {
        return array(
            0 => '無害化前のファイルを保持する',
            1 => '無害化前のファイルを保持しない'
        );
    }

    // 2017/12/04 @yanai 連続ログイン通知
    static function getContinuous_Login_Notice_Flg_Options()
    {
        return array(
            1 => "メール通知を有効にする",
            0 => "メール通知を無効にする"
        );
    }
    static function getContinuous_Login_Notice_Each_Time_Flg_Options()
    {
        return array(
            1 => "毎回通知する",
            0 => "初回のみ通知する"
        );
    }
    static function getGathering_Flg_Options()
    {
        return array(
            1 => "一斉収集機能を有効にする",
            0 => "一斉収集機能を無効にする"
        );
    }
    static function getGathering_Folder_Flg_Options()
    {
        $options = array(
            1 => "全てのフォルダを指定可能にする",
            // 0 => "フォルダ指定を不可にする"
        );

        if (SHARE_GROUP_FLG) {
            $options[2] = "個人フォルダのみ指定可能にする";
            $options[3] = "共有フォルダのみ指定可能にする";
        }
        return $options;
    }
    static function getGathering_Sample_Temp_Flg_Options()
    {
        return array(
            0 => "サンプル文を初期表示しない",
            1 => "サンプル文を初期表示する"
        );
    }
    static function getGathering_Default_Pass_Flg_Options()
    {
        return array(
            1 => "ワンタイムパスワードロックを必ず設定する　",
            2 => "指定パスワードロックを必ず設定する",
            3 => "パスワードロックを必ず設定する",
            0 => "パスワードロックを自由に設定する"
        );
    }
    static function getGathering_Notice_Pass_Flg_Options()
    {
        return array(
            0 => "メール通知を無効にする",
            1 => "メール通知を有効にする",
        );
    }
    static function getKp_News_Flg_Options()
    {
        return array(
            0 => "ニュースを表示しない",
            1 => "ニュースを表示する",
        );
    }
    static function getHFRadio_text()
    {
        return array(
            0 => "付加する",
            1 => "付加しない"
        );
    }
}

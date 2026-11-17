<?php

class CrossBrowser {
    var $browser;
    function __construct() {

        if (isset($_SERVER['HTTP_USER_AGENT'])) {

            // Internet Explorer 10 以前
            if (preg_match("/MSIE/",$_SERVER['HTTP_USER_AGENT'])) {
                $this->browser = "IE";
            // Internet Explorer 11 以降
            } elseif (preg_match("/Trident\/([0-9.]+);/i", $_SERVER['HTTP_USER_AGENT'], $matches)) {
                $this->browser = "IE";
            } elseif (preg_match("/Edge\/([0-9.]+)$/i", $_SERVER['HTTP_USER_AGENT'], $matches)) {
                $this->browser = "Edge";
            } else {
                $this->browser = "etc";
            }
        } else {
            $this->browser = "etc";
        }
    }

    /************************************************************
     * 文字列をIEの場合SJISに変換する。主にダウンロード時のファイル名
     *
     * @param    String    変換前文字列
     * @retur    String    変換後文字列
     ************************************************************/
    function FilenameExchange($str) {
        if ($this->browser == "IE" || $this->browser == "Edge") {
//            return mb_convert_encoding($str,"SJIS-win","UTF-8");
            return mb_convert_encoding($str,"SJIS-win","AUTO");
        } else {
            return $str;
        }
    }

    /************************************************************
     * IEとその他のブラウザで、ダウンロード用ヘッダを着替える
     ************************************************************/
    function DownloadHeader($file_name,$file_size,$file_type) {
        if ($this->browser == "IE" || $this->browser == "Edge") {
            // セミコロン以下が切れちゃうので置き換え
            $file_name = strtr($file_name,array(";"=>"_"));
            $file_name = strtr($file_name,array("#"=>"_"));
            $file_name = $this->FilenameExchange($file_name);
            header('Content-disposition: attachment; filename="'.$file_name.'"');
            header('Content-type: application/octet-stream; name="'.$file_name.'"');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            if($file_size>0) {
                header("Content-length: ".$file_size);
            }
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
            header('Pragma: public');
        } else {
            header("Content-type: ".$file_type);
            if($file_size) {
                header("Content-length: ".$file_size);
            }
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

            header('Content-Disposition: attachement; filename="'.$file_name.'"');
            header('Pragma: no-cache');
        }
    }
}
?>

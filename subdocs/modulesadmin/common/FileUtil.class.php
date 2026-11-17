<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * [機能説明]ファイルユーティリティ
 *
 * @package     WebFile
 * @author      
 * @copyright   KeyPoint
 * @access      public
 */
class FileUtil
{
    /**
     * [機能説明] 大容量向けファイル出力
     * @author       Yusuke Numata
     * @access       public
     * @return       boolean
     */
    static function freadfile($file_path, $chunk_size_mb=1, $retbytes=true) {
        $chunk_size = $chunk_size_mb * (1024 * 1024);
        $buffer = ''; 
        $cnt =0; 
        $handle = fopen($file_path, 'rb'); 
        if ($handle === false) { 
            return false; 
        }

        // 有効な出力バッファがなければ生成する
        if (ob_get_level() <= 0) {
            ob_start();
        }

        while (!feof($handle)) { 
            $buffer = fread($handle, $chunk_size);
            echo $buffer; 
            ob_flush(); 
            flush(); 
            if ($retbytes) { 
                $cnt += strlen($buffer); 
            } 
        } 
        $status = fclose($handle); 
        if ($retbytes && $status) { 
            return $cnt;
        } 
        return $status; 
    } 

    /**
     * [機能説明]日本語対応版pathinfo()
     * @author       Yusuke Numata
     * @access       public
     * @return       boolean
     */
    function mb_pathinfo($path, $options=null) {
        $data = array();
        preg_match("/(?:^(.*)\/)?(([^\/.]*)|([^\/]*)\.([^\.]*))$/", $path, $matches);
        if (strlen($matches[1]) > 0) {
            $data['dirname'] = $matches[1];
        } else if (strlen($path) !== 0) {
            $data['dirname'] = ".";
        }
        $data['basename'] = $matches[2];
        if (isset($matches[5])) $data['extension'] = $matches[5];
        if (isset($matches[4])) {
            $data['filename'] = $matches[4];
        } else {
            $data['filename'] = $matches[3];
        }
        if (!is_null($options)) {
            $str = "";
            if ($options === PATHINFO_DIRNAME) {
                if (isset($data['dirname'])) $str = $data['dirname'];
            } else if ($options === PATHINFO_BASENAME) {
                if (isset($data['basename'])) $str = $data['basename'];
            } else if ($options === PATHINFO_EXTENSION) {
                if (isset($data['extension'])) $str = $data['extension'];
            } else if ($options === PATHINFO_FILENAME) {
                if (isset($data['filename'])) $str = $data['filename'];
            }
            return $str;
        }
        return $data;
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
?>

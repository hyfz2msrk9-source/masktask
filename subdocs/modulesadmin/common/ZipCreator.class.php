<?php
class ZipCreator {
    /**
     * ZIP作成をする
     *
     * @param string $sourcePath 圧縮するファイルが格納されている一時ディレクトリのパス (ex. /var/tmp/zip/0_187679218169e6fb760fd24)
     * @param string $outputZip 作成するzipファイルの名前（フルパス・拡張子あり）
     * @param string $password ZIPファイルに設定するパスワード（空文字の場合はパスワードなし）
     * 
     * @return string "success" 成功した場合、"zip error" エラーが発生した場合
     */
    function createZip($sourcePath, $outputZip, $password = '')
    {
        $log = &LogFactory::getLog();
        $log->debug(__CLASS__ . 'の' . __FUNCTION__ . 'が実行されました', __CLASS__ . "#execute");

        $zip = new ZipArchive();
        try {
            // ZIPオープン
            if ($zip->open($outputZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                $this->saveErrorLog([
                    'message' => 'zipファイルの作成に失敗しました: ' . $zip->getStatusString()
                ]);
                return "zip error";
            }
            // ファイル追加
            if (!$this->addFilesToZip($zip, $sourcePath, $password)) {
                $this->saveErrorLog([
                    'message' => 'ファイルのZIP追加に失敗しました: ' . $zip->getStatusString()
                ]);
                $zip->close();
                return "zip error";
            }
            // ZIPクローズ
            if ($zip->close() === false) {
                $this->saveErrorLog([
                    'message' => 'zip->close()が失敗しました'
                ]);
                return "zip error";
            }
        } catch (Exception $e) {
            $this->saveErrorLog([
                'message' => $e->getMessage()
            ]);
            return "zip error";
        }

        return "success";
    }


    /**
     * フォルダ内ファイルをZIPへ追加
     */
    private function addFilesToZip($zip, $sourcePath, $password = '')
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourcePath, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $file) {
            if (!$file->isFile()) continue;
            $fullPath  = $file->getPathname();
            // zip内のパスを再現
            $localName = $files->getSubIterator()->getSubPathName();
            $localName = mb_convert_encoding($localName, 'UTF-8', 'auto');
            if (!$zip->addFile($fullPath, $localName)) {
                $this->saveErrorLog([
                    'status'      => $zip->status,
                    'file_exists' => file_exists($fullPath) ? 'Yes' : 'No',
                    'is_readable' => is_readable($fullPath) ? 'Yes' : 'No',
                    'message'     => $zip->getStatusString()
                ]);
                return false;
            }
            if (!empty($password)) {
                if (!$zip->setEncryptionName($localName, \ZipArchive::EM_TRAD_PKWARE, $password)) {
                    return false;
                }
            }
        }
        return true;
    }
    /**
     * エラーログを作成する（webapp/logs/error-log--yyyymmdd.log）
     *
     * @param array $errorInfo エラーに関する情報を含む連想配列。例: ['message' => 'エラーメッセージ', 'file_exists' => 'Yes', 'is_readable' => 'No']
     */
    function saveErrorLog($errorInfo)
    {
        if (!is_dir(LOG_DIR) || !is_writable(LOG_DIR)) {
            return;
        }
        $errorInfo = ['time' => date('Y-m-d H:i:s')] + $errorInfo;
        $error_file = LOG_DIR . '/error-log-' . date('Ymd') . '.log';
        file_put_contents($error_file, print_r($errorInfo, true), FILE_APPEND);
        // 古いログファイルの削除（8日以上前のファイルを削除）
        $files = glob(LOG_DIR . '/error-log-*.log') ?: [];
        foreach ($files as $file) {
            if (!preg_match('/error-log-(\d{8})\.log$/', $file, $m)) continue;
            $fileDate = DateTime::createFromFormat('Ymd', $m[1]);
            if ($fileDate && (new DateTime())->diff($fileDate)->days > 7) {
                if (file_exists($file) && is_file($file) && is_writable($file)) {
                    @unlink($file);
                }
            }
        }
    }
}
?>
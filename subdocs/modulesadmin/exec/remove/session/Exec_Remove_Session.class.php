<?php
require_once( MODULE_DIR . "/common/common.php");

class Exec_Remove_Session {
    function execute() {
        $log =& LogFactory::getLog();
        $log->debug("Exec_Remove_Sessionが実行されました。", "Exec_Remove_Session#execute");

        # 削除対象ファイルの経過時間(分単位)
        define("DEL_TIME",55);
        deleteOldSession();

    }
}

// 最終更新からDEL_TIME分以上経過したセッションは削除する
function deleteOldSession(){
    // パスがおかしい場合は終了
    if(!file_exists(SESSION_SAVEPATH)){
        return;
    }

    // SESSION_SAVEPATH内のファイルの更新日時を確認し、DEL_TIME分以上経過していたら削除する
    $files = glob(SESSION_SAVEPATH."/*");
    foreach ($files as $file) {
        $gap = strtotime('now') - 
        filemtime($file);
        if($gap > DEL_TIME * 60){
            unlink($file);
        }
    }
}
?>
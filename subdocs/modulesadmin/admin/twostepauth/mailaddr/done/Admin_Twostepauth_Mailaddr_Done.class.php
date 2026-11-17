<?php


/**
 * 二要素認証の設定
 *
 * @package     WebFile
 * @version     5.4.?
 * @author
 * @copyright   KEYPOINT.INC
 * @access      public
 **/
require_once(MODULE_DIR . "/common/common.php");
require_once(VALIDATOR_DIR . "/Validator_Mail.class.php");
require_once(MAPLE_DIR . '/core/Token.class.php');

class Admin_Twostepauth_Mailaddr_Done 
{
    var $MdbAdminTwostepauth;
    var $session;
    var $DtoAdminTwostepauth;
    var $TwostepauthLogic;

    //log
    var $DtoLog;

    function execute()
    {
        $log = &LogFactory::getLog();
        $log->debug(__CLASS__ . "が実行されました", __CLASS__);

        if (!ADMIN_TWO_STEP_AUTH_FLG || $this->TwostepauthLogic->getAdmin_two_step_auth_type() !== '2') {
            echo json_encode([
                'status' => 'error',
                'message' => '不正なアクセスです。',
                'tokenValue' => $this->getNewTokenValue(),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($this->DtoAdminTwostepauth->getEmail() !== '') {
            //メールのバリデーションチェック
            $validatorMail = new Validator_Mail;
            if ($validatorMail->validate($this->DtoAdminTwostepauth->getEmail(), 'hasError') === 'hasError') {
                echo json_encode([
                    'status' => 'input',
                    'message' => 'メールアドレスを正しく入力してください。',
                    'tokenValue' => $this->getNewTokenValue(),
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            //文字数チェック
            if (mb_strlen($this->DtoAdminTwostepauth->getEmail()) > 255) {
                echo json_encode([
                    'status' => 'input',
                    'message' => 'メールアドレスを255文字以内で入力してください。',
                    'tokenValue' => $this->getNewTokenValue(),
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
        //保存処理
        $admin_id = $this->session->getParameter('admin_id');
        $beforeRegisteringSettings = $this->MdbAdminTwostepauth->getAdminTwostepauthInfo($admin_id);
        $saveSettings = [
            'admin_id' => $admin_id,
            'email' => $this->DtoAdminTwostepauth->getEmail() ?: null,
        ];
        //テーブルに保存
        if (!$beforeRegisteringSettings) {
            //テーブルにレコードがなければ追加
            $result = $this->MdbAdminTwostepauth->add($saveSettings);
            $beforeRegisteringSettings["email"] = '';
        } else {
            //更新する
            $result = $this->MdbAdminTwostepauth->update($saveSettings, $beforeRegisteringSettings["admin_two_step_auth_id"]);
        }
        //保存に失敗した場合エラーを返す
        if (!$result) {
            echo json_encode([
                'status' => 'error',
                'message' => '登録できませんでした。お手数ですが、もう一度お試しください。',
                'tokenValue' => $this->getNewTokenValue(),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // メニューバーの表示を切り替え
        $this->session->setParameter('login_admin_two_step_auth_email', $saveSettings['email']);

        //Log
        $logMemo = '';
        if ($saveSettings["email"] !== $beforeRegisteringSettings["email"]) {
            $beforeEmail = $beforeRegisteringSettings["email"] ?: '未設定';
            $afterEmail = $saveSettings["email"] ?: '未設定';
            $logMemo = 'メールアドレス：';
            $logMemo .=  $beforeEmail . "\n" . "→" . $afterEmail;
            $logMemo .= "\n";
        }
        //変更している場合ログの処理
        if ($beforeRegisteringSettings["email"] !== $saveSettings["email"]) {
            $this->TwostepauthLogic->addTwostepauthLog($this->DtoLog, history_id: '200', memo: $logMemo); // ログへ記録
        }
        // ２要素認証済みフラグを立てる
        $this->session->setParameter('two_factor_authenticated', '1');
        echo json_encode([
            'status' => 'success',
            'message' => 'メールアドレスを保存しました。'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    function setMdbAdminTwostepauth($MdbAdminTwostepauth)
    {
        $this->MdbAdminTwostepauth = $MdbAdminTwostepauth;
    }
    function setSession($session)
    {
        $this->session = $session;
    }
    function setDtoAdminTwostepauth($DtoAdminTwostepauth)
    {
        $this->DtoAdminTwostepauth = $DtoAdminTwostepauth;
    }
    function setDtoLog($DtoLog)
    {
        $this->DtoLog = $DtoLog;
    }
    /**
     * getter
     */
    function getMdbAdminTwostepauth()
    {
        return $this->MdbAdminTwostepauth;
    }
    function getDtoAdminTwostepauth()
    {
        return $this->DtoAdminTwostepauth;
    }
    //エラー画面用
    function getPreviousAction()
    {
        //戻るボタンのリンク先
        return '';
    }
    function getFunctionName()
    {
        //エラー画面のタイトル
        return '二要素認証の設定';
    }
    function getAdminTwostepauthInfo()
    {
        $admin_id = $this->session->getParameter("admin_id");
        return $this->MdbAdminTwostepauth->getAdminTwostepauthInfo($admin_id);
    }
    /**
     * 
     * 画面が遷移する系のエラーと通信に成功した際にTokenを削除する。
     * （バリデーションエラーなど画面が遷移しない系のエラーでTokenが消えると二回目以降入力できなくなるため）
     *
     * @return void
     */
    function getNewTokenValue()
    {
        $container = &DIContainerFactory::getContainer();
        $session = &$container->getComponent("Session");
        $token = new Token;
        $container->register($token, "Token");
        $token->setSession($session);
        $token->setName('AdminTwoStepAuthMailToken');
        return $token->getValue();
    }
    function setTwostepauthLogic($TwostepauthLogic)
    {
        $this->TwostepauthLogic = $TwostepauthLogic;
    }
}

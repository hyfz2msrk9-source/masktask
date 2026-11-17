<?php
/**
* [機能説明]データベース接続等 関数群
*
* @package     WebFileAsp
* @author      Kiyofumi Nagami
* @copyright   KeyPoint
* @access      public
*/

class Db_Common
{
	private static $instance;

	// connection
	private $con;

	/**
	* [機能説明]コンストラクタ
	* @auther  Kiyofumi Nagami
	*/
	private function __construct() {
	}

	/**
	* [機能説明]インスタンス化
	* @auther  Kiyofumi Nagami
	* @return  instance
	*/
	public static function getInstance()
	{
		if (self::$instance == null){
			$className = __CLASS__;
			self::$instance = new $className;
		}
		return self::$instance;
	}

	/**
	* [機能説明]DB接続
	* @auther  Kiyofumi Nagami
	* @return  mysqli_connect
	*/
	static function connect() {
		if(!isset(self::$instance)) {
			self::getInstance();
		}
		if(!isset(self::$instance->con)) {
			self::$instance->con = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, 3306);
			if (! self::$instance->con) {
				self::write_error("Database connection error.");
				exit();
			} else {
				if(! @mysqli_select_db(self::$instance->con, DB_NAME)) {
					self::write_error("Database does not exist.");
					exit();
				}
			}
			/* 接続状況をチェックします */
			if (mysqli_connect_errno()) {
				self::write_error("Connect failed: %s\n", mysqli_connect_error());
			    exit();
			}
		}
		return self::$instance->con;
	}

	/**
	* [機能説明]DBクローズ
	* @auther  Kiyofumi Nagami
	*/
	static function close() {
    	mysqli_close(self::connect());
		unset(self::$instance->con);
	}

	/**
	* [機能説明]クエリを実行する
	* @auther  Kiyofumi Nagami
	* @params  String  $stmt
	* @return  mysqli_resultオブジェクト or true or false
	*/
	static function query($stmt) {
		$connect = self::connect();
		if (!$result = mysqli_query($connect, $stmt)) {
			$msg = "[query]:".$stmt;
			$msg .= "\n[errorcode]:".$connect->errno;
			$msg .= "\n[errormessage]:".$connect->error;
			self::write_error($msg);
		}
		return $result;
	}

	/**
	* [機能説明]直近のクエリで使用した自動生成の ID を返す
	* @auther  Kiyofumi Nagami
	* @return  int
	*/
	static function insert_id() {
		return mysqli_insert_id(self::connect());
	}

	/**
	* [機能説明]接続の現在の文字セットを考慮して、SQL 文で使用する文字列の特殊文字をエスケープする
	* @auther  Kiyofumi Nagami
	* @params  String  $str
	* @return  String
	*/
	static function escape_string($str) {
		return mysqli_real_escape_string(self::connect(), $str);
	}

	/**
	* [機能説明]結果の行を連想配列・数値添字配列あるいはその両方の形式で取得する
	* @auther  Kiyofumi Nagami
	* @params  mysqli_result  $result
	* @return  array
	*/
	static function fetch_array($result) {
		return mysqli_fetch_array ($result);
	}

	/**
	* [機能説明]結果の行を連想配列で取得する
	* @auther  Kiyofumi Nagami
	* @params  mysqli_result  $result
	* @return  連想配列
	*/
	static function fetch_assoc($result) {
		return mysqli_fetch_assoc($result);
	}

	/**
	* [機能説明]結果の行数を取得する
	* @auther  Kiyofumi Nagami
	* @params  mysqli_result  $result
	* @return  int
	*/
	static function num_rows($result) {
		return mysqli_num_rows($result);
	}

	/**
	* [機能説明]エラーを出力
	* @auther  Kiyofumi Nagami
	* @params  string  $msg
	*/
	static function write_error($msg) {
		if (defined("MYSQL_ERROR_LOG_FLG") && MYSQL_ERROR_LOG_FLG) {
			$fp = fopen(LOG_DIR."/sql_error.log","a+");
			$errmsg = "\n".date("Y-m-d H:i:s")." db_common error\n".$msg."\n";
			fwrite($fp,$errmsg);
			fclose($fp);
		}
	}
}

?>

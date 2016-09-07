<?php

require_once('Define.php');
require_once(CP.'Config.php');
require_once "CoreLog.php";
require_once "Janitor.php";
require_once "SqlCore.php";

class LoginUser {

	private $LOGGED_IN = false;
	public $USER_ID;

	function __construct(){
		session_start();
		$this->check_login();
		}

	//Logs User In Functions
	public function start_login($arr){

		//CHECK ARR IF SET
		if(!isset($arr['USER_EMAIL']) || !isset($arr['USER_PASS'])){
			echo Janitor::build_json_alert('Array Was Not Passed', 'Login User Error', 100, 2);
			exit;
			}

		$email = $arr['USER_EMAIL'];
		$password = $arr['USER_PASS'];

		//STARTS SESSION IF NON
		session_id() == '' ? session_start(): NULL;

		$db = new SqlCore;

		//CHECK IF USER IS LOGGED IN
		if($this->check_login()){
			echo Janitor::build_json_alert('User Is Already Logged Into System', 'User Logged In', 102, 2);
			exit;
			}

		$sql = "SELECT `USER_SALT` FROM `user_tbl` WHERE `USER_EMAIL` = ?";
		$bind = array($email);
		$saltArr = $db->query_array($sql, $bind);

		if($saltArr==NULL){
			echo Janitor::build_json_alert('Login Fail', 'Email Is Incorrect', 103, 2);
			exit;
			}

		$sql2 = "SELECT u.`USER_EMAIL`, u.`USER_ID`, u.`USER_FIRST_NAME`, u.`USER_LAST_NAME`, u.`USER_KEY`, u.`USER_PERM` FROM `user_tbl` AS u WHERE u.`USER_EMAIL`= ? AND u.`USER_PASS` = ?";
		$bind2 = array($email, sha1($password.$saltArr[0]['USER_SALT']));
		$userArr = $db->query_array($sql2, $bind2);

		if($userArr==NULL){
			echo Janitor::build_json_alert('Email Or Password Are Incorrect', 'Login Fail', 104, 2);
			exit;
			}

		//SET LOGIN VARS
		$_SESSION['userLgInfo'] = array('USER_EMAIL' => $userArr[0]['USER_EMAIL'], 'USER_ID' => Janitor::encrypt_data($userArr[0]['USER_ID'], Config::$ENCRYPT_KEY), 'USER_FULL_NAME' => $userArr[0]['USER_FIRST_NAME'].' '.$userArr[0]['USER_LAST_NAME'], 'USER_FIRST_NAME' => $userArr[0]['USER_FIRST_NAME'], 'USER_LAST_NAME' => $userArr[0]['USER_LAST_NAME'], 'USER_PERM' => Janitor::encrypt_data($userArr[0]['USER_PERM'], Config::$ENCRYPT_KEY));

		setcookie("user", Janitor::encrypt_data($userArr[0]['USER_KEY'], Config::$ENCRYPT_KEY), time()+86400, '/');

		$this->USER_ID = $userArr[0]['USER_ID'];
		$this->LOGGED_IN = true;

		echo Janitor::build_json_alert('Login Was Successful', 'Success', 105, 1);
		exit;
	}

	//LOGIN FOR USER IS DESTOYED
	public function end_login(){

		//STARTS SESSION IF NON
		session_id() == ''? session_start(): NULL;

		$this->LOGGED_IN = false;
		unset($this->USER_ID);

		$_SESSION = Array();

		setcookie("PHPSESSID", NULL, time()-3600, '/');
		setcookie("user", NULL, time()-3600, '/');

		echo Janitor::build_json_alert('Logged Out Successfully', 'Success', 107, 1);
		exit;
		}

	//CHECKS TO SEE IF USER IS LOGGED IN
	//RETURNS TRUE IF USER IS LOGGED IN
	public function check_login(){

		session_id() == '' ? session_start(): NULL;

		if($this->LOGGED_IN){//IF THE LOGIN OBJECT VAR IS SET
			return true;
			}
		else{

			if(isset($_SESSION['userLgInfo']['USER_ID'])){//IF SESSION IS SET
				$this->LOGGED_IN = true;
				$this->USER_ID = $_SESSION['userLgInfo']['USER_ID'];
				return true;
				}
			else if(isset($_COOKIE['user'])){
				$key = Janitor::decrypt_data($_COOKIE['user'], Config::$ENCRYPT_KEY);
				$sql = "SELECT u.`USER_EMAIL`, u.`USER_ID`, u.`USER_FIRST_NAME`, u.`USER_LAST_NAME`, u.`USER_KEY`, u.`USER_PERM` FROM `user_tbl` AS u WHERE u.`USER_KEY`= ?"; $bind = array($key); $db = new SqlCore;
				$userArr = $db->query_array($sql, $bind);

				if($userArr==NULL){ return false; }

				//SET LOGIN VARS
				$_SESSION['userLgInfo'] = array('USER_EMAIL' => $userArr[0]['USER_EMAIL'], 'USER_ID' => Janitor::encrypt_data($userArr[0]['USER_ID'], Config::$ENCRYPT_KEY), 'USER_FULL_NAME' => $userArr[0]['USER_FIRST_NAME'].' '.$userArr[0]['USER_LAST_NAME'], 'USER_FIRST_NAME' => $userArr[0]['USER_FIRST_NAME'], 'USER_LAST_NAME' => $userArr[0]['USER_LAST_NAME'], 'USER_PERM' => Janitor::encrypt_data($userArr[0]['USER_PERM'], Config::$ENCRYPT_KEY));
				$this->LOGGED_IN = true;
				$this->USER_ID = $userArr[0]['USER_ID'];
				return true;
				}
			else{
				return false;
				}
			}
		}

	//CHANGE USER PASS
	public function change_pass($arr){

		session_id() == '' ? session_start(): NULL;

		if(!Janitor::check_vars($arr)){return Janitor::build_json_alert('No Variables Were Sent To Backend', 'No Variables', 116, 2); exit; }

		$db = new SqlCore;

		$id = Janitor::decrypt_data($_SESSION['userLgInfo']['USER_ID'], Config::$ENCRYPT_KEY);

		$sql = "SELECT `USER_SALT` FROM `user_tbl` WHERE `USER_ID` = ?";
		$bind = array($id);

		$userArr = $db->query_array($sql, $bind);
		if(count($userArr)==0){ return Janitor::build_json_alert('Sql Failed To Select From Database', 'Failed To Execute', 118, 2); exit; }

		$salt = $userArr[0]['USER_SALT'];

		$sql2 = "SELECT `USER_ID` FROM `user_tbl` WHERE `USER_PASS` = ? AND `USER_ID` = ? LIMIT 0, 1";
		$bind2 = array(sha1($arr['USER_CURRENT_PASS'].$salt), $id);

		if($db->query_num($sql2, $bind2)!==1){ return Janitor::build_json_alert('User ID Is Incorrect', 'Failed To Update Password', 120, 2); exit; }

		$sql3 = "UPDATE `user_tbl` SET `USER_PASS` = ? WHERE `USER_ID` = ?";
		$bind3 = array(sha1($arr['USER_PASS'].$salt), $id);

		if($db->query_sql_impact($sql3, $bind3)){
			return Janitor::build_json_alert('Successfully Updated Password', 'Success', 122, 1);
			exit;
			}
		else{
			return Janitor::build_json_alert('Sql Failed To Updated Database', 'Failed To Update Password', 123, 2);
			exit;
			}
		}

	//RETRIEVE DATA FROM SESSION
	public function return_user_array(){

		session_id() == '' ? session_start(): NULL;

		if(!$this->check_login()){
			return false;
			exit;
			}

		return $_SESSION['userLgInfo'];
		}

	//CHECK SUPER USER
	public function check_user_perm(){
		if(!$this->check_login()){
			echo Janitor::build_json_alert('Please Log Back Into Backend', 'User Session Expired', 114, 2);
			return false;
			}

		$arr = $this->return_user_array();
		$userPerm = Janitor::decrypt_data($arr['USER_PERM'], Config::$ENCRYPT_KEY);

		if($userPerm==1){
			return true;
			}
		else{
			return false;
			}
		}

}
?>

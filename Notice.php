<?php
require_once('Define.php');
require_once(CP.'Config.php');
require_once "SqlCore.php";
require_once "CoreLog.php";
require_once "Janitor.php";

class Notice {
	//INIT FUNCTION
	function __construct(){}

	//LOAD NOTICES
	/*-----------------------------------
	Send Array With Load And Start Values
	------------------------------------*/
	public static function load_notices($array){
		if(!Janitor::check_vars(array($array))){exit;}
		//STARTS SESSION IF NON
		session_id() == '' ? session_start(): NULL;

		$db = new SqlCore;

		$sql = "SELECT `NOTICE_ID`, `NOTICE_TITLE`, `NOTICE_READ`, `NOTICE_LINK`, DATE_FORMAT(`NOTICE_TIMESTAMP`, '%b %D %Y') AS timeStamp FROM `notice_tbl` WHERE `NOTICE_USER_ID` = ? ORDER BY `NOTICE_TIMESTAMP` DESC LIMIT ?, ?";
		$bind = array(Janitor::decrypt_data($_SESSION['userLgInfo']['USER_ID'], Config::$ENCRYPT_KEY), $array['start'], $array['load']);

		$structure = array("id" => "NOTICE_ID",
							"read" => "NOTICE_READ",
							"link" => "NOTICE_LINK",
							"title" => "NOTICE_TITLE",
							"date" => "timeStamp");

		return $db->structure_select($sql, $bind, $structure);
		}

	//LOAD NOTICE DATA
	/*-----------------------------------

	Send The Notice ID

	------------------------------------*/
	public static function load_notice_data($id){
		if(!Janitor::check_vars(array($id))){exit;}

		$db = new SqlCore;

		$sql = "SELECT `NOTICE_TITLE`, `NOTICE_MESS`, `NOTICE_LINK`, DATE_FORMAT(`NOTICE_TIMESTAMP`, '%b %D %Y') AS timeStamp FROM `notice_tbl` WHERE `NOTICE_ID` = ?";
		$bind = array($id);

		$structure = array("NOTICE_TITLE" => "NOTICE_TITLE",
							"NOTICE_MESS" => "NOTICE_MESS",
							"NOTICE_LINK" => "NOTICE_LINK",
							"timeStamp" => "timeStamp");

		return $db->structure_select($sql, $bind, $structure);
		}

	//LOAD NUMBER OF NOTICES
	/*-----------------------------------

	If notices exceed 10 records they will return "10+"

	------------------------------------*/
	public static function number_notices(){
		//STARTS SESSION IF NON
		session_id() == '' ? session_start(): NULL;

		$db = new SqlCore;

		$sql = "SELECT `NOTICE_ID` FROM `notice_tbl` WHERE `NOTICE_USER_ID` = ? AND `NOTICE_READ` IS NULL";
		$bind = array(Janitor::decrypt_data($_SESSION['userLgInfo']['USER_ID'], Config::$ENCRYPT_KEY));
		$num = $db->query_num($sql, $bind);

		if((int)$num >= 10){
			return '10+';
			exit;
			}
		elseif((int)$num==0){
			return NULL;
			exit;
			}

		return $num;
		exit;
		}


	//INSERT NOTICE
	/*-----------------------------------
	Different Insert Types
	$message = The Notice Message

	$link = Link For Message

	$type = 0 = All Users
			1 = Specific Users
			2 = All Exclude A One Or More

	$array = Arr Of User IDS for 1 and 2 Types

	Will Return True/False
	------------------------------------*/
	public static function insert_notice($title, $message, $link = NULL, $type = 0, $array = NULL){
		if(!Janitor::check_vars(array($message))){return false;}

		//STARTS SESSION IF NON
		session_id() == '' ? session_start(): NULL;

		$db = new SqlCore;

		//For Different Notice Types
		switch($type){

			case 0:
				//SELECT ALL USER IDS
				$sql = "SELECT `USER_ID` FROM `user_tbl` WHERE `USER_ID` != ? AND `USER_ACTIVE` = ?";
				$bind = array(Janitor::decrypt_data($_SESSION['userLgInfo']['USER_ID'], Config::$ENCRYPT_KEY), 1);
				$userArr = $db->query_array($sql, $bind);

				//LOOP THRU AND CREATE NOTICE RECORDS
				foreach($userArr as $user){
					$sql2 = "INSERT INTO `notice_tbl`(`NOTICE_MESS`, `NOTICE_USER_ID`, `NOTICE_TITLE`, `NOTICE_LINK`) VALUES(?, ?, ?, ?)";
					$bind2 = array($message, $user['USER_ID'], $title, $link);
					if(!$db->query_sql_impact($sql2, $bind2)){
						return false;
						}
					}
				break;
			case 1:

				break;
			case 2:

				break;
			default:
				return false;
				break;
			}

		return true;
		}

	//MARK NOTICE
	/*-----------------------------------

	$array = An Array Of IDs For Notices To Be Marked

	Will Return True / False
	------------------------------------*/
	public static function mark_notice($array){
		if(!Janitor::check_vars(array($array))){return false;}

		//STARTS SESSION IF NON
		session_id() == '' ? session_start(): NULL;
		$db = new SqlCore;

		foreach($array as $id){

			$sql = "UPDATE `notice_tbl` SET `NOTICE_READ` = ? WHERE `NOTICE_ID` = ?";
			$bind = array(1, $id);
			if(!$db->query_sql_impact($sql, $bind)){
				return false;
				exit;
				}

			}

		return true;

		}



}
?>

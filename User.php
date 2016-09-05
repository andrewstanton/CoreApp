<?php
require_once "CoreLog.php";
require_once "Config.php";
require_once "SqlCore.php";
require_once "Janitor.php";
require_once "LoginUser.php";

class User extends LoginUser {
	
	function __construct(){}
	
	//*************************************
	//*************************************
	//***TESTING FUNCTIONS FOR SQLCORE*****
	//*************************************
	//*************************************
	
	//TEST FUNCTION
	public function test_conn(){
		$db = new SqlCore;
		if($db->test()){
			return 'Connection is Strong!';
			}
		else{
			return 'Unable to Connect!';
			}
		}
	
	//TEST FOR SQL 
	public function test_sql(){
		$db = new SqlCore;
		
		$sql = "SELECT * FROM `user_tbl` WHERE `user_email` = ?";
		$bind = array("andrew@toddprod.com");
		$arr = $db->query_array($sql, $bind);
		
		print_r($arr);
		}
	
	
	//*************************************
	//*************************************
	//******END OF SQLCORE TESTING*********
	//*************************************
	//*************************************
	
	//RETURN CLASS NAME IF URLS COMPARE CORRECT
	/*----------------------------------------
	
	$currentURL = Current Whole URL
	$urlPage = Page For Class To Be Sent (just page name with ext)
	$urlArr = an array that getVariables should be
	$class = the returned Class name
		
	------------------------------------------*/
	
	public function class_active($currentURL, $urlPage, $urlVars = NULL, $class, $exact = false){
		$currentpage = basename($currentURL);
		$pageArr = explode("?", $currentpage);
		$page = $pageArr[0];
		
		if($page!==$urlPage){ return false;}
		if($urlVars==NULL && $exact==false){ return $class;}
		if($urlVars==NULL && count($pageArr)==1){ return $class;}
		if($urlVars==NULL){ return false;}
		if(count($pageArr)==1){ return false;}
		
		$varsArr = explode("&", $pageArr[1]);
		
		$countVars = count($urlVars);
		$countCheck = 0;
		
		foreach($varsArr as $val){

			$vl = explode('=', $val);
			if(!isset($urlVars[$vl[0]])){continue;}
			if($urlVars[$vl[0]]==$vl[1]){
				$countCheck++;
				}
			}
		
		if($countCheck==$countVars){
			return $class;
			}
		else{
			return false;
			}
		}
	
		
	//ADD BAND-ARTIST RECORD
	public function add_page($arr){
		if(!Janitor::check_vars(array($arr))){exit;}
		$db = new SqlCore;
		
		$userdata = $this->return_user_array();
		$key = md5(Janitor::generate_key(100));
		$id = $this->get_site_id();
		
		$sql = "INSERT INTO `page_tbl`(`PAGE_TITLE`, `PAGE_USER_ID`, `PAGE_LAST_UPDATE`, `PAGE_PUBLIC_ID`, `PAGE_SITE_ID`) VALUES(?, ?, now(), ?, ?)";
		$bind = array($arr['PAGE_TITLE'], Janitor::decrypt_data($userdata['USER_ID'], Config::$ENCRYPT_KEY), $key, $id);
		$insId = $db->insert_id($sql, $bind);
	
		return Janitor::build_json_alert($insId, 'Success', 500, 1);
		}

	//SELETS TAG BASED ON THE TYPE OF ID GIVEN
	//BAND - ARTIST - ALBUM
	public function load_pages($arr){
		if(!Janitor::check_vars(array($arr))){exit;}
		$db = new SqlCore;
		$id = $this->get_site_id();
		
		if(!$id){
			return Janitor::build_json_alert('Backend Variables For Site Id Has Not Been Set. Refresh Page.', 'No Site ID', 500, 2);
			exit;
			}
		
		$sql = "SELECT `PAGE_ID`, `PAGE_TITLE`, DATE_FORMAT(`PAGE_LAST_UPDATE`, '%b %D %Y') AS LastUpdate, `PAGE_USER_ID` FROM `page_tbl` WHERE `PAGE_SITE_ID` = ? LIMIT ?, ?";		
		
		$bind = array($id, $arr['start'], $arr['load']);
		$structure = array("id" => "PAGE_ID", 
							"title" => "PAGE_TITLE",
							"update" => "LastUpdate",
							"user" => "PAGE_USER_ID");
		
		return $db->structure_select($sql, $bind, $structure);
		}
	
	//GET PAGE DETAILS
	public function get_page_arr($id){
		if(!Janitor::check_vars(array($id))){exit;}
		$db = new SqlCore;
		
		$sql = "SELECT `PAGE_ID`, `PAGE_TITLE`, `PAGE_PUBLIC_ID`, DATE_FORMAT(`PAGE_LAST_UPDATE`, '%b %D %Y') AS LastUpdate, `PAGE_USER_ID`, DATE_FORMAT(`PAGE_TIMESTAMP`, '%b %D %Y') AS TimeStamp FROM `page_tbl` WHERE `PAGE_ID` = ?";		
		
		$bind = array($id);
		
		$arr = $db->array_select($sql, $bind);
		return $arr[0];
		}
		
	//SELECT USERS FROM 
	public function load_site_users(){
		if(!$this->check_user_perm()){
			return Janitor::build_json_alert('Your User Creditials Do Not Have Proper Permission', 'Access Denied', 504, 2);
			exit;
			}
		$db = new SqlCore;
		
		$sql = "SELECT `USER_ID`, `USER_FIRST_NAME`, DATE_FORMAT(`USER_TIMESTAMP`, '%b %D %Y') AS AddDate, `USER_EMAIL` FROM `user_tbl` WHERE `PAGE_SITE_ID` = ? LIMIT ?, ?";		
		
		$bind = array(Janitor::decrypt_data($userdata['USER_SITE_ID'], Config::$ENCRYPT_KEY), $arr['start'], $arr['load']);
		$structure = array("id" => "USER_ID", 
							"first" => "USER_FIRST_NAME",
							"last" => "USER_LAST_NAME",
							"date" => "AddDate");
		
		return $db->structure_select($sql, $bind, $structure);
		}
	
	//GET SITE COOKIE
	private function get_site_id(){
		if(!$this->check_site_cookie(false)){return false;}
		
		return Janitor::decrypt_data($_COOKIE['site'], Config::$ENCRYPT_KEY);
		}
	
	//DOES THE USER HAVE MULTIPLE SITES
	//RETURNS TRUE IF USER OWNS MULTIPLE SITES
	public function multi_sites(){
		$db = new SqlCore;
		
		$arr = $this->return_user_array();
		
		$sql = "SELECT `LINK_ID` FROM `site_link_tbl` WHERE `LINK_USER_ID` = ?";
		$bind = array(Janitor::decrypt_data($arr['USER_ID'], Config::$ENCRYPT_KEY));
		
		if($db->number_select($sql, $bind) > 1){
			return true;
			}
		else{
			return false;
			}
		}
	
	//CHECK IF SITE COOKIE HAS BEEN SET
	public function check_site_cookie($full=true){
		if(!isset($_COOKIE['site'])){return false;}
		$db = new SqlCore;
		
		if($full==false){
			return true;
			}
		
		$sql = "SELECT `SITE_ID` FROM `site_tbl` AS s WHERE `SITE_ID` = ?";
		$bind = array(Janitor::decrypt_data($_COOKIE['site'], Config::$ENCRYPT_KEY));
		
		if($db->number_select($sql, $bind)==1){
			return true;
			}
		else{
			return false;
			}
		}
		
	//DESTORY SITE COOKIE
	public function destroy_site_cookie(){
		setcookie("site", NULL, time()-3600, '/');
		return true;
		}
	
	//SET SINGLE SITE COOKIE
	public function set_single_site_cookie(){
		$db = new SqlCore;
		
		$userarr = $this->return_user_array();
		
		$sql = "SELECT s.`SITE_ID` FROM `site_tbl` AS s LEFT JOIN `site_link_tbl` AS l ON l.`LINK_SITE_ID` = s.`SITE_ID` WHERE l.`LINK_USER_ID` = ?";
		$bind = array(Janitor::decrypt_data($userarr['USER_ID'], Config::$ENCRYPT_KEY));
		
		$arr = $db->array_select($sql, $bind);
		
		if(count($arr)==0){
			Corelog::add('Site Key For User Could Not Be Selected! Error With SQL Select');
			return false;
			}
		
		setcookie("site", Janitor::encrypt_data($arr[0]['SITE_ID'], Config::$ENCRYPT_KEY), time()+86400, '/');
		return true;
		}
	
	//SET SITE COOKIE FROM COOKIE
	public function set_site_cookie($key){
		if(!Janitor::check_vars(array($key))){return false;}
		
		$db = new SqlCore;
		$userarr = $this->return_user_array();
		
		
		$sql = "SELECT s.`SITE_ID` FROM `site_tbl` AS s LEFT JOIN `site_link_tbl` AS l ON l.`LINK_SITE_ID` = s.`SITE_ID` WHERE l.`LINK_USER_ID` = ? AND s.`SITE_KEY` = ?";
		$bind = array(Janitor::decrypt_data($userarr['USER_ID'], Config::$ENCRYPT_KEY), $key);
		
		$arr = $db->array_select($sql, $bind);
		
		if(count($arr)==0){
			Corelog::add('Site Key For User Could Not Be Selected With Key And User ID! Error With SQL Select');
			return False;
			}
		
		setcookie("site", Janitor::encrypt_data($arr[0]['SITE_ID'], Config::$ENCRYPT_KEY), time()+86400, '/');
		return true;
		}
	
	
	//GET SITES OWNED BY USER
	public function get_sites(){
		//if(!$this->check_user_perm()){return false;}
		$db = new SqlCore;
		
		$userarr = $this->return_user_array();
		
		$sql = "SELECT s.`SITE_KEY`, s.`SITE_NAME` FROM `site_tbl` AS s LEFT JOIN `site_link_tbl` AS l ON l.`LINK_SITE_ID` = s.`SITE_ID` WHERE s.`SITE_ACTIVE` = '1' AND l.`LINK_USER_ID` = ?";
		$bind = array(Janitor::decrypt_data($userarr['USER_ID'], Config::$ENCRYPT_KEY));
		
		return $db->array_select($sql, $bind);
		}
	
	//GET SITE INFORMATION
	public function get_site_arr(){
		$db = new SqlCore;
		
		$sql = "SELECT `SITE_KEY`, `SITE_NAME`, DATE_FORMAT(`SITE_TIMESTAMP`, '%b %D %Y') AS TimeStamp FROM `site_tbl` WHERE `SITE_ID` = ? LIMIT 0, 1";
		$bind = array($this->get_site_id());
		
		$arr = $db->array_select($sql, $bind);
		return $arr[0];
		}
	
	
	//LOAD SITES
	public function load_sites($arr){
		if(!Janitor::check_vars(array($arr))){return false;}
		$db = new SqlCore;
		
		$userarr = $this->return_user_array();
		
		$sql = "SELECT s.`SITE_KEY`, s.`SITE_NAME` FROM `site_tbl` AS s LEFT JOIN `site_link_tbl` AS l ON l.`LINK_SITE_ID` = s.`SITE_ID` WHERE s.`SITE_ACTIVE` = '1' AND l.`LINK_USER_ID` = ? LIMIT ?, ?";
		$bind = array(Janitor::decrypt_data($userarr['USER_ID'], Config::$ENCRYPT_KEY), $arr['start'], $arr['load']);
		
		$structure = array("name" => "SITE_NAME", 
							"key" => "SITE_KEY");
		
		return $db->structure_select($sql, $bind, $structure);
		}
	
	//LOAD USERS
	public function load_users($arr){
		if(!$this->check_user_perm()){return false;}
		if(!Janitor::check_vars(array($arr))){return false;}
		
		$db = new SqlCore;
		$id = $this->get_site_id();
		
		$sql = "SELECT u.`USER_FIRST_NAME`, u.`USER_LAST_NAME`, u.`USER_EMAIL`, DATE_FORMAT(u.`USER_TIMESTAMP`, '%b %D %Y') AS TimeStamp FROM `user_tbl` AS u LEFT JOIN `site_link_tbl` AS l ON l.`LINK_USER_ID` = u.`USER_ID` WHERE u.`USER_PERM` = '2' AND l.`LINK_SITE_ID` = ? LIMIT ?, ?";
		$bind = array($id, $arr['start'], $arr['load']);
		
		$structure = array("first" => "USER_FIRST_NAME", 
							"last" => "USER_LAST_NAME",
							"email" => "USER_EMAIL",
							"date" => "TimeStamp");
		
		return $db->structure_select($sql, $bind, $structure);
		}
		
	//LOAD NAV PAGES TO ADD
	public function load_add_nav_pages($arr){
		if(!Janitor::check_vars(array($arr))){return false;}
		$db = new SqlCore;
		$id = $this->get_site_id();
		
		$sql = "SELECT p.`PAGE_ID`, p.`PAGE_TITLE` FROM `page_tbl` AS p WHERE p.`PAGE_SITE_ID` = ? AND p.`PAGE_ID` NOT IN (SELECT n.`NAV_ITEM_PAGE_ID` FROM `nav_item_tbl` AS n WHERE n.`NAV_ITEM_SITE_ID` = ?) ORDER BY p.`PAGE_TITLE` ASC LIMIT ?, ?";
		$bind = array($id, $id, $arr['start'], $arr['load']);
		
		$structure = array("id" => "PAGE_ID",
							"title" => "PAGE_TITLE");
		
		return $db->structure_select($sql, $bind, $structure);
		}
	
	//LOAD NAV PAGES - MULTI STRUCTURE BUILD
	/*---------------------------------------
	Selects Nav Pages
	Arranges Array Returned In A New Array
	Builds New JSON With Structure Arranging
	----------------------------------------*/
	public function load_nav_pages(){
		
		$db = new SqlCore;
		$id = $this->get_site_id();
		
		$sql = "SELECT n.`NAV_ITEM_ID`, n.`NAV_ITEM_PAGE_ID`, n.`NAV_ITEM_TITLE`, n.`NAV_ITEM_IX`, n.`NAV_ITEM_PARENT_ID`, p.`PAGE_TITLE` FROM `nav_item_tbl` AS n LEFT JOIN `page_tbl` AS p ON n.`NAV_ITEM_PAGE_ID` = p.`PAGE_ID` WHERE n.`NAV_ITEM_SITE_ID` = ? AND n.`NAV_ITEM_PARENT_ID` IS NULL ORDER BY n.`NAV_ITEM_IX` ASC";
		$bind = array($id);
		
		$arrMain = $db->query_array($sql, $bind);
		
		$sql2 = "SELECT n.`NAV_ITEM_ID`, n.`NAV_ITEM_PAGE_ID`, n.`NAV_ITEM_TITLE`, n.`NAV_ITEM_IX`, n.`NAV_ITEM_PARENT_ID`, p.`PAGE_TITLE` FROM `nav_item_tbl` AS n LEFT JOIN `page_tbl` AS p ON n.`NAV_ITEM_PAGE_ID` = p.`PAGE_ID` WHERE n.`NAV_ITEM_SITE_ID` = ? AND n.`NAV_ITEM_PARENT_ID` IS NOT NULL ORDER BY n.`NAV_ITEM_IX` ASC";
		$arrSub = $db->query_array($sql2, $bind);
		
		
		//LOOP AND INSERT SUB RESULTS
		if(count($arrSub)>=1){
			
			$arr = array();
			
			
			foreach($arrMain as $par){
				$newRecord = $par;
				//LOOP THRU SUB
				foreach($arrSub as $sub){
					if($par['NAV_ITEM_ID']==$sub['NAV_ITEM_PARENT_ID']){
						if(!isset($newRecord['NAV_SUB'])){
							$newRecord['NAV_SUB'] = array();
							}
						array_push($newRecord['NAV_SUB'], $sub);
						}
					}
				array_push($arr, $newRecord);
				}
			
			$structure = array("id" => "NAV_ITEM_ID",
							"ix" => "NAV_ITEM_IX",
							"title" => "NAV_ITEM_TITLE",
							"pgtitle" => "PAGE_TITLE",
							"sub" => array( "NAV_SUB", array(
										"subid" => "NAV_ITEM_ID",
										"subix" => "NAV_ITEM_IX",
										"subtitle" => "NAV_ITEM_TITLE",
										"subpgtitle" => "PAGE_TITLE"
										)
									)
								);
			
			}
		else{
			$arr = $arrMain;
			$structure = array("id" => "NAV_ITEM_ID",
							"ix" => "NAV_ITEM_IX",
							"title" => "NAV_ITEM_TITLE",
							"pgtitle" => "PAGE_TITLE",
							);
			}
		
		return Janitor::build_json_list($arr, $structure);
		}
		
	//ADD PAGE TO NAVIGATION
	public function add_nav_page($id){
		if(!Janitor::check_vars(array($id))){return false;}
		$db = new SqlCore;
		$siteid = $this->get_site_id();
		$userarr = $this->return_user_array();
		
		$sql = "INSERT INTO `nav_item_tbl`(`NAV_ITEM_SITE_ID`, `NAV_ITEM_PAGE_ID`, `NAV_ITEM_USER_ID`) VALUES(?, ?, ?)";
		$bind = array($siteid, $id, Janitor::decrypt_data($userarr['USER_ID'], Config::$ENCRYPT_KEY));
	
		if($db->query_sql_impact($sql, $bind)){
			return Janitor::build_json_alert('Page Was Added To Navigation', 'Success', 520, 1);
			}
		else{
			return Janitor::build_json_alert('Page Could Not Be Added To Navigation', 'Error', 521, 2);
			}
		
		}
	
	
	//UPDATE NAVIGATION
	public function update_nav($arr){
		if(!Janitor::check_vars(array($arr))){return false;}
		
		$db = new SqlCore;
		$failUpdates = 0; $count = 0;
		
		foreach($arr as $ix => $nav){
			
			$id = $nav['id'];
			$count++;
			if(isset($nav['children']) && count($nav['children'])>0){
				foreach($nav['children'] as $subix => $sub){
					$count++;
					$sql1 = "UPDATE `nav_item_tbl` SET `NAV_ITEM_IX` = ?, `NAV_ITEM_PARENT_ID` = ? WHERE `NAV_ITEM_ID` = ?";
					$bind1 = array($subix, $id, $sub['id']);
					if(!$db->query_sql_impact($sql1, $bind1)){$failUpdates++;}
					}
				}
			
			$sql2 = "UPDATE `nav_item_tbl` SET `NAV_ITEM_IX` = ?, `NAV_ITEM_PARENT_ID` = NULL WHERE `NAV_ITEM_ID` = ?";
			$bind2 = array($ix, $id);
			if(!$db->query_sql_impact($sql2, $bind2)){$failUpdates++;}
			}
		
		if($count==$failUpdates){
			return Janitor::build_json_alert('Navigation Has Failed To Update', 'Error', 522, 2);
			}
		else{
			return Janitor::build_json_alert('Navigation Has Been Updated', 'Success', 523, 1);
			}
		}
	
	
		
	//UPDATE NAV ITEM
	public function remove_nav_page($arr){
		if(!Janitor::check_vars(array($arr))){return false;}
		$db = new SqlCore;
		
		$sql = "DELETE FROM `nav_item_tbl` WHERE `NAV_ITEM_ID` = ?";
		
		$bind = array($arr['id']);
	
		if($db->query_sql_impact($sql, $bind)){
			//UPDATE SUB NAV PAGES
			if($arr['child']=='true'){
				$sql2 = "UPDATE `nav_item_tbl` SET `NAV_ITEM_PARENT_ID` = NULL, `NAV_ITEM_IX` = NULL WHERE `NAV_ITEM_PARENT_ID` = ?";
				$bind2 = array($arr['id']);
				
				if($db->query_sql_impact($sql2, $bind2)){
					return Janitor::build_json_alert('Page Was Removed From Navigation', 'Success', 524, 1);
					}
				else{
					return Janitor::build_json_alert('Sub Page(s) Could Not Be Updated On Navigation', 'Error', 525, 2);
					}
				
				}
			else{
				return Janitor::build_json_alert('Page Was Removed From Navigation', 'Success', 526, 1);
				}
			
			}
		else{
			return Janitor::build_json_alert('Page Could Not Be Removed From Navigation', 'Error', 526, 2);
			}
		}
	
	//LOAD NAV ITEM DETAILS
	public function load_nav_page($id){
		if(!Janitor::check_vars(array($id))){return false;}
		$db = new SqlCore;
		
		$sql = "SELECT i.`NAV_ITEM_TITLE`, i,`NAV_ITEM_ID`, p.`PAGE_TITLE`, p.`PAGE_PUBLIC_ID` FROM `nav_item_tbl` AS i LEFT JOIN `page_tbl` AS p ON p.`PAGE_ID` = i.`NAV_ITEM_PAGE_ID` WHERE i.`NAV_ITEM_ID` = ?";
		$bind = array($id);
	
		$structure = array("id" => "NAV_ITEM_ID",
							"nm" => "NAV_ITEM_TITLE",
							"pgnm" => "PAGE_TITLE",
							"title" => "PAGE_TITLE");
	
		if($db->query_sql_impact($sql, $bind)){
			return Janitor::build_json_alert('Nav Item Was Removed', 'Success', 524, 1);
			}
		else{
			return Janitor::build_json_alert('Nav Item Could Not Be Removed', 'Error', 525, 2);
			}
		
		
		}
	
}

?>
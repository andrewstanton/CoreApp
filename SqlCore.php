<?php
require_once "../Config.php";
require_once "CoreLog.php";
require_once "Janitor.php";

class SqlCore {

	private $connection;
	private $stmt;

	function __construct(){}

	//CONNECTION FUNCTION
	//LOGS IF ERRORS HAPPEN
	private function createConnection(){

		//CHECK IF CONNECTION IS CREATED YET
		if($this->connection!==NULL){
			if(@$this->connection->ping()){
				return true;
				}
			}

		$this->connection = mysqli_connect( Config::$DB_HOST, Config::$DB_USER, Config::$DB_PASS, Config::$DB_NAME);

		if (mysqli_connect_error()){

			CoreLog::add('SQLCore Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error(), "sqlcore");

			return false;

			}

		if ($this->connection == false){

			Corelog::add("SQLCore Connection - createConnection Function", "sqlcore");

			return false;

			}

		return true;
		}

	//TEST FUNCTION
	public function test(){
		if($this->createConnection()){
			return true;
			}
		else{
			return false;
			}
		}

	//--------------------------------------
	//MAIN QUERY EXECUTING FUNCTION
	//USES A STATEMENT
	//Replaces Variables With Bind Array Values
	//All Bind Values Are Assumed As Strings
	//--------------------------------------
	private function exec_query($sql, $bind){
		if(!$this->createConnection()){
            Corelog::add("SQLCore Connection - exec_query Function", "sqlcore");
            return false;
        	}

		$conn = $this->connection;
		$this->stmt = $conn->prepare($sql);

		if($this->stmt==false){
			Corelog::add("SQLCore Prepration - exec_query Function - ".$conn->error, "sqlcore");
			return false;
			}

		//Loop Thru Bind Values
		if(isset($bind) && $bind!==NULL){
			$type = array(); $typeStrn = '';
			for($i=1; $i<=count($bind); $i++){
				//PREP BIND VALUES
				$typeStrn .= 's';
				}

			$type[] = $typeStrn;
			$params = array_merge($type, $bind);

			$return = call_user_func_array(array($this->stmt, 'bind_param'), $this->makeReferences($params));

			}

		//Excute SQL Statement
		$e = $this->stmt->execute();
		$this->stmt->store_result();

		if($e == false){
			Corelog::add("SQLCore Execution - exec_query Function - ".$conn->error, "sqlcore");
			return false;
			}

		return true;
		}

	private function makeReferences($arr){
		$refs = array();

    	foreach($arr as $key => $value)
        	$refs[$key] = &$arr[$key];

		return $refs;
		}

	//--------------------------------------
	//SINGLE QUERY EXECUTING FUNCTION
	//USES A STATEMENT
	//Doesn't Use Bind Values
	//--------------------------------------
	private function exec_single_query($sql){
		if(!$this->createConnection()){
            Corelog::add("SQLCore Connection - exec_query Function", "sqlcore");
            return false;
        	}

		$conn = $this->connection;
		$result = $conn->query($sql);

		return $result;
		}



	//------------------------------
	//FUNCTION USES EXEC_QUERY() FUNCTION
	//TO GET ARRAY BACK FROM DATABASE
	//------------------------------
	public function query_array($sql, $bind = NULL){

		if($bind==NULL){//SINGLE QUERY EXECUTE

			$result = $this->exec_single_query($sql);
			$returnArr = array();
			if($result->num_rows > 0){
				//APPEND TO NEW ARRAY
				while($data = $result->fetch_assoc()){
					array_push($returnArr, $data);
					}

				return $returnArr;
				}
			else{
				return NULL;
				}
			}

		else{

			if(!$this->exec_query($sql, $bind)){
				Corelog::add("SQLCore Query Failure - query_array Function", "sqlcore");
				echo Janitor::build_json_alert('SQLCore Query Failure', 'Query Could Not Be Executed', 10, 2);
				exit;
				}

			//CHECK IF ANY RESULTS
			if($this->stmt->num_rows > 0){
				//GET RESULT NAMES FOR BINDING DATA
				$meta = $this->stmt->result_metadata();
				while ($field = $meta->fetch_field()) {
					$params[] = &$row[$field->name];
					}

				call_user_func_array(array($this->stmt, 'bind_result'), $params);

				while ($this->stmt->fetch()) {
					foreach ($row as $key => $val) {
						$c[$key] = $val;
						}
					$result[] = $c;
					}

				$this->stmt->free_result();

				return $result;
				}
			else{
				return NULL;
				}

			}

		}

	//------------------------------
	//FUNCTION USES EXEC_QUERY() FUNCTION
	//TO GET NUMBER OF AFFECTED ROWS
	//------------------------------
	public function query_affect($sql, $bind = NULL){

		if($bind==NULL){//SINGLE QUERY EXECUTE

			$result = $this->exec_single_query($sql);
			return $this->connection->affected_rows;

			}

		else{

			if(!$this->exec_query($sql, $bind)){
				Corelog::add("SQLCore Query Failure - query_affect Function", "sqlcore");
				echo Janitor::build_json_alert('SQLCore Query Failure', 'Query Could Not Be Executed', 20, 2);
				}

			$rows = $this->stmt->affected_rows;
			return $rows;
			}

		}

	//------------------------------
	//FUNCTION USES EXEC_QUERY() FUNCTION
	//TO GET NUMBER OF SELECTED ROWS
	//------------------------------
	public function query_num($sql, $bind = NULL){

		if($bind==NULL){//SINGLE QUERY EXECUTE

			$result = $this->exec_single_query($sql);
			return $result->num_rows;

			}

		else{

			if(!$this->exec_query($sql, $bind)){
				Corelog::add("SQLCore Query Failure - query_affect Function", "sqlcore");
				echo Janitor::build_json_alert('SQLCore Query Failure', 'Query Could Not Be Executed', 30, 2);
				}

			$rows = $this->stmt->num_rows;
			return $rows;
			}

		}


	//------------------------------
	//FUNCTION USES EXEC_QUERY() FUNCTION
	//TO GET THE ID GENERATED FROM SQL
	//------------------------------
	public function query_id($sql, $bind = NULL){

		if($bind==NULL){//SINGLE QUERY EXECUTE

			$this->connection->exec_single_query($sql);
			return $this->connection->insert_id;

			}

		else{

			if(!$this->exec_query($sql, $bind)){
				Corelog::add("SQLCore Query Failure - query_affect Function", "sqlcore");
				echo Janitor::build_json_alert('SQLCore Query Failure', 'Query Could Not Be Executed', 40, 2);
				}

			$id = $this->stmt->insert_id;
			return $id;
			}

		}



	//-------------------------------
	//USES QUERY_AFFECTION FUNCTION
	//TO CHECK IF SQL AFFECTED WORKED
	//---------------------------------
	public function query_sql_impact($sql, $bind){

		if($this->query_affect($sql, $bind) >= 1){
			return true;
			}
		else{
			return false;
			}

		}


	/*-------------------------------------
	FUNCTIONS USING SQL THAT ARE MOST OFTEN USED
	REDUCES REWRITING SAME CODE
	--------------------------------------*/

	//RETURNS A STRUCTURE
	//A SELECT SQL
	public function structure_select($sql, $bind=NULL, $structure){

		if($sql == NULL || $structure==NULL){
			Corelog::add('Error 500: Vars Were Not Set - Backend Variables On Structure Select Were Not Set');
			return Janitor::build_json_alert('Vars Were Not Set', 'Backend Variables On Structure Select Were Not Set', 500, 2);
			exit;
			}

		$arr = $this->query_array($sql, $bind);

		if($arr==NULL){
			return '[]';
			exit;
			}

		return Janitor::build_json_list($arr, $structure);
   		exit;
		}

	//RETURNS ARRAY DATA
	//SELECT STATEMENT
	public function array_select($sql, $bind=NULL){

		if($sql == NULL){
			Corelog::add('Error 501: Vars Were Not Set - Backend Variables On Array Select Were Not Set');
			return Janitor::build_json_alert('Vars Were Not Set', 'Backend Variables On Array Select Were Not Set', 501, 2);
			exit;
			}

		$arr = $this->query_array($sql, $bind);

		return $arr;
		}

	//RETURNS NUMBER OF SELECT
	//A SELECT SQL
	public function number_select($sql, $bind=NULL){
		if($sql == NULL){
			Corelog::add('Error 502: Vars Were Not Set - Backend Variables On Number Select Were Not Set');
			return Janitor::build_json_alert('Vars Were Not Set', 'Backend Variables On Number Select Were Not Set', 502, 2);
			exit;
			}

		return $this->query_num($sql, $bind);
		}

	//RETURNS TRUE OR FALSE
	//AN IMPACT SQL EITHER UPDATE, ADD, OR DELETE
	public function impact_statement($sql, $bind=NULL){
		if($sql == NULL){
			Corelog::add('Error 503: Vars Were Not Set - Backend Variables On Impact Were Not Set');
			return false;
			exit;
			}

		if(!$this->query_sql_impact($sql, $bind)){
			Corelog::add('Error 504: Impact Record Failed - SQL Failed To Impact With SQL');
			return false;
			exit;
			}

		return true;
		}

	//RETURNS ID OF INSERTED SQL
	//FOR ADD SQLS
	public function insert_id($sql, $bind=NULL){
		if($sql == NULL){
			Corelog::add('Error 505: Vars Were Not Set - Backend Variables On Impact Were Not Set');
			return false;
			exit;
			}

		$id = $this->query_id($sql, $bind);

		if($id==''){
			Corelog::add('Error 506: Impact Record Failed - SQL Failed To Impact With SQL');
			return false;
			exit;
			}

		return $id;
		}


	/*---------------------------------------
	----------------------------------------*/



}

?>

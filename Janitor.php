<?php
require_once "SqlCore.php";
require_once "CoreLog.php";

class Janitor {
	//INIT FUNCTION
	function __construct(){}
	
    /**
     * Returns empty brckets if the records value is null
     * 
     * @param $records type array the data to be converted to json list
     * @param $structure array the value pairs use for the record elements
     */
	public static function build_json($records, $structure){
		if(!$records) {
			return '[]';
		}
		
		$tblData = array();
	   
		// for each record build the structure for converting to json
		foreach ($records as $record) {
			// loop through the structure
			array_push($tblData, self::convertRecordToJSONArrayStructure($record, $structure));
		}
		
		return json_encode($tblData);
		}
	
	/**
     * Returns empty brckets if the records value is null
     * 
     * @param $records type array the data to be converted to json list
     * @param $structure array the value pairs use for the record elements
     */
    public static function build_json_list($records, $structure) {
        if(!$records) {
            return '[]';
        }
        
        $tblData = array();
       
        // for each record build the structure for converting to json
        foreach ($records as $record) {
            // loop through the structure
            array_push($tblData, self::convertRecordToJSONArrayStructure($record, $structure));
        }
        
        return json_encode($tblData);
    }
	
	
    /**
     * Takes a single record of data and converts it to the supplied
     * JSON structure.
     * 
     * @param $record single object
     * @param $structure array json format for conversion
     */
	private static function convertRecordToJSONArrayStructure($record, $structure) {
		$construct = array();
		
		foreach ($structure as $key => $val) {
			if (is_array($val)) {
				if(is_string($val[0])){

					//SKIP RECORD IF NO SUB ARRAY
					if(!isset($record[$val[0]])){continue;}
					
					//BUILD SUB JSON
					$subArray = array();
					foreach($record[$val[0]] as $subRecord){
						
						array_push($subArray, self::convertRecordToJSONArrayStructure($subRecord, $val[1]));
						}
					$construct[$key] = $subArray;
					}
				else{
					$construct[$key] = self::convertRecordToJSONArrayStructure($record, $val);
					}
			}
			
			else {
				// The key in the structure is set to the matching value in the record
				// the structure value should equal the sql column in the data (or alias)      
				$construct[$key] = $record[$val];
			}
		}
		
		return $construct;
	}

	/*
	 *
	 *
	 * Function to take a MySQL array and add make it clean
	 * Needs to pass in the array
	 *
	 *
	 */
	public static function build_clean_array($array, $structure){
		
		$cleanArr = array();
		
		if(count($array)==0){
			return $cleanArr;
			}
		else{
			//LOOP THROUGHT THE ARRAY SENT
			while($array){
				foreach($array as $key => $value){
					echo $value;
					//array_push($cleanArr, self::buildFromStructureArray());
					}
				}
			}
		}
	/**
     * Sanitizes an array of parameters.
     * @param type $arr
     */
    public static function sanitizeArray(&$arr) {
        foreach ($arr as $key => $val) {
            if (is_array($val)) {
                self::sanitizeArray($arr[$key]);
            }
            else {
                $arr[$key] = self::sanitizeInput($val, ENT_QUOTES);
            }
        }
    }

    /**
     * Applies trim, stripslasses and html special characters to onput.
     */
    public static function sanitizeInput($data) {
        $data = trim($data);
        $data = stripslashes($data);
        
        // First convert br tags to new lines before encoding
        $breaks = array("<br />","<br>","<br/>");  
        $data = str_ireplace($breaks, "\r\n", $data);  
        
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
      return $data;
    }
    
    /**
     * Builds a json msg.
     * 
     * MsgID = 1 for normal alert
     * MsgID = 2 for error alert
     * MsgID = 3 for system alert
     */
    public static function build_json_alert($msg, $msgTitle, $msgID, $typeID = 1) {
      $alert = array('alert' => array(   'msg' => $msg,
                                         'msgTitle' => $msgTitle,
                                         'msgTypeID' => $typeID,
                                         'msgID' => $msgID));
        
      return json_encode($alert);
    }	
	
	
	//ENCRYPTS DATA
	public static function encrypt_data($encrypt, $key){
		$encrypt = serialize($encrypt);
		$iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC), MCRYPT_DEV_URANDOM);
		$key = pack('H*', $key);
		$mac = hash_hmac('sha256', $encrypt, substr(bin2hex($key), -32));
		$passcrypt = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $encrypt.$mac, MCRYPT_MODE_CBC, $iv);
		$encoded = base64_encode($passcrypt).'|'.base64_encode($iv);
		return $encoded;
		}
		
	//DECYRPTS DATA
	public static function decrypt_data($decrypt, $key){
		$decrypt = explode('|', $decrypt.'|');
		$decoded = base64_decode($decrypt[0]);
		$iv = base64_decode($decrypt[1]);
		if(strlen($iv)!==mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC)){ return false; }
		$key = pack('H*', $key);
		$decrypted = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $decoded, MCRYPT_MODE_CBC, $iv));
		$mac = substr($decrypted, -64);
		$decrypted = substr($decrypted, 0, -64);
		$calcmac = hash_hmac('sha256', $decrypted, substr(bin2hex($key), -32));
		if($calcmac!==$mac){ return false; }
		$decrypted = unserialize($decrypted);
		return $decrypted;
		}
	
	
	//GENERATES RANDOM KEY
	public static function generate_key($len){
		$key = '';
		for($i = 0; $i < $len; $i++){
			$key .= chr(mt_rand(97, 122));
			}
		return $key;
		}
	
	//TESTS TO SEE IF VARS ARE SET
	public static function check_vars($vars){
		//LOOP THRU
		foreach($vars as $var){
			if(!isset($var) || $var==NULL){
				Corelog::add('Janitor Error: No Variables Were Checked');
				return false;
				}
			}
		return true;
		}



}
?>
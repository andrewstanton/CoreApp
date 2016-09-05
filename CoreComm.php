<?php

require_once('CoreDebug.php');

/**
 * Used as a form of standard communication between different classes of
 * the CorePrint library.
 */
class CoreComm {

    /**
     * Message of the communication.
     * Should house a string version of the message.
     * Suitable for output to a user.
     * 
     * @var string
     */
    public $TEXT = '';
    
    
    /**
     * The type of message
     * 
     * @var type 
     */
    public $TYPE = '';
    
    
    /**
     * The result of the communication
     * stored as a boolean.  Primarily used when the 
     * CoreComm needs to indicate success or failure.
     * 
     * @var type 
     */
    public $RESULT = false;
    
    
    /**
     * Used when a code is associated with the communication.
     * Most often used for logging.
     * 
     * @var type 
     */
    public $CODE = 0;
    
    
    /**
     * Stores the provided parameters.
     * Used by the output log functionality.
     * 
     * @var array 
     */
    private $PARAMS = array();
    
    
    /**
     * If a LOG property is contained in the 
     * params array then upon generation of the communication
     * the comunication will be logged.
     * This property should contain the name of the log, not the entry contents.
     * 
     * @var type 
     */
    private $LOG = '';
    
    /**
     * Accepts a keyed array;
     * Available keys:
     * TEXT, TYPE, RESULT, CODE, LOG
     * Which are paired with the associated properties.
     * 
     * @param array $param
     */
    function __construct($params) {
        $this->PARAMS = $params;
        if(array_key_exists('TEXT', $params)) { $this->TEXT = $params['TEXT']; }
        if(array_key_exists('TYPE', $params)) { $this->TYPE = $params['TYPE']; }
        if(array_key_exists('RESULT', $params)) { $this->RESULT = $params['RESULT']; }
        if(array_key_exists('CODE', $params)) { $this->CODE = $params['CODE']; }
        if(array_key_exists('LOG', $params)) { 
            $this->LOG = $params['LOG']; 
            $this->generateLog();   
        }
    }
    
    
    
    /**
     * Outputs a log of the CoreComm details.
     * This is not autoamtically logged but provides an easy entry of this logs 
     * content.
     * 
     * @return array
     */
    public function outputLog() {
        return print_r($this->PARAMS, true);
    }
    
    
    
    /**
     * Generates a log of the communication to the deisgnated log
     */
    private function generateLog() {
        CoreLog::add(print_r($this->PARAMS, true), $this->LOG);
    }
}

?>

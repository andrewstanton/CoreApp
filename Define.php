<?php
//This class defines variables for scripts
//Mainly used for Config File passthru

//DIRECTORY_SEPARATOR
defined('DS') ? NULL : define('DS', DIRECTORY_SEPARATOR);
//Path To Config File
defined('CP') ? NULL : define('CP', dirname(dirname(__FILE__)).DS);

?>

<?php

require_once('../Config.php');
require_once('CoreDebug.php');

class CoreLog {

    /**
     * Toggle logging of normal events.
     *
     * @var boolean
     */
    private static $enabled = true;

    /**
     * Toggle debugging- these are events that shouldn't happen.
     * If they do they indicate a need to debug the problem.
     * They are thrown in production.
     *
     * @var boolean
     */
    private static $debugEnabled = true;

    /**
     * General log
     *
     * @param string $msg
     * @param string $logName
     * @return
     */
    public static function add($msg, $logName = 'core_events') {
        if (self::$enabled == false) {
            return;
        }

        self::createLog($msg, $logName);
    }

    /**
     * Events that should never happen and may indicate an error.
     *
     * @param string $msg
     * @param string $logName
     * @return
     */
    public static function debug($msg, $logName = 'debug_log') {
        if (self::$debugEnabled == false) {
            return;
        }

        self::createLog($msg, $logName);
    }

    /**
     * Automated tasks output log.
     *
     * @param string $msg
     * @param string $logName
     * @return
     */
    public static function cron($msg, $logName = 'cron_log') {
        self::createLog($msg, $logName);
    }

    /**
     * Creates a trace of an error in the trace log.
     * Used for testing and debugging.
     *
     * @return
     */
    public static function trace(){
        self::createLog(CoreDebug::backtrace(debug_backtrace()) , 'trace_log');
    }


    /**
     * Authorization protocol logging
     *
     * @param string $msg
     * @param string $logName
     * @return
     */
    public static function auth($msg, $logName = 'authorization_log') {
        self::createLog($msg, $logName);
    }

    /**
     * Creates a log entry in the specified log.
     *
     * @param string $msg
     * @param string $logName
     * @return
     */
    private static function createLog($msg, $logName = 'debug_log') {

        $file = Config::$LOG_PATH . $logName . ' ' . date('Y-m-d') . '.log';

        $logMsg = date('Y-m-d H:i:s') . ";  ";
        $logMsg .= $msg . "\n";
        file_put_contents($file, $logMsg, FILE_APPEND);
    }
}

?>

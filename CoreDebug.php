<?php

/**
 * Copyright 2014 Flash Concepts LLC
 */

/**
 * Useful methods for debugging.
 *
 * @author Jonathan Rios
 */
class CoreDebug {
    
    /**
     * Follows a backtrace the defined amount of iterations
     * 
     * @param array $traces
     * @param int $iterations
     * @return string
     */
    public static function backtrace($traces, $iterations = 3) {
        $msg = 'trace: ' . "\n";
        $step = 0;
        
        foreach($traces as $trace) {
            $step = $step + 1;
            
            if($step > $iterations) {
                break;
            }
            
            $msg .= $trace['file'] . ' on line ' . $trace['line'] . "\n";
        }
        
        return $msg;
    }    
}

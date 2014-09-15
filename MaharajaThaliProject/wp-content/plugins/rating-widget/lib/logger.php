<?php
    // Exit if accessed directly.
    if (!defined('ABSPATH')) exit;
    
    class RWLogger
    {
        static $_on = false;
        static $_log = array();
        
        public static function PowerOn()
        {
            self::$_on = true;
        }

        public static function PowerOff()
        {
            self::$_on = false;
        }
        
        public static function IsOn()
        {
            return self::$_on;
        }
        
        public static function Log($pId, $pMessage = '')
        {
            if (false === self::$_on)
                return;

            $msg = date(WP_RW__DEFAULT_DATE_FORMAT . " " . WP_RW__DEFAULT_TIME_FORMAT . ":u") . "  -  {$pId}:  {$pMessage}";
            
            self::$_log[] = $msg;
            
            if (WP_RW__LOG_DUMP)
                echo $msg . '<br>';
        }
        
        public static function LogEnterence($pId, $pParams = null, $pLogParams = false)
        {
            if (false === self::$_on)
                return;
            
            $msg = date(WP_RW__DEFAULT_DATE_FORMAT . " " . WP_RW__DEFAULT_TIME_FORMAT . ":u") . "  -  {$pId} (Enterence)" . 
                            (($pLogParams) ? ":  " . var_export($pParams, true) : "");
                            
            self::$_log[] = $msg;
            
            if (WP_RW__LOG_DUMP)
                echo $msg . '<br>';
        }
        
        public static function LogDeparture($pId, $pRet = "")
        {
            if (false === self::$_on)
                return;
            
            $msg = date(WP_RW__DEFAULT_DATE_FORMAT . " " . WP_RW__DEFAULT_TIME_FORMAT . ":u") . "  -  {$pId} (Departure):  " . var_export($pRet, true);
            
            self::$_log[] = $msg;
            
            if (WP_RW__LOG_DUMP)
                echo $msg . '<br>';
        }
        
        public static function Output($pPadding)
        {
            foreach (self::$_log as $log)
                echo "{$pPadding}{$log}\n";
        }
    }
?>

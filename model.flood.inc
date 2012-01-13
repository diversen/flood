<?php

/**
 * @package flood
 */

/**
 * simple class for insureing only a specified amount of posts
 * call the check method and a database table will be updated 
 * @package flood
 */

class flood {
    
    public static $table = 'flood';
    public static $log = null;
    
    /**
     * method for checking 
     */
    public static function check () {
        
        if (config::getMainIni('debug')) {
            self::$log = true;
        }
        
        $db = new db();
        $user_id = session::getUserId();
        $search = array ('user_id' =>  $user_id);
        $post_max = config::getModuleIni('flood_post_max');
        $row = $db->selectOne(self::$table, null, $search);
        
        // no user in db
        $values = array();
        if (empty($row)) {
            // first row
            $values['user_id'] = session::getUserId();
            $values['posts'] = 1;
            $db->insert(self::$table, $values);
        
        // found a row    
        } else {
            // Exceed max posts
            if ($row['posts'] >= $post_max) {
                // And exceed timelimit. Redirect to error
                if (self::exceedsInterval($row['updated'])) {
                    if (self::$log) cos_error_log ('Exceeds time');
                    http::locationHeader('/flood/index');
                } else {
                    // Out of timelimit: reset posts
                    $values['updated'] = date('Y-m-d H:i:s');
                    $values['posts'] = 1;
                    if (self::$log) {
                        cos_error_log('update db: with values');
                        cos_error_log(var_export ($values, true));
                    }
                    $db->update(self::$table, $values, $row['id']);
                }               
            } else {
                $values['posts'] = $row['posts']++;               
                $values['updated'] = date('Y-m-d H:i:s');
                
                if (self::$log) {
                    cos_error_log('update db: with values');
                    cos_error_log(var_export ($values, true));
                }
                
                $db->update(self::$table, $row, $row['id']);
            }
        }
    }
    
    /**
     * method for checking if interval has been exceeded
     * @param string $updated sql timestamp (Y-m-d H:i:s)
     * @return boolean $res true if exceeds else false
     */
    public static function exceedsInterval ($updated) {
        $interval = config::getModuleIni('flood_post_interval');
        $max = strtotime($updated) + $interval; 

        if ( $max > time() ) {
            return true;
        }
        return false;
    }
    
    public static function event ($args = null) {
        self::check();
    }
    
    public static function getUserRow () {
        $db = new db();
        $search = array ('user_id' =>  session::getUserId());
        $row = $db->selectOne(self::$table, null, $search);
        return $row;
    }
}

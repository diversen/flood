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
    public static function events ($args) {

        if (config::getMainIni('debug')) {
            self::$log = true;
        }
        
        // check if it is something we are configured to do
        $ini = self::getIniSection($args);
        if (empty($ini)) return;
        
        $db = new db();
        $row = self::getUserRow($args['action']);
        
        
        $post_max = $ini['post_max'];
        $values = array();
        $res = null;
        if (empty($row)) {
            // first row
            $values['user_id'] = session::getUserId();
            $values['reference'] = $args['action'];
            $values['posts'] = 1;
            $res = $db->insert(self::$table, $values); 
        } else {
            // Exceed max posts
            if ($row['posts'] >= $post_max) {
                // And exceed timelimit. Redirect to error
                if (self::exceedsInterval($args, $row['updated'])) {
                    if (self::$log) cos_error_log ('Exceeds time');
                    http::locationHeader("/flood/index?action=$args[action]");
                } else {
                    // Out of timelimit: reset posts
                    $values['updated'] = date('Y-m-d H:i:s');
                    $values['posts'] = 1;
                    if (self::$log) {
                        cos_error_log('update db: with values');
                        cos_error_log(var_export ($values, true));
                    }
                    $res = $db->update(self::$table, $values, $row['id']);
                    
                }               
            } else {
                $values['posts'] = $row['posts']++;               
                $values['updated'] = date('Y-m-d H:i:s');
                
                if (self::$log) {
                    cos_error_log('update db: with values');
                    cos_error_log(var_export ($values, true));
                }
                
                $res = $db->update(self::$table, $row, $row['id']);
            }
        }
        
        return $res;
    }
    
    /**
     * gets a ini section, e.g. for comment_create
     * @param type $args
     * @return type 
     */
    public static function getIniSection ($args) {
        static $sections = array();
        
        $action = $args['action'];
        
        // check if the static sections[$action] is set
        if (!isset($sections[$action])) {
            $section = "flood_$action";
            $ini = config::getModuleIni($section);
            $sections[$action] = $ini;
        }
        return $sections[$action];
    }
    
    /**
     * method for checking if interval has been exceeded
     * @param string $updated sql timestamp (Y-m-d H:i:s)
     * @return boolean $res true if exceeds else false
     */
    public static function exceedsInterval ($args, $updated) {
        $ini = self::getIniSection($args);
        $interval = $ini['post_interval'];
        $max = strtotime($updated) + $interval; 

        if ( $max > time() ) {
            return true;
        }
        return false;
    }
    

    /**
     * return a user row from reference
     * @param string $reference e.g. comment_create
     * @return array $row
     */
    public static function getUserRow ($reference) {
        $db = new db();
        $search = array (
            'user_id' =>  session::getUserId(), 
            'reference' => $reference);
        $row = $db->selectOne(self::$table, null, $search);
        return $row;
    }
}

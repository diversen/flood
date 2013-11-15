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
     * method for checking if something if being flooded,
     */
    public static function events ($args) {

        if (config::getMainIni('debug')) {
            self::$log = true;
        }
                
        // check if it is something we are configured to do
        $ini = self::getIniSection($args);
        if (empty($ini)) { 
            return;
        }

        $row = self::getUserRow($args['action']);
        
        $post_max = $ini['post_max'];
        $res = null;
        
        if (empty($row)) {
            $res = self::insertFirstRow($args['action']);
        } else {
            
            // Exceed max posts            
            if ($row['posts'] >= $post_max) {
                // And exceed timelimit. Redirect to error
                if (self::exceedsInterval($args, $row['updated'])) {
                    self::redirect($args['action']);
                } else {
                    $res = self::resetPosts($row);    
                }
                
            // increment posts
            } else {
                $res = self::incrementRow($row);
            }
        }
        return $res;
    }
    
    /**
     * increment a user row
     * @param array $row
     * @return boolean $res result from db
     */
    public static function incrementRow($row) {
        $db = new db();
        $row['posts']++;
        $row['updated'] = date('Y-m-d H:i:s');
        if (self::$log) {
            log::debug('update db: with values');
            log::debug($row);
        }
        return $db->update(self::$table, $row, $row['id']);
    }

    /**
     * insert first row
     * @param array $row
     * @return boolean $res result from db
     */
    public static function insertFirstRow($action) {
        $db = new db();
        $values = array ();
        $values['user_id'] = session::getUserId();
        $values['reference'] = $action;
        $values['posts'] = 1;
        return $db->insert(self::$table, $values);
    }
    
    /**
     * redirect to flood page if time interval has been exceeded
     * @param string $redirect (containing the action in order to fetch info 
     *                          about what went wrong. And when user can perform 
     *                          action again)
     */
    public static function redirect($redirect) {
        if (self::$log) {
            log::debug('Flood: Exceeds time');
        }
        http::locationHeader("/flood/index?action=$redirect");
    }
    
    /**
     * reset posts to 1
     * @param type $row
     * @return boolean $res result from db
     */
    public static function resetPosts($row) {
        // Out of timelimit: reset posts
        $db = new db();
        $values = array ();
        $values['updated'] = date('Y-m-d H:i:s');
        $values['posts'] = 1;
        if (self::$log) {
            log::error('update db: with values');
            log::error(var_export($values, true));
        }
        return $db->update(self::$table, $values, $row['id']);
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

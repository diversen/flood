<?php

use diversen\conf;
use diversen\date;
use diversen\db;
use diversen\html;
use diversen\http;
use diversen\lang;
use diversen\log;
use diversen\session;
use diversen\time;

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
    
    public function indexAction () {
        $action = htmlspecialchars($_GET['action']);
        echo flood::getFloodedMessage($action);
    }
    
    /**
     * method for checking if something if being flooded with events,
     * if is performed on action, e.g. 'comment_create'
     * then: whenever a comment is created we examine the ini settings for 
     * comment_create which will look like this:
     *  
     * ; flood.ini from default profile
     * ; max posts
     * flood_comment_create[post_max] = "3"
     * ; in secs 
     * flood_comment_create[post_interval] = "86400"
     * 
     * Then with a user_id and a timestamp we know if a user has posted 
     * to 
     * 
     */
    public static function events ($args) {
        $res = self::performFloodCheck($args['action']);
        if (!$res) {
            self::redirect($args['action']);
        }
    }
    
    /**
     * performs flood check based on action and increments flood table, 
     * see above method for explanation
     * 
     * @param string $action
     * @return boolean $res true on success else failure
     */
    public static function performFloodCheck($action) {

        if (conf::getMainIni('debug')) {
            self::$log = true;
        }
                
        // check if it is something we are configured to do
        $ini = self::getIniSection($action);
        if (empty($ini)) { 
            return true;
        }

        $row = self::getUserRow($action);
        
        $post_max = $ini['post_max'];

        
        if (empty($row)) {
            self::insertFirstRow($action);
            return true;
        } else {
            
            // Exceed max posts            
            if ($row['posts'] >= $post_max) {
                if (self::exceedsInterval($action, $row['updated'])) {             
                    return false;
                } else {
                    self::resetPosts($row);
                    return true;
                }
                
            // increment posts
            } else {
                self::incrementRow($row);
                return true;
            }
        }
    }
    
    /**
     * checks if specified action is allowed to be performed
     * @param string $action
     * @return boolean $res true if allowed else false
     */
    public static function allowAction($action) {
        // check if it is something we are configured to do
        $ini = self::getIniSection($action);
        if (empty($ini)) {
            return true;
        }

        $row = self::getUserRow($action);   
        $post_max = $ini['post_max'];

        if (empty($row)) {
            
            return true;
        } else {
            
            // Exceed max posts            
            if ($row['posts'] >= $post_max) {
                
                // And exceed timelimit. Redirect to error
                if (self::exceedsInterval($action, $row['updated'])) {
                    return false;
                } else {
                    return true;  
                }
                
            // increment posts
            } else {
                return true;
            }
        }
    }
    
    /**
     * increment a user row
     * @param array $row
     * @return boolean $res result from db
     */
    public static function incrementRow($row) {
        $db = new db();
        $row['posts']++;
        //$row['updated'] = date('Y-m-d H:i:s');
        if (self::$log) {
            //log::debug('update db: with values');
            //log::debug($row);
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
    public static function redirect($action) {
        if (self::$log) {
            log::debug('Flood: Exceeds time');
        }
        http::locationHeader("/flood/index?action=$action");
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
        $values['posts'] = 0;
        return $db->update(self::$table, $values, $row['id']);
    }

    /**
     * gets a ini section, e.g. for comment_create
     * @param type $args
     * @return type 
     */
    public static function getIniSection ($action) {
        static $sections = array();

        // check if the static sections[$action] is set
        if (!isset($sections[$action])) {
            $section = "flood_$action";
            $ini = conf::getModuleIni($section);
            $sections[$action] = $ini;
        }
        return $sections[$action];
    }
    
    /**
     * method for checking if interval has been exceeded
     * @param string $updated sql timestamp (Y-m-d H:i:s)
     * @return boolean $res true if exceeds else false
     */
    public static function exceedsInterval ($action, $updated) {
        $ini = self::getIniSection($action);
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
    
    public static function getFloodedMessage($action) {
        
        $row = flood::getUserRow($action);
        if (empty($row)) {
            $row = array ();
            $row['updated'] = date::getDateNow();
        }
        
        $ini = flood::getIniSection($action);
        $max_posts = $ini['post_max'];

        $interval = $ini['post_interval'];
        $post_next = strtotime($row['updated']) + $interval;

        $str = '';
        $time_to_next_post = $post_next - time();
        if ($time_to_next_post < 0) {
            $str.= html::getHeadline(lang::translate('You can post again'));
            $str.= lang::translate('You should be able to post');
            return $str;
        }

        $res = time::getSecsDivided($time_to_next_post);
        $str.= html::getHeadline(lang::translate('Exceed time limit title'));
        $res_int = time::getSecsDivided($interval);

        $str.= lang::translate('Max Amount of posts is') . ' ';
        $str.= $max_posts;
        $str.= lang::translate('per') . ' ';

        $str.= $res_int['days'] . ' ';
        $str.= lang::translate('days and') . ' ';
        $str.= $res_int['hours'] . ' ';
        $str.= lang::translate(' hours and') . ' ';
        $str.= $res_int['minutes'] . ' ';
        $str.= lang::translate('minutes and') . ' ';
        $str.= $res_int['seconds'] . ' ';
        $str.= lang::translate('seconds') . ' ';

        $str.= "<br />\n";

        $str.= lang::translate('Your post counter will be reset in') . ' ';
        $str.= $res['hours'] . ' ';
        $str.= lang::translate('hours and') . ' ';
        $str.= $res['minutes'] . ' ';
        $str.= lang::translate('minutes and') . ' ';
        $str.= $res['seconds'] . ' ';
        $str.= lang::translate('seconds') . ' ';
        return $str;
    }
    
    public static function getFloodedMessageSimple($action) {
        $row = flood::getUserRow($action);
        if (empty($row)) {
            $row = array ();
            $row['updated'] = date::getDateNow();
        }
        
        $ini = flood::getIniSection($action);
        $max_posts = $ini['post_max'];

        $interval = $ini['post_interval'];
        $post_next = strtotime($row['updated']) + $interval;

        $str = '';
        $time_to_next_post = $post_next - time();

        $res = time::getSecsDivided($time_to_next_post);
        $res_int = time::getSecsDivided($interval);

        $str.= lang::translate('Max Amount of posts is') . ' ';
        $str.= $max_posts . ' ';
        $str.= lang::translate('per') . ' ';

        $str.= $res_int['days'] . ' ';
        $str.= lang::translate('days and') . ' ';
        $str.= $res_int['hours'] . ' ';
        $str.= lang::translate('hours and') . ' ';
        $str.= $res_int['minutes'] . ' ';
        $str.= lang::translate('minutes and') . ' ';
        $str.= $res_int['seconds'] . ' ';
        $str.= lang::translate('seconds.') . ' ';

        $str.= "<br />\n";

        $str.= lang::translate('Your post counter will be reset in') . ' ';
        $str.= $res['hours'] . ' ';
        $str.= lang::translate('hours and') . ' ';
        $str.= $res['minutes'] . ' ';
        $str.= lang::translate('minutes and') . ' ';
        $str.= $res['seconds'] . ' ';
        $str.= lang::translate('seconds') . ' ';
        return $str;
    }
}

class flood_module extends flood {}

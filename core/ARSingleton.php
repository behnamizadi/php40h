<?php
class ARSingleton {
    private static $instance;

    private function __construct() {
    }

    public function __clone() {
        if (isset(self::$instance))
            return self::$instance;
        return FALSE;
    }

    public static function get() {
        if (!isset(self::$instance)) {
            self::$instance = new ARSingleton();
        }
        return self::$instance;
    }

    public static function setFields($fields,$values) {
        foreach ($fields as $field) {
            if(isset($values->$field))
            {
                self::get() -> $field = $values->$field;
            }
        }
    }
    
    public static function f($fields)
    {
        $x = array();
        foreach ($fields as $field) {
            if(isset(self::get() -> $field)) 
                $x[] = self::get() -> $field;  
        }
        return $x;
    }
    
    public function ff($id)
    {
        var_dump(self::get() -> $id);
    }

}

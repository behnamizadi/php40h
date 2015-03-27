<?php 
Class CView {
    /*
     * @the registry
     * @access private
    */
    //private $registry;
    /*
     * @Variables array
     * @access private
    */
    private static $vars = array();
    /**
     *
     * @set undefined vars
     *
     * @param string $index
     *
     * @param mixed $value
     *
     * @return void
     *
     */
    public function __set($index, $value) {
        self::$vars[$index] = $value;
    }
    public static function getVar($var) {
        if (isset(self::$vars[$var])) {
            return self::$vars[$var];
        }
        return FALSE;
    }
    public function run($name = '', $terminate = TRUE) {
        if (empty($name)) {
            $name = PHP40::get()->view['messageView'];
        }
        if (isset(self::$vars['layout'])) {
            if (empty(self::$vars['layout'])) $path = APP_ROOT . 'views/' . $name . '.php';
            $path = APP_ROOT . 'views/layouts/' . self::$vars['layout'] . '.php';
            if (file_exists($path) == FALSE) {
                $path = APP_ROOT . 'views/' . $name . '.php';
            } else {
                $view = APP_ROOT . 'views/' . $name . '.php';
            }
        } elseif (isset(PHP40::get()->view['layout'])) {
            $path = APP_ROOT . 'views/layouts/' . PHP40::get()->view['layout'] . '.php';
            if (file_exists($path) == FALSE) {
                $path = APP_ROOT . 'views/' . $name . '.php';
            } else {
                $view = APP_ROOT . 'views/' . $name . '.php';
            }
        } else {
            $path = APP_ROOT . 'views/' . $name . '.php';
        }
        // Load variables
        $use = '';
        foreach (self::$vars as $key => $value) {
            $$key = $value;
        }
        include ($path);
        if ($terminate === TRUE) exit();
    }
}
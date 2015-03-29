<?php
class CRoute {
    private $cName;
    private $action;
    private $cPath;
    private $defaultAction;
    private $controller;
    /*public function __construct()
    {
    $this->defaultAction = PHP40::get()->route['defaultAction'];
    $defaultController = PHP40::get()->NOW;
    $this->cName = (CUrl::segment(1) === FALSE) ? PHP40::get()->NOW : CUrl::segment(1);
    $this->controller = $this->cName.'Controller';
    $this->action = CUrl::segment(2);
    $cPath = APP_ROOT.'controllers/'.$this->controller.'.php';
    }*/
    public function run() {
        $controllerName = PHP40::get()->NOW . 'Controller';
        $cPath = APP_ROOT . 'controllers/' . $controllerName . '.php';
        if (file_exists($cPath)) {
            require_once ($cPath);
            $controller = new $controllerName;
            $urlAction = CUrl::segment(2);
            if (empty($urlAction)) {
                if (!empty($controller->defaultAction)) {
                    $action = $controller->defaultAction;
                } else {
                    $defaultAction = PHP40::get()->route['defaultAction'];
                    if (method_exists($controller, $defaultAction)) {
                        $action = $defaultAction;
                    } else {
                        $error = '404';
                    }
                }
            } elseif (!method_exists($controller, $urlAction)) {
                $error = '404';
            } else {
                $action = $urlAction;
            }
            $aclFile = APP_ROOT . 'config/acl/' . PHP40::get()->NOW . '.php';
            if (file_exists($aclFile) && !isset($error)) {
                $acl = new CAcl($action);
                if ($acl->run() === FALSE) {
                    if (!empty($acl->redirect)) {
                        $error = $acl->redirect;
                    } else {
                        $error = '401';
                    }
                }
            }
        } else {
            $error = '404';
        }
        if (isset($error)) {
            //echo $error;
            CUrl::redirect($error);
        } else {
            $controller->{$action}();
        }
    }
}
?>
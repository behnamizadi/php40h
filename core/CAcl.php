<?php
class CAcl extends CGeneral {
    public $a;
    private $b;
    public function __construct($c = '') {
        $this->config = $this->getConfig('acl', $c);
    }
    public function run() {
        if (!is_array($this->config)) {
            return TRUE;
        }
        $d = FALSE;
        $d = $this->isAllowed($this->config[0], $this->config[1]);
        if ($d === FALSE && isset($this->config[2])) {
            $this->redirect = $this->config[2];
        }
        return $d;
    }
    private function isAllowed($access_type, $roles) {
        switch ($roles) {
            case '*':
                if ($access_type == 'allow') {
                    return TRUE;
                }
                return FALSE;
            break;
            case '?':
                if ($access_type == 'allow') {
                    if (!isset(PHP40::get()->user)) return TRUE;
                    return FALSE;
                } else {
                    if (isset(PHP40::get()->user)) return TRUE;
                    return FALSE;
                }
                break;
            case '@':
                if ($access_type == 'allow') {
                    if (isset(PHP40::get()->user)) return TRUE;
                    return FALSE;
                } else {
                    if (!isset(PHP40::get()->user)) return TRUE;
                    return FALSE;
                }
                break;
            default:
                if (isset(PHP40::get()->role)) {
                    $all_roles = explode(',', $roles);
                    if (in_array(PHP40::get()->role,$all_roles)){
                        return TRUE;
                    }
                }
                return FALSE;
                break;
            }
        }
    }
?>
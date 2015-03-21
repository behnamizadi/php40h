<?php
class CAuth {
    public $config;
    private $hash = 'sha1';
    private $message;
    private $cookieName = 'auto';
    private $username;
    private $password; //hashed password
    private $role = '';
    public $user;
    const USERNAME_ERROR = '   .';
    const PASSWORD_ERROR = '   .';
    const USERNAME_EMPTY = '   .';
    const PASSWORD_EMPTY = '   .';
    const MIXED = '      .';
    public function __construct($type = 'default') {
        $typeFromCookie = $this->getCookieData(TRUE);
        if ($typeFromCookie !== FALSE) {
            $type = $typeFromCookie;
        }
        if (isset(PHP40::get()->auth[$type])) {
            $this->config = PHP40::get()->auth[$type];
        }
        $this->config['type'] = $type;
        if ($this->check() === FALSE && PHP40::get()->debug === TRUE) exit("Authentication is not configured properly in APP_ROOT/main/config.php file.");
    }
    /*
     * return: array(containing 'username','user','role'(if set),'loginTime')
    */
    public function authorize($username = '', $password = '') {
        $sData = $this->getSessionData();
        if (is_array($sData)) {
            $this->user = $sData['user'];
            return $sData;
        }
        $cData = $this->getCookieData();
        if (is_array($cData)) {
            $result = $this->getFromDb($cData['username'], $cData['password'], FALSE);
            if (is_array($result)) {
                if (isset($this->config['role'])) {
                    $result['role'] = $this->config['role'];
                }
                $result['loginTime'] = $cData['LoginTime'];
                $this->setSessionData($result);
                $this->user = $result['user'];
                return $result;
            } else {
                $this->removeCookie();
                return FALSE;
            }
        } elseif (!empty($username) && !empty($password)) {
            $result = $this->getFromDb($username, $password);
            if (is_array($result)) {
                if (isset($this->config['role']) && empty($result['role'])) //empty($result['role']: check to see if role isn't set to be get from db
                {
                    $result['role'] = $this->config['role'];
                }
                if ($this->config['cookie']['use'] == TRUE) {
                    if (!empty($this->config['cookie']['field'])) {
                        if (!empty($_POST[$this->config['cookie']['field']])) {
                            $toBeSet = array('username' => $result['username'], 'password' => $result['password']);
                            if (isset($result['role'])) $toBeSet['role'] = $result['role'];
                            $this->setCookieData($toBeSet);
                        }
                    } else {
                        $this->setCookieData(array('username' => $result['username'], 'password' => $result['password'], 'role' => $result['role']));
                    }
                }
                unset($result['password']);
                $result['loginTime'] = time();
                $this->setSessionData($result);
                $this->user = $result['user'];
                return $result;
            } else {
                $this->message = self::MIXED;
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }
    public function getMessage() {
        return $this->message;
    }
    /*
     * return: user in authintication part of config from table
    */
    private function getFromDb($username, $password, $hash = TRUE) {
        $user = $this->getUser();
        if ($hash === TRUE) {
            $hashFunc = $this->hashFunction();
            $password = $hashFunc($password);
        }
        $db = new CDatabase;
        $username = $db->escape($username);
        $sql = 'SELECT ' . $user;
        if (!empty($this->config['database']['role'])) {
            $sql.= ',' . $this->config['database']['role'];
        }
        $sql.= ' FROM ' . $this->config['database']['table'] . ' WHERE ' . $this->config['database']['username'] . '=\'' . $username . '\' AND ' . $this->config['database']['password'] . '=\'' . $password . '\'';
        if (isset($this->config['database']['condition'])) {
            if (is_array($this->config['database']['condition'])) {
                $sql.= ' AND ' . $db->getAnd($this->config['database']['condition']);
            } elseif (is_string($this->config['database']['condition'])) {
                $sql.= ' ' . $this->config['database']['condition'];
            }
        }
        $result = $db->queryOne($sql);
        if ($result !== FALSE) {
            $return = array('user' => $result->$user, 'username' => $username,);
            if ($hash === TRUE) {
                $return['password'] = $password;
            }
            if (!empty($this->config['database']['role'])) {
                $role = $this->config['database']['role'];
                $return['role'] = $result->$role;
            }
            return $return;
        }
        return FALSE;
    }
    private function check() {
        if (isset($this->config['database']['table']) && isset($this->config['database']['username']) && isset($this->config['database']['password'])) {
            return TRUE;
        }
        return FALSE;
    }
    private function getUser() {
        if (!empty($this->config['database']['user'])) {
            return $this->config['database']['user'];
        } elseif (!empty($this->config['database']['username'])) {
            return $this->config['database']['username'];
        }
        return FALSE;
    }
    private function hashFunction() {
        return empty($this->config['passwordCoding']) ? $this->hash : $this->config['passwordCoding'];
    }
    private function setSessionData($data) {
        if (is_array($data)) {
            $session = new CSession;
            $session->set($data);
        }
    }
    private function getSessionData() {
        $result = '';
        $session = new CSession;
        $result['username'] = $session->get('username');
        $result['user'] = $session->get('user');
        if ($result['username'] !== FALSE && $result['user'] !== FALSE) {
            $result['role'] = $session->get('role');
            $result['loginTime'] = $session->get('loginTime');
            return $result;
        }
        return FALSE;
    }
    private function setCookieData($data) {
        $flag = FALSE;
        $name = time();
        if (isset($data['username'])) {
            $flag = TRUE;
            $cookie = $data['username'];
            $cookie.= $name . $data['password'];
            $cookie.= $name . $this->config['type'];
            if (!empty($data['role'])) {
                $cookie.= $name . $data['role'];
            }
        }
        if ($flag === TRUE) {
            $time = time() + 60 * 60 * 24 * 30;
            setcookie($this->cookieName, $name, $time, '/');
            setcookie($name, $cookie, $time, '/');
        }
    }
    //type: if to get just type
    private function getCookieData($type = FALSE) {
        if (isset($_COOKIE[$this->cookieName])) {
            $name = $_COOKIE[$this->cookieName]; //$this->cookieName is the cookie name which contains timestamp
            if (isset($_COOKIE[$name])) {
                //admin23423545password23423545table23423545role
                $cookie = $_COOKIE[$name];
                $nameLength = strlen($name);
                if ($type !== FALSE) {
                    $cookie = substr($cookie, strpos($cookie, $name) + $nameLength);
                    $cookie = substr($cookie, strpos($cookie, $name) + $nameLength);
                    if (strpos($cookie, $name) === FALSE) {
                        $type = substr($cookie, 0);
                    } else {
                        $type = substr($cookie, 0, strpos($cookie, $name));
                    }
                    return $type;
                }
                $result['username'] = substr($cookie, 0, strpos($cookie, $name));
                $cookie = substr($cookie, strpos($cookie, $name) + $nameLength);
                $result['password'] = substr($cookie, 0, strpos($cookie, $name));
                $cookie = substr($cookie, strpos($cookie, $name) + $nameLength);
                if (strpos($cookie, $name) === FALSE) {
                    $result['type'] = substr($cookie, 0);
                } else
                //we have role too
                {
                    $result['type'] = substr($cookie, 0, strpos($cookie, $name));
                    $cookie = substr($cookie, strpos($cookie, $name) + $nameLength);
                    $result['role'] = substr($cookie, 0);
                }
                $result['LoginTime'] = $name;
                return $result;
            }
            return FALSE;
        }
        return FALSE;
    }
    public function removeCookie() {
        if (isset($_COOKIE[$this->cookieName])) {
            $c = new CCookie;
            $name = $_COOKIE[$this->cookieName]; //a timestamp
            $c->delete($this->cookieName);
            if (isset($_COOKIE[$name])) {
                $c->delete($name);
            }
        }
    }
    public function logout() {
        $session = new CSession;
        $session->delete('username');
        $session->delete('user');
        $session->delete('loginTime');
        $session->delete('role');
        $this->removeCookie();
    }
}
?>

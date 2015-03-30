<?php
class CForm extends CGeneral {
    public $type;
    public $showFieldErrorText = TRUE; //wether to show field error with th field
    public $showFieldErrorColor = TRUE; //wether to show field error with th field
    public $dontClose;
    public $autoClean = TRUE;
    private static $errors = array();
    private $reference; //used in edit or update
    private $action; //form action
    private $config;
    private $method;
    private $labels = array();
    private $validation = array();
    //private $dontCheck; //don't check if field is not required
    public function __construct($method = '') {
        $this->method = $method;
        $this->config = $this->getConfig('form', $method);
        if (is_array($this->config)) {
            $this->generateLabels();
            $this->generateValidations();
        }
    }
    private function generateLabels() {
        $label = new CLabel($this->method);
        foreach ($this->config as $field => $data) {
            if (is_string($field)) {
                if (isset($data['label'])) {
                    $this->labels[$field] = $data['label'];
                } else {
                    $this->labels[$field] = $label->getLabel($field);
                }
            }
        }
    }
    private function generateValidations() {
        foreach ($this->config as $name => $data) {
            if (isset($data['validation'])) {
                $this->validation[$name] = $data['validation'];
            }
        }
    }
    private function getLabel($field, $data = '') {
        $result = '';
        if (isset($data['label'])) {
            $result = $data['label'];
        } elseif (isset($this->labels[$field])) {
            $result = $this->labels[$field];
        }
        if (isset($this->validation[$field])) {
            if (is_array($this->validation[$field])) {
                if (strpos($this->validation[$field][0], 'required') !== FALSE) {
                    $result.= '<span class="error">*</span>';
                }
            } else {
                if (strpos($this->validation[$field], 'required') !== FALSE) {
                    $result.= '<span class="error">*</span>';
                }
            }
        }
        return $result;
    }
    public function run() {
        if (!is_array($this->config)) {
            return FALSE;
        }
        $body = '';
        if (isset($this->config['reference'])) {
            $this->reference = CView::getVar($this->config['reference']);
        }
        foreach ($this->config as $name => $data) //get the form elements.$name is the name of the element
        {
            if (is_int($name) && (!is_array($data))) {
                $body.= $data;
            } else {
                if (is_array($data)) {
                    if (!empty($data['type'])) {
                        switch ($data['type']) {
                            case 'form':
                                $form = $this->beginForm($name, $data);
                            break;
                            case 'select':
                                $body.= $this->select($name, $data);
                            break;
                            case 'textarea':
                                $body.= $this->textarea($name, $data);
                            break;
                            case 'radio':
                                $body.= $this->radio($name, $data);
                            break;
                            case 'captcha':
                                $body.= $this->captcha($name, $data);
                            break;
                            case 'extra':
                                $body.= $this->extra($name, $data);
                            break;
                            case 'checkbox':
                                $body.= $this->checkbox($name, $data);
                            break;
                            case 'view_isset':
                                $body.= CView::getVar($name);
                            break;
                            default:
                                if ($name != 'reference') {
                                    $body.= $this->makeField($name, $data);
                                }
                        }
                    } else { //put sth I want, but I'd like to have the options!
                        $decoration = TRUE;
                        if (isset($data['decoration']) && $data['decoration'] === FALSE) $decoration = FALSE;
                        if ($decoration === TRUE) {
                            $body.= '<div class="form-group"><label class="col-xs-3 col-md-3 col-sm-3 " for="' . $name . '">';
                            $body.= $this->getLabel($name, $data);
                            $body.= '</label>';
                        }
                        if (isset($data['value'])) {
                            $body.= $data['value'];
                        }
                        if (!empty(self::$errors[$name])) {
                            if (isset($data['showFieldErrorText']) && $data['showFieldErrorText'] != FALSE) {
                                $body.= ' <sapn class="error">';
                                $body.= self::$errors[$name];
                                $body.= '</span>';
                            } elseif ($this->showFieldErrorText !== FALSE) {
                                $body.= ' <sapn class="error">';
                                $body.= self::$errors[$name];
                                $body.= '</span>';
                            }
                        }
                    }
                } elseif ($name != 'reference') {
                    $body.= '<div class="form-group"><label class="col-xs-3 col-md-3 col-sm-3 " for="' . $name . '">';
                    $body.= $this->getLabel($name, $data);
                    $body.= '</label>';
                    $body.= $data;
                    $body.= '</div>';
                }
            }
        }
        if (!isset($form)) {
            $form = $this->beginForm();
        }
        $body = $form . $body;
        if ($this->dontClose !== TRUE) $body.= '</form>';
        return $body;
    }
    public function validate() //array('clerk_number'=>array('required,number'))
    {
        if (count($_POST) == 0) {
            return FALSE;
        }
        if (!empty($this->validation)) {
            $validator = new CValidator;
            $db = new CDatabase;
            foreach ($this->validation as $field => $data) {
                $data = is_array($data) ? $data[0] : $data;
                //don't check if $data is not required
                if (stripos($data, 'required') === FALSE && empty($_POST[$field])) {
                    //$this->dontCheck = TRUE;
                    continue;
                } else {
                    //since its message has contact with other rules
                    if (strpos($data, 'required') !== FALSE) {
                        if (isset($_POST[$field])) {
                            $result = $validator->required($_POST[$field]);
                            if ($result === FALSE) {
                                $message = $this->buildMessage('required', $field);
                                $this->setError($field, $message);
                            }
                        } else {
                            $message = $this->buildMessage('required', $field);
                            $this->setError($field, $message);
                        }
                    }
                    $field_rules = explode(',', $data);
                    foreach ($field_rules as $rule) {
                        $result = TRUE;
                        $param = FALSE;
                        $unit = FALSE;
                        if ($this->getError($field) === FALSE) //$field has no error,i.e no 'required' error is set
                        {
                            if (($first_occurrence = strpos($rule, '[')) !== FALSE) {
                                $ruleTempValue = substr($rule, 0, $first_occurrence);
                                $length = ($ruleTempValue == 'pattern') ? (strrpos($rule, ']') - $first_occurrence - 1) : (strpos($rule, ']') - $first_occurrence - 1);
                                $param = substr($rule, $first_occurrence + 1, $length);
                                $unitStart = strrpos($rule, '[');
                                if ($unitStart != $first_occurrence) {
                                    $unit = substr($rule, $unitStart + 1, -1);
                                }
                                if (strpos($param, '$') !== FALSE && $ruleTempValue != 'pattern') {
                                    $param = str_replace('$', '', $param);
                                    $param = CView::getVar($param);
                                }
                                if (isset($unit) && strpos($unit, '$') !== FALSE) {
                                    $unit = str_replace('$', '', $unit);
                                    $unit = CView::getVar($unit);
                                }
                                $rule = $ruleTempValue;
                            }
                            if (method_exists($validator, $rule) === TRUE) {
                                //these rules are in array mode
                                if ($param !== FALSE) {
                                    //maxLength,minLength,maxValue,minValue,length,match,pattern
                                    if ($rule == 'unique') {
                                        $result = $validator->unique($param, $field, $_POST[$field]);
                                    } else {
                                        $result = $validator->$rule($_POST[$field], $param);
                                    }
                                } else {
                                    if ($rule == 'captcha') {
                                        $result = $validator->captcha($field);
                                    } else $result = $validator->$rule($_POST[$field]);
                                }
                            }
                            if ($result === FALSE) {
                                $message = $this->buildMessage($rule, $field, $param, $unit);
                                $this->setError($field, $message);
                            }
                        } //if($this->getError($field) === FALSE)
                        else {
                            break;
                        }
                    } //foreach($field_rules as $rule)
                    
                }
                if (empty(self::$errors[$field])) // there is no error,so we can do some cleaning
                {
                    $ac = TRUE;
                    if (isset($this->validation[$field]['autoClean'])) $ac = $this->validation[$field]['autoClean'];
                    elseif (isset($this->autoClean)) $ac = $this->autoClean;
                    if ($ac) {
                        if (isset($ac['trim'])) {
                            if ($ac['trim'] !== FALSE) $_POST[$field] = trim($_POST[$field]);
                        } else $_POST[$field] = trim($_POST[$field]);
                        if (isset($ac['stripslashes'])) {
                            if ($ac['stripslashes'] !== FALSE) $_POST[$field] = stripslashes($_POST[$field]);
                        } else $_POST[$field] = stripslashes($_POST[$field]);
                        if (isset($ac['escape'])) {
                            if ($ac['escape'] !== FALSE) $_POST[$field] = $db->escape($_POST[$field]);
                        } else $_POST[$field] = $db->escape($_POST[$field]);
                        if (isset($ac['htmlentities'])) {
                            if ($ac['htmlentities'] !== FALSE) $_POST[$field] = htmlentities($_POST[$field], ENT_QUOTES, "UTF-8");
                        } else $_POST[$field] = htmlentities($_POST[$field], ENT_QUOTES, "UTF-8");
                    }
                }
            }
        } //if(! empty($this->validation))
        if (empty(self::$errors)) return TRUE;
        return FALSE;
    }
    private function buildMessage($rule, $field, $param = FALSE, $unit = FALSE) {
        if (isset($this->validation[$field]['message'][$rule])) return $this->validation[$field]['message'][$rule];
        if (file_exists(FRAMEWORK . 'messages/validation.php')) {
            $message = require (FRAMEWORK . 'messages/validation.php');
        }
        if (isset($message[$rule])) {
            $message = $message[$rule];
        } else {
            $message = '{{field}}, has error.';
            if (PHP40::get()->debug === TRUE) {
                $message.= 'PLEASE check if the file "validation.php" exists in the directory ' . FRAMEWORK . 'messages/';
            }
        }
        $message = str_replace('{{field}}', $this->labels[$field], $message);
        if ($param !== FALSE) {
            if (isset($this->labels[$param])) {
                $message = str_replace('{{value}}', $this->labels[$param], $message);
            } else {
                $message = str_replace('{{value}}', $param, $message);
                if ($unit !== FALSE) {
                    $message = str_replace('{{unit}}', $unit, $message);
                } else {
                    $message = str_replace('{{unit}}', '', $message);
                }
            }
        }
        return $message;
    }
    public function setError($field, $message) {
        self::$errors[$field] = $message;
    }
    public function getError($field) {
        if (empty(self::$errors[$field])) return FALSE;
        else return self::$errors[$field];
    }
    public function getAllErrors() {
        if (!empty(self::$errors)) {
            $result = '<div class="red"><ul>';
            foreach (self::$errors as $field => $message) {
                $result.= '<li>' . $message . '</li>';
            }
            $result.= '</ul></div>';
            return $result;
        }
        return FALSE;
    }
    public function beginForm($name = '', $data = '') {
        $form = '<form class="form-horizontal"';
        if (!empty($name)) {
            $form.= ' name="' . $name . '" id="' . $name . '"';
        }
        if (!empty($data['enctype'])) {
            $form.= ' enctype="' . $data['enctype'] . '"';
        }
        if (!empty($data['method'])) {
            $form.= ' method="' . $data['method'] . '"';
        }
        if (!empty($data['action'])) {
            $form.= ' action="' . CUrl::createUrl($data['action']) . '"';
        }
        if (strpos($form, 'enctype') === FALSE && isset($this->type)) {
            $form.= ' enctype="' . $this->type . '"';
        }
        if (strpos($form, 'method') === FALSE) {
            $form.= ' method="post"';
        }
        if (strpos($form, 'action') === FALSE) {
            if (isset($this->action)) {
                $form.= ' action="' . $this->action . '"';
            } elseif (isset($_SERVER['PATH_INFO'])) {
                $form.= ' action="' . CUrl::createUrl($_SERVER['PATH_INFO']) . '"';
            }
        }
        if (isset($data['in'])) $form.= ' ' . $data['in'];
        if (isset($data['out'])) $form.= ' ' . $data['out'];
        $form.= '>';
        return $form;
    }
    public function captcha($name, $data) {
        //$x = ROOT.FRAMEWORK.'core/captcha/CCaptcha.php';
        //echo $x; echo '<br />'; $y = FRAMEWORK.'core/captcha/CCaptcha.php'; echo $y; exit();
        $decoration = TRUE;
        if (isset($data['decoration']) && $data['decoration'] === FALSE) $decoration = FALSE;
        $flag = TRUE;
        $body = '';
        $path = ROOT . 'public/runtime/captcha.php';
        $content = '<?php require_once("' . ROOT . FRAMEWORK . 'core/captcha/CCaptcha.php");$obj = new CCaptcha;';
        foreach ($data as $key => $value) {
            if (property_exists('CCaptcha', $key) === TRUE) $content.= '$obj->' . $key . '= \'' . $value . '\';';
        }
        $content.= '$obj->captchaField=\'' . $name . '\'; $obj->generateRandom(); 
        require_once("' . ROOT . FRAMEWORK . 'core/CSession.php"); $session = new CSession;
        $session->set("captcha",md5($obj->getCode())); $obj->generateImage();?>';
        if (!$file = fopen($path, 'w')) {
            $flag = FALSE;
        } else {
            if (fwrite($file, $content) === FALSE) {
                $flag = FALSE;
            }
        }
        if ($flag === TRUE) {
            if ($decoration === TRUE) {
                $body.= '<div class="form-group"><label>&nbsp;</label>';
            }
            $imgPath = PHP40::get()->homeUrl . 'public/runtime/captcha.php';
            $body.= '<img src="' . $imgPath . '" alt="captcha" />';
            if ($decoration === TRUE) {
                $body.= '</div><div class="form-group"><label class="col-xs-3 col-md-3 col-sm-3 " for="' . $name . '">کد امنیتی<span class="error">*</span></label>';
            }
            $body.= '<div class="col-xs-5 col-md-3 col-sm-4"><input class="form-control" type="text" name="' . $name . '" id="' . $name . '"  value="';
            $temp = isset($data['post']) ? $data['post'] : TRUE;
            if (isset($_POST[$name]) && $temp !== FALSE) {
                $body.= $_POST[$name];
            }
            $body.= '"';
            if (isset($data['in'])) {
                $body.= ' ' . $data['in'];
            }
            if (!empty(self::$errors[$name])) {
                $showFieldErrorColor = isset($data['showFieldErrorColor']) ? $data['showFieldErrorColor'] : $this->showFieldErrorColor;
                if ($showFieldErrorColor) {
                    $body.= ' style="background:#FFE3E4"';
                }
            }
            $body.= ' /></div>';
            $showFieldErrorText = FALSE;
            if (!empty(self::$errors[$name])) {
                $showFieldErrorText = isset($data['showFieldErrorText']) ? $data['showFieldErrorText'] : $this->showFieldErrorText;
                if ($showFieldErrorText) {
                    $body.= ' <sapn class="error">';
                    $body.= self::$errors[$name];
                    $body.= '</span>';
                    $showFieldErrorText = TRUE;
                }
            }
            if (isset($data['out']) && $showFieldErrorText === FALSE) {
                $body.= $data['out'];
            }
            if ($decoration === TRUE) $body.= '</div>';
        } elseif (PHP40::get()->debug === TRUE) {
            echo 'There was error in generating captch.';
        }
        return $body;
    }
    public function checkbox($name, $data) {
        $decoration = TRUE;
        if (isset($data['decoration']) && $data['decoration'] === FALSE) $decoration = FALSE;
        $body = '';
        if ($decoration === TRUE) {
            $body.= '<div class="form-group">';
        }
        $modelValue = '';
        $post = TRUE;
        if (isset($data['post'])) $post = $data['post'];
        $values = '';
        $default = '';
        if (isset($data['value'])) {
            $values = $data['value'];
            if (isset($data['value']['default'])) $default = $data['value']['default'];
        }
        $postFlag = FALSE;
        if (isset($_POST[$name]) && (!empty($post))) {
            $postFlag = TRUE;
        }
        if (is_array($values)) {
            foreach ($values as $key => $value) {
                $body.= '<div class="col-xs-5 col-md-3 col-sm-4"><input type="checkbox" class="form-control" name="' . $name . '" id="' . $name . '" value="' . $key . '"';
                if (($hasBracket = $this->getBracket($name)) !== FALSE && $postFlag !== FALSE && isset($_POST[$hasBracket['field']][$hasBracket['in']]) && $_POST[$hasBracket['field']][$hasBracket['in']] == $key) {
                    $body.= ' checked';
                } elseif ($postFlag === TRUE && isset($_POST[$name]) && $_POST[$name] == $key) {
                    $body.= ' checked';
                } elseif ($default == $key) {
                    $body.= ' checked';
                }
                if (!empty($data['in'])) {
                    $body.= ' ' . $data['in'];
                }
                $body.= ' /></div>';
                $body.= '<span style="padding-left:10px">' . $value . '</span>';
            }
        } else
        //it is just a string, no value
        {
            $body.= '<div class="col-xs-5 col-md-3 col-sm-4"><input class="form-control" type="checkbox" name="' . $name . '" id="' . $name . '"';
            if (($hasBracket = $this->getBracket($name)) !== FALSE && $postFlag !== FALSE && isset($_POST[$hasBracket['field']][$hasBracket['in']]) && $_POST[$hasBracket['field']][$hasBracket['in']] == 'on') {
                $body.= ' checked';
            } elseif ($postFlag === TRUE && isset($_POST[$name]) && $_POST[$name] == 'on') {
                $body.= ' checked';
            }
            if (!empty($data['in'])) {
                $body.= ' ' . $data['in'];
            }
            $body.= ' /></div>';
            $body.= '<span>' . $values . '</span>';
        }
        $showFieldErrorText = FALSE;
        if (!empty(self::$errors[$name])) {
            $showFieldErrorText = isset($data['showFieldErrorText']) ? $data['showFieldErrorText'] : $this->showFieldErrorText;
            if ($showFieldErrorText) {
                $body.= ' <sapn class="error">';
                $body.= self::$errors[$name];
                $body.= '</span>';
                $showFieldErrorText = TRUE;
            }
        }
        if (isset($data['out']) && $showFieldErrorText === FALSE) {
            $body.= $data['out'];
        }
        if ($decoration === TRUE) {
            $body.= '</div>';
        }
        return $body;
    }    public function extra($main, $other) {
        if (!empty($other['reference'])) {
            if (is_array($other['reference'])) {
                foreach ($other['reference'] as $reference) {
                    $model = CView::getVar($reference);
                    if (strpos($main, '$' . $reference) !== FALSE) {
                        $main = str_replace('$' . $reference, $model, $main);
                    }
                }
            } else {
                $model = CView::getVar($other['reference']);
                if (strpos($main, '$' . $other['reference']) !== FALSE) {
                    $main = str_replace('$' . $other['reference'], $model, $main);
                }
            }
        }
        return $main;
    }
    public function makeField($name, $data) {
        $decoration = TRUE;
        if (isset($data['decoration']) && $data['decoration'] === FALSE) $decoration = FALSE;
        $body = '';
        if ($decoration === TRUE && $data['type'] != 'hidden') {
            $body.= '<div class="form-group"><label class="col-xs-3 col-md-3 col-sm-3 " for="' . $name . '">';
            if ($data['type'] != 'submit') {
                $body.= $this->getLabel($name, $data);
            } else $body.= '&nbsp;';
            $body.= '</label>';
        }
        if ($data['type']=='submit'){
            $body.= '<div class="col-xs-5 col-md-3 col-sm-4"><input class="btn btn-success" type="' . $data['type'] . '" name="' . $name . '" id="' . $name . '" value="';
        }
        else{
        $body.= '<div class="col-xs-5 col-md-3 col-sm-4"><input class="form-control" type="' . $data['type'] . '" name="' . $name . '" id="' . $name . '" value="';
        }
        $post = isset($data['post']) ? $data['post'] : TRUE;
        $reference = empty($data['reference']) ? '' : $data['reference'];
        if (($hasBracket = $this->getBracket($name)) !== FALSE && isset($_POST[$hasBracket['field']][$hasBracket['in']]) && $post !== FALSE) {
            $body.= $_POST[$hasBracket['field']][$hasBracket['in']];
        } elseif (isset($_POST[$name]) && $post !== FALSE) {
            $body.= $_POST[$name];
        } elseif (($modelValue = $this->getRefValue($name, $reference)) !== FALSE) {
            $body.= $modelValue;
        } elseif (isset($data['value'])) {
            $body.= $data['value'];
        }
        $body.= '"';
        if (isset($data['in'])) {
            $body.= ' ' . $data['in'];
        }
        if (!empty(self::$errors[$name])) {
            $showFieldErrorColor = isset($data['showFieldErrorColor']) ? $data['showFieldErrorColor'] : $this->showFieldErrorColor;
            if ($showFieldErrorColor) {
                $body.= ' style="background:#FFE3E4"';
            }
        }
        $body.= ' /></div>';
        $showFieldErrorText = FALSE;
        if (!empty(self::$errors[$name])) {
            $showFieldErrorText = isset($data['showFieldErrorText']) ? $data['showFieldErrorText'] : $this->showFieldErrorText;
            if ($showFieldErrorText) {
                $body.= ' <sapn class="error">';
                $body.= self::$errors[$name];
                $body.= '</span>';
                $showFieldErrorText = TRUE;
            }
        }
        if (isset($data['out']) && $showFieldErrorText === FALSE) {
            $body.= $data['out'];
        }
        if ($decoration === TRUE && $data['type'] != 'hidden') $body.= '</div>';
        return $body;
    }
    public function radio($name, $data) {
        $decoration = TRUE;
        if (isset($data['decoration']) && $data['decoration'] === FALSE) $decoration = FALSE;
        $body = '<div class="form-group">';
        if ($decoration === TRUE) {
            $body.= '<label class="col-xs-3 col-md-3 col-sm-3 " for="' . $name . '">';
            $body.= $this->getLabel($name, $data);
            $body.= '</label>';
        }
        $modelValue = '';
        $postFlag = TRUE;
        $default = '';
        $values = '';
        $reference = empty($data['reference']) ? '' : $data['reference'];
        $modelValue = $this->getRefValue($name, $reference);
        if (isset($data['post'])) $postFlag = $data['post'];
        if (isset($data['value'])) {
            $values = $data['value'];
            if (isset($data['values']['default'])) $default = $data['default'];
        }
        if (is_array($values)) {
            
            $hasBracket = $this->getBracket($name);
            $body.='<div class="col-xs-3 col-md-3 col-sm-3 ">';
            foreach ($values as $key => $value) {
                if ($decoration === TRUE) {
                    if (isset($data['veritical']) && $data['veritical'] == TRUE) $body.= '';
                    else $body.= '&nbsp;&nbsp;';
                }
                if (is_string($value)) {
                    $body.= '<span>' . $value . '</span>';
                }
                $body.= '<input class="form-control" type="radio" name="' . $name . '"';
                $body.= ' value="' . $key . '"';
                if ($hasBracket !== FALSE && $postFlag !== FALSE && isset($_POST[$hasBracket['field']][$hasBracket['in']]) && $_POST[$hasBracket['field']][$hasBracket['in']] == $key) {
                    $body.= ' checked="checked"';
                } elseif ($postFlag === TRUE && isset($_POST[$name]) && $_POST[$name] == $key) {
                    $body.= ' checked="checked"';
                } elseif ($modelValue == $key) {
                    $body.= ' checked="checked"';
                } elseif ($default == $key) {
                    $body.= ' checked="checked"';
                }
                if (!empty($data['in'])) {
                    if (is_array($data['in']) && isset($data['in'][$key])) {
                        $body.= ' ' . $data['in'][$key];
                    } else $body.= ' ' . $data['in'];
                }
                $body.= ' />';
                if ($decoration === TRUE) {
                    if (isset($data['veritical']) && $data['veritical'] == TRUE) $body.= '<div class="form-group">';
                }
            }
            $body.="</div>";
            
        } else {
            $body.= '<input class="form-control" type="radio" name="' . $name . '"';
            $body.= ' value="' . $values . '"';
            if (($hasBracket = $this->getBracket($name)) !== FALSE && $postFlag !== FALSE && isset($_POST[$hasBracket['field']][$hasBracket['in']]) && $_POST[$hasBracket['field']][$hasBracket['in']] == $data) {
                $body.= ' checked="checked"';
            } elseif ($postFlag === TRUE && isset($_POST[$name]) && $_POST[$name] == $data) {
                $body.= ' checked="checked"';
            } elseif ($modelValue == $data) {
                $body.= ' checked="checked"';
            } elseif ($default == $data) {
                $body.= ' checked="checked"';
            }
            if (!empty($data['type'])) {
                $body.= ' ' . $data['type'];
            }
            $body.= ' />';
        }
        $showFieldErrorText = FALSE;
        if (!empty(self::$errors[$name])) {
            $showFieldErrorText = isset($data['showFieldErrorText']) ? $data['showFieldErrorText'] : $this->showFieldErrorText;
            if ($showFieldErrorText) {
                $body.= ' <sapn class="error">';
                $body.= self::$errors[$name];
                $body.= '</span>';
                $showFieldErrorText = TRUE;
            }
        }
        if (isset($data['out']) && $showFieldErrorText === FALSE) {
            $body.= $data['out'];
        }
        if ($decoration === TRUE) {
            $body.= '</div>';
        }
        return $body;
    }
    public function select($name, $data) {
        $decoration = TRUE;
        if (isset($data['decoration']) && $data['decoration'] === FALSE) $decoration = FALSE;
        $body = '';
        if ($decoration === TRUE) {
            $body.= '<div class="form-group"><label class="col-xs-3 col-md-3 col-sm-3 " for="' . $name . '">';
            $body.= $this->getLabel($name, $data);
            $body.= '</label>';
        }
        $body.= '<div class="col-xs-5 col-md-3 col-sm-4"><select class="form-control" name="' . $name . '" id="' . $name . '"';
        if (!empty($data['in'])) {
            $body.= ' ' . $data['in'];
        }
        if (!empty(self::$errors[$name])) {
            $showFieldErrorColor = isset($data['showFieldErrorColor']) ? $data['showFieldErrorColor'] : $this->showFieldErrorColor;
            if ($showFieldErrorColor) {
                $body.= ' style="background:#FFE3E4"';
            }
        }
        $body.= ' >';
        $modelValue = '';
        if (isset($data['options']) && is_string($data['options'])) {
            $data['options'] = $this->specialOption($data['options']);
        }
        if (isset($data['options']) && is_array($data['options'])) {
            $body.= $this->options($data, $name);
        }
        $body.= '</select></div>';
        $showFieldErrorText = FALSE;
        if (!empty(self::$errors[$name])) {
            $showFieldErrorText = isset($data['showFieldErrorText']) ? $data['showFieldErrorText'] : $this->showFieldErrorText;
            if ($showFieldErrorText) {
                $body.= ' <sapn class="error">';
                $body.= self::$errors[$name];
                $body.= '</span>';
                $showFieldErrorText = TRUE;
            }
        }
        if (isset($data['out']) && $showFieldErrorText == FALSE) {
            $body.= $data['out'];
        }
        if ($decoration === TRUE) $body.= '</div>';
        return $body;
    }
    public function options($data, $fieldName) {
        $body = '';
        if (isset($data['default'])) {
            $body.= '<option value="">' . $data['default'] . '</option>';
        }
        $post = isset($data['post']) ? $data['post'] : TRUE;
        $reference = empty($data['reference']) ? '' : $data['reference'];
        $modelValue = $this->getRefValue($fieldName, $reference);
        if (isset($data['options'])) $data = $data['options'];
        if (isset($data['default'])) {
            $body.= '<option value="">' . $data['default'] . '</option>';
        }
        $hasBracket = $this->getBracket($fieldName);
        foreach ($data as $key => $value) {
            if ($key == 'default') continue;
            $body.= '<option value="' . $key . '"';
            if ($post === TRUE && $hasBracket !== FALSE && isset($_POST[$hasBracket['field']][$hasBracket['in']]) && $_POST[$hasBracket['field']][$hasBracket['in']] == $key) {
                $body.= ' selected="selected"';
            } elseif ($post === TRUE && isset($_POST[$fieldName]) && $_POST[$fieldName] == $key) {
                $body.= ' selected="selected"';
            } elseif ($modelValue == $key) {
                $body.= ' selected="selected"';
            }
            $body.= '>' . $value . '</option>';
        }
        return $body;
    }
    public function specialOption($option) {
        $result = FALSE;
        if ($option == 'days_of_month') {
            $result = array('default' => 'روز', 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10, 11 => 11, 12 => 12, 13 => 13, 14 => 14, 15 => 15, 16 => 16, 17 => 17, 18 => 18, 19 => 19, 20 => 20, 21 => 21, 22 => 22, 23 => 23, 24 => 24, 25 => 25, 26 => 26, 27 => 27, 28 => 28, 29 => 29, 30 => 30, 31 => 31,);
        } elseif ($option == 'months_of_year') {
            $result = array('default' => 'ماه', 1 => '01', 2 => '02', 3 => '03', 4 => '04', 5 => '05', 6 => '06', 7 => '07', 8 => '08', 9 => '09', 10 => '10', 11 => '11', 12 => '12',);
        } 
        elseif (stripos($option, 'lastTenYears') !== FALSE) {
            $c = new CJcalendar;
            $current=intval($c->date("Y",FALSE,FALSE));
            $result = array('default' => 'سال' ,);
            for ($i = $current-10;$i <= $current;$i++) {
                $result[$i] = $i;
                }
         }
                elseif (stripos($option,
 'numbers') !== FALSE) {
            if (($first_ = strpos($option, '_')) !== FALSE) {
                $numbers = substr($option, $first_ + 1);
                if (($last_ = strpos($numbers, '_')) !== FALSE) {
                    $minNum = (int)substr($numbers, 0, $last_);
                    $maxNum = (int)ltrim(strstr($numbers, '_'), '_');
                    $result = array('default' => 'انتخاب',);
                    for ($i = $minNum;$i <= $maxNum;$i++) {
                        $result[$i] = $i;
                    }
                }
            }
        }
        return $result;
    }
    public function textarea($name, $data) {
        $decoration = TRUE;
        if (isset($data['decoration']) && $data['decoration'] === FALSE) $decoration = FALSE;
        $body = '';
        if ($decoration === TRUE) {
            $body.= '<div class="form-group"><label class="col-xs-3 col-md-3 col-sm-3 " for="' . $name . '">';
            $body.= $this->getLabel($name, $data);
            $body.= '</label>';
        }
        $body.= '<div class="col-xs-5 col-md-3 col-sm-4"><textarea class="form-control"  name="' . $name . '" id="' . $name . '"';
        if (isset($data['rows'])) {
            $body.= ' rows=' . $data['rows'];
        }
        if (isset($data['cols'])) {
            $body.= ' cols=' . $data['cols'];
        }
        if (isset($data['in'])) {
            $body.= ' ' . $data['in'];
        }
        if (!empty(self::$errors[$name])) {
            $showFieldErrorColor = isset($data['showFieldErrorColor']) ? $data['showFieldErrorColor'] : $this->showFieldErrorColor;
            if ($showFieldErrorColor) {
                $body.= ' style="background:#FFE3E4"';
            }
        }
        $body.= '>';
        $post = isset($data['post']) ? $data['post'] : TRUE;
        $reference = empty($values['reference']) ? '' : $values['reference'];
        if ($post !== FALSE && ($hasBracket = $this->getBracket($name)) !== FALSE && isset($_POST[$hasBracket['field']][$hasBracket['in']])) {
            $body.= $_POST[$hasBracket['field']][$hasBracket['in']];
        }
        if (isset($_POST[$name]) && $post !== FALSE) {
            $body.= $_POST[$name];
        } elseif (($modelValue = $this->getRefValue($name, $reference)) !== FALSE) $body.= $modelValue;
        elseif (isset($data['value'])) {
            $body.= $data['value'];
        }
        $body.= '</textarea></div>';
        $showFieldErrorText = FALSE;
        if (!empty(self::$errors[$name])) {
            $showFieldErrorText = isset($data['showFieldErrorText']) ? $data['showFieldErrorText'] : $this->showFieldErrorText;
            if ($showFieldErrorText) {
                $body.= ' <sapn class="error">';
                $body.= self::$errors[$name];
                $body.= '</span>';
                $showFieldErrorText = TRUE;
            }
        }
        if (isset($data['out']) && $showFieldErrorText === FALSE) {
            $body.= $data['out'];
        }
        if ($decoration === TRUE) $body.= '</div>';
        return $body;
    }
    private function getRefValue($name, $referenceName = '') {
        $reference = empty($referenceName) ? $this->reference : CView::getVar($referenceName);
        if (!empty($reference)) {
            if (is_object($reference)) {
                if (isset($reference->$name)) return $reference->$name;
                return FALSE;
            } elseif (is_array($reference)) {
                if (isset($reference[$name])) return $reference[$name];
                return FALSE;
            } else {
                return $reference;
            }
            return FALSE;
        }
        return FALSE;
    }
    private function getBracket($param) {
        //grade[0]
        if (($startPos = strpos($param, '[')) !== FALSE) {
            $endPos = strrpos($param, ']');
            $length = $endPos - $startPos - 1;
            $result['in'] = substr($param, $startPos + 1, $length);
            $result['field'] = substr($param, 0, $startPos);
            return $result;
        }
        return FALSE;
    }
}
?>
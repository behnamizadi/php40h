<?php
class CGDL {
    public $paginate = TRUE;
    public $table;
    public $condition;
    public $pageSize = 10;
    public $value;
    public $values;
    protected $start = 0;
    public $sqlSelect = '';
    public $pk;
    /*
     * $data :value of each row of $this->values
    */
    protected function setDisplay($data, $format) {
        $first_occurrence = strpos($format, '[');
        if ($first_occurrence !== FALSE) //it's function!
        {
            $length = strrpos($format, ']') - $first_occurrence - 1;
            $param = substr($format, $first_occurrence + 1, $length);
            $format = substr($format, 0, $first_occurrence);
        }
        $format = strtolower($format);
        switch ($format) {
            case 'date':
                if (isset($param)) {
                    return date($param, $data);
                }
                return $data;
            break;
            case 'link':
                if (isset($param)) {
                    if (strpos($param, '$value') !== FALSE && isset($this->value)) {
                        $param = $this->getReal($param);
                    }
                    //$param = $this->getReal($param);
                    return CUrl::createLink($data, $param);
                }
                return $data;
            break;
            case 'model':
                if (isset($param)) {
                    if (strpos($param, '::') !== FALSE) //Lookup::getById($value,CAT)
                    {
                        $class = substr($param, 0, strpos($param, '('));
                        $parameters = substr($param, strpos($param, '(') + 1, -1);
                        $parameters = str_replace('$value', $data, $parameters);
                        $parameters = explode(',', $parameters);
                        return call_user_func_array($class, $parameters);
                    } elseif (($commaPos = strpos($param, ',')) !== FALSE) //Lookup,getById($value,CAT)
                    {
                        $class = substr($param, 0, $commaPos);
                        $method = substr($param, $commaPos + 1, strpos($param, '(') - strlen($class) - 1);
                        $parameters = substr($param, strpos($param, '(') + 1, -1);
                        $parameters = str_replace('$value', $data, $parameters);
                        $parameters = explode(',', $parameters);
                        $classObj = new $class;
                        return call_user_func_array(array($classObj, $method), $parameters);
                    }
                }
                return $data;
            break;
            case 'img':
                if (isset($param)) {
                    if (strpos($param, ',') !== FALSE) {
                        $imgInfo = explode(',', $param);
                        $location = $imgInfo[0];
                        $width = $imgInfo[1];
                        $height = $imgInfo[2];
                    } else {
                        $location = $param;
                    }
                    $location = rtrim($location, '/');
                    $img = '<img src="' . PHP40::get()->homeUrl . $location . '/' . $data . '"';
                    if (isset($width)) {
                        $img.= ' width="' . $width . '"';
                    }
                    if (isset($height)) {
                        $height.= ' height="' . $height . '"';
                    }
                    $img.= ' />';
                    return $img;
                }
                return $data;
            break;
            case 'decode':
                return htmlspecialchars_decode($data, ENT_QUOTES);
            break;
            case 'length':
                if (isset($param)) return substr($data, 0, $param);
                return $data;
                break;
            case 'type': //type[1:type1,2:type2,3:type3]
                if (isset($param)) {
                    while (($colonPlace = strpos($param, ':')) !== FALSE) {
                        $type = substr($param, 0, $colonPlace);
                        $commaPlace = strpos($param, ',');
                        if ($type == $data) {
                            if ($commaPlace !== FALSE) {
                                $data = substr($param, $colonPlace + 1, $commaPlace - $colonPlace - 1);
                            } else { //last one
                                $data = substr($param, $colonPlace + 1);
                            }
                        }
                        if ($commaPlace !== FALSE) $param = substr($param, $commaPlace + 1);
                        else break;
                    }
                }
                return $data;
                break;
            default:
                return $data . $format;
            }
            return $data;
        }
        protected function paginate($count = 0) {
            $paginate = new CPagination;
            $page = (empty($_GET[$paginate->pageGet])) ? 1 : $_GET[$paginate->pageGet];
            $this->pageSize = (empty($this->pageSize)) ? $paginate->pageSize : $this->pageSize;
            $this->start = ($page - 1) * $this->pageSize;
            $paginate->pageSize = $this->pageSize;
            if ($count == 0) {
                $db = new CDatabase;
                if (!empty($this->table)) $db->setTbl($this->table);
                $paginate->totalRows = $db->getCountRows($db->condition($this->condition));
            } else {
                $paginate->totalRows = $count;
                if (is_array($this->values)) $this->values = array_slice($this->values, $this->start, $this->pageSize);
            }
            return $paginate->run();
        }
        protected function getSort() {
            $sortType = '';
            if (!empty($_GET['sort'])) {
                $dotPos = strrpos($_GET['sort'], '.');
                $sortField = $_GET['sort'];
                if ($dotPos !== FALSE) {
                    if (substr($_GET['sort'], $dotPos + 1) == 'desc') $sortType = ' DESC';
                    $sortField = substr($_GET['sort'], 0, $dotPos);
                }
            }
            if (isset($sortField)) {
                return $sortField . $sortType;
            } elseif (!empty($this->sort)) return $this->sort;
            return FALSE;
        }
        protected function valueSort($field, $desc = FALSE) {
            if (empty($this->values)) return;
            $result = $this->values;
            $count = count($result);
            for ($i = 0;$i < $count;$i++) {
                $pointer = $i;
                for ($j = $i + 1;$j < $count;$j++) {
                    if ($desc === FALSE) //ascending sort
                    {
                        if ($result[$j]->$field < $result[$pointer]->$field) {
                            $pointer = $j;
                        }
                    } else {
                        if ($result[$j]->$field > $result[$pointer]->$field) {
                            $pointer = $j;
                        }
                    }
                }
                $temp = $result[$i];
                $result[$i] = $result[$pointer];
                $result[$pointer] = $temp;
            }
            return $result;
        }
        protected function getValue() {
            $db = new CDatabase;
            if (!empty($this->table)) $db->setTbl($this->table);
            $sql = '';
            if (!empty($this->condition)) $sql.= $db->condition($this->condition);
            if (($sort = $this->getSort()) !== FALSE) {
                $sql = $db->eliminate($sql, 'order');
                $sql.= ' ORDER BY ' . $sort;
            }
            //$this->paginate sets $this->start,$this->pageSize
            if ($this->paginate == TRUE) $sql.= " LIMIT $this->start,$this->pageSize";
            return $db->getAll($sql, $this->sqlSelect);
        }
        protected function evaluate($data) {
            return eval('return ' . $data . ';');
        }
        /*
         * replaces $value->field in $raw with apprporiate value
        */
        protected function getReal($raw, $endSign = '/') {
            while (($valuePos = strpos($raw, '$value->')) !== FALSE) {
                //xxxx/1/$value->sth/sth else
                $field = substr($raw, $valuePos + 8);
                $end = strpos($field, $endSign);
                if ($end !== FALSE) {
                    $field = substr($field, 0, $end);
                }
                $head = substr($raw, 0, $valuePos);
                $trail = substr($raw, $valuePos + 8 + strlen($field));
                if (isset($this->value->$field)) $raw = $head . $this->value->$field . $trail;
                else $raw = $head . $trail;
            }
            return $raw;
        }
    }
?>
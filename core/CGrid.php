<?php
class CGrid extends CGDL {
    public $operations;
    public $headers;
    public $sort;
    public $noSort;
    public $css = 'clist';
    public $counter = FALSE;
    const NOTFOUND = '<div class="red">  </div>';
    private $method;
    public function __construct($method = '') {
        $this -> method = $method;
    }

    public function run() {
        if (!isset($this -> values)) {
            if ($this -> paginate == TRUE)
                $pResult = $this -> paginate();
            $this -> values = $this -> getValue();
        } else {
            $sortField = $this -> getSort();
            if ($sortField !== FALSE) {
                $desc = FALSE;
                if (strpos($sortField, 'DESC') !== FALSE) {
                    $sortField = substr($sortField, 0, -5);
                    // ' DESC'
                    $desc = TRUE;
                }
                $this -> values = $this -> valueSort($sortField, $desc);
            }
            if ($this -> paginate == TRUE) {
                $count = count($this -> values);
                $pResult = $this -> paginate($count);
            }
        }
        if (empty($this -> values)) {
            return self::NOTFOUND;
        }
        $output = '<table class="table table-striped table-bordered table-hover">';
        $output .= $this -> makeCols();
        if ($this -> counter) {
            $page = 1;
            if (!empty($_GET['page'])) {
                $page = $_GET['page'];
            }
            $rowCounter = ($page - 1) * $this -> pageSize + 1;
        }
        $i = 0;
        foreach ($this->values as $row) {
            $this -> value = $row;
            $class = (($i % 2) == 0) ? 'even' : 'odd';
            $output .= '<tr>';
            if ($this -> counter) {
                $output .= "<td>$rowCounter</td>";
                $rowCounter++;
            }
            foreach ($this->headers as $key => $field) {
                if (is_string($key)) {
                    $data = $row -> $key;
                    if (is_array($field)) {
                        if (isset($field['onEmpty']) && empty($data)) {
                            $data = $field['onEmpty'];
                        } elseif (!empty($field['format']))//field[0] is format
                        {
                            if (is_array($field['format'])) {
                                foreach ($field['format'] as $format)
                                    $data = $this -> setDisplay($data, $format);
                            } else
                                $data = $this -> setDisplay($data, $field['format']);
                        }
                    }
                } else {
                    $data = $row -> $field;
                }
                $output .= '<td>' . $data . '</td>';
            }
            $db = new CDatabase;
            if (!empty($this -> table))
                $db -> setTbl($this -> table);
            if (empty($this -> pk))
                $this -> pk = $db -> pkName();
            if ($this -> operations != FALSE) {
                $output .= '<td>';
                $output .= $this -> generateOperations();
                $output .= '</td>';
            }
            $output .= '</tr>';
            $i++;
        }
        $output .= '</table>';
        if (isset($pResult))
            $output .= $pResult;
        unset($this -> values);
        return $output;
    }

    private function generateOperations() {
        if (!is_array($this -> operations))
            $this -> operations = array();

        if (empty($this -> pk))
            return;
        if (is_array($this -> operations)) {
            $output = '<div class="btn-group" role="group">';
            foreach ($this->operations as $operation => $data) {
                //if $operation has $value->sth
                //$operation = $this->getReal($operation);
                if (!is_array($data)) {
                    continue;
                }
                if (isset($data['visible'])) {
                    $condition = $data['visible'];
                    $condition = str_replace('$value', $this -> value -> {$this -> pk}, $condition);
                    $condition = $this -> evaluate($condition);
                    if ($condition !== TRUE) {
                        continue;
                    }
                }
                $urlFlag = TRUE;
                if (isset($data['noLink']) && $data['noLink'] == TRUE)
                    $urlFlag = FALSE;
                if ($urlFlag) {
                    $url = CUrl::createUrl($data['href']);
                    $output .= '<a class="btn btn-default" href="' . $url . '"';
                    if (!empty($data['title'])) {
                        $output .= ' title="' . $data['title'] . '"';
                    }
                    if (stripos($operation, 'delete') !== FALSE) {
                        $output .= ' onclick="return confirm(\'   \')"';
                        $icon= 'glyphicon glyphicon-remove';
                    }
                    if (stripos($operation, 'view') !== FALSE) {
                        $icon= 'glyphicon glyphicon-eye-open';
                    }
                    if (stripos($operation, 'edit') !== FALSE) {
                        $icon .= 'glyphicon glyphicon-edit';
                    }
                    if (!empty($data['in']))
                        $output .= " $data[in]";
                    $output .= '>';

                    $output .= '<span class="' . $icon . '" aria-hidden="true"></span>';

                    $output .= '</a>';
                }
            }
            $output .= '</div>';
            return $output;
        }
    }

    private function makeCols() {
        $label = new CLabel($this -> method);
        $route = CGeneral::makeUrlQuery('sort');
        $sortType = '';
        $output = '<tr>';
        if (!empty($_GET['sort'])) {
            $dotPos = strrpos($_GET['sort'], '.');
            if ($dotPos !== FALSE) {
                $sortType = (substr($_GET['sort'], $dotPos + 1) == 'desc') ? '' : 'desc';
            } else {
                $sortType = 'desc';
            }
        }
        if ($this -> counter) {
            $output .= '<th scope="col"></th>';
        }
        foreach ($this->headers as $key => $field) {
            $output .= '<th scope="col">';
            if (is_string($key))//user has set the value e.g. header=array('field_in_tbl'=>'label') or header=array('field_in_tbl'=>array('format','label')
            {
                if (is_array($field)) {
                    //label is already set
                    if (!empty($field['label'])) {
                        $tempLbl = $field['label'];
                    } else {
                        $tempLbl = $label -> getLabel($key);
                    }
                }
                if ((is_array($this -> noSort) && (array_search($key, $this -> noSort) !== FALSE)) || ($this -> noSort === TRUE)) {
                    $output .= $tempLbl;
                } else {
                    $output .= '<a href="' . $route . $key;
                    if (!empty($sortType))
                        $output .= '.' . $sortType;
                    $output .= '">' . $tempLbl . '</a>';
                }
            } else {
                if ((is_array($this -> noSort) && (array_search($key, $this -> noSort) !== FALSE)) || ($this -> noSort === TRUE)) {
                    $output .= $label -> getLabel($field);
                } else {
                    $output .= '<a href="' . $route . $field;
                    if (!empty($sortType))
                        $output .= '.' . $sortType;
                    $output .= '">' . $label -> getLabel($field) . '</a>';
                }
            }
            $output .= '</th>';
        }
        if (is_array($this -> operations)) {
            $output .= '<th></th>';
        }
        $output .= '</tr>';
        return $output;
    }

}
?>
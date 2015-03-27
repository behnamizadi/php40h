<?php
class CDetail extends CGDL {
    public $headers;
    public $pkValue;
    public $additional;
    public $return;
    public $twoColumn = FALSE;
    public $numberOfColumns = 1; //works if $twoColumn = FALSE
    private $returnResult;
    const NOTFOUND = '<div class="red">موردی یافت نشد</div>';
    public function run($strict = FALSE) {
        if ($this->twoColumn !== FALSE) $table = '<table class="clist">';
        else $table = '<table class="create">';
        $db = new CDatabase;
        if (!empty($this->table)) $db->setTbl($this->table);
        if (empty($this->pkValue)) $this->pkValue = CUrl::segment(3);
        if (!isset($this->value)) {
            if (!empty($this->pk)) $db->pk = $this->pk;
            $this->value = $db->getByPk($this->pkValue, $this->condition);
        }
        if (empty($this->value) && empty($this->additional)) {
            if ($strict !== FALSE) return self::NOTFOUND;
            elseif (!is_array($this->additional)) return self::NOTFOUND;
        }
        $i = 0;
        if (!empty($this->value)) {
            if (!empty($this->return)) {
                if (strpos($this->return, ',') !== FALSE) {
                    $this->return = explode(',', $this->return);
                }
                if (is_array($this->return)) {
                    foreach ($this->return as $field) {
                        if (isset($this->value->$field)) $this->returnResult[$field] = $this->value->$field;
                    }
                } elseif (is_string($this->return)) {
                    $field = $this->return;
                    if (isset($this->value->$field)) $this->returnResult = $this->value->$field;
                }
            }
        }
        if ($this->twoColumn !== FALSE) $table.= $this->twoColumnMode();
        else $table.= $this->nColumnMode();
        /*if(is_array($this->additional))
        {
        if($this->twoColumn !== FALSE)
        $table .= $this->twoColumnMode(TRUE);
            else
                $table .= $this->nColumnMode(TRUE);
        }*/
        $table.= '</table>';
        return $table;
    }
    private function twoColumnMode() {
        $label = new CLabel;
        $table = '';
        $i = 0;
        if (!empty($this->value)) {
            if (is_array($this->headers)) {
                foreach ($this->headers as $key => $field) {
                    $class = ($i % 2 == 0) ? 'even' : 'odd';
                    $table.= '<tr class="' . $class . '"><th>';
                    if (is_string($key)) //user has set the value for header('field_in_tbl'=>'header')
                    {
                        if (is_array($field)) {
                            if (isset($field['label'])) //user has set the value e.g. header=array('field_in_tbl'=>'label') or header=array('field_in_tbl'=>array('format','label')
                            {
                                $table.= $field['label'];
                            } else {
                                $table.= $label->getLabel($key);
                            }
                            $data = $this->value->$key;
                            if (isset($field['format'])) {
                                if (is_array($field['format'])) {
                                    foreach ($field['format'] as $format) $data = $this->setDisplay($data, $format);
                                } else $data = $this->setDisplay($data, $field['format']);
                            }
                        } else {
                            $table.= $field;
                            $data = $this->value->$key;
                        }
                    } else {
                        $table.= $label->getLabel($field);
                        $data = $this->value->$field;
                    }
                    $table.= '</th>';
                    $table.= '<td>' . $data . '</td>';
                    $table.= '</tr>';
                    $i++;
                }
            }
        }
        if (is_array($this->additional)) {
            foreach ($this->additional as $key => $value) {
                $class = ($i % 2 == 0) ? 'even' : 'odd';
                $table.= '<tr class="' . $class . '"><th>';
                $table.= $key;
                $table.= '</th>';
                $table.= '<td>' . $value . '</td>';
                $table.= '</tr>';
                $i++;
            }
        }
        return $table;
    }
    //header=array('field_in_tbl'=>'label') or header=array('field_in_tbl'=>array('format','label') or header=array('field_in_tbl','field_in_tbl2')
    private function nColumnMode() {
        $label = new CLabel;
        $values = array();
        if (!empty($this->value)) {
            if (is_array($this->headers)) {
                foreach ($this->headers as $key => $field) {
                    $row = '';
                    if (is_string($key)) {
                        if (is_array($field)) {
                            if (isset($field['label'])) $row.= '<b>' . $field['label'] . '</b>';
                            else $row.= '<b>' . $label->getLabel($key) . '</b>';
                            $row.= ': ';
                            $data = $this->value->$key;
                            if (isset($field['format'])) {
                                if (is_array($field['format'])) {
                                    foreach ($field['format'] as $format) $data = $this->setDisplay($data, $format);
                                } else $data = $this->setDisplay($data, $field['format']);
                            }
                            $row.= $data;
                        }
                        //$field is label
                        else {
                            $row.= '<b>' . $field . '</b>';
                            $row.= ': ';
                            $row.= $this->value->$key;
                        }
                    } else {
                        $row.= '<b>' . $label->getLabel($field) . '</b>';
                        $row.= ': ';
                        $row.= $this->value->$field;
                    }
                    $values[] = $row;
                }
            }
        }
        if (is_array($this->additional)) {
            foreach ($this->additional as $label => $value) {
                $temp = '<b>' . $label . '</b>:';
                $temp.= $value;
                $values[] = $temp;
            }
        }
        if (!empty($values)) {
            $table = '';
            $maxTr = count($values);
            $maxTr = $maxTr / $this->numberOfColumns;
            $maxTr = ceil($maxTr);
            for ($i = 0;$i < $maxTr;$i++) {
                $table.= '<tr>';
                for ($j = 0;$j < $this->numberOfColumns;$j++) {
                    $table.= '<td>';
                    $x = $i * $this->numberOfColumns + $j;
                    if (isset($values[$x])) $table.= $values[$x];
                    $table.= '</td>';
                }
                $table.= '</tr>';
            }
        }
        $table.= '</tr>';
        return $table;
    }
    public function getReturnResult() {
        return $this->returnResult;
    }
}
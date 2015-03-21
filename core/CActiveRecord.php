<?php
require('ARSingleton.php');
class CActiveRecord extends CDatabase
{
    private $fields;
    private $table;
    public function __construct($table = '')
    {
        parent::__construct();
        $this->table = $this->getTbl($table);
        $this->fields = $this->columnInfo('Field');
    }
    
    public function readBy($field,$value)
    {
        if(! in_array($field, $this->fields))
            return FALSE;
        $sql = "SELECT * FROM $this->table WHERE $field='$value'";
        $result = $this->execute($sql);
        $rows = array();
        while($row = $result->fetch_object())
        {   
           $rows[] = ARSingleton::get()->setFields($this->fields,$row);
        }
        
        if(empty($rows))
            return FALSE;
        if(count($rows) == 1)
            return $rows[0];
        return $rows;
    }
    
    public function f()
    {
        return ARSingleton::f($this->fields);
    }
    
    public function create()
    {
    }
}

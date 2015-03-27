<?php
class CDatabase {
    public $a;
    public $b;
    private $d = array();
    private $e;
    private $f;
    private $g;
    public function __construct($e = '') {
        $this -> db = !empty($e) ? $e : 'default';
        if (isset(PHP40::get() -> database[$this -> db])) {
            $this -> config = PHP40::get() -> database[$this -> db];
        }
        $this -> connection[$this -> db] = $this -> connect();
    }

    public function connect() {
        if (empty($this -> connection[$this -> db])) {
            $h = new mysqli($this -> config['host'], $this -> config['user'], $this -> config['password'], $this -> config['database']) or die('connection error');
            $h -> set_charset($this -> config['charset']);
            return $h;
        }
        return $this -> connection[$this -> db];
    }

    public function queryAll($i, $j = FALSE) {
        $k = $this -> execute($i);
        $l = array();
        if ($j !== FALSE) {
            while ($m = $k -> fetch_assoc()) {
                $l[] = $m;
            }
        } else {
            while ($m = $k -> fetch_object()) {
                $l[] = $m;
            }
        }
        if (empty($l))
            return FALSE;
        return $l;
    }

    public function queryToArray($i, $n) {
        $k = $this -> execute($i);
        $l = array();
        while ($m = $k -> fetch_assoc()) {
            foreach ($n as $o => $p) {
                if (isset($m[$o]) && isset($m[$p]))
                    $l[$m[$o]] = $m[$p];
            }
        }
        if (empty($l))
            return FALSE;
        return $l;
    }

    public function escape($q, $r = TRUE) {
        if (is_array($q)) {
            $k = array();
            foreach ($q as $s => $t) {
                if ($r == TRUE) {
                    if (isset($_POST[$t]))
                        $k[$t] = $this -> connection[$this -> db] -> real_escape_string(trim($_POST[$t]));
                } else {
                    $k[$s] = $this -> connection[$this -> db] -> real_escape_string(trim($t));
                }
            }
            return $k;
        }
        return $this -> connection[$this -> db] -> real_escape_string(trim($q));
    }

    public function lastId() {
        $k = $this -> connection[$this -> db] -> insert_id;
        if ($k == 0)
            return FALSE;
        return $k;
    }

    public function queryOne($i, $j = FALSE) {
        $k = $this -> execute($i);
        if ($k === FALSE) {
            return FALSE;
        }
        if ($j !== FALSE) {
            $k = $k -> fetch_assoc();
        } else {
            $k = $k -> fetch_object();
        }
        if (empty($k)) {
            return FALSE;
        }
        return $k;
    }

    public function execute($i) {
        $k = $this -> connection[$this -> db] -> query($i);
        if (empty($k)) {
            $u = 'Could not successfully run query';
            if (PHP40::get() -> debug === TRUE)
                $u .= " ($i) from DB: " . $this -> connection[$this -> db] -> error;
            echo $u;
            return FALSE;
        } else
            return $k;
    }

    public function countRows($i) {
        $k = $this -> execute($i);
        if ($k === FALSE)
            return0;
        $v = $k -> fetch_array(MYSQLI_ASSOC);
        return $v['COUNT(*)'];
    }

    public function avgRows($w, $i) {
        $k = $this -> execute($i);
        if ($k === FALSE)
            return NULL;
        $v = $k -> fetch_array(MYSQLI_ASSOC);
        $x = 'AVG(' . $w . ')';
        return $v[$x];
    }

    public function sumRows($w, $i) {
        $k = $this -> execute($i);
        if ($k === FALSE)
            return0;
        $v = $k -> fetch_array(MYSQLI_ASSOC);
        $x = 'SUM(' . $w . ')';
        if ($v[$x] === NULL)
            $v[$x] = 0;
        return $v[$x];
    }

    public function getCountRows($y = '') {
        $z = $this -> getTbl();
        $i = 'SELECT COUNT(*) FROM ' . $z;
        if (is_array($y))
            $i .= ' ' . $this -> condition($y);
        elseif (!empty($y))
            $i .= " $y";
        return $this -> countRows($i);
    }

    public function getAvg($w, $y = '') {
        $z = $this -> getTbl();
        $i = 'SELECT AVG(' . $w . ') FROM ' . $z;
        if (is_array($y))
            $i .= ' ' . $this -> condition($y);
        elseif (!empty($y))
            $i .= " $y";
        return $this -> avgRows($w, $i);
    }

    public function getSum($w, $y = '') {
        $z = $this -> getTbl();
        $i = 'SELECT SUM(' . $w . ') FROM ' . $z;
        if (is_array($y))
            $i .= ' ' . $this -> condition($y);
        elseif (!empty($y))
            $i .= " $y";
        return $this -> sumRows($w, $i);
    }

    private function getTblPerfix() {
        $aa = '';
        if (isset($this -> config['perfix'])) {
            $aa = $this -> config['perfix'];
        }
        return $aa;
    }

    public function getTbl($z = '') {
        if (!empty($this -> table))
            return $this -> table;
        elseif (empty($z))
            return $this -> getTblPerfix() . CUrl::segment(1);
        return $this -> getTblPerfix() . $z;
    }

    public function setTbl($z) {
        $this -> table = $z;
    }

    public function pkName() {
        if (!empty($this -> pk))
            return $this -> pk;
        $z = $this -> getTbl();
        $i = "SHOW KEYS FROM $z WHERE Key_name = 'PRIMARY'";
        $bb = $this -> queryOne($i, TRUE);
        if ($bb === FALSE) {
            return FALSE;
        }
        return $bb['Column_name'];
    }

    public function getByPk($b, $cc = '', $dd = '') {
        $bb = $this -> pkName();
        if ($bb === FALSE)
            return FALSE;
        $z = $this -> getTbl();
        if (!empty($dd) && strpos($dd, $bb) === FALSE) {
            $dd = $z . '.' . $bb . ',' . $dd;
        }
        $b = $this -> escape($b);
        $dd = !empty($dd) ? $dd : '*';
        $i = "SELECT $dd FROM $z WHERE $bb = '$b'";
        if (!empty($cc)) {
            $ee = $this -> condition($cc);
            $ee = str_replace('WHERE', ' AND', $ee);
            $i .= $ee;
        }
        return $this -> queryOne($i);
    }

    public function getByPkOnly($b, $q, $ff = '') {
        $k = $this -> getByPk($b, $ff, $q);
        if ($k !== FALSE) {
            return $k -> $q;
        }
        return FALSE;
    }

    public function getAll($gg = '', $dd = '') {
        $z = $this -> getTbl();
        $bb = $this -> pkName();
        if (!empty($dd) && strpos($dd, $bb) === FALSE) {
            $dd = $z . '.' . $bb . ',' . $dd;
        }
        $dd = (!empty($dd)) ? $dd : '*';
        $i = "SELECT $dd FROM $z";
        if (!empty($gg)) {
            if (is_array($gg)) {
                $i .= $this -> condition($gg);
            } else {
                $i .= " $gg";
            }
        }
        return $this -> queryAll($i);
    }

    public function insert($hh = '') {
        $z = $this -> getTbl();
        $i = 'INSERT INTO ' . $z;
        if (is_array($hh)) {
            $ii = array_keys($hh);
            if (is_string($ii[0])) {
                $i .= ' (';
                $i .= implode(',', $ii);
                $i .= ') VALUES(';
                foreach ($hh as $s => $t) {
                    $i .= '\'';
                    $i .= $this -> escape($t);
                    $i .= '\',';
                }
                $i = rtrim($i, ',');
                $i .= ')';
            } else {
                $i .= ' VALUES(';
                foreach ($hh as $t) {
                    $i .= '\'';
                    $i .= $this -> escape($t);
                    $i .= '\',';
                }
                $i = rtrim($i, ',');
                $i .= ')';
            }
            return $this -> execute($i);
        } else {
            $jj = $this -> columnInfo('Field');
            if ($jj === FALSE)
                return FALSE;
            $i .= ' (';
            $i .= implode(',', $jj);
            $i .= ') VALUES(';
            foreach ($jj as $kk) {
                $i .= '\'';
                if (isset($this -> additional[$kk])) {
                    $i .= $this -> escape($this -> additional[$kk]);
                } elseif (isset($_POST[$kk])) {
                    $i .= $this -> escape($_POST[$kk]);
                }
                $i .= '\',';
            }
            if (!empty($this -> additional))
                $this -> additional = '';
            $i = rtrim($i, ',');
            $i .= ')';
            return $this -> execute($i);
        }
    }

    public function update($y = '', $hh = '') {
        $z = $this -> getTbl();
        $i = 'UPDATE ' . $z . ' SET ';
        if (is_array($hh)) {
            foreach ($hh as $s => $t) {
                $i .= $s . '=\'' . $this -> escape($t) . '\',';
            }
            $i = rtrim($i, ',');
        } else {
            $jj = $this -> columnInfo('Field');
            if ($jj === FALSE)
                return FALSE;
            foreach ($jj as $kk) {
                if (isset($this -> additional[$kk])) {
                    $i .= $kk . '=\'' . $this -> escape($this -> additional[$kk]) . '\',';
                } elseif (isset($_POST[$kk])) {
                    $i .= $kk . '=\'' . $this -> escape($_POST[$kk]) . '\',';
                }
            }
            if (!empty($this -> additional))
                $this -> additional = '';
            $i = rtrim($i, ',');
        }
        if ($y === '') {
            $b = $this -> pkName();
            if ($b !== FALSE) {
                $i .= ' WHERE ' . $this -> pkName() . '=\'' . CUrl::segment(3) . '\'';
            } else if (PHP40::get() -> debug !== FALSE) {
                echo 'Warning: No primary key is defined in ' . $z . ' table. Update will affect all rows!';
            }
        } else {
            if (is_array($y)) {
                $i .= ' ' . $this -> condition($y);
            } else {
                $i .= ' ' . $y;
            }
        }
        return $this -> execute($i);
    }

    public function delete($y) {
        $z = $this -> getTbl();
        $i = 'DELETE FROM ' . $z;
        if (is_array($y)) {
            $i .= ' ' . $this -> condition($y);
        } else {
            $i .= ' ' . $y;
        }
        return $this -> execute($i);
    }

    protected function columnInfo($q) {
        $ll = array('Field', 'Type', 'Null', 'Key', 'Default', 'Extra');
        if (!in_array($q, $ll)) {
            return FALSE;
        }
        $z = $this -> getTbl();
        $i = 'SHOW COLUMNS FROM ' . $z;
        $jj = $this -> queryAll($i, TRUE);
        if ($jj === FALSE)
            return FALSE;
        $k = array();
        foreach ($jj as $kk) {
            $k[] = $kk[$q];
        }
        return $k;
    }

    public function condition($gg) {
        $i = '';
        if (is_array($gg)) {
            if (isset($gg['where'])) {
                if (is_array($gg['where'])) {
                    $i .= $this -> where($gg['where']);
                }
            }
            if (isset($gg['group'])) {
                $i .= ' GROUP BY ' . $gg['group'];
            }
            if (isset($gg['having'])) {
                if (is_array($gg['having'])) {
                    foreach ($gg['having'] as $w => $t) {
                        $t = $this -> escape($t);
                        $mm[] = $w . '=\'' . $t . '\'';
                    }
                    $mm = implode(' AND ', $mm);
                    $i .= ' HAVING ' . $mm;
                }
            }
            if (isset($gg['order'])) {
                $i .= ' ORDER BY ' . $gg['order'];
            }
            if (isset($gg['limit'])) {
                $i .= ' LIMIT ' . $gg['limit'];
            } else {
                $ii = array('where', 'group', 'having', 'order', 'limit');
                $nn = array_keys($gg);
                $oo = FALSE;
                foreach ($ii as $s) {
                    if (array_search($s, $nn) !== FALSE) {
                        $oo = TRUE;
                        break;
                    }
                }
                if ($oo === FALSE)
                    $i .= $this -> where($gg);
            }
        } elseif (is_string($gg)) {
            $i .= $gg;
        }
        return $i;
    }

    public function where($y) {
        return ' WHERE ' . $this -> getAnd($y);
    }

    public function getAnd($y) {
        $mm = array();
        foreach ($y as $w => $t) {
            $t = $this -> escape($t);
            $mm[] = $w . '=\'' . $t . '\'';
        }
        return implode(' AND ', $mm);
    }

    public function eliminate($i, $pp) {
        $pp = strtolower($pp);
        if ($pp == 'order' || $pp == 'orderby') {
            $qq = strpos($i, 'ORDER BY');
            if ($qq === FALSE)
                return $i;
            $rr = substr($i, 0, $qq);
            if (($ss = strpos($i, 'LIMIT')) !== FALSE) {
                $ee = substr($i, $ss);
                $i = $rr . ' ' . $ee;
                return $i;
            }
            return $rr;
        }
    }

    public function queryToJson($i) {
        $k = $this -> execute($i);
        while ($m = $k -> fetch_assoc()) {
            $l['Records'][]=$m;
        }
        if (empty($l))
            return "{}";
        $l['Result']='OK';
        return json_encode($l);
    }

}
?>
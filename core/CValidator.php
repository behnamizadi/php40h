<?php
class CValidator extends CGeneral
{
	
	public function captcha($field)
	{
		$c = new CCaptcha;
		$c->captchaField = $field;
		return $c->validate();
	}
	public function match($data, $field)
	{
		if ( ! isset($_POST[$field]))
		{
			return FALSE;
		}
		return ($data !== $_POST[$field]) ? FALSE : TRUE;
	}
	
	public function minLength($data, $length)
	{
		//for the sake of UTF-8
		if (function_exists('mb_strlen'))
		{
			return (mb_strlen($data) < $length) ? FALSE : TRUE;
		}
		return (strlen($data) < $length) ? FALSE : TRUE;
	}
	
	public function length($data, $length)
	{
		if (function_exists('mb_strlen'))
		{
			return (mb_strlen($data) != $length) ? FALSE : TRUE;
		}
		return (strlen($data) != $length) ? FALSE : TRUE;	
	}
	
	public function maxLength($data, $length)
	{
		//for the sake of UTF-8
		if (function_exists('mb_strlen'))
		{
			return (mb_strlen($data) > $length) ? FALSE : TRUE;
		}
		return (strlen($data) > $length) ? FALSE : TRUE;
	}
	
	public function minValue($data, $min)
	{
		if ( ! is_numeric($data))
		{
			return FALSE;
		}
		return $data >= $min;	
	}
	
	public function maxValue($data, $max)
	{
		if ( ! is_numeric($data))
		{
			return FALSE;
		}
		return $data <= $max;	
	}
	//alpha: /^([a-z])+$/i
	//alpha_numeric: /^([a-z0-9])+$/i
	//alpha dash underscore: /^([-a-z0-9_-])+$/i
	public function pattern($data, $pattern)
	{
		return (preg_match($pattern,$data) === 0) ? FALSE : TRUE;
	}
	
	public function required($data)
	{
		if ( ! is_array($data))
		{
			if(trim($data) == '')
			{
				return FALSE;
			}
			return TRUE;
		}
		else
		{
			if(empty($data))
			{
				return FALSE;
			}
			return TRUE;
		}
	
	}
	
	public function number($data)
	{
		return is_numeric($data);
	} 
	
	public function intNumber($data)
	{
		return (preg_match('/^\s*[+-]?\d+\s*$/',$data) === 0) ? FALSE : TRUE;
	}
	
	public function natural($data)
	{
		if($this->intNumber($data) === TRUE && $data > 0)
			return TRUE;
		return FALSE;
	}
	
	
	public function email($data)
	{
		$pattern='/^[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/';
		return is_string($data) && preg_match($pattern,$data);
	}
	
	
	public function url($data)
	{
		if(is_string($data) && strlen($data)<2000)
		{
			$pattern='/^(http|https):\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)/i';
			return preg_match($pattern,$data);
		}
		return FALSE;
	}
	
	public function ip4($data)
	{
		$ip_segments = explode('.', $data);
		// Always 4 segments needed
		if (count($ip_segments) != 4)
		{
			return FALSE;
		}
		// IP can not start with 0
		if ($ip_segments[0][0] == '0')
		{
			return FALSE;
		}
		// Check each segment
		foreach ($ip_segments as $segment)
		{
			// IP segments must be digits and can not be
			// longer than 3 digits or greater than 255
			if ($segment == '' OR preg_match("/[^0-9]/", $segment) OR $segment > 255 OR strlen($segment) > 3)
			{
				return FALSE;
			}
		}
		return TRUE;
	}

	public function unique($table, $field, $data)
	{
		$db = new CDatabase;
		$data = $db->escape($data);
		$sql = "SELECT COUNT(*) FROM $table WHERE $field = '$data'";
		if($db->countRows($sql) > 0)
		{
			return FALSE;
		}
		return TRUE;
	}
}
?>

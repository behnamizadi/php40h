<?php
session_start();
class CSession
{
	public function set($data,$value = '')
	{
		if(is_array($data))
		{
			foreach($data as $k=>$v)
			{
				$_SESSION[$k] = $v;
			}
		}
		else 
		{
			$_SESSION[$data] = $value;
		}
	}
	public function get($key)
	{
		if(isset($_SESSION[$key]))
			return $_SESSION[$key];
		return FALSE;
	}
	
	public function delete($key)
	{
		if(is_array($key))
		{
			foreach($key as $a => $b)
			{
				unset($_SESSION[$a][$b]);
			}
		}
		else 
		{
			unset($_SESSION[$key]);
		}
	}
	
	private function getKeys($array)
	{
		if(is_array($array))
		{
			foreach($array as $key => $value)
			{
				$this->keys[] = $key;
				$this->getKeys($value);
			}
		}
		else 
		{
			return;
		}
	}
}

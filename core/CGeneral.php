<?php
class CGeneral
{
	protected function getConfig($type,$method = '')
	{
		if(empty($method))
			$method = CUrl::segment(2);
        if(empty($method))
        {
            $method = PHP40::get()->route['defaultAction'];
        }
		$file = APP_ROOT.'config/'.$type.'/'.PHP40::get()->NOW.'.php';
		if(file_exists($file))
		{
			$configs = require($file);
			$config = FALSE;
			if(is_array($configs))
			{
				foreach ($configs as $key => $value) 
				{
				    if($key == '*')
                    {
                        $config = $configs[$key];
                        break;
                    }
                    else //multiple actions seperated by comma
					{
						$actions = explode(',', $key);
						if(array_search($method, $actions) !== FALSE)
						{
							$config = $configs[$key];
							break;
						}
					}
				}
			}
			return $config;
		}
		return FALSE;
	}
	
	public static function generateRandom($numAlpha = 16)
	{
		$listAlpha = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		return str_shuffle(substr(str_shuffle($listAlpha),0,$numAlpha));
	}
	
	public static function makeUrlQuery($param)
	{
		$route = htmlspecialchars($_SERVER['PHP_SELF'] );
		$q = '';
		if(!empty($_SERVER['QUERY_STRING']))
		{
			$querise = explode('&', $_SERVER['QUERY_STRING']);
			foreach ($querise as $value) 
			{
				$end = strrpos($value, '=');
				$tmp = substr($value, 0, $end);
				if($tmp != $param)
				{
					$q = $value;
				}
			}
			if(!empty($q))
				$route .= '?'.$q.'&'.$param.'=';
			else 
				$route .= '?'.$param.'=';
		}
		else 
		{
			$route .= '?'.$param.'=';
		}
		return $route;
	}
	
	public static function isPostRequest()
	{
		return isset($_SERVER['REQUEST_METHOD']) && !strcasecmp($_SERVER['REQUEST_METHOD'],'POST');
	}
}

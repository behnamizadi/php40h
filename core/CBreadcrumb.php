<?php
class CBreadcrumb extends CGeneral
{
	public $ulCss = 'breadcrumbUl';
	public $liCss = 'breadcrumbLi';
	public $extra;
    
    private $config;
     
    public function __construct($method = '')
    {
        $this->config = $this->getConfig('breadcrumb',$method);
    }
	public function run()
	{
	    if(! is_array($this->config))
        {
            return FALSE;
        }
		$body = '<ul';
		if(! empty($this->ulCss))
		{
			$body .= ' class="'.$this->ulCss.'"';
		}
		$body .= '>';
		foreach ($this->config as $text => $url) {
			$body .= '<li';
			if(! empty($this->liCss))
			{
				$body .= ' class="'.$this->liCss.'"';
			}
			$body .= '>';
			if(is_string($text))
			{
				if(strpos($url, '/') === FALSE) //url is in this controller
				{
					$url = CUrl::segment(1).'/'.$url;
				}
				$body .= CUrl::createLink($text,$url);
				$body .= ' >> ';
			}
			else {
				$body .= $url;
			}
			$body .= '</li>';
		}
		if(isset($this->extra))
		{
			$body .= '<li';
			if(! empty($this->liCss))
			{
				$body .= ' class="'.$this->liCss.'"';
			}
			$body .= '>'.$this->extra;
			$body .= '</li>';
		}
		$body .= '</ul>';
		return $body;
	}
	
	/*private function getConfig($method)
	{
		if(function_exists('breadcrumb'))
		{ 
			$configs = breadcrumb();
			$config = '';
			foreach ($configs as $key => $value) 
			{
				if(strpos($key,',') !== FALSE) //multiple actions seperated by comma
				{
					$actions = explode(',', $key);
					if(array_search($method, $actions) !== FALSE)
					{
						$config = $configs[$key];
						break;
					}
				}
				else 
				{
					break;	
				}
			}
			if(! is_array($config))
			{ 
				if(isset($configs[$method]))
				{
					return $configs[$method];
				}
				elseif(PHP40::get()->debug === TRUE)
				{
					echo $method.' is not found in breadcrumb() function for '.CUrl::segment(1).'.php.';
				}
				return FALSE;
			}
			return $config;
		}
		elseif(PHP40::get()->debug === TRUE)
		{
			echo 'breadcrumb() function is not exist for '.CUrl::segment(1).'.php.';
		}
		return FALSE;
	}*/
}

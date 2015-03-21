<?php
class CUrl
{
	public static function redirect($url,$in = TRUE)
	{
		if(isset(PHP40::get()->view[$url]))
		{
			$view = new CView;
                        if($url == '404' || $url == '401')
                             $view->layout = 'error';
			$view->title = $url;
			$view->run(PHP40::get()->view[$url]);
		}
		elseif($in === TRUE)
		{
			self::setReturnUrl();
			$url = self::createUrl($url);	
		}
		if (!headers_sent())
		{
			header("Location: ".$url);
			exit();
		}
		else {
			echo'
				<script>
				window.location.href = "'.$url.'";
				</script>
				';
		}
	}
	public static function createUrl($url, $in = TRUE)
	{
		$url = trim($url,'/');
        $index = 'index.php/';
        if(PHP40::get()->route['showIndex'] == FALSE)
            $index = '';
		if($in === TRUE)
			$url = PHP40::get()->homeUrl.$index.$url;
		return $url;
	}
	
	
	public static function segment($i = 1)
	{
		if(isset($_SERVER['PATH_INFO']))
		{
			$urlParts = explode('/',$_SERVER['PATH_INFO']);
			if(!empty($urlParts[$i]))
			{
				$search=array("\\\\","\\0","\\n","\\r","\Z","\'",'\"');
				$replace=array("\\","\0","\n","\r","\x1a","'",'"');
				return str_replace($search,$replace,$urlParts[$i]);
			}
			return FALSE;
		}
		return FALSE;
	}
	
	public static function createLink($text, $url = '#', $other = '', $in = TRUE)
	{
		$output = '<a href="';
		if($in !== TRUE || $url == '#')
		{
			$output .= $url;
		}
		else 
		{
			$output .= self::createUrl($url);
		}
		$output .= '" '.$other.'>';
		$output .= $text.'</a> ';
		return $output;
	}
	
	private static function setReturnUrl()
	{
		//setting returnUrl
		$returnUrl = '';
		if(isset($_SERVER['PATH_INFO']))
		{
			$returnUrl .= trim($_SERVER['PATH_INFO'],'/');
			$returnUrl .= '/';
		}
		if(! empty($_SERVER['QUERY_STRING']))
		{
			$returnUrl .= '?'.$_SERVER['QUERY_STRING'];
		}
		//if(strpos($returnUrl, 'user/login') === FALSE)	
			//CSession::set('returnUrl',$returnUrl);
	}
}

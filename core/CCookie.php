<?php
class CCookie
{
	public function delete($cookieName)
	{
		if(isset($_COOKIE[$cookieName]))
		{
			setcookie($cookieName,'',time()-3600,'/');
		}
	}
}

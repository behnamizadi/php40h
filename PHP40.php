<?php
class PHP40
{
	private static $instance;

	private function __construct()
	{
	}
	public function __clone()
	{
		if(isset( self::$instance ) )
			return self::$instance;
		return FALSE;
	}
	
	public static function get()
	{
		if( !isset( self::$instance ) )
		{
			self::$instance = new PHP40();
		}
		return self::$instance;
	}
	
	public function init($config)
	{
		if(file_exists($config))
		{
			$values = require($config);
			foreach($values as $key=>$value)
			{
				self::get()->$key = $value;
			}
		}
		//setting initial auth data
		$this->setAuthData();
        $session = new CSession;
		if(($returnUrl =$session->get('returnUrl')) !== FALSE)
			self::get()->returnUrl = $returnUrl;
		self::get()->NOW = $this->getNow($values['route']['defaultController']);
		$route = new CRoute;
		$route->run();
	}
	
	private function getNow($defaultController)
	{
		return (CUrl::segment(1) === FALSE) ? $defaultController : CUrl::segment(1);
	}
	
	public static function autoload($className)
	{
		$dir = dirname(__FILE__).DIRECTORY_SEPARATOR;
		switch($className)
		{
			case 'CAcl':
				$file = $dir.'core/CAcl.php';
				break;
            case 'CActiveRecord':
                $file = $dir.'core/CActiveRecord.php';
                break;
			case 'CAuth':
				$file = $dir.'core/CAuth.php';
				break;
			case 'CBreadcrumb':
				$file = $dir.'core/CBreadcrumb.php';
				break;
			case 'CCaptcha':
				$file = $dir.'core/captcha/CCaptcha.php';
				break;
			case 'CCookie':
				$file = $dir.'core/CCookie.php';
				break;
			case 'CDatabase':
				$file = $dir.'core/CDatabase.php';
				break;
			case 'CDetail':
				$file = $dir.'core/CDetail.php';
				break;
			case 'CForm':
				$file = $dir.'core/CForm.php';
				break;
			case 'CGDL':
				$file = $dir.'core/CGDL.php';
				break;
			case 'CGeneral':
				$file = $dir.'core/CGeneral.php';
				break;
			case 'CGmail':
				$file = $dir.'core/CGmail.php';
				break;
			case 'CGrid':
                $file = $dir.'core/CGrid.php';
                break;
            case 'CJcalendar':
                $file = $dir.'core/CJcalendar.php';
                break;
			case 'CLabel':
				$file = $dir.'core/CLabel.php';
				break;
			case 'CList':
				$file = $dir.'core/CList.php';
				break;
			case 'CLookup':
				$file = $dir.'core/CLookup.php';
				break;
			case 'CPagination':
				$file = $dir.'core/CPagination.php';
				break;
			case 'CRoute':
				$file = $dir.'core/CRoute.php';
				break;
			case 'CSession':
				$file = $dir.'core/CSession.php';
				break;
			case 'CUpload':
				$file = $dir.'core/CUpload.php';
				break;
			case 'CUrl':
				$file = $dir.'core/CUrl.php';
				break;
			case 'CValidator':
				$file = $dir.'core/CValidator.php';
				break;
			case 'CView':
				$file = $dir.'core/CView.php';
				break;
			case 'PHPMailer':
				$file = $dir.'core/phpmailer/class.phpmailer.php';
				break;
			default:
				$file = APP_ROOT.'models/'.$className.'.php';	
		}
		if (file_exists($file))
	    {
	        require_once($file);
	    }
		else 
		{
			echo $className.' is not ready!';			
		}	
	}
	public function setData($data)
	{
		if(is_array($data))
		{
			foreach ($data as $key => $value) 
			{
				self::get()->$key = $value;
			}
		}
	}
	private function setAuthData()
	{
		$auth = new CAuth;
		$data = $auth->authorize();
		if(is_array($data))
		{
			foreach ($data as $key => $value) 
			{
				self::get()->$key = $value;
			}
		}
	}
}
spl_autoload_register("PHP40::autoload");

<?php
class CCaptcha
{
	public $backgroundImage = "white2.png";
	public $backgroundColor = "BFBFBF";
	public $height = 40;
	public $width = 150;
	public $fontSize = 23;
	public $font="Duality.ttf";
	//text angle to the left
	public $textMinimumAngle = 10;
	//text angle to the right
	public $textMaximumAngle = 25;
	//Text Color - HEX
	public $textColor = '000000';
	//Number of Captcha Code Character
	public $textLength = 6;
	//Background Image transparency
	public $transparency = 50;
	public $lowCase = TRUE;
	public $captchaField = 'captcha';
	public $pattern;
	
	private $code;
	
	//private static $config;

    public function getCode()
    {
		if($this->lowCase == TRUE)
			return strtolower($this->code);
        return $this->code;
    }
    
	public function validate()
	{
		$session = new CSession;
		$code = $session->get('captcha');
		if(isset($_POST[$this->captchaField]))
		{
			$captchaField = $_POST[$this->captchaField];
			if($this->lowCase == TRUE)
				$captchaField = strtolower($_POST[$this->captchaField]);
			if(md5($captchaField) == $code)
				return TRUE;
			return FALSE;
		}
		return FALSE;
	}
	
	/*public function captchaField()
	{
		$output = '<label>کد امنیتی<span class="error">*</span></label><input type="text" class="txt" name="'.$this->captchaField.'" id="'.$this->captchaField.'" />';
		return $output;
	}
	
	public function captchaImage()
	{
		$this->setConfig();
		
		$output = '<img src="'.PATH.'application/core/captcha/file.php" alt="captcha" width="'.$this->width.'" height="'.$this->height.'" />';
		//$output = '<img src="';
		//include_once('file.php');
		//$output .= '" alt="captcha" width="'.$this->width.'" height="'.$this->height.'" />';
		return $output;
	}*/
	
	public function generateImage()
	{
		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Content-Transfer-Encoding: binary');
		//header("Content-type: image/png");
		$backgroundImage = dirname(__FILE__).DIRECTORY_SEPARATOR.$this->backgroundImage;
		$image = imagecreatefrompng($backgroundImage);
		$txcolor = $this->colorDecode($this->textColor);
		$bgcolor = $this->colorDecode($this->backgroundColor);
		$image2 = imagecreate($this->width,$this->height);
		$imgColor = imagecolorallocate($image2, $bgcolor['red'], $bgcolor['green'], $bgcolor['blue']);
		imagecopymerge($image2,$image,0,0,0,0,$this->width,$this->height,$this->transparency);
		$textcolor = imagecolorallocate($image2, $txcolor['red'], $txcolor['green'], $txcolor['blue']);
		$font = dirname(__FILE__).DIRECTORY_SEPARATOR.$this->font;
		if(!file_exists($font) && PHP40::app()->config('debug') === TRUE)
		{
			echo 'font does not exists';
		}
		for($i = 0; $i < $this->textLength; $i++)
		{
			imagettftext(
	             $image2,
	             $this->fontSize,
	             rand(-($this->textMinimumAngle),$this->textMaximumAngle),
	             $i*23+10,
	             $this->fontSize*1.2,
	             $textcolor,
	             $font,
	             substr($this->code, $i, 1));
		}
		imagepng($image2);
		imagedestroy($image2);
	}

	/*public function getConfig()
	{
		if(!empty(self::$config))
		{
			$this->backgroundImage = self::$config['backgroundImage'];
			$this->backgroundColor = self::$config['backgroundColor'];
			$this->height = self::$config['height'];
			$this->width = self::$config['width'];
			$this->fontSize = self::$config['fontSize'];
			$this->font = self::$config['font'];
			$this->textMinimumAngle = self::$config['textMinimumAngle'];
			$this->textMaximumAngle = self::$config['textMaximumAngle'];
			$this->textColor = self::$config['textColor'];
			$this->textLength = self::$config['textLength'];
			$this->transparency = self::$config['transparency'];
			$this->captchaField = self::$config['captchaField'];
			$this->pattern = self::$config['pattern'];
		}
	}
	
	private function setConfig()
	{
		self::$config = array(
			'backgroundImage'=>$this->backgroundImage,
			'backgroundColor'=>$this->backgroundColor,
			'height'=>$this->height,
			'width'=>$this->width,
			'fontSize'=>$this->fontSize,
			'font'=>$this->font,
			'textMinimumAngle'=>$this->textMinimumAngle,
			'textMaximumAngle'=>$this->textMaximumAngle,
			'textColor'=>$this->textColor,
			'textLength'=>$this->textLength,
			'transparency'=>$this->transparency,
			'captchaField'=>$this->captchaField,
			'pattern'=>$this->pattern,
		);
	}*/
	
	/*public function setCode()
	{
		$session = new Session;
		$session->setSession('captcha',$this->code);
	}*/
	public function generateRandom()
	{
		if(!empty($this->pattern))
			$listAlpha = $this->pattern;
		else
			$listAlpha = 'ABCDEFGHIJKLMNPQRSTUVWXYZabcdefghijklmnpqrstuvwxyz';
		$this->code = str_shuffle(substr(str_shuffle($listAlpha),0,$this->textLength));
	}
	
	private function colorDecode($hex)
	{
	   $decoded['red'] = hexdec(substr($hex, 0 ,2));
	   $decoded['green'] = hexdec(substr($hex, 2 ,2));
	   $decoded['blue'] = hexdec(substr($hex, 4 ,2));
	   return $decoded;

	}
}
?>

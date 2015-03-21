<?php
class CLabel extends CGeneral
{
    private $config;
    private $method;
    
    public function __construct($method = '')
    {
        $this->method = $method;
        $this->config = $this->getConfig('label',$method);
    }
	public function getLabel($field)
	{
		if($field == 'captcha')
		{
			$label = 'کد امنیتی';
		}
		elseif(!empty($this->config[$field]))
		{
			$label = $this->config[$field];
		}
		else
		{
			$replace = array('_'=>' ','-'=>' ','.'=>' ');
			$label = ucfirst(strtr($field,$replace));
		}
		/*if($star !== FALSE)
		{
		    if(($rules = $this->getConfig('validation',$this->method)) !== FALSE)
            {
                if(is_array($rules) && array_key_exists($field, $rules))
                {
                    if(strpos($rules[$field][0],'required') !== FALSE)
                    {
                        $label .= '<span class="error">*</span>';
                    }
                }
            }
		}*/
		return $label;
	}
    
    public function getAllLabels()
    {
        return $this->config;        
    }
}
?>

<?php
function encode($param)
{
	return htmlspecialchars($param,ENT_QUOTES,"UTF-8");	
}



function validateFile($file,$field_name,$type = 'image')
{
	$format = array();
	$sizeOK = false;
	$typeOK = false;
	$result = array(
			'error'=>'',
			'file_name'=>'');
	switch($type)
	{
		case 'image':
			$format = array(
				'image/gif',
				'image/jpeg',
				'image/jpg',
				'image/pjpeg',
				'image/png',
				);	
			
	}
	if ($_FILES["$field_name"]["size"] > MAX_FILE_SIZE) 
	{
  		$result['error'] = 'حداکثر اندازه تصویر '.(MAX_FILE_SIZE/1024).' کیلوبایت می باشد.';
		return $result;	
	}
	foreach ($format as $type) 
	{
		if ($type == $_FILES["$field_name"]["type"]) 
		{
			$typeOK = true;
			break;
	  	}
	}
	if($typeOK == false && $_FILES["$field_name"]["type"])
	{
		$result['error'] = 'تصاویر با فرمت های jpg, gif, png, jpeg قابل قبول هستند';
		return $result;	
	}
	switch($_FILES["$field_name"]["error"])
	{
		case 0: //no problem
			$parts=ereg("^(image/)([[:alnum:]]+)",$_FILES["$field_name"]["type"],$regs);
			if($regs[2]=='pjpeg')
	  			$regs[2]='jpg';	
			$name = time().'_'.generateRandom().'.'.$regs[2];
			if(move_uploaded_file($_FILES["$field_name"]["tmp_name"],PIC_DIR.$name))
			{
				$result['file_name'] = $name;
				return $result;
			}
			else
			{
				$result['error'] = 'مشکلی در بارگذاری تصویر پیش آمد.';
				return $result;	
			}
			break;
		case 4:
			$result['error'] = 4;
			return $result;	
		default:

				$result['error'] = 'مشکلی در بارگذاری تصویر پیش آمد.';
				return $result;	
	}
}

function generateRandom($numAlpha=3)
{
	$listAlpha = 'abcdefghijklmnopqrstuvwxyz0123456789';
	return str_shuffle(substr(str_shuffle($listAlpha),0,$numAlpha));	
}
?>
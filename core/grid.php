<?php
/*
@param headers = array(
	'column'=>'label',
	....
	);
@param $model: returned from db::queryAll()
@param operations: functions in the controller 
*/
function grid($headers,$model,$operations = array(),$id = 'id')
{
	$output = '<table width="600" cellpadding="3">
			<tr>'; //start caption
	 foreach($headers as $label)
	 {
		 $output .= '<th scope="col" class="tbl_col">';
		 $output .= $label;
		 $output .= '</th>';
	 }
	 if(!empty($operations))
	 {
		 $output .= '<th scope="col" class="tbl_col">عمليات</th>';
	 }
	 $output .= '</tr>';
	 $i = 0;
	 foreach($model as $value)
	 {	 
		 $class = (($i%2) == 0) ? 'even' : 'odd';
		 $output .= '<tr class="'.$class.'">';
		 foreach($headers as $header=>$label)
		 {			 
		 	$output .= "<td>$value[$header]</td>";
		 }
		 if(!empty($operations))
		 {
			 $output .= generateOperations($operations,$value[$id]);
		 }
		 $i++;
		 $output .= '</tr>';
	 }
	 $output .= '</table>';
	 return $output;
}

function generateOperations($operations,$id)
{
	$output = '<td>';
	foreach($operations as $controller=>$methods)
	{
		foreach($methods as $label=>$method)
		{
			$output .= '|<a href="'.PATH.'index.php/'.$controller.'/'.$method.'?id='.$id.'" ';	
			if($label == 'delete')
			{
				$output .= 'onclick="return confirm(\'واقعا میخوای حذفش کنی؟\')"';	
			}
			$output .= '>'.$label.'</a>';
		}
	}
	$output .= '|</td>';
	return $output;
}
?>

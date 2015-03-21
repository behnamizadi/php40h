<?php

class CList extends CGDL
{
	public $type = '';
	public $field;
	public $css;
	public $liCss;
	public $anchor;
	
	public function run()
	{
		if(empty($this->field))
		{
			exit('No "field" is specified to be listed');
		}
		$output = '';
		if(! isset($this->values))
		{
			if($this->paginate == TRUE)
				$pResult = $this->paginate();
			$this->values = $this->getValue();
		}	
		else 
		{
			$sortField = $this->getSort();
			if($sortField !== FALSE)
			{
				$desc = FALSE;
				if(strpos($sortField,'DESC') !== FALSE)
				{
					$sortField = substr($sortField, 0,-5); // ' DESC'
					$desc = TRUE;
				}
				$this->values = $this->valueSort($sortField,$desc);
			}
			if($this->paginate == TRUE)
			{
				$count = count($this->values);
				$pResult = $this->paginate($count);
			}
		}
		if(empty($this->values))
		{
			return '<div class="red">موردی یافت نشد</div>';
		}
		$end = '</ul>';
		if($this->type == 'ul')
		{
			$output .= '<ul';
		}
		elseif($this->type == 'ol')
		{
			$output .= '<ol';
			$end = '</ol>';
		}
		else 
		{
			$output .= '<ul style="list-style:none;"';
		}
		if(isset($this->css))
		{
			$output .= ' class="'.$this->css.'"';
		}
		$output .= '>';
		$db = new CDatabase;
		foreach($this->values as $row)
		{
		    $this->value = $row;
			$output .= '<li';
			if(isset($this->liCss))
			{
				$output .= ' class="'.$this->liCss.'"';
			}
			$output .= '>';
			$text = '';
		    if(is_array($this->field))
			{
				if(isset($this->field[1]))
				{
					$text = $this->setDisplay($row->{$this->field[0]}, $this->field[1]);
				}
				else 
				{
					$text = $row->{$this->field[0]};
				}
			}
			else 
			{
				$text = $row->{$this->field};
			}
			
			if(is_array($this->anchor))
			{
				if(isset($this->anchor['url']))
				{
					$url = $this->getReal($this->anchor['url']);
					$title = '';
					if(isset($this->anchor['title']))
					{
						if(is_array($this->anchor['title'])) //has formet.e.g,'title'=>array('field','format')
						{
							if(isset($this->anchor['title'][1]))
							{
								$title = $this->setDisplay($row->{$this->anchor['title'][0]}, $this->anchor['title'][1]);
							}
							else
							{
								$title = $row->$this->anchor['title'][0];
							}
						}
						else 
						{
							$title = $row->$this->anchor['title'];
						}
					}
					$other = ' title="'.$title.'"';
					if(isset($this->anchor['target']))
						$other .= ' target="'.$this->anchor['target'].'"';
					if(isset($this->anchor['css']))
						$other .= ' class="'.$this->anchor['css'].'"';
					if(isset($this->anchor['other']))
						$other .= $this->anchor['other'].'"';
					$output .= CUrl::createLink($text,$url,$other);
				}
			}
			elseif(is_string($this->anchor))
			{
				$url = $this->getUrl($this->anchor, $row);
				$output .= CUrl::createLink($text,$url);
			}
			else 
			{
				$output .= $text;
			}
			$output .= '</li>';
		}
		$output .= $end;
		unset($this->values);
		return $output;
	}
	
}

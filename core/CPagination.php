<?php 
class CPagination
{
	//number of all Rows(SELECT COUNT(*) FROM tbl;)
	public $totalRows;
	// How many adjacent pages should be shown on each side?
	public $adjacents = 3;
	//how many items to show per page
	public $pageSize = 10;
	
	public $pageGet = 'page';
	
	public function run()
	{
		if($this->totalRows <= $this->pageSize)
		{
			return ' ';	
		}
		else
		{
			$page = empty($_GET[$this->pageGet]) ? 1 : $_GET[$this->pageGet];
			$prev = $page - 1;							//previous page is page - 1
			$next = $page + 1;
			$route = CGeneral::makeUrlQuery('page');
			$lastPage = ceil($this->totalRows/$this->pageSize);
			$pagination = '';
			if($lastPage > 1)
			{
				$pagination .= '<div class="pagination">';
				//previous button
				if ($page > 1) 
					$pagination.= '<a href="'.$route.$prev.'">« قبلی</a>';
				else
					$pagination.= '<span class="disabled">« قبلی</span>';	
			}
			//pages	
			if ($lastPage < 7 + ($this->adjacents * 2))	//not enough pages to bother breaking it up
			{	
				for ($counter = 1; $counter <= $lastPage; $counter++)
				{
					if ($counter == $page)
						$pagination.= ' <span class="current">'.$counter.'</span> ';
					else
						$pagination.=' <a href="'.$route.$counter.'">'.$counter.'</a> ';					
				}
			}
			elseif($lastPage > 5 + ($this->adjacents * 2))	//enough pages to hide some
			{
				//close to beginning; only hide later pages
				if($page < 1 + ($this->adjacents * 2))		
				{
					for ($counter = 1; $counter < 4 + ($this->adjacents * 2); $counter++)
					{
						if ($counter == $page)
							$pagination.= ' <span class="current">'.$counter.'</span> ';
						else
							$pagination.= ' <a href="'.$route.$counter.'">'.$counter.'</a> ';					
					}
					$lpm1 = $lastPage -1;
					$pagination.= "...";
					$pagination.= ' <a href="'.$route.$lpm1.'">'.$lpm1.'</a> ';
					$pagination.= ' <a href="'.$route.$lastPage.'">'.$lastPage.'</a> ';		
				}
				//in middle; hide some front and some back
				elseif($lastPage - ($this->adjacents * 2) > $page && $page > ($this->adjacents * 2))
				{
					$lpm1 = $lastPage -1;
					$pagination.= ' <a href="'.$route.'1">1</a> ';
					$pagination.= ' <a href="'.$route.'2">2</a> ';
					$pagination.= "...";
					for ($counter = $page - $this->adjacents; $counter <= $page + $this->adjacents; $counter++)
					{
						if ($counter == $page)
							$pagination.= ' <span class="current">'.$counter.'</span> ';
						else
							$pagination.= ' <a href="'.$route.$counter.'">'.$counter.'</a> ';					
					}
					$pagination.= " ... ";
					$pagination.= ' <a href="'.$route.$lpm1.'">'.$lpm1.'</a> ';
					$pagination.= ' <a href="'.$route.$lastPage.'">'.$lastPage.'</a> ';		
				}
				//close to end; only hide early pages
				else
				{
					$pagination.= ' <a href="'.$route.'1">1</a> ';
					$pagination.= ' <a href="'.$route.'2">2</a> ';
					$pagination.= " ... ";
					for ($counter = $lastPage - (2 + ($this->adjacents * 2)); $counter <= $lastPage; $counter++)
					{
						if ($counter == $page)
							$pagination.= ' <span class="current">'.$counter.'</span> ';
						else
							$pagination.= ' <a href="'.$route.$counter.'">'.$counter.'</a> ';					
					}
				}
			}
			//next button
			if ($page < $counter - 1) 
				$pagination.= '<a href="'.$route.$next.'">بعدی »</a>';
			else
				$pagination.= '<span class="disabled">بعدی »</span>';
			$pagination.= "</div>\n";
			return $pagination;
		}
	}
									
	
}
?>
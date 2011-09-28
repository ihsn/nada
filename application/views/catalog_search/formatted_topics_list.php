<?php 
echo create_topic_list($topics,$filter);

function create_topic_list($data,$filter=NULL)
{
		//var_dump($data);
		$result=NULL;
		
		//process parent items
		foreach($data as $key=>$row)
		{
			$tmp='';
			$row=(object)$row;

			$tmp.='<div class="topic-container">';
			$tmp.='<div class="topic-heading">';
			$tmp.='<input class="chk-topic-hd" name="topic[]" type="checkbox" id="t-'.$row->tid.'" value="'.$row->tid.'"/><label for="t-'.$row->tid.'">'.$row->title. "</label>";
			//$tmp.=count($row->children);
			$tmp.='</div>';
			
			$children='';
			
			//child terms	
			if (isset($row->children) && count($row->children)>0)
			{
				$children=process_sub_item($row->children,$filter);			

				if ($children!==FALSE)
				{
					$tmp.=$children;
					$tmp.='</div>';
					$result[]=$tmp;
				}
			}
			else
			{
					$tmp.='</div>';
					$result[]=$tmp;						
			}
			
		}

		$items_per_column=ceil(count($result)/2);
		$total_rows=count($result);
		
		//first column
		$output='<div class="left">';
		for($i=0;$i<$items_per_column;$i++)
		{
			$output.=$result[$i];
		}
		$output.='</div>';
		
		//second column
		$output.='<div class="left">';
		for($i=$items_per_column;$i<$total_rows;$i++)
		{
			$output.=$result[$i];
		}
		$output.='</div>';				
		return $output;
	}

	//show children of tid
	function process_sub_item($children,$filter=NULL)
	{
		$li='';
		foreach($children as $key=>$row)
		{			
			$row=(object)$row;
			if (is_array($filter))
			{
				if(in_array($row->tid, $filter))
				{			
					$li.='<li class="topic">';
					$li.='<input class="chk-topic" name="topic[]" type="checkbox" id="t-'.$row->tid.'" value="'.$row->tid.'"/>';
					$li.='<label for="t-'.$row->tid.'">'.$row->title.'</label></li>';
				}
			}			
			
			if($filter==NULL)
			{
					$li.='<li class="topic">';
					$li.='<input class="chk-topic" name="topic[]" type="checkbox" id="t-'.$row->tid.'" value="'.$row->tid.'"/>';
					$li.='<label for="t-'.$row->tid.'">'.$row->title.'</label></li>';			
			}	
		}
		
		if ($li=='')
		{
			return FALSE;
		}
		
		return '<ul class="topic-items">'.$li.'</ul>';		
	}
	
?>
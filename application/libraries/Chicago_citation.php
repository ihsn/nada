<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 
 * Chicago citations formatting class
 *
 *
 *
 */
class Chicago_Citation{
	
	var $ci;
	
    //constructor
	function __construct()
	{
		$this->ci =& get_instance();	
    }
	
	/**
	*
	* Format the citation according to Chicago style
	*
	*	@data	array
	*	@type	citation type - journal, book
	*/
	function format($data,$type)
	{
		switch($data["ctype"])
		{	
			case 'report':
			case 'book':
				return str_replace("..",".",$this->format_book($data));
				break;
			case 'journal':
				return str_replace("..",".",$this->format_journal($data));
				break;
			case 'conference-paper':			
				return str_replace("..",".",$this->format_conference_paper($data));
				break;
			break;
			case 'corporate-author':			
				return str_replace("..",".",$this->format_corporate_author($data));
				break;
			break;			
			case 'thesis':
				return str_replace("..",".",$this->format_thesis($data));
				break;
			break;
			
			default:
				return str_replace("..",".",$this->format_journal($data));
			break;	
		}	
	}
	
	/**
	* Formats a Thesis
	*
	**/
	function format_thesis($data)
	{
		$output='';
		
		//format authors
		$fields['authors']=$this->format_authors($data['authors']);
		
		//format title
		$fields['title']=sprintf('<span class="title">"%s."</span>',anchor('citations/'.$data['id'],$data['title']));
		
		//format subtitle,institute, year of preparation
		$thesis=array();
		if ($data["subtitle"]!="") 
		{
			$thesis["subtitle"]=$data["subtitle"];
		}	
		
		//institute
		if ($data["organization"]!="") 
		{
			$thesis["organization"]=$data["organization"];
		}	
		
		//year of preparation
		if ((integer)$data["pub_year"]>0) 
		{
			$thesis["pub_year"]=$data["pub_year"];
		}	
		
		//merge thesis
		$fields['tay']=implode(", ",$thesis);
					
		$output=implode(" ",$fields);
		
		return $output.".";	
	}


	/**
	* Formats a Corporate Author
	*
	**/
	function format_corporate_author($data)
	{
		$output='';
		
		//format corporate author
		if ($data['organization']!="")
		{
			$fields['authors']=$data['organization'].".";
		}	
		
		//format title
		$fields['title']=$this->format_periodical_title($data);
		
		//format city/country/state
//		$tmp["place"]=$this->format_city_country($data);
		$fields["place"]=$this->format_book_publisher($data);

		//format day month year
	//	$tmp["date"]=$this->format_date($data['pub_day'],$data['pub_month'],$data['pub_year']);
		
		/*if ($tmp["place"] !="" && $tmp["date"]!="")
		{
			$fields["place_date"]=implode(", ",$tmp);
		}
		else
		{
			$fields["place"]=$tmp["place"];
			$fields["date"]=$tmp["date"];			
		}*/
				
		$output=implode(" ",$fields);
		
		return $output;	
	}



	/**
	* Formats a Conference Paper
	*
	**/
	function format_conference_paper($data)
	{
		$output='';
		
		//format authors
		$fields['authors']=$this->format_authors($data['authors']);
		
		//format title
		$fields['title']=$this->format_periodical_title($data);
		
		//format city/country/state
		$tmp["place"]=$this->format_city_country($data);

		//format day month year
		$tmp["date"]=$this->format_date($data['pub_day'],$data['pub_month'],$data['pub_year']);
		
		if ($tmp["place"] !="" && $tmp["date"]!="")
		{
			$fields["place_date"]=implode(", ",$tmp);
		}
		else
		{
			$fields["place"]=$tmp["place"];
			$fields["date"]=$tmp["date"];			
		}
				
		$output=implode(" ",$fields);
		
		return $output;	
	}
	
	
	/**
	*
	* Formats city & country for various formats
	*
	* 
	* Note: For each citation type there could be a different format and and if statement is needed to format accordingly
	**/
	function format_city_country($data)
	{
		$fields=array();
		
		if ($data['ctype']=='conference-paper')
		{
			//city		
			if ($data['place_publication']!='')
			{
				$fields[]=$data['place_publication'];
			}
			
			//country or state
			if ($data['place_state']!='')
			{
				$fields[]=$data['place_state'];
			}
	
			//combine with (,)
			return implode(", ",$fields);			
		}
		else
		{
			//Default format
			//city		
			if ($data['place_publication']!='')
			{
				$fields[]=$data['place_publication'];
			}
			
			//country or state
			if ($data['place_state']!='')
			{
				$fields[]=$data['place_state'];
			}
	
			//combine with (,)
			return implode(", ",$fields);			
		}
	}


	/**
	* Formats a Journal Citation
	*
	**/
	function format_journal($data)
	{
		$output='';
		
		//format authors
		$fields['authors']=$this->format_authors($data['authors']);
		
		//format title
		$fields['title']=$this->format_title($data,'journal');
		
		$output=implode(" ",$fields);
		
		return $output;	
	}

	/**
	* Formats a Book Citation
	*
	**/
	function format_book($data)
	{
		$output='';
		
		//format authors
		$fields['authors']=$this->format_authors($data['authors']);
		
		//format title
		$fields['title']=$this->format_title($data,'book');
		
		$output=implode(" ",$fields);
		
		return $output;	
	}
	
	
	
	/**
	*
	*	Format Title and Subtitles
	*
	**/
	function format_title($data,$type)
	{
		if ($type=='journal')
		{
				return $this->format_journal_title($data);
		}		
		else if ($type=='book')
		{
				return $this->format_book_title($data);
		}
		else
		{
			$str[]=sprintf('<span class="title">"%s."</span>',$data['title']);
						
			if (trim($data['subtitle'])!='')
			{
				$str[]=sprintf('<span class="sub-title">%s</span>',$data['subtitle'].".");
			}

			return implode(" ",$str);

		}
	}
	
	

	/**
	*
	* Format the title and subtitle only
	*
	**/
	function format_periodical_title($data)
	{
		
		$data=(object)$data;
		
		//title field always be filled in
		
		//subtitle
		if ($data->subtitle!='')
		{
			$str[]=sprintf('<span class="title">"%s."</span>',anchor('citations/'.$data->id,$data->title));
			$str[]=sprintf('<span class="sub-title">%s</span>',$data->subtitle.".");
			return implode(" ",$str);		
		}
		else //no subtitle		
		{
			$str[]=sprintf('<span class="title">"%s."</span>',anchor('citations/'.$data->id,$data->title));
			return implode(" ",$str);				
		}
	}



	function format_journal_title($data)
	{
		
		$data=(object)$data;
		
		//title + subtitle -volume -year -page
		if ($data->subtitle !='' && $data->volume=='' && $data->pub_year=='' && $data->issue=='' && (integer)$data->pub_year<1700 && $data->page_from=='' )
		{
			$str[]=sprintf('<span class="title">"%s."</span>',anchor('citations/'.$data->id,$data->title));
			$str[]=sprintf('<span class="sub-title">%s</span>',$data->subtitle.".");
			return implode(" ",$str);		
		}
		//title + subtitle + volume + issue + year -page
		else if ($data->subtitle !='' && $data->volume!='' && $data->issue!='' && $data->pub_year>0 && $data->page_from=='' ) 
		{
			$str[]=sprintf('<span class="title">"%s."</span>',anchor('citations/'.$data->id,$data->title));
			$str[]=sprintf('<span class="sub-title">%s %s, no. %s (%s).</span>',
								$data->subtitle,
								$data->volume,
								$data->issue,
								$data->pub_year
								);
			return implode(" ",$str);		
		}
		//title + subtitle + volume + issue + year +page
		else if ($data->subtitle !='' && $data->volume!='' && $data->issue!='' && $data->pub_year>0 && $data->page_from!='' ) 
		{
			$str[]=sprintf('<span class="title">"%s."</span>',anchor('citations/'.$data->id,$data->title));			
			$str[]=sprintf('<span class="sub-title">%s %s, no. %s (%s): %s.</span>',
								$data->subtitle,
								$data->volume,
								$data->issue,
								$data->pub_year,
								$this->format_page_numbers($data->page_from, $data->page_to)
								);
			return implode(" ",$str);		
		}
		//title + subtitle -volume +issue + year - page
		else if ($data->subtitle !='' && $data->volume=='' && $data->issue!='' && $data->pub_year>0) 
		{
			$str[]=sprintf('<span class="title">"%s."</span>',anchor('citations/'.$data->id,$data->title));			
			$str[]=sprintf('<span class="sub-title">%s %s, no. %s (%s).</span>',
								$data->subtitle,
								$data->volume,
								$data->issue,
								$data->pub_year
								);
			return implode(" ",$str);		
			
		}
		else
		{
			$str[]=sprintf('<span class="title">"%s."</span>',anchor('citations/'.$data->id,$data->title));			
			$str[]=sprintf('<span class="sub-title">%s (%s).</span>',
								$data->subtitle,
								$data->pub_year
								);
			return implode(" ",$str);				
		}			
	}
	
	
	function format_book_publisher($data)
	{
		$data=(object)$data;
		$place=array();
    
		if ($data->publisher=="" && $data->pub_year=="")
		{
			return "";
		}
		else if ($data->publisher=="" && (integer)$data->pub_year>0)
		{
			return sprintf("%s.",$data->pub_year);		
		}
		
		
		if ($data->place_publication!="")
		{
			$place[]=$data->place_publication;
		}
		if ($data->place_state!="")
		{
			$place[]=$data->place_state;
		}
		
		$place=implode(", ", $place);	
		
		$publisher= sprintf("%s: %s",$place,$data->publisher);
		
		if ($data->pub_year>0)
		{
			return sprintf("%s, %s.",$publisher,$data->pub_year);
		}
		else
		{
			return sprintf("%s.",$publisher);
		}		
		
	}

function format_edition($num)
{
	switch(substr($num,strlen($num)))
	{
		case 1:
			return $num.'st';
			break;			
		case 2:
			return $num.'nd';
			break;
		case 3:
			return $num.'rd';
			break;
		case 5:			
		case 4:
		case 6:
		case 7:
		case 8:
		case 9:
		case 0:
			return $num.'th';
			break;
		default:
		return $num;	
	}	
}	

function format_book_title($data)
	{
		
		$data=(object)$data;
		
		//title + edition + volume + publisher 
		if ($data->edition !='' && 
				$data->volume!='' && 
				$data->publisher!='')
		{
			$str[]=sprintf('<span class="sub-title">%s.</span>',anchor('citations/'.$data->id,$data->title));
			$str[]=sprintf('<span>%s ed. Vol. %s. %s.</span>',
								$this->format_edition($data->edition),
								$data->volume,
								$this->format_book_publisher((array)$data)
								);

			return implode(" ",$str);		
		}
		//title - edition - volume + publisher 
		else if ($data->edition =='' && 
				$data->volume=='' && 
				$data->publisher!='')
		{
			$str[]=sprintf('<span class="sub-title">%s.</span>',anchor('citations/'.$data->id,$data->title));
			$str[]=sprintf('<span>%s.</span>',
								$this->format_book_publisher((array)$data)
								);

			return implode(" ",$str);		
		}		
		else
		{
			$str[]=sprintf('<span class="sub-title">%s.</span>',anchor('citations/'.$data->id,$data->title));
			$str[]=sprintf('<span>%s.</span>',
								$this->format_book_publisher((array)$data)
								);
			
			return implode(" ",$str);				
		}			
	}	
	
	function format_page_numbers($from, $to)
	{
		if ($to=='')
		{
			return $from;
		}
		else
		{
			return sprintf("%s-%s",$from, $to);
		}
	}
	
	/**
	*
	* Format authors
	*
	**/
	function format_authors($authors)
	{
	
		if (!is_array($authors))
		{
			return "";
		}
		
		$output=array();

		if (count($authors)==1)
		{
			//single author
			if ($authors[0]['lname']!='' || $authors[0]['initial'])
			{
				//combine last name + initial
				$tmp[]=trim(sprintf("%s %s",trim($authors[0]['lname']), trim($authors[0]['initial'])));
			}

			$tmp[]=trim($authors[0]['fname']);
			$output[]=implode(", ",$tmp);
		}
		else //multi-author
		{
			for($i=0;$i<count($authors);$i++)
			{
				//replace period
				$authors[$i]['initial']=str_replace(".","",$authors[$i]['initial']);
				
				//first author
				if ($i==0)
				{
					$tmp=array();
					if ($authors[$i]['lname']!='' || $authors[$i]['fname'])
					{
						//combine last, first name						
						if (!empty(trim($authors[$i]['lname'])) )
						{
							$tmp[]=trim($authors[$i]['lname']);
						}						
						if (trim($authors[$i]['fname']!="") )
						{
							//lastname + initial
							$tmp[]=trim(sprintf("%s %s",trim($authors[$i]['fname']), trim($authors[$i]['initial'])));
						}						
					}
					
					$output[]=implode(", ",$tmp);
				}
				else if ($i==count($authors)-1)//last author
				{
					$output[]= sprintf('and %s %s %s', $authors[$i]['fname'],$authors[$i]['initial'],$authors[$i]['lname']);
				}
				else //if ($i==count($authors)-1)//last author
				{
					$output[]= sprintf('%s %s %s', $authors[$i]['fname'],$authors[$i]['initial'],$authors[$i]['lname']);
				}

			}
		}
	
		$result=trim(implode(", ", $output));
		if ($result!=='')
		{
			$result.=". ";
		}
		
		//remove double periods
		return str_replace("..",".",$result);
	}


	function format_place($city, $country, $publisher, $date)
	{
		$output=NULL;
		
		if ($city!=='')
		{
			$output[]=$city;
		}
		if ($country!=='')
		{
			$output[]=$country;
		}
		
		//combine city and country
		$city_country=NULL;	
		if ($output!==NULL)
		{
			$city_country=implode(", ", $output);
		}
		
		$tmp=NULL;
		//combine publisher and date
		if ($publisher!=='')
		{
			$tmp[]=$publisher;
		}
		if ($date!=='')
		{
			$tmp[]=$date;
		}
	
		$pub_and_date='';
		if ($tmp !=NULL)
		{
			//join publisher and date
			$pub_and_date=implode(", ", $tmp);
		}
		
		if ($pub_and_date!=='')
		{
			$pub_and_date.=". ";
		}
		
		//combine all
		$result=NULL;
		if ($city_country!=='')
		{
			$result[]=$city_country;
			$result[]=$pub_and_date;
		}
	
		$final_output=implode(": ", $result);
		
		return $final_output;
	}
	
	function format_date($day,$month,$year)
	{
		$month_day='';
		
		//format Month, day	
		if($month!='')
		{
			$month_day=$month;
		}		
		
		if ((integer)$day>0)
		{
			if ($month!='')
			{
				$month_day.=' '. $day;
			}
			else
			{
				$month_day=$day;
			}	
		}
		
		$output='';
		
		//add year
		if ((integer)$year>0)
		{
		
			if ($month_day!='')
			{
				$output=$month_day.', '. $year;
			}
			else
			{
				$output=$year;
			}	
		}
		
		if ($output!='')
		{
			return $output.=".";
		}	
		else
		{
			return "";
		}
	}
	
}

/* End of file Chicago_Citation.php */
/* Location: ./application/libraries/Chicago_Citation.php */
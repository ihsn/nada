<style>
.grid-table .citation-title{font-weight:normal;}
.grid-table .citation-subtitle{ font-style:italic}
.sort-links a{border-left:1px solid gainsboro;padding:0px 5px 0px 5px;display:inline-block;text-decoration:none;}
.pagination{background-color:gainsboro;padding:5px;}
.search-box{padding:4px;background-color:gainsboro;}
.citation-row{padding:5px;margin-bottom:5px;border-bottom:1px dashed gainsboro;color:#333333; list-style-position:outside}
em{font-style:italic}

.title {}
.sub-title{font-style:italic;}
.citation-rows{margin-left:10px;}
.citation-rows ul li{margin-left:10px;}

</style>
<div class="body-container" style="padding:10px;">
<?php if (!isset($hide_form)):?>
<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<h1 class="page-title"><?php echo t('citations');?></h1>
<form class="search-box" style="margin-bottom:10px;" method="GET" id="user-search">
  <input type="text" size="40" name="keywords" id="keywords" value="<?php echo form_prep($this->input->get('keywords')); ?>"/>
  <select name="field" id="field">
    <option value="all"	<?php echo ($this->input->get('field')=='all') ? 'selected="selected"' : '' ; ?> ><?php echo t('all_fields')?></option>
    <option value="title"	<?php echo ($this->input->get('field')=='title') ? 'selected="selected"' : '' ; ?> ><?php echo t('title')?></option>
    <option value="authors"	<?php echo ($this->input->get('field')=='authors') ? 'selected="selected"' : '' ; ?> ><?php echo t('authors')?></option>
    <option value="pub_year"	<?php echo ($this->input->get('field')=='pub_year') ? 'selected="selected"' : '' ; ?> ><?php echo t('date')?></option>
    <option value="country"	<?php echo ($this->input->get('field')=='country') ? 'selected="selected"' : '' ; ?> ><?php echo t('country')?></option>
  </select>
  <input type="submit" value="<?php echo t('search')?>" name="search" class="button"//>
  <?php if ($this->input->get("keywords")!=''): ?>
    <a href="<?php echo site_url();?>/citations/"><?php echo t('reset')?></a>
  <?php endif; ?>
</form>
<?php endif; ?>
<?php if ($rows): ?>
<?php		
		$sort_by=$this->input->get("sort_by");
		$sort_order=$this->input->get("sort_order");			
?>
<?php 
	//pagination 
	$page_nums=$this->pagination->create_links();
	$current_page=($this->pagination->cur_page == 0) ? 1 : $this->pagination->cur_page;
	
	//sort
	$sort_by=$this->input->get("sort_by");
	$sort_order=$this->input->get("sort_order");
	
	//current page url
	$page_url=site_url().'/citations';//form_prep($this->uri->uri_string());
?>

<?php
  $from_page=1;
  
	if ($this->pagination->cur_page>0) {
		$from_page=(($this->pagination->cur_page-1)*$this->pagination->per_page+(1));
		$to_page=$this->pagination->per_page*$this->pagination->cur_page;

		if ($to_page> $this->pagination->total_rows) 
		{
			$to_page=$this->pagination->total_rows;
		}

		$pager=sprintf(t('showing %d-%d of %d')
						,(($this->pagination->cur_page-1)*$this->pagination->per_page+(1))
						,$to_page
						,$this->pagination->total_rows);
	}
	else
	{
		$pager=sprintf(t('showing %d-%d of %d')
				,$current_page
				,$this->pagination->total_rows
				,$this->pagination->total_rows);
	}
?>

<form autocomplete="off">
    <div class="sort-links">
    <?php echo t('sort_results_by');?>    
    <?php echo create_sort_link($sort_by,$sort_order,'authors',t('authors'),$page_url,array('keywords','field') ); ?>
    <?php echo create_sort_link($sort_by,$sort_order,'pub_year',t('date'),$page_url,array('keywords','field') ); ?>
    <?php echo create_sort_link($sort_by,$sort_order,'title',t('title'),$page_url,array('keywords','field') ); ?>
    </div>

	<div class="pagination"><em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?></div>
    
	<?php $tr_class=""; ?>
    <ol type="1" start="<?php echo $from_page;?>" class="citation-rows">
	<?php foreach($rows as $row): ?>
		<li class="citation-row">
		<?php //$this->load->view('citations/view_chicago',$row);?>
        <?php echo $this->chicago_citation->format($row,'journal');?>
        </li>
    <?php endforeach;?>
    </ol>
    <div class="pagination">
		<em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?>
    </div>

<?php else: ?>
<?php echo t('no_records_found');?>
<?php endif; ?>
</form>
</div>
<script type='text/javascript' >
//checkbox select/deselect
jQuery(document).ready(function(){
	$("#chk_toggle").click(
			function (e) 
			{
				$('.chk').each(function(){ 
                    this.checked = (e.target).checked; 
                }); 
			}
	);
	$(".chk").click(
			function (e) 
			{
			   if (this.checked==false){
				$("#chk_toggle").attr('checked', false);
			   }			   
			}
	);			
	$("#batch_actions_apply").click(
		function (e){
			if( $("#batch_actions").val()=="delete"){
				batch_delete();
			}
		}
	);
});

function batch_delete(){
	if ($('.chk:checked').length==0){
		alert("You have not selected any items");
		return false;
	}
	if (!confirm("Are you sure you want to delete the selected item(s)?"))
	{
		return false;
	}
	selected='';
	$('.chk:checked').each(function(){ 
		if (selected!=''){selected+=',';}
        selected+= this.value; 
     });
	
	$.ajax({
		timeout:1000*120,
		dataType: "json",
		data:{ submit: "submit"},
		type:'POST', 
		url: CI.base_url+'/admin/resources/delete/'+selected+'/?ajax=true',
		success: function(data) {
			if (data.success){
				location.reload();
			}
			else{
				alert(data.error);
			}
		},
		error: function(XHR, textStatus, thrownError) {
			alert("Error occured " + XHR.status);
		}
	});	
}

</script>

<?php
/**
*
* Format authors according to chicago style
*/
function format_author($authors)
{
	$output=array();
	if (count($authors)==1)
	{
		//single author
		if ($authors[0]['lname']!='' || $authors[0]['initial'])
		{
			$tmp[]=sprintf("%s %s",trim($authors[0]['lname']), trim($authors[0]['initial']));
		}
		$tmp[]=trim($authors[0]['fname']);
		$output[]=implode(", ",$tmp);
	}
	else //multi-author
	{
		for($i=0;$i<count($authors);$i++)
		{
			if ($i==0)
			{
				if ($authors[$i]['lname']!='' || $authors[$i]['initial'])
				{
					$tmp[]=sprintf("%s %s",trim($authors[$i]['lname']), trim($authors[$i]['initial']));
				}
				$tmp[]=trim($authors[$i]['fname']);
				$output[]=implode(", ",$tmp);
			}
			else if ($i==count($authors)-1)//last author
			{
				$output[]= sprintf('and %s %s %s', $authors[$i]['fname'],$authors[$i]['initial'],$authors[$i]['lname']);
			}
		}
	}

	$result=trim(implode(", ", $output));
	if ($result!=='')
	{
		$result.=". ";
	}
	return $result;
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

?>
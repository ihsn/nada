<div class="body-container" style="padding:10px;">
<?php if (!isset($hide_form)):?>
<div class="page-links">
	<a href="<?php echo current_url(); ?>/add" class="button"><img src="images/icon_plus.gif"/>Add new</a> 
    <a href="<?php echo current_url(); ?>/import" class="button"><img src="images/icon_plus.gif"/>Import RDF file</a> 
</div>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<h1 class="page-title">External Resources</h1>
<form class="left-pad" style="margin-bottom:10px;" method="GET" id="user-search">
  <input type="text" size="40" name="keywords" id="keywords" value="<?php echo $this->input->get('keywords'); ?>"/>
  <select name="field" id="field">
    <option value="all"		<?php echo ($this->input->get('field')=='all') ? 'selected="selected"' : '' ; ?> >All fields</option>
    <option value="title"	<?php echo ($this->input->get('field')=='title') ? 'selected="selected"' : '' ; ?> >Title</option>
    <option value="author"	<?php echo ($this->input->get('field')=='author') ? 'selected="selected"' : '' ; ?> >author</option>
  </select>
  <input type="submit" value="Search" name="search"/>
  <?php if ($this->input->get("keywords")!=''): ?>
    <a href="<?php echo current_url();?>">Reset</a>
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
	$page_url=site_url().$this->uri->uri_string();
?>

<?php
	if ($this->pagination->cur_page>0) {
		//pager displays records e.g. showing n - n of N
		$pager= 'showing '.(($this->pagination->cur_page-1)*$this->pagination->per_page+(1));
		$pager.= ' - ';
		$to_page=$this->pagination->per_page*$this->pagination->cur_page;
		if ($to_page> $this->pagination->get_total_rows()) 
		{
			$to_page=$this->pagination->get_total_rows();
		}
		$pager.= $to_page;
		$pager.= ' of '.$this->pagination->get_total_rows();
	}
	else
	{
		$pager='showing 1 - '.$this->pagination->get_total_rows(). ' of '.$this->pagination->get_total_rows();
	}
?>
<form autocomplete="off">
	<!-- batch operations -->
    <table width="100%">
        <tr>
            <td>
                <select id="batch_actions">
                    <option value="-1">Batch actions</option>
                    <option value="delete">Delete</option>
                </select>
                <input type="button" id="batch_actions_apply" name="batch_actions_apply" value="Apply"/>                
            </td>
            <td align="right">
                <div class="pagination"><em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?></div>
            </td>
        </tr>
    </table>
    
    <table class="grid-table" width="100%" cellspacing="0" cellpadding="0">
    	<tr class="header">
        	<th><input type="checkbox" value="-1" id="chk_toggle"/></th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'title','Title',$page_url); ?></th>
            <th>Description</th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'dctype','Resource type',$page_url); ?></th>
			<th><?php echo create_sort_link($sort_by,$sort_order,'changed','Modified',$page_url); ?></th>
			<th>Actions</th>
        </tr>
	<?php $tr_class=""; ?>
	<?php foreach($rows as $row): ?>
    	<?php $row=(object)$row; //var_dump($row);exit;?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="<?php echo $tr_class; ?>">
        	<td><input type="checkbox" value="<?php echo $row->resource_id; ?>" class="chk"/></td>
            <td><a href="<?php echo current_url();?>/edit/<?php echo $row->resource_id;?>"><?php echo $row->title; ?></a></td>
            <td><?php echo substr($row->description,0,60); ?>&nbsp;</td>
            <td><?php echo $row->dctype; ?>&nbsp;</td>
			<td nowrap="nowrap"><?php echo date($this->config->item('date_format'), $row->changed); ?></td>
			<td nowrap="nowrap"><a href="<?php echo current_url();?>/edit/<?php echo $row->resource_id;?>">Edit</a> | 
            <a href="<?php echo current_url();?>/delete/<?php echo $row->resource_id;?>/?destination=<?php echo $this->uri->uri_string();?>">Delete</a></td>
        </tr>
    <?php endforeach;?>
    </table>
    <div class="pagination">
		<em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?>
    </div>

<?php else: ?>
No records found
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
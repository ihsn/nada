<div class="body-container" style="padding:10px;">
<?php if (!isset($hide_form)):?>

<?php $this->load->view('collections/navigation_links'); ?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>


<h1 class="page-title"><?php echo t('collections_management');?></h1>
<form class="left-pad" style="margin-bottom:10px;" method="GET" id="user-search">
  <input type="text" size="40" name="keywords" id="keywords" value="<?php echo form_prep($this->input->get('keywords')); ?>"/>
  <select name="field" id="field">
    <option value="all"		<?php echo ($this->input->get('field')=='all') ? 'selected="selected"' : '' ; ?> ><?php echo t('all_fields');?></option>
    <option value="title"	<?php echo ($this->input->get('field')=='title') ? 'selected="selected"' : '' ; ?> ><?php echo t('title');?></option>
    <option value="description"	<?php echo ($this->input->get('field')=='description') ? 'selected="selected"' : '' ; ?> ><?php echo t('description');?></option>
  </select>
  <input type="submit" value="<?php echo t('search');?>" name="search"/>
  <?php if ($this->input->get("keywords")!=''): ?>
    <a href="<?php echo current_url();?>"><?php echo t('reset');?></a>
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
	$page_url=site_url().'/'.$this->uri->uri_string();
?>

<?php
	if ($this->pagination->cur_page>0) {
		$to_page=$this->pagination->per_page*$this->pagination->cur_page;

		if ($to_page> $this->pagination->get_total_rows()) 
		{
			$to_page=$this->pagination->get_total_rows();
		}

		$pager=sprintf(t('showing %d-%d of %d')
						,(($this->pagination->cur_page-1)*$this->pagination->per_page+(1))
						,$to_page
						,$this->pagination->get_total_rows());
	}
	else
	{
		$pager=sprintf(t('showing %d-%d of %d')
				,$current_page
				,$this->pagination->get_total_rows()
				,$this->pagination->get_total_rows());
	}
?>

<?php echo form_open();?>

	<!-- batch operations -->
    <table width="100%">
        <tr>
            <td>
                <select id="batch_actions">
                    <option value="-1"><?php echo t('batch_actions');?></option>
                    <option value="delete"><?php echo t('delete');?></option>
                </select>
                <input type="button" id="batch_actions_apply" name="batch_actions_apply" value="<?php echo t('apply');?>"/>                
            </td>
            <td align="right">
                <div class="pagination"><em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?></div>
            </td>
        </tr>
    </table>
    
    <!-- grid -->
    <table class="grid-table" width="100%" cellspacing="0" cellpadding="0">
    	<tr class="header">
        	<th><input type="checkbox" value="-1" id="chk_toggle"/></th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'title',t('title'),$page_url); ?></th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'description',t('description'),$page_url); ?></th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'weight',t('weight'),$page_url); ?></th>
			<th><?php echo t('actions');?></th>
        </tr>
	<?php $tr_class=""; ?>
	<?php foreach($rows as $row): ?>
    	<?php $row=(object)$row;?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="<?php echo $tr_class; ?>">
        	<td><input type="checkbox" value="<?php echo $row->id; ?>" class="chk"/></td>
            <td><a href="<?php echo current_url();?>/edit/<?php echo $row->id;?>"><?php echo $row->title; ?></a></td>
            <td><?php echo $row->description; ?>&nbsp;</td>
            <td><?php echo $row->weight; ?>&nbsp;</td>
			<td>
            	<a href="<?php echo current_url();?>/edit/<?php echo $row->id;?>"><?php echo t('edit');?></a> | 
                <a href="<?php echo current_url();?>/delete/<?php echo $row->id;?>"><?php echo t('delete');?></a>
            </td>
        </tr>
    <?php endforeach;?>
    </table>
    <div class="pagination">
		<em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?>
    </div>
<?php echo form_close();?>

<?php else: ?>
<?php echo t('no_records_found'); ?>
<?php endif; ?>
</div>

<script type="text/javascript" >

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
		cache:false,
        dataType: "json",
		data:{ 
			submit: "submit",
			'<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
		},
		type:'POST', 
		url: CI.base_url+'/admin/collections/delete/'+selected+'/?ajax=true',
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
<?php
	//set default page size, if none selected
	if(!$this->input->get("ps"))
	{
		$ps=15;
	}
?>
<style>
.resource-found{background:url(images/tick.png) no-repeat; padding-left:25px;}
.resource-notfound{background:url(images/close.gif) no-repeat; padding-left:25px;}
</style>
<div class="body-container" >
<?php include dirname(__FILE__).'/../managefiles/tabs.php';?>
<?php if (!isset($hide_form)):?>
<div class="page-links">
	<a href="<?php echo current_url(); ?>/add" class="button"><img src="images/icon_plus.gif"/><?php echo t('link_add_new_resource'); ?></a> 
    <a href="<?php echo current_url(); ?>/import" class="button"><img src="images/icon_plus.gif"/><?php echo t('link_import_rdf'); ?></a> 
    <a href="<?php echo current_url(); ?>/fixlinks" class="button"><img src="images/wrench.png"/><?php echo t('link_fix_broken'); ?></a> 
	<a href="<?php echo site_url(); ?>/admin/catalog/export_rdf/<?php echo $this->uri->segment(3);?>" class="button"><img src="images/rdf.gif"/><?php echo t('rdf_export'); ?></a> 
</div>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<form class="left-pad" style="margin-bottom:10px;" method="GET" id="search-form">
  <input type="text" size="40" name="keywords" id="keywords" value="<?php echo $this->input->get('keywords'); ?>"/>
  <select name="field" id="field">
    <option value="all"		<?php echo ($this->input->get('field')=='all') ? 'selected="selected"' : '' ; ?> ><?php echo t('all_fields'); ?></option>
    <option value="title"	<?php echo ($this->input->get('field')=='title') ? 'selected="selected"' : '' ; ?> ><?php echo t('title'); ?></option>
    <option value="author"	<?php echo ($this->input->get('field')=='author') ? 'selected="selected"' : '' ; ?> ><?php echo t('authors'); ?></option>
  </select>
  <input type="submit" value="<?php echo t('search'); ?>" name="search"/>
  <?php if ($this->input->get("keywords")!=''): ?>
    <a href="<?php echo current_url();?>">Reset</a>
  <?php endif; ?>

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

	<!-- batch operations -->
    <table width="100%">
        <tr>
            <td>
                <select id="batch_actions">
                    <option value="-1"><?php echo t('batch_actions'); ?></option>
                    <option value="delete"><?php echo t('delete'); ?></option>
                </select>
                <input type="button" id="batch_actions_apply" name="batch_actions_apply" value="<?php echo t('apply'); ?>"/>                
            </td>
            <td align="right">
                <div class="pagination"><em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?></div>
            </td>
        </tr>
    </table>
    
    <table class="grid-table" width="100%" cellspacing="0" cellpadding="0">
    	<tr class="header">
        	<th><input type="checkbox" value="-1" id="chk_toggle"/></th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'title',t('title'),$page_url,array('keywords','field','ps')); ?></th>
            <th><?php echo t('link'); ?></th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'dctype',t('resource_type'),$page_url,array('keywords','field','ps')); ?></th>
			<th><?php echo create_sort_link($sort_by,$sort_order,'changed',t('modified'),$page_url,array('keywords','field','ps')); ?></th>
			<th><?php echo t('actions'); ?></th>
        </tr>
	<?php $tr_class=""; ?>
	<?php foreach($rows as $row): ?>
    	<?php $row=(object)$row; //var_dump($row);exit;?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>        
        <?php 
				$resource_exists=FALSE;
				if( trim($row->filename)=='')
				{
					$resource_exists=FALSE;
				}
				else if(file_exists(unix_path($this->survey_folder.'/'.$row->filename)) )
				{
					$resource_exists=TRUE;
				}
				else if (is_url($row->filename))
				{
					$resource_exists=TRUE;
				}		
		?>
        <?php $resource_class=($resource_exists===TRUE) ? 'resource-found' : 'resource-notfound';?> 
    	<tr class="<?php echo $tr_class; ?>">
        	<td><input type="checkbox" value="<?php echo $row->resource_id; ?>" class="chk"/></td>
            <td><a href="<?php echo current_url();?>/edit/<?php echo $row->resource_id;?>"><?php echo $row->title; ?></a></td>
            <td><span class="<?php echo $resource_class; ?>">&nbsp;</span></td>
            <td><?php echo $row->dctype; ?>&nbsp;</td>
			<td nowrap="nowrap"><?php echo date($this->config->item('date_format'), $row->changed); ?></td>
			<td nowrap="nowrap">
            <a href="<?php echo current_url();?>/edit/<?php echo $row->resource_id;?>"><?php echo t('edit'); ?></a> | 
            <a href="<?php echo current_url();?>/delete/<?php echo $row->resource_id;?>/?destination=<?php echo $this->uri->uri_string();?>"><?php echo t('delete'); ?></a>
            <?php if($row->filename!=''):?>
            | <a href="<?php echo site_url();?>/ddibrowser/<?php echo $this->uri->segment(3); ?>/download/<?php echo $row->resource_id;?>"><?php echo t('download'); ?></a>
            <?php endif;?>
            </td>
        </tr>
    <?php endforeach;?>
    </table>    
    <div class="pagination">
    	<div style="float:left;color:#999999">
        	<span style="padding-right:20px;">
            <?php echo t("select_number_of_records_per_page");?>:
        	<?php echo form_dropdown('ps', array(5=>5,10=>10,15=>15,30=>30,50=>50,100=>100,500=>t('ALL')), get_form_value("ps",isset($ps) ? $ps : ''),'id="ps" style="font-size:10px;"'); ?>
			</span>
            	
        	<div style="display:inline;"><img src="images/tick.png"/> <?php echo t('legend_file_exist'); ?></div>
            <div style="display:inline;margin-left:10px;"><img src="images/close.gif"/> <?php echo t('legend_file_no_exist'); ?></div>
        </div>
		<em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?>
    </div>

<?php else: ?>
<div>
<?php echo t('no_records_found'); ?>
</div>
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
//page change
$('#ps').change(function() {
  $('#search-form').submit();
});
</script>
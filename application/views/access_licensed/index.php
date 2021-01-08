<?php
	$active_tab=strtoupper($this->input->get("status"));
?>

<div class="container-fluid">
<?php if (!isset($hide_form)):?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="alert alert-success">'.$message.'</div>' : '';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="alert alert-danger">'.$error.'</div>' : '';?>

<div class="pull-right page-links">
        <a href="<?php echo site_url('admin/licensed_requests/export');?>" class="btn btn-default"><span class="glyphicon glyphicon-plus ico-add-color right-margin-5" aria-hidden="true"></span><?php echo t('export_to_csv');?></a>
</div>

<h1 class="page-title"><?php echo t('title_licensed_request');?></h1>

<form class="form-inline" style="margin-bottom:20px;" method="GET" id="user-search">

	<div class="input-group">
		<input class="form-control" type="text" size="40" name="keywords" id="keywords" value="<?php echo form_prep($this->input->get('keywords')); ?>"/>
		<input type="hidden" name="field" value="title"/>
		<input type="hidden" name="status" value="<?php echo ($active_tab) ? $active_tab : '';?>"/>
		<span class="input-group-btn">
			<input class="btn btn-primary" type="submit" value="<?php echo t('search');?>" name="search"/>
		</span>
		<?php if ($this->input->get("keywords")!=''): ?>
		<a href="<?php echo current_url();?>"><?php echo t('reset');?></a>
		<?php endif; ?>
 	</div>

</form>


<ul class="nav nav-tabs">
  <li <?php echo (!$active_tab) ? 'class="active"' : '';?> ><a href="<?php echo site_url('admin/licensed_requests');?>"><?php echo t('all_requests');?></a></li>
  <li <?php echo ($active_tab=='PENDING') ? 'class="active"' : '';?> ><a href="<?php echo site_url('admin/licensed_requests?status=PENDING');?>" ><?php echo t('pending');?></a></li>
  <li <?php echo ($active_tab=='APPROVED') ? 'class="active"' : '';?> ><a href="<?php echo site_url('admin/licensed_requests?status=APPROVED');?>"><?php echo t('approved');?></a></li>
  <li <?php echo ($active_tab=='DENIED') ? 'class="active"' : '';?> ><a href="<?php echo site_url('admin/licensed_requests?status=DENIED');?>"><?php echo t('denied');?></a></li>
  <li <?php echo ($active_tab=='MOREINFO') ? 'class="active"' : '';?> ><a href="<?php echo site_url('admin/licensed_requests?status=MOREINFO');?>"><?php echo t('request_more_info');?></a></li>
  <li <?php echo ($active_tab=='CANCELLED') ? 'class="active"' : '';?> ><a href="<?php echo site_url('admin/licensed_requests?status=CANCELLED');?>"><?php echo t('cancelled');?></a></li>
</ul>

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

<form autocomplete="off" style="margin-top:25px;">

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
                <div class="nada-pagination"><em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?></div>
            </td>
        </tr>
    </table>
    
    <!-- grid -->
    <table class="table table-bordered table-striped" width="100%" cellspacing="0" cellpadding="0">
    	<tr class="header">
        	<th><input type="checkbox" value="-1" id="chk_toggle"/></th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'survey_title',t('title'),$page_url); ?></th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'username',t('requested_by'),$page_url); ?></th>
			<th><?php echo create_sort_link($sort_by,$sort_order,'status',t('status'),$page_url); ?></th>
			<th><?php echo create_sort_link($sort_by,$sort_order,'created',t('date'),$page_url); ?></th>
			<th><?php echo t('actions');?></th>
        </tr>
	<?php $tr_class=""; ?>
	<?php foreach($rows as $row): ?>
    	<?php $row=(object)$row; ?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="<?php echo $tr_class; ?>">
        	<td><input type="checkbox" value="<?php echo $row->id; ?>" class="chk"/></td>
            <td><a href="<?php echo current_url();?>/edit/<?php echo $row->id;?>">
						<?php echo $row->request_title; ?>
                </a>
            </td>
            <td><?php echo $row->username; ?>&nbsp;</td>
			<td><?php echo t($row->status); ?></td>
			<td nowrap="nowrap"><?php echo date("m-d-Y",$row->created); ?></td>
			<td nowrap="nowrap">
            <a href="<?php echo current_url();?>/edit/<?php echo $row->id;?>"><?php echo t('edit');?></a> | 
            <a href="<?php echo current_url();?>/delete/<?php echo $row->id;?>/"><?php echo t('delete');?></a>
            </td>
        </tr>
    <?php endforeach;?>
    </table>
    <div class="nada-pagination">
		<em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?>
    </div>
</form>
<?php else: ?>
	<?php echo t('no_records_found');?>
<?php endif; ?>
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
		url: CI.base_url+'/admin/licensed_requests/delete/'+selected+'/?ajax=true',
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

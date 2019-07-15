<style>
	.repo-thumbnail{
		max-width:150px;
	}
	</style>
<?php
$repository_types=array(
	'0'=>'Internal',
	'1'=>'External',
	//'2'=>'System'
);

?>
<div class="container-fluid">

<?php include 'page_links.php'; ?>

<?php if (!isset($hide_form)):?>
<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="alert alert-success">'.$message.'</div>' : '';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="alert alert-danger">'.$error.'</div>' : '';?>


<h1 class="page-title"><?php echo t('repositories');?></h1>
<form class="left-pad" style="margin-bottom:10px;" method="GET" id="user-search">
  <input type="text" size="40" name="keywords" id="keywords" value="<?php echo $this->input->get('keywords'); ?>"/>
  <select name="field" id="field">
    <option value="all"		<?php echo ($this->input->get('field')=='all') ? 'selected="selected"' : '' ; ?> ><?php echo t('all_fields');?></option>
    <option value="title"	<?php echo ($this->input->get('field')=='title') ? 'selected="selected"' : '' ; ?> ><?php echo t('title');?></option>
    <option value="body"	<?php echo ($this->input->get('field')=='body') ? 'selected="selected"' : '' ; ?> ><?php echo t('body');?></option>
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
	$page_url=site_url().'/'.$this->uri->uri_string().'/';
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

<form autocomplete="off">

	<!-- batch operations -->
    <table width="100%">
        <tr>
            <td>
            </td>
            <td align="right">
                <div class="nada-pagination"><em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?></div>
            </td>
        </tr>
    </table>

    <!-- grid -->
    <table class="table table-striped" width="100%" cellspacing="0" cellpadding="0">
    	<tr class="header">
				<th>&nbsp;</th>			
				<th>ID</th>
				<th><?php echo create_sort_link($sort_by,$sort_order,'title',t('title'),$page_url); ?></th>
				<th><?php echo create_sort_link($sort_by,$sort_order,'weight',t('weight'),$page_url); ?></th>
				<th><?php echo create_sort_link($sort_by,$sort_order,'ispublished',t('status'),$page_url); ?></th>
			<th><?php echo t('actions');?></th>
        </tr>
	<?php $tr_class=""; ?>
	<?php foreach($rows as $row): ?>
    	<?php $row=(object)$row;?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="repo-row <?php echo $tr_class; ?>">
						<td><div class="thumb"><a href="<?php echo site_url('catalog/'.$row->repositoryid.'/about');?>"><img class="img-thumbnail repo-thumbnail" src="<?php echo base_url();?><?php echo $row->thumbnail; ?>"/></a></div></td>
						<td><a href="<?php echo site_url();?>/admin/repositories/edit/<?php echo $row->id;?>"><?php echo strtoupper($row->repositoryid); ?></a></td>
            <td><a href="<?php echo site_url();?>/admin/repositories/edit/<?php echo $row->id;?>"><?php echo $row->title; ?></a></td>
            <!--<td><?php echo (array_key_exists($row->type,$repository_types) ) ? $repository_types[(int)$row->type] : $row->type; ?></td>-->
            <td><input class="weight" type="textbox" value="<?php echo (int)$row->weight; ?>" data-id="<?php echo $row->id;?>" size="2"/></td>
            <td>
                <div class="status">
                <span class="btn btn-xs publish <?php echo ($row->ispublished==1) ? "btn-success" :'btn-danger'; ?>" data-value="<?php echo $row->ispublished;?>" data-id="<?php echo $row->id;?>"><?php echo ($row->ispublished==1) ? t('published') : t('draft'); ?></span>
                </div>			
			</td>
			<td>
            	<a href="<?php echo current_url();?>/edit/<?php echo $row->id;?>"><?php echo t('edit');?></a> | 
                <a href="<?php echo current_url();?>/delete/<?php echo $row->id;?>"><?php echo t('delete');?></a> |
                <a href="<?php echo current_url();?>/permissions/<?php echo $row->id;?>"><?php echo t('permissions');?></a> |
                <a target="_blank" href="<?php echo site_url('catalog/'.$row->repositoryid);?>/about"><?php echo t('preview');?></a>
            </td>
        </tr>
    <?php endforeach;?>
    </table>
    <div class="nada-pagination">
		<em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?>
    </div>
</form>
<?php else: ?>
<?php echo t('no_records_found'); ?>
<?php endif; ?>
</div>

<script type="text/javascript" >

//checkbox select/deselect
jQuery(document).ready(function(){
	//publish/unpublish
	$(document.body).on("click",".repo-row .publish", function(){ 
		//if (!confirm("<?php echo t('confirm_collection_status_change');?>")){return false;}
		var id=$(this).attr("data-id");
		if ($(this).attr("data-value")==0){
			$(this).attr("data-value",1);
			$(this).html('<?php echo t("published");?>');
			$(this).addClass("btn-success");
			$(this).removeClass("btn-danger");
			$.post(CI.base_url+'/admin/repositories/publish/'+id+'/1?ajax=1',{submit:"submit"});
		}
		else{
			$(this).html('<?php echo t("draft");?>');
			$(this).attr("data-value",0);
			$(this).removeClass("btn-success");
			$(this).addClass("btn-danger");
			$.post(CI.base_url+'/admin/repositories/publish/'+id+'/0?ajax=1',{submit:"submit"});
		}
	
	});	
	
	//weight
	$(document.body).on("change",".repo-row .weight", function(){ 
		var id=$(this).attr("data-id");
		var value=$(this).attr("value");
		$.post(CI.base_url+'/admin/repositories/weight/'+id+'/'+value+'?ajax=1',{submit:"submit"});
	});	

});


</script>

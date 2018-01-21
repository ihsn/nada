<style>
.published, .unpublished{
	cursor:pointer;
}
</style>
<div class="container-fluid menu-index-page">
<?php if (!isset($hide_form)):?>

<?php
	//menu breadcrumbs
	include 'menu_breadcrumb.php';
?>


<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="alert alert-success">'.$message.'</div>' : '';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="alert alert-danger">'.$error.'</div>' : '';?>


<h1 class="page-title"><?php echo t('menu_management');?></h1>
<div class="form-inline">
<form class="form-group" method="GET" id="user-search">
  <input class="form-control" type="text" size="40" name="keywords" id="keywords" value="<?php echo form_prep($this->input->get('keywords')); ?>"/>
  <select name="field" id="field" class="form-control">
    <option value="all"		<?php echo ($this->input->get('field')=='all') ? 'selected="selected"' : '' ; ?> ><?php echo t('all_fields');?></option>
    <option value="title"	<?php echo ($this->input->get('field')=='title') ? 'selected="selected"' : '' ; ?> ><?php echo t('title');?></option>
    <option value="body"	<?php echo ($this->input->get('field')=='body') ? 'selected="selected"' : '' ; ?> ><?php echo t('body');?></option>
  </select>
  
  <input type="submit" class="btn btn-primary" value="<?php echo t('search');?>" name="search"/>
  <?php if ($this->input->get("keywords")!=''): ?>
    <a  class="btn btn-default" href="<?php echo current_url();?>"><?php echo t('reset');?></a>
  <?php endif; ?>
</form>
</div>
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

<form autocomplete="off" class="form-group">

	<!-- batch operations -->
	<div class="row" style="margin-top:30px;">
		<div class="col-md-6">    
			<select id="batch_actions" style="margin-bottom:5px;">
				<option value="-1"><?php echo t('batch_actions');?></option>
				<option value="delete"><?php echo t('delete');?></option>
			</select>
			<input type="button" class="btn btn-default btn-xs" id="batch_actions_apply" name="batch_actions_apply" value="<?php echo t('apply');?>"/>			
			</div>
		<div class="col-md-6">
			<div class="pull-right">
				<div class="nada-pagination"><em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?></div>
			</div>
		</div>
	</div>

    <!-- grid -->
    <table class="table table-striped table-border-bottom top-margin-10" width="100%" cellspacing="0" cellpadding="0">
    	<tr class="header">
        	<th><input type="checkbox" value="-1" id="chk_toggle"/></th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'title',t('title'),$page_url); ?></th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'url',t('url'),$page_url); ?></th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'linktype',t('type'),$page_url); ?></th>
			<th><?php echo create_sort_link($sort_by,$sort_order,'published',t('published'),$page_url); ?></th>
			<th><?php echo create_sort_link($sort_by,$sort_order,'changed',t('modified'),$page_url); ?></th>
			<th><?php echo t('actions');?></th>
        </tr>
	<?php $tr_class=""; ?>
	<?php foreach($rows as $row): ?>
    	<?php $row=(object)$row;?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="<?php echo $tr_class; ?>">
        	<td><input type="checkbox" value="<?php echo $row->id; ?>" class="chk"/></td>
            <td><a href="<?php echo current_url();?>/edit/<?php echo $row->id;?>"><?php echo html_escape($row->title); ?></a></td>
            <td><?php echo html_escape($row->url); ?>&nbsp;</td>
            <td><?php echo ($row->linktype==0 ? '<span class="glyphicon glyphicon-file"></span>' : '<span class="glyphicon glyphicon-link"></span>'); ?></td>
			<td title="Click to publish/unpublish"><span class="<?php echo ($row->published==1 ? 'published glyphicon glyphicon-ok ico-add-color ' : 'unpublished glyphicon glyphicon-remove red-color'); ?>" id="<?php echo $row->id; ?>"></span></td>
			<td><?php echo date("m-d-Y",$row->changed); ?></td>
			<td>
            	<a href="<?php echo current_url();?>/edit/<?php echo $row->id;?>"><?php echo t('edit');?></a> |
                <a href="<?php echo current_url();?>/delete/<?php echo $row->id;?>"><?php echo t('delete');?></a>
            </td>
        </tr>
    <?php endforeach;?>
    </table>
    <div class="pull-right nada-pagination">
		<em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?>
    </div>
</form>
<?php else: ?>
<?php echo t('no_records_found'); ?>
<?php endif;?>
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

	bind_events();
});

function bind_events()
{
	//remove events
    $(".unpublished, .published").unbind('click');

	$(".unpublished").click(
			function (e) {
                $(this).removeClass('unpublished').addClass('published');bind_events();
				$(this).removeClass("glyphicon-remove").addClass("glyphicon-ok");
                url=CI.base_url+'/admin/menu/publish/'+$(this).attr("id")+'/'+1;
                $.get(url);
			}
	);

	$(".published").click(
			function (e) {
				$(this).removeClass('published').addClass('unpublished');bind_events();
				$(this).removeClass("glyphicon-ok").addClass("glyphicon-remove");
                url=CI.base_url+'/admin/menu/publish/'+$(this).attr("id")+'/'+0;
                $.get(url);
			}
	);
}

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
		data:{ submit: "submit"},
		type:'POST',
		url: CI.base_url+'/admin/menu/delete/'+selected+'/?ajax=true',
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

<style>
.related-citations{
	padding:15px;background:red;
}
.glyphicon-check{color:green;}
.dialog-title{
	/*text-transform: uppercase;*/
}
</style>
<?php
	//set default page size, if none selected
	if(!$this->input->get("ps"))
	{
		$ps=15;
	}
?>


<nav class="navbar navbar-default" style="background:#337ab7;">
  <div class="container-fluid">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="navbar-header" >
      	<div class="navbar-brand" href="#" style="color:white;">
			<a href="<?php echo site_url('admin/catalog/edit/'.$survey_id.'/citations');?>"	style="color:white;">
				<span class="glyphicon glyphicon-arrow-left" aria-hidden="true"></span> <span><?php echo $survey_title;?></span>
			</a>
		</div>
    </div>

	<div class="navbar-right" style="margin-right:10px;">
		<a type="button" class="btn btn-info navbar-btn" href="<?php echo site_url('admin/catalog/edit/'.$survey_id.'/citations');?>">
			<span class="glyphicon glyphicon-arrow-left" aria-hidden="true"></span> <?php echo t('return_to_edit_page');?>
		</a>
	</div>
  </div>
</nav>

<div class="body-container"> 

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<!-- search form-->
<div class="container-fluid">

<h2 class="dialog-title mt-3"><?php echo t('attach_citations');?></h2>

<form method="GET" id="catalog-search">

	<div class="form-inline" >

		<div class="form-group">
			<input class="form-control" type="text" size="40" name="keywords" id="keywords" value="<?php echo form_prep($this->input->get('keywords')); ?>"/>
		</div>
	
		<div class="form-group">
			<input class="btn btn-primary" type="submit" value="<?php echo t('search');?>" name="search"/>
		</div>

		<?php if ($this->input->get("keywords")!=''): ?>
			<a class="btn btn-link ml-2" href="<?php echo site_url('/admin/related_citations/index/'.$survey_id);?>"><?php echo t('reset');?></a>
		<?php endif; ?>

	</div>
</form>


<?php if ($rows): ?>
<?php
	//pagination
	$page_nums=$this->pagination->create_links();
	$current_page=($this->pagination->cur_page == 0) ? 1 : $this->pagination->cur_page;

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

<script type="text/javascript">
$(function() {
	$('a.attach, a.remove').on('click',null, function() {	
		window._this=$(this);
		if ($(this).attr("data-isattached")==0){
			//attach
			$(this).attr("data-isattached",1);
			url='<?php echo site_url('/admin/related_citations/add/'.$survey_id);?>/'+$(this).attr("item");
			$(this).html('<i class="fas fa-check-circle"></i>');
			
			//$(this).removeClass("attach").addClass("remove");
		}else{
			$(this).attr("data-isattached",0);
			url='<?php echo site_url('/admin/related_citations/remove/'.$survey_id);?>/'+$(this).attr("item");
			$(this).html('<i class="far fa-circle"></i>');
			//$(this).removeClass("attach").addClass("remove");
		}
		$.get(url);
		//id=$(this).parent().parent().children().first().children('b').html();
		return false;
	});

});
</script>

<div id="related-citations">

<div class="table-container">
	<!--<p class="text-warning">Click on the <span class="glyphicon glyphicon-unchecked" aria-hidden="true"></span> icon, to attach citations.</p>-->
	<p class="text-warning"><i class="fas fa-check-circle"></i> = click to select/deselect citations</p>
<table class="table table-striped grid-table" width="100%" cellspacing="0" cellpadding="0">
	<?php $tr_class=""; ?>
    <?php foreach($rows as $row): ?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
			<tr class="citation-row <?php echo $tr_class; ?>" id="s_<?php echo $row['id']; ?>" >
				<?php $data=t('attach');?>
				<td class="<?php echo ($data == t('attach')) ? 'attached' : 'published'; ?>">
				<?php if (!in_array($row['id'],$selected_citations)):?>
					<a href="#" class="attach btn btn-default" data-isattached="0" item="<?php echo $row['id'];?>" title="<?php echo t('attach'); ?>" >
						<i class="far fa-circle"></i>
					</a>
				<?php else:?>
					<a href="#" class="remove btn btn-default" data-isattached="1"  item="<?php echo $row['id'];?>" title="<?php echo t('remove') ?>">
						<i class="fas fa-check-circle"></i>
					</a>
				<?php endif?>
				</td>
				<td><?php echo $this->chicago_citation->format($row,'journal',false);?></td>
			</tr>
    <?php endforeach;?>
</table>
</div>

<table width="100%">
    <tr>
        <td>
          <div class="nada-pagination">
              <em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?>
          </div>
		</td>
    </tr>
</table>
</div>
<?php else: ?>
<?php echo t('no_records_found');?>
<?php endif; ?>



</div>
</div>

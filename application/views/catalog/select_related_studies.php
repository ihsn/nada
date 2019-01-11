<style>
.hide-overflow{overflow:hidden;width:100%;height:23px;}
.uid{color:silver;}
.survey-sub-info{font-size:smaller;color:gray;}
.survey-title{font-weight:normal;}
.remove {width:100px;}
.attach{width:100px;}
</style>

<script type="text/javascript">
$(function() {
	$('.form-attach-related-study .attach').on('click',null, function() {
		sid_1=<?php echo $survey_id;?>;
		sid_2=$(this).parent(".form-attach-related-study").data('sid2');
		relationship_id=$(this).parent(".form-attach-related-study").find(".rel-type").val();
		url='<?php echo site_url('admin/catalog/update_related_study/');?>';
		url=url + '/' + sid_1 + '/' + sid_2 + '/' + relationship_id;
		$.get(url);
		$(this).html('<?php echo t('remove');?>');
		$(this).removeClass("attach").addClass("remove").addClass("btn-default").addClass("btn-warning");
		return false;
	});
	$('.form-attach-related-study .remove').on('click',null, function() {
		sid_1=<?php echo $survey_id;?>;
		sid_2=$(this).parent(".form-attach-related-study").data('sid2');
		relationship_id=$(this).parent(".form-attach-related-study").find(".rel-type").val();
		url='<?php echo site_url('admin/catalog/remove_related_study/');?>';
		url=url + '/' + sid_1 + '/' + sid_2 + '/' + relationship_id;
		$.get(url);
		$(this).html('<?php echo t('attach');?>');
		$(this).removeClass("remove").addClass("attach").removeClass("btn-warning").addClass("btn-default");
		return false;
	});

});
</script>




<?php
	//set default page size, if none selected
	if(!$this->input->get("ps"))
	{
		$ps=15;
	}
?>
<!--
<div>attached: <?php //var_dump($attached_studies);?></div>
<div>excluded: <?php //var_dump($excluded_studies);?></div>
-->


<nav class="navbar navbar-default" style="background:#337ab7;">
  <div class="container-fluid">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="navbar-header" >
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <div class="navbar-brand">
				<a style="color:white;" href="<?php echo site_url('admin/catalog/edit/'.$survey_id.'/related-data');?>">
					<span class="glyphicon glyphicon-arrow-left" aria-hidden="true"></span> <?php echo $survey_title;?></a>
			 </div>
    </div>

		<div class="navbar-right" style="margin-right:10px;">
		<a type="button" class="btn btn-info navbar-btn" href="<?php echo site_url('admin/catalog/edit/'.$survey_id.'/related-data');?>">
			<span class="glyphicon glyphicon-arrow-left" aria-hidden="true"></span> Return to edit page</a>
	</div>

</div>
</nav>

<div class="body-container container-fluid">

	<h2><span><?php echo t('attach_related_data');?></span></h2>

<!-- search form-->
<form class="left-pad" style="margin-bottom:10px;" method="GET" id="catalog-search">
  <input type="text" size="40" name="keywords" id="keywords" value="<?php echo form_prep($this->input->get('keywords')); ?>"/>
  <select name="field" id="field">
    <option value="title"	<?php echo ($this->input->get('field')=='title') ? 'selected="selected"' : '' ; ?> ><?php echo t('title');?></option>
    <option value="nation"	<?php echo ($this->input->get('field')=='nation') ? 'selected="selected"' : '' ; ?> ><?php echo t('country');?></option>
    <option value="year_start"	<?php echo ($this->input->get('field')=='year_start') ? 'selected="selected"' : '' ; ?> ><?php echo t('year');?></option>
    <option value="surveyid"><?php echo t('survey_id');?></option>
    <option value="authoring_entity"><?php echo t('producer');?></option>
  </select>
  <input type="submit" value="<?php echo t('search');?>" name="search" class="btn-search-submit"/>
  <?php if ($this->input->get("keywords")!=''): ?>
    <a href="<?php echo site_url('admin/catalog/attach_related_data/'.$survey_id);?>"><?php echo t('reset');?></a>
  <?php endif; ?>
<br/><br/>

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


<div id="related-surveys">

<div class="table-container">
<table class="grid-table table table-striped" cellspacing="0" cellpadding="0">
    	<tr class="header">
        	<?php $params_persist=array('keywords','field','ps','show_selected_only');?>
             <th><?php echo create_sort_link($sort_by,$sort_order,'id',t('ID'),$page_url,$params_persist); ?></th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'title',t('title'),$page_url,$params_persist); ?></th>
						<th><?php echo t('relationship_type');?></th>
        </tr>
	<?php $tr_class=""; ?>
    <?php foreach($rows as $row): ?>
		<?php
			//skip active survey from the list
			if($row['id']==$survey_id){ continue;}
		?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="table-row <?php echo $tr_class; ?>" id="s_<?php echo $row['id']; ?>" >
            <td><b><?php echo $row['id'];?></b></td>
            <td style="width:50%;">
							<div class="survey-title"><?php echo $row['title']; ?></div>
							<div class="survey-sub-info">
								<span class="">
									<?php echo $row['nation']; ?>
								</span>
								<span class="uid">ID: <?php echo $row['idno']; ?></span>
							</div>
						</td>
<!--
						<?php
							$data=t('attach');
							if ($this->session->userdata($this->input->get('id'))) {
								$img  = site_url() . '/../images/tick.png';
								$data = (in_array($row['id'], $attached_studies)) ? "<img src='{$img}' alt='tick' />" : t('attach');
							}
						?>
            <td class="<?php echo ($data == t('attach')) ? 'attached' : 'published'; ?>">
            	<?php if (!in_array($row['id'],$attached_studies)):?>
            	<a class="attach" data-value="<?php echo $row['id'];?>" href="<?php echo site_url();?>/admin/dialog_select_studies/add/<?php echo $row['id'];?>"><?php echo t('select'); ?></a>
                <?php else:?>
                <a class="remove" data-value="<?php echo $row['id'];?>" href="<?php echo site_url();?>/admin/dialog_select_studies/remove/<?php echo $row['id'];?>"><?php echo t('deselect') ?></a>
				<?php endif?>
            </td>
-->

				<?php $data=t('attach');?>
				<td class="<?php echo ($data == t('attach')) ? 'attached' : 'published'; ?>">
					<div class="form-attach-related-study" data-sid2="<?php echo $row['id'];?>">
					<?php echo form_dropdown('relation_id', $relationship_types, @$survey_relationships[$row['id']]['relationship_id'],'class="rel-type"'); ?>
					<?php if (!in_array($row['id'],$attached_studies)):?>
						<span href="#" class="attach btn btn-default btn-xs" item="<?php echo $row['id'];?>" title="<?php echo t('attach'); ?>" >
							<?php echo t('attach');?>
						</span>
					<?php else:?>
							<a href="#" class="remove btn btn-warning btn-xs" item="<?php echo $row['id'];?>" title="<?php echo t('remove') ?>">
								 <?php echo t('remove');?>
							</a>
					<?php endif?>
				</div>
				</td>

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
</form>

</div>

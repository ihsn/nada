<?php
	//set default page size, if none selected
	if(!$this->input->get("ps"))
	{
		$ps=15;
	}
?>
<div><?php var_dump($attached_studies);?></div>
<div class="body-container dialog-container">

<!-- search form-->
<form class="left-pad" style="margin-bottom:10px;" method="GET" id="catalog-search">	
  <input type="text" size="40" name="keywords" id="keywords" value="<?php echo form_prep($this->input->get('keywords')); ?>"/>
  <select name="field" id="field">
    <option value="titl"	<?php echo ($this->input->get('field')=='titl') ? 'selected="selected"' : '' ; ?> ><?php echo t('title');?></option>
    <option value="nation"	<?php echo ($this->input->get('field')=='nation') ? 'selected="selected"' : '' ; ?> ><?php echo t('country');?></option>
    <option value="proddate"	<?php echo ($this->input->get('field')=='proddate') ? 'selected="selected"' : '' ; ?> ><?php echo t('year');?></option>
    <option value="surveyid"><?php echo t('survey_id');?></option>
    <option value="authenty"><?php echo t('producer');?></option>
  </select>
  <input type="submit" value="<?php echo t('search');?>" name="search" class="btn-search-submit"/>
  <?php if ($this->input->get("keywords")!=''): ?>
    <a href="<?php echo site_url();?>/admin/dialog_select_studies"><?php echo t('reset');?></a>
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

<script type="text/javascript">
$(function() {
	$(document.body).on("click",".table-container a.attach", function(e){ 
		$.get($(this).attr("href"));
		$(this).html("<?php echo t('deselect'); ?>");
		$(this).removeClass("attach").addClass("remove");
		return false;
	});
	$(document.body).on("click","#related-surveys .table-container a.remove", function(){ 
		$.get($(this).attr("href"));
		$(this).html("<?php echo t('select'); ?>");	
		$(this).removeClass("remove").addClass("attach");
		return false;
	});
	
});
</script>

<div id="related-surveys">
<div style="text-align:right;font-size:11px;"><input type="checkbox" name="show" value="selected"/> show selected only</div>
<div class="table-container">
<table class="grid-table" cellspacing="0" cellpadding="0">
    	<tr class="header">
             <th><?php echo create_sort_link($sort_by,$sort_order,'id',t('id'),$page_url,array('keywords','field','ps')); ?></th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'titl',t('title'),$page_url,array('keywords','field','ps')); ?></th>
             <th><?php echo create_sort_link($sort_by,$sort_order,'nation',t('country'),$page_url,array('keywords','field','ps')); ?></th>                
            <th><?php echo create_sort_link($sort_by,$sort_order,'proddate',t('year'),$page_url,array('keywords','field','ps')); ?></th>
			<th>&nbsp;</th>
        </tr>
	<?php $tr_class=""; ?>
    <?php foreach($rows as $row): ?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="table-row <?php echo $tr_class; ?>" id="s_<?php echo $row['id']; ?>" >
            <td><b><?php echo $row['id'];?></b></td>
            <td><?php echo $row['titl']; ?></td>
            <td style="width:80px"><?php echo $row['nation'];?></td>
            <td><?php echo $row['proddate']; ?></td>
            <?php
				$data=t('attach');
				if ($this->session->userdata($this->input->get('id'))) {
					$id   = $this->session->userdata[$this->input->get('id')];
					$img  = site_url() . '/../images/tick.png';
					$data = (in_array($row['id'], $id)) ? "<img src='{$img}' alt='tick' />" : t('attach'); 
				}
			?>
            <td class="<?php echo ($data == t('attach')) ? 'attached' : 'published'; ?>">
            	<?php if (!in_array($row['id'],$attached_studies)):?>
            	<a class="attach" href="<?php echo site_url();?>/admin/related_surveys/add/<?php echo $this->sess_id;?>/<?php echo $row['id'];?>"><?php echo t('select'); ?></a>
                <?php else:?>
                <a class="remove" href="<?php echo site_url();?>/admin/related_surveys/remove/<?php echo $this->sess_id;?>/<?php echo $row['id'];?>"><?php echo t('deselect') ?></a>
				<?php endif?>                
            </td>
        </tr>
    <?php endforeach;?>
</table>    
</div>

<table width="100%">
    <tr>
        <td>    
            <div class="pagination">
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
<style>
.icon-legend{margin-top:20px;}
.icon-legend span{padding-right:5px;color:gray;}
.published{background:url(images/tick.png) no-repeat center;cursor:pointer; }
.unpublished{background:url(images/cross.png) no-repeat center; cursor:pointer;}
</style>

<div class="body-container" style="padding:10px;">
<?php if (!isset($hide_form)):?>
<div class="page-links">
	<a href="<?php echo site_url(); ?>/admin/citations/add" class="button"><img src="images/icon_plus.gif"/><?php echo t('add_new_citation');?></a> 
    <a href="<?php echo site_url(); ?>/admin/citations/import" class="button"><img src="images/icon_plus.gif"/><?php echo t('import_citation');?></a> 
</div>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<h1 class="page-title"><?php echo t('title_citations');?></h1>
<form class="left-pad" style="margin-bottom:10px;" method="GET" id="citation-search">
  <input type="text" size="40" name="keywords" id="keywords" value="<?php echo form_prep(get_form_value("keywords",$this->keywords)); ?>"/>
  <select name="field" id="field">
    <option value="all"		<?php echo ($this->field=='all') ? 'selected="selected"' : '' ; ?> ><?php echo t('all_fields');?></option>
    <option value="title"	<?php echo ($this->field=='title') ? 'selected="selected"' : '' ; ?> ><?php echo t('title');?></option>
    <option value="authors"	<?php echo ($this->field=='authors') ? 'selected="selected"' : '' ; ?> ><?php echo t('authors');?></option>    
    <option value="publisher"	<?php echo ($this->field=='publisher') ? 'selected="selected"' : '' ; ?> ><?php echo t('publisher');?></option>
    <option value="doi"	<?php echo ($this->field=='doi') ? 'selected="selected"' : '' ; ?> ><?php echo t('doi');?></option>
    <option value="keywords"	<?php echo ($this->field=='keywords') ? 'selected="selected"' : '' ; ?> ><?php echo t('keywords');?></option>
    <option value="notes"	<?php echo ($this->field=='notes') ? 'selected="selected"' : '' ; ?> ><?php echo t('notes');?></option>
    <option value="flag"	<?php echo ($this->field=='flag') ? 'selected="selected"' : '' ; ?> ><?php echo t('flag');?></option>
    <option value="owner"	<?php echo ($this->field=='owner') ? 'selected="selected"' : '' ; ?> ><?php echo t('citation_owner');?></option>
  </select>
  <input type="submit" value="<?php echo t('search');?>" name="search"/>
  <?php if ($this->keywords!==FALSE && $this->keywords!==''): ?>
    <a href="<?php echo site_url();?>/admin/citations/?reset=1"><?php echo t('reset');?></a>
  <?php endif; ?>
<?php endif; ?>
<?php if ($rows): ?>
<?php 
	//pagination 
	$page_nums=$this->pagination->create_links();
	$current_page=($this->pagination->cur_page == 0) ? 1 : $this->pagination->cur_page;
	
	//sort
	$sort_by=$this->sort_by;
	$sort_order=$this->sort_order;
	
	//current page url
	$page_url=site_url().$this->uri->uri_string();
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

<form autocomplete="off">
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
    
    <table class="grid-table" width="100%" cellspacing="0" cellpadding="0">
    	<tr class="header">
        	<th><input type="checkbox" value="-1" id="chk_toggle"/></th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'ctype',t('citation_type'),$page_url,array('keywords','field','ps')); ?></th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'title',t('title'),$page_url,array('keywords','field','ps')); ?></th>
			<th><?php echo create_sort_link($sort_by,$sort_order,'pub_year',t('date'),$page_url,array('keywords','field','ps')); ?></th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'published',t('published'),$page_url,array('keywords','field','ps')); ?></th>
			<th><?php echo create_sort_link($sort_by,$sort_order,'created',t('created'),$page_url,array('keywords','field','ps')); ?></th>            
            <th><?php echo create_sort_link($sort_by,$sort_order,'changed',t('modified'),$page_url,array('keywords','field','ps')); ?></th>
			<th><?php echo t('actions');?></th>
            <th>&nbsp;</th>
            <th>&nbsp;</th>
            <th><a href="<?php echo site_url();?>/admin/citations/?keywords=*&field=notes"><img title="<?php echo t('show_all_note_entries');?>" src="images/note.png"/></a></th>
            <th><a href="<?php echo site_url();?>/admin/citations/?keywords=*&field=flag"><img title="<?php echo t('show_all_flag_entries');?>" src="images/flag_yellow.png"/></a></th>
        </tr>
	<?php $tr_class=""; ?>
	<?php foreach($rows as $row): ?>
    	<?php $row=(object)$row; //var_dump($row);exit;?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="<?php echo $tr_class; ?>">
        	<td><input type="checkbox" value="<?php echo $row->id; ?>" class="chk"/></td>
            <td><?php echo t($row->ctype); ?></td>
            <td><a href="<?php echo current_url();?>/edit/<?php echo $row->id;?>"><?php echo $row->title; ?></a></td>
            <td nowrap="nowrap"><?php echo $row->pub_year; ?>&nbsp;</td>
            <td title="Click to publish/unpublish" class="<?php echo ($row->published==1 ? 'published' : 'unpublished'); ?>" id="<?php echo $row->id; ?>"></td>
            <td nowrap="nowrap"><?php echo date($this->config->item('date_format'), $row->created); ?></td>
			<td nowrap="nowrap"><?php echo date($this->config->item('date_format'), $row->changed); ?></td>            
			<td nowrap="nowrap"><a href="<?php echo current_url();?>/edit/<?php echo $row->id;?>"><?php echo t('edit');?></a> | 
            <a href="<?php echo current_url();?>/delete/<?php echo $row->id;?>/?destination=<?php echo $this->uri->uri_string();?>"><?php echo t('delete');?></a></td>
            <td nowrap="nowrap">
				<?php if (trim($row->owner)!==''):?>
	                <a href="<?php echo site_url(); ?>/admin/citations/?field=owner&keywords=<?php echo $row->owner; ?>"><img title="<?php echo $row->owner;?>" src="images/user.png"/></a>
                <?php endif;?>

            </td>
            <td nowrap="nowrap">
				<?php echo ($row->survey_count==0) ? '<img title="0" src="images/bullet_error.png"/>' : '<img title="'.$row->survey_count.'" src="images/bullet_green.png"/>' ?>
            </td>
            <td>    
                <?php if (trim($row->notes)!==''):?>
	                <img title="<?php echo $row->notes;?>" src="images/note.png"/>
                <?php endif;?>
            </td>
            <td>    
                <?php if (trim($row->flag)!==''):?>
	                <a href="<?php echo site_url(); ?>/admin/citations/?field=flag&keywords=<?php echo $row->flag; ?>"><img title="<?php echo $row->flag;?>" src="images/flag_yellow.png"/></a>
                <?php endif;?>
            </td>
        </tr>
    <?php endforeach;?>
    </table>
<table width="100%">
    <tr>
        <td>
        <?php echo t("select_number_of_records_per_page");?>:
        <?php echo form_dropdown('ps', array(5=>5,10=>10,15=>15,30=>30,50=>50,100=>100,500=>t('ALL')), get_form_value("ps",$this->per_page),'id="ps" style="font-size:10px;"'); ?>
        </td>
        <td>    
            <div class="pagination">
                    <em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?>
            </div>
		</td>
    </tr>
</table>

<div class="icon-legend">
<span><img src="images/user.png"/> <?php echo t('icon_user');?></span>
<span><img src="images/bullet_green.png"/> <?php echo t('icon_related_study');?></span>
<span><img src="images/bullet_error.png"/> <?php echo t('icon_no_related_study');?></span>
<span><img src="images/note.png"/> <?php echo t('icon_note');?></span>
<span><img src="images/flag_yellow.png"/> <?php echo t('icon_flag');?></span>
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
	
	bind_events();
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
		url: CI.base_url+'/admin/citations/delete/'+selected+'/?ajax=true',
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
  $('#citation-search').submit();
});

function bind_events()
{
	//remove events
    $(".unpublished, .published").unbind('click');
    
	$(".unpublished").click(
			function (e) {				
                $(this).removeClass('unpublished').addClass('published');bind_events();
                url=CI.base_url+'/admin/citations/publish/'+$(this).attr("id")+'/'+1;
                $.get(url);
			}
	);    

	$(".published").click(
			function (e) {				
				$(this).removeClass('published').addClass('unpublished');bind_events();
                url=CI.base_url+'/admin/citations/publish/'+$(this).attr("id")+'/'+0;
                $.get(url);
			}
	);    
}
</script>
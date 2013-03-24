<style>
.filter-box{margin:5px;margin-right:20px;}
.filter-box li{font-size:11px;}
.filter-box a{text-decoration:none;color:black;display:block;padding:3px;padding-left:15px;background:url('images/bullet_green.png') left top no-repeat;}
.filter-box a:hover{background:black;color:white;}
.filter-field{
border: 1px solid gainsboro;
-moz-border-radius: 5px;
-webkit-border-radius: 5px;
color: #333;
margin-bottom:10px;
}
.filter-title {
	font-size: 14px;
	text-transform: uppercase;
	padding: 5px;
	background: gainsboro;
}
body{margin:0px;padding:0px;font-size:12px;}
a.attach,a.remove{
	background:green;
	padding:3px;
	color:white;
	display:inline-block;
	-webkit-border-radius: 3px;
	-moz-border-radius: 3px;
	border-radius: 3px;}
a.remove{background:red;}
a.attach:hover, a.remove:hover{color:white;}
.table-container{height: 300px;
overflow: scroll;
overflow-x: auto;}
.ui-dialog .ui-dialog-titlebar{color:white;}
</style>
<?php
	//set default page size, if none selected
	if(!$this->input->get("ps"))
	{
		$ps=15;
	}
?>
<div class="body-container">

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<?php /*<h1 class="page-title"><?php echo t('related_survey_title');?></h1>*/?>

<!-- search form-->
<form class="left-pad" style="margin-bottom:10px;" method="GET" id="catalog-search">	
  <input type="text" size="40" name="keywords" id="keywords" value="<?php echo form_prep($this->input->get('keywords')); ?>"/>
  <select name="field" id="field">
    <option value="all"	<?php echo ($this->input->get('field')=='all') ? 'selected="selected"' : '' ; ?> ><?php echo t('all');?></option>
    <option value="titl"	<?php echo ($this->input->get('field')=='title') ? 'selected="selected"' : '' ; ?> ><?php echo t('title');?></option>
  </select>
  <input type="submit" value="<?php echo t('search');?>" name="search"/>
  <?php if ($this->input->get("keywords")!=''): ?>
    <a href="<?php echo site_url('/admin/related_citations/index/'.$survey_id);?>"><?php echo t('reset');?></a>
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
	$('a.attach').live('click', function() {
		id=$(this).parent().parent().children().first().children('b').html();
		url='<?php echo site_url('/admin/related_citations/add/'.$survey_id);?>/'+$(this).attr("item");
		$.get(url);
		$(this).html("<?php echo t('remove'); ?>");
		$(this).removeClass("attach").addClass("remove");
		return false;
	});
	$('a.remove').live('click', function() {	
		id=$(this).parent().parent().children().first().children('b').html();
		url='<?php echo site_url('/admin/related_citations/remove/'.$survey_id);?>/'+$(this).attr("item");
		$.get(url);
		$(this).html("<?php echo t('attach'); ?>");
		$(this).removeClass("remove").addClass("attach");
		return false;
	});
	
});
</script>

<div id="related-citations">

<div class="table-container">
<table class="grid-table" width="100%" cellspacing="0" cellpadding="0">
    	<tr class="header">
            <th><?php echo create_sort_link($sort_by,$sort_order,'title',t('title'),$page_url,array('keywords','field','ps')); ?></th>
			<th>&nbsp;</th>
        </tr>
	<?php $tr_class=""; ?>
    <?php foreach($rows as $row): ?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="row <?php echo $tr_class; ?>" id="s_<?php echo $row['id']; ?>" >
            <td><?php echo $this->chicago_citation->format($row,'journal',false);?></td>
            <?php $data=t('attach');?>
            <td class="<?php echo ($data == t('attach')) ? 'attached' : 'published'; ?>">
            	<?php if (!in_array($row['id'],$selected_citations)):?>
	            	<a class="attach" item="<?php echo $row['id'];?>" href="#"><?php echo t('attach'); ?></a>
                <?php else:?>
    	            <a class="remove" item="<?php echo $row['id'];?>" href="#"><?php echo t('remove') ?></a>
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
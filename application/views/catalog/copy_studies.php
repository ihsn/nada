<style>
.box1{padding:10px; background-color:#FFFFCC;border:1px solid gainsboro;margin-top:10px;margin-bottom:15px;}
span.active-repo{font-size:smaller;color:gray;}
</style>
<?php
	//set default page size, if none selected
	if(!$this->input->get("ps"))
	{
		$ps=15;
	}
?>
<div class="body-container" style="padding:10px;">
<?php include 'catalog_page_links.php';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<h1 class="page-title">
	<?php echo t('copy_studies_to');?>
    <?php if (isset($this->active_repo) && $this->active_repo!=NULL):?>
    	<span class="active-repo">[<?php echo $this->active_repo->title;?>]</span>
    <?php endif;?>
</h1>

<div class="box1"><?php echo t('msg_copy_studies');?></div>

<!-- search form-->
<form class="left-pad" style="margin-bottom:10px;" method="GET" id="catalog-search">	
  <input type="text" size="40" name="keywords" id="keywords" value="<?php echo form_prep($this->input->get('keywords')); ?>"/>
  <select name="field" id="field">
    <option value="all"		<?php echo ($this->input->get('field')=='all') ? 'selected="selected"' : '' ; ?> ><?php echo t('all_fields');?></option>
    <option value="titl"	<?php echo ($this->input->get('field')=='titl') ? 'selected="selected"' : '' ; ?> ><?php echo t('title');?></option>
    <option value="nation"	<?php echo ($this->input->get('field')=='nation') ? 'selected="selected"' : '' ; ?> ><?php echo t('country');?></option>
    <option value="surveyid"><?php echo t('survey_id');?></option>
    <option value="authenty"><?php echo t('producer');?></option>
    <option value="sponsor"><?php echo t('sponsor');?></option>
    <option value="repositoryid"><?php echo t('repository');?></option>
  </select>
  <input type="submit" value="<?php echo t('search');?>" name="search"/>
  <?php if ($this->input->get("keywords")!=''): ?>
    <a href="<?php echo site_url();?>/admin/catalog"><?php echo t('reset');?></a>
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

<table width="100%">
    <tr>
        <td>
        </td>
        <td align="right">
            <div class="pagination"><em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?></div>
        </td>
    </tr>
</table>

<div id="surveys">
	<?php $tr_class=""; ?>
    <table class="grid-table" width="100%" cellspacing="0" cellpadding="0">
    <tr class="header">
    		<th><input type="checkbox" id="chk_toggle"/></th>
         	<?php if ($this->config->item("regional_search")=='yes'):?>
            	<th><?php echo t('repository');?></th>
			  	<th><?php echo create_sort_link($sort_by,$sort_order,'nation',t('country'),$page_url); ?></th>
            <?php endif;?>
			<th><?php echo create_sort_link($sort_by,$sort_order,'title',t('title'),$page_url); ?></th>
			<th><?php echo create_sort_link($sort_by,$sort_order,'changed',t('modified'),$page_url); ?></th>
			<th><?php echo t('actions');?></th>
        </tr>
	<?php foreach($rows as $row): ?>
	    <?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
        <tr class="<?php echo $tr_class;?>">
        	<td><input type="checkbox" id="chk_toggle"/></td>
         	<?php if ($this->config->item("regional_search")=='yes'):?>
            	<td><?php echo $row['repositoryid'];?></td>
			  	<td><?php echo $row['nation'];?></td>
            <?php endif;?>
            <td><?php echo $row['titl']; ?></td>
            <td><?php echo date($this->config->item('date_format_long'), $row['changed']); ?></td>
            <td><a class="repo-link" href="<?php echo site_url();?>/admin/catalog/do_copy_study/<?php echo $this->active_repo->repositoryid;?>/<?php echo $row['id'];?>"><img class="copy-study" src="images/bullet-gray.gif" alt="COPY" title="<?php echo t('alt_copy_study')?>"/></a></td>
        </tr>        
    <?php endforeach;?>
	</table>
<table width="100%">
    <tr>
        <td>
        <?php echo t("select_number_of_records_per_page");?>:
        <?php echo form_dropdown('ps', array(5=>5,10=>10,15=>15,30=>30,50=>50,100=>100,500=>t('ALL')), get_form_value("ps",isset($ps) ? $ps : ''),'id="ps" style="font-size:10px;"'); ?>
        </td>
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

<script type='text/javascript'>

//checkbox select/deselect
jQuery(document).ready(function(){

	$(".repo-link").click(
		function (e) 
		{
			var obj=$(this);
			obj.html('<img class="loading" src="images/loading.gif"/>');
			$.ajax({
				timeout:1000*120,
				cache:false,
				dataType: "json",
				data:{ submit: "submit"},
				type:'POST', 
				url: $(this).attr("href"),
				success: function(data) {
					if (data.success){
						obj.html('<img class="loading" src="images/bullet-green.gif"/>');
					}
					else{
						alert(data.error);
					}
				},
				error: function(XHR, textStatus, thrownError) {
					alert("Error occured " + XHR.status);
				}
			});	
			return false;
		}
	);
});

//page change
$('#ps').change(function() {
  $('#catalog-search').submit();
});
</script>
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
span.active-repo{font-size:smaller;color:gray;}
span.link-change{font-size:10px;padding-left:5px;}
.unlink-study .linked{padding-left:20px;}
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
	<?php echo t('catalog_maintenance');?>
    <?php if (isset($this->active_repo) && $this->active_repo!=NULL):?>
    	<span class="active-repo">[<?php echo $this->active_repo->title;?>]</span><span class="link-change"><?php echo anchor('admin/repositories/select',t('change_repo'));?></span>
    <?php endif;?>
</h1>

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
            <select id="batch_actions">
                <option value="-1"><?php echo t('batch_actions');?></option>
                <option value="transfer"><?php echo t('transfer_ownership');?></option>
                <option value="delete"><?php echo t('delete');?></option>
            </select>
            <input type="button" id="batch_actions_apply" name="batch_actions_apply" value="<?php echo t('apply');?>"/>              
        </td>
        <td align="right">
            <div class="pagination"><em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?></div>
        </td>
    </tr>
</table>

<div id="surveys">

<table class="grid-table" width="100%" cellspacing="0" cellpadding="0">
    	<tr class="header">
        	<th><input type="checkbox" value="-1" id="chk_toggle"/></th>
            <?php if ($this->config->item("regional_search")=='yes'):?>            
                <th><?php echo create_sort_link($sort_by,$sort_order,'repositoryid',t('repositoryid'),$page_url,array('keywords','field','ps')); ?></th>
                <th><?php echo create_sort_link($sort_by,$sort_order,'nation',t('country'),$page_url,array('keywords','field','ps')); ?></th>                
            <?php endif;?> 
            <th><?php echo create_sort_link($sort_by,$sort_order,'titl',t('title'),$page_url,array('keywords','field','ps')); ?></th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'changed',t('modified'),$page_url,array('keywords','field','ps')); ?></th>
			<th>&nbsp;</th>
        </tr>
	<?php $tr_class=""; ?>
    <?php foreach($rows as $row): ?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="row <?php echo $tr_class; ?>" id="s_<?php echo $row['id']; ?>" >
        	<td><input type="checkbox" value="<?php echo $row['id']; ?>" class="chk"/></td>
			<?php if ($this->config->item("regional_search")=='yes'):?>
                <td><b><?php echo $row['repositoryid'];?></b></td>
                <td><?php echo $row['nation'];?></td>
            <?php endif;?>
            <td><?php echo $row['titl']; ?></td>
            <td><?php echo date($this->config->item('date_format_long'), $row['changed']); ?></td>
            <td nowrap="nowrap">
            	<?php if ($row['repo_isadmin']==0):?>
                <span class="icon linked" title="<?php echo t('is_harvested_study');?>"></span>
                <?php elseif ($row['repo_isadmin']==1):?>
				<span class="icon owned" title="<?php echo t('study_owned');?>"></span>
				<?php endif;?>
                
            </td>
        </tr>
        <tr class="study-info hide" id="s_<?php echo $row['id']; ?>_info">
        	<td class="study-info-box" colspan="<?php echo ($this->config->item("regional_search")=='yes') ? '6': '4'; ?>">--survey-info-box--</td>
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
//translations	
var i18n={
		'no_item_selected':"<?php echo t('js_no_item_selected');?>",
		'confirm_delete':"<?php echo t('js_confirm_delete');?>",
		'js_loading':"<?php echo t('js_loading');?>"
		};
</script>
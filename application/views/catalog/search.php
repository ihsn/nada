<?php if (!$rows): ?>
	<?php echo t('no_records_found');return;?>
<?php endif;?>
<?php		
	//pagination 
	$page_nums=$this->pagination->create_links();
	$current_page=($this->pagination->cur_page == 0) ? 1 : $this->pagination->cur_page;

	$sort_by=$this->input->get("sort_by");
	$sort_order=$this->input->get("sort_order");
	
	if (!$sort_by)
	{
		$sort_by='created';
	}
	
	//current page url
	$page_url=site_url('admin/catalog/');
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

<?php
	$this->load->helper('catalog_admin_helper');
	$filters=filter();
?>

<?php if ($filters):?>
	<div class="filter-container label">
    	<div class="filter-info">Filter: 
		<?php foreach($filters as $f):?>
        	<span class="label filter"><?php echo $f;?></span>
        <?php endforeach;?>
        </div>
        <a class="clear-filter" href="<?php echo site_url('admin/catalog/');?>/?reset=reset">Clear filter</a>
    </div>
<?php endif;?>

<?php
//persist vars for sorting
$qs_sort=array('titl','nation','surveyid','ps','tag','published','producer');
?>
<table width="100%">
<tr>
<td>	
	<span><?php echo t('sort_by');?></span>
  <ul class="sort_by">
        <?php if ($this->config->item("regional_search")=='yes'):?>            
        <li><?php echo create_sort_link($sort_by,$sort_order,'repositoryid',t('repositoryid'),$page_url,$qs_sort); ?></li>
        <li><?php echo create_sort_link($sort_by,$sort_order,'nation',t('country'),$page_url,$qs_sort); ?>          </li>
    <?php endif;?> 
    <li><?php echo create_sort_link($sort_by,$sort_order,'titl',t('title'),$page_url,$qs_sort); ?></li>
    <li><?php echo create_sort_link($sort_by,$sort_order,'surveyid',t('surveyid'),$page_url,$qs_sort); ?></li>
    <li><?php echo create_sort_link($sort_by,$sort_order,'changed',t('modified'),$page_url,$qs_sort); ?></li>
  </ul>
</td>
<td>
<div class="pagination">
	<em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?>
</div>
</td>
</tr>
<tr style="background:gainsboro;">
    <td>
        <input type="checkbox" value="-1" id="chk_toggle" style="margin-left:8px;"/>
        <select id="batch_actions" >
            <option value="-1"><?php echo t('batch_actions');?></option>
            <option value="transfer"><?php echo t('transfer_ownership');?></option>
            <option value="publish"><?php echo t('publish');?></option>
            <option value="unpublish"><?php echo t('unpublish');?></option>
            <option value="delete"><?php echo t('delete');?></option>
        </select>
        <input class="" type="button" id="batch_actions_apply" name="batch_actions_apply" value="<?php echo t('apply');?>"/>
        <span style="padding-right:20px"></span>
        </td>
    <td align="right">

    </td>
</tr>
</table>

<table class="grid-table" width="100%" cellspacing="0" cellpadding="0" >
    <?php $tr_class=""; ?>
    <?php foreach($rows as $row): ?>
    	<?php //var_dump($row);?>
        <?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
        <tr class="<?php echo $tr_class; ?>" id="s_<?php echo $row['id']; ?>"  valign="top">
            <td><input type="checkbox" value="<?php echo $row['id']; ?>" class="chk"/></td>
            <td>
                    <div class="survey-row">
                        <h3>
                            <a href="<?php echo site_url().'/admin/catalog/edit/'.$row['id'];?>">
                            <?php if ($this->config->item("regional_search")=='yes'):?> 
                                <?php echo $row['nation'];?> -
                            <?php endif;?>
                            <?php echo $row['titl'];?>
                            <?php if (!$row['published']):?>
                            	<span class="label" title="<?php echo t('unpublished');?>">Draft</span>
                        	<?php endif;?>
                            </a>
                        </h3>
                        <!--
                        <table>
                        	<tr>
                            <td></td>
                            <td></td>
                            </tr>
                        </table>
                        -->
                        <div class="table-row">
                        	<span class="cell-label">SurveyID:</span>
							<span class="cell-value"><?php echo $row['surveyid'];?></span>
                        </div>						

                        <?php /* ?>
						<div class="table-row">
                        	<span class="cell-label">Producers:</span>
							<span class="cell-value"><?php echo $row['authenty'];?></span>
                        </div>
                        <?php */?>
                        <div class="table-row">
                        	<span class="cell-label"><?php echo t('Repository');?>:</span>
                            <span class="cell-value">
                                <!-- repository ownership -->
                                <?php if ($row['repositories']):?>
                                    <?php foreach($row['repositories'] as $repo):?>
                                    	<?php if ($repo['isadmin']==1):?>
											<span class="repo-owner"><?php echo strtoupper($repo['repositoryid']);?></span>
                                        <?php else:?>
                                            <span class="repo-link" ><?php echo strtoupper($repo['repositoryid']);?></span>
                                        <?php endif;?>
                                    <?php endforeach;?>
                                <?php else:?>
                                	<span class="repo-owner"><?php echo strtoupper($row['repositoryid']);?></span>
                              <?php endif;?>  
                            </span>
                        </div>
                        
                        <div class="table-row">
                        	<span class="cell-label"><?php echo t('Modified on')?>:</span>
							<span class="cell-value"><?php echo date($this->config->item('date_format'), $row['changed']); ?></span>
                        </div>
                        
                        <?php if (isset($row['tags'])):?>
                        <div class="table-row">
                        	<span class="cell-label">Tags:</span>
							<span class="cell-value">
								<?php foreach($row['tags'] as $tag):?>
                                    <span class="label"><?php echo $tag;?></span>
                                <?php endforeach;?>
                            </span>
                        </div>                                                            
                        <?php endif;?>
                        
                        <div class="links">
                        
                        <span style="float:left;">
                            <span><a href="<?php echo site_url();?>/admin/catalog/edit/<?php echo $row['id'];?>">Edit</a></span> | 
                            <span><a href="<?php echo site_url();?>/admin/catalog/delete/<?php echo $row['id'];?>">Delete</a></span>
                        </span>
                        
                        <span class="survey-options">
                        
                        <!-- survey data access type -->
						<?php if ($row['form_model']!=''):?>                            
                            <?php if($row['form_model']=='direct'): ?>
                                <span title="<?php echo t('link_data_direct_hover');?>"><img src="images/form_direct.gif" /></span>                    
                            <?php elseif($row['form_model']=='public'): ?>
                                <span  title="<?php echo t('link_data_public_hover');?>"><img src="images/form_public.gif" /></span>
                            <?php elseif($row['form_model']=='data_na'): ?>                            	
                                <span  title="<?php echo t('link_data_na_hover');?>" class="" ><img src="images/form_na.png" /></span>                    
                            <?php elseif($row['form_model']=='licensed'): ?>
                            		<a href="<?php echo site_url('admin/licensed_requests');?>">
                            		<?php if (isset($row['pending_lic_requests'])):?>
											<span class="da-icon" title="<?php echo t('link_data_licensed_hover');?>"><img src="images/form_licensed.gif" /> (<?php echo $row['pending_lic_requests'];?>) </span>
                                    <?php else:?>
                                            <span class="da-icon" title="<?php echo t('link_data_licensed_hover');?>"><img src="images/form_licensed.gif" /></span>
                                    <?php endif;?>
                                    </a>
                            <?php elseif($row['form_model']=='data_enclave'): ?>
                                <span title="<?php echo t('link_data_enclave_hover');?>"><img src="images/form_enclave.gif" /></span>
                            <?php elseif($row['form_model']=='remote'): ?>
                                <?php //if (isset($row['link_da']) && strlen($row['link_da'])>1):?>
                                <a class="data-access" target="_blank" href="<?php echo $row['link_da']; ?>"  title="<?php echo $row['link_da']; ?>" >
                                <span title="<?php echo $row['link_da']; ?>"><img src="images/form_remote.gif" /></span>
                                </a>
                                <?php //endif; ?>
                            <?php endif; ?>
                        <?php endif;?>
						</span>                                                
                        
                        </div>
                    </div>
            </td>            
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
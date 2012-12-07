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
	<span><?php echo t('sort_by');?></span>
  <ul class="sort_by">
        <?php if ($this->config->item("regional_search")=='yes'):?>            
        <li><?php echo create_sort_link($sort_by,$sort_order,'repositoryid',t('repositoryid'),$page_url,array('keywords','field','ps')); ?></li>
        <li><?php echo create_sort_link($sort_by,$sort_order,'nation',t('country'),$page_url,array('keywords','field','ps')); ?>          </li>
    <?php endif;?> 
    <li><?php echo create_sort_link($sort_by,$sort_order,'titl',t('title'),$page_url,array('keywords','field','ps')); ?></li>
    <li><?php echo create_sort_link($sort_by,$sort_order,'surveyid',t('surveyid'),$page_url,array('keywords','field','ps')); ?></li>
    <li><?php echo create_sort_link($sort_by,$sort_order,'changed',t('modified'),$page_url,array('keywords','field','ps')); ?></li>
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
                        <div>Producers: <?php echo $row['authenty'];?></div>
                        <div>SurveyID: <?php echo $row['surveyid'];?></div>						
                        <div>Repository: <?php echo $row['repositoryid'];?>, 
                         modified on: <?php echo date($this->config->item('date_format'), $row['changed']); ?></div>
                        
                        <div class="links">
                        
                        <span><a href="<?php echo site_url();?>/admin/catalog/edit/<?php echo $row['id'];?>">Edit</a></span> | 
                        <span><a href="<?php echo site_url();?>/admin/catalog/delete/<?php echo $row['id'];?>">Delete</a></span>
                        
                        <span class="tags">
							<?php if ($row['repo_isadmin']==0):?>
                                <span class="label" title="<?php echo t('is_harvested_study');?>">Linked</span>
                            <?php elseif ($row['repo_isadmin']==1):?>
                                <span class="label label-success" title="<?php echo t('study_owned');?>">Owned</span>
                            <?php endif;?>                                                
    
                            <?php if (isset($row['tags'])):?>                        
                                <?php foreach($row['tags'] as $tag):?>
                                    <span class="label"><?php echo $tag;?></span>
                                <?php endforeach;?>                            
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
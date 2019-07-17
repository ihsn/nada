<?php if (!$rows): ?>
    <?php echo t('no_records_found');?>
    <?php return;?>
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

<?php
	$this->load->helper('catalog_admin_helper');
	$filters=filter();
?>


<!--search tokens-->
<?php if ($filters):?>
	<div class="filter-container-x" style="padding-15px;margin-bottom:20px;">
        <div class="filter-infox"><strong><?php echo t('search_results_for');?></strong>
        <?php foreach($filters as $f):?>            
        	<span class="label label-info filter"><?php echo $f;?></span>
        <?php endforeach;?>
        <a class="btn btn-default btn-xs clear-filter" href="<?php echo site_url('admin/catalog/');?>/?reset=reset"><?php echo t('clear_filter');?></a>
        </div>
    </div>
<?php endif;?>

<?php
//persist vars for sorting
$qs_sort=array('ps','title','idno','published','nation','tag','no_question','no_datafile','dtype');
?>
<table width="100%">
<tr>
<td>	
	<span><?php echo t('sort_by');?></span>
    <ul class="sort_by">
        <?php if ($this->config->item("regional_search")=='yes'):?>            
            <li><?php echo create_sort_link($sort_by,$sort_order,'nation',t('country'),$page_url,$qs_sort); ?></li>
        <?php endif;?> 
        <li><?php echo create_sort_link($sort_by,$sort_order,'title',t('title'),$page_url,$qs_sort); ?></li>
        <li><?php echo create_sort_link($sort_by,$sort_order,'idno',t('idno'),$page_url,$qs_sort); ?></li>
        <li><?php echo create_sort_link($sort_by,$sort_order,'changed',t('modified'),$page_url,$qs_sort); ?></li>
    </ul>
</td>
<td>
<div class="nada-pagination">
	<em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?>
</div>
</td>
</tr>
<tr style="background:gainsboro;">
    <td style="padding:5px;padding-left:0px" class="form-inline">
        <input type="checkbox" value="-1" id="chk_toggle" style="margin-left:8px;"/>
        <select id="batch_actions" class="form-control">
            <option value="-1"><?php echo t('batch_actions');?></option>
            <option value="transfer"><?php echo t('transfer_ownership');?></option>
            <option value="publish"><?php echo t('publish');?></option>
            <option value="unpublish"><?php echo t('unpublish');?></option>
            <option value="delete"><?php echo t('delete');?></option>
        </select>
        <input class="btn btn-secondary btn-sm" type="button" id="batch_actions_apply" name="batch_actions_apply" value="<?php echo t('apply');?>"/>
        <span style="padding-right:20px"></span>
        </td>
    <td align="right">

    </td>
</tr>
</table>

<table class="table table-striped" width="100%" cellspacing="0" cellpadding="0" >
    <?php $tr_class=""; ?>
    <?php foreach($rows as $row): ?>
        <?php 
			//is_owned by collection
            $study_ownership=($active_repo_obj->repositoryid==$row['repositoryid']) ? 'owned' : 'linked';
            
            if ($row['form_model']==''){
                $row['form_model']='data_na';
            }
		?>
        <?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
        <tr class="<?php echo $tr_class; ?> study-<?php echo $study_ownership;?>" id="s_<?php echo $row['id']; ?>"  valign="top">
            <td><input type="checkbox" value="<?php echo $row['id']; ?>" class="chk"/></td>
            <td>
                    <div class="survey-row">
                    	<div class="data-access-icon data-access-<?php echo $row['form_model'];?>" title="<?php echo $row['form_model'];?>"></div>
                        <h3>
                            <a href="<?php echo site_url().'/admin/catalog/edit/'.$row['id'];?>"><?php echo $row['title'];?></a>
                            <!--<span class="data-access-type"><?php echo t($row['form_model']);?></span>-->
                            
                        </h3>
                        <?php 
							$study_years=array_unique(array($row['year_start'],$row['year_end']));
							$study_years=implode(" - ",$study_years);
						?>
                        <div class="sub-title">
							<?php echo $row['nation'];?>
							 <?php if ($study_years==0):?>
                            	<!--<span class="label label-warning"><?php echo t('Year Missing');?></span>-->
                            <?php else:?>
	                            <?php echo $study_years;?>
                            <?php endif;?>
                            - 
                            <span class="dataset-idno"><?php echo $row['idno'];?></span>
                        </div>

                        <!--
                        <div class="table-row">
                        	<span class="cell-label"><?php echo t('ID');?>:</span>
							<span class="cell-value"><?php echo $row['idno'];?></span>
                        </div>						
                        !-->

                        <div class="table-row">
                            
                            <span class="subgroup">
                        	<span><?php echo t('collection');?>:</span>
                            <span>
                                <!-- repository ownership -->
                                <?php if ($row['repositories']):?>
                                    <?php foreach($row['repositories'] as $repo):?>
                                    	<?php if ($repo['isadmin']==1):?>
											<span class="label label-primary" title="<?php echo t('Owner');?>" ><?php echo strtoupper($repo['repositoryid']);?></span>
                                        <?php else:?>
                                            <span class="label label-default" title="<?php echo t('Linked');?>" ><?php echo strtoupper($repo['repositoryid']);?></span>
                                        <?php endif;?>
                                    <?php endforeach;?>
                                <?php else:?>
                                	<span class="label label-primary"><?php echo strtoupper($row['repositoryid']);?></span>
                              <?php endif;?>  
                            </span>
                            </span>

                            
                            <?php if (isset($row['tags']) &&  count($row['tags'])>0):?>
                            <span class="subgroup">
                                <span><?php echo t('tags');?>:</span>
                                <span>
                                    <?php foreach($row['tags'] as $tag):?>
                                        <span class="label label-warning"><?php echo $tag;?></span>
                                    <?php endforeach;?>
                                </span>                                                       
                            </span>
                            <?php endif;?>
                            
                        </div>
                        
                        
                        
                        
                        <div class="links">
                        
                        <span style="float:left;">
                        <span class="subgroup">
                            <span class="date-changed">
                                <?php echo t('modified_on')?>:    
                                <?php echo date($this->config->item('date_format'), $row['changed']); ?>
                            </span>
                        </span>

                            <span><a href="<?php echo site_url();?>/admin/catalog/edit/<?php echo $row['id'];?>"><?php echo t('edit');?></a></span> | 
                            <?php if($study_ownership=='owned'):?>
                            	<span><a href="<?php echo site_url();?>/admin/catalog/delete/<?php echo $row['id'];?>"><?php echo t('delete');?></a></span>
                            <?php elseif($study_ownership=='linked'):?>
                            	<span><a title="<?php echo t('remove_from_collection_description');?>" href="<?php echo site_url('/admin/catalog/unlink/'.$active_repo_obj->repositoryid.'/'.$row['id']);?>"><?php echo t('remove_from_collection');?></a></span>
                            <?php endif;?>
                            
                            

                        </span>
                        
                        <span class="survey-options">
                        
						</span>                                                
                        
                        </div>
                    </div>
            </td>
            <td class="col-published">
                        <div class="actions">
                        	<div class="status">
                                <?php $published_checked=(!$row['published']) ? '' :'checked="checked"';?>                             
                                <input class="publish-toggle" type="checkbox" data-sid="<?php echo $row['id'];?>" <?php echo $published_checked;?> data-toggle="toggle" data-on="<?php echo t('published');?>" data-off="<?php echo t('draft');?>" data-onstyle="success" data-offstyle="danger">
                            </div>
                            
                            <?php if (isset($row['pending_lic_requests'])):?>
                            	<div class="info"><span class="badge badge-warning"><?php echo $row['pending_lic_requests'];?></span> <?php echo t('pending requests');?></div>
                            <?php endif;?>                            
                        </div>
            </td>
        </tr>
    <?php endforeach;?>
</table>    

<table width="100%" style="border-top:1px solid gainsboro;margin-top:10px;">
<tr>
    <td>
    <?php echo t("select_number_of_records_per_page");?>:
    <?php echo form_dropdown('pagesize', array(15=>15,30=>30,50=>50,100=>100,500=>t('ALL')), get_form_value("pagesize",isset($ps) ? $ps : ''),'id="pagesize" style="font-size:10px;"'); ?>
    </td>
    <td>    
        <div class="nada-pagination">
                <em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?>
        </div>
    </td>
</tr>
</table>

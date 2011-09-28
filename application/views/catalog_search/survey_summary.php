<style>
/*grid/table listing format*/
.grid-table{width:100%;}
.grid-table .header{background-color:none;text-align:left;}
.grid-table .header th{padding:5px;border-bottom:2px solid #C1DAD7;border-top:2px solid #C1DAD7;}
.grid-table .header a, .grid-table .header {color:black;}
.grid-table tr td{padding:5px;border-bottom:1px solid #C1DAD7;}
.grid-table .alternate{background-color:#FBFBFB}
.grid-table a{color:#00679C;text-decoration:none;}
.grid-table a:hover{color:maroon;}
.table-heading-row{}
.table-heading-cell{font-weight:bold;font-size:12px;border-bottom:2px solid #CFD9FE;}
.table-row{}
.table-row-alternate{background:#F7F7F7}
.table-cell,.table-cell-alternate{padding:5px;border-bottom:1px solid #F7F7F7;}
.table a{text-decoration:none;color:#003366}
.table a:hover{text-decoration:underline;color:black}
.links img ,.content-container img {vertical-align:bottom;}
.link-col {float:left;display:block;margin-right:10px;}
.link-col-2{float:left;display:block;}
.es td{background-color:#EAEAEA}
.grid-table td {vertical-align:top;}
.box-harvested{background-color:#D0E6F4;border:1px solid #99CCFF; padding:10px;margin-top:10px;margin-bottom:20px;font-size:14px;}
</style>

<div class="content-container">
<?php if ($this->input->get("print")) :?>
<div style="padding-bottom:20px;">
<h1><?php echo $nation;?> - <?php echo $titl;?></h1></div>
<?php endif;?>

<?php if($this->harvested):?>    
<div class="box-harvested">
<?php echo sprintf(t('harvested_study_access_complete_metadata'),$this->harvested['repo_title'], $this->harvested['survey_url']);?>
</div>
<?php endif?>

<table class="grid-table" cellspacing="0">
	<tr class="header">
    	<td><?php echo t('refno');?></td>
        <td><?php echo $refno;?></td>
    </tr>
	<tr>
    	<td style="width:100px;"><?php echo t('year');?></td>
        <td><?php 
				if ($data_coll_start==$data_coll_end)
				{
					echo $data_coll_start;
				}
				else
				{
					if ($data_coll_start!='')
					{
						$dates[]=$data_coll_start;
					}
					if ($data_coll_end!='')
					{
						$dates[]=$data_coll_end;
					}						
					echo implode(" - ", $dates);
				}?>
        </td>
    </tr>
	<?php if ($nation!=''):?>
	<tr>
    	<td><?php echo t('country');?></td>
        <td><?php echo $nation;?></td>
    </tr>
	<?php endif;?>
	<tr valign="top">
    	<td><?php echo t('producers');?></td>
        <td>
        	<?php if (isset($authenty)):?>
				<?php $authenty_arr=json_decode($authenty);?>
                <?php if (is_array($authenty_arr)):?>
                    <?php echo implode("<BR>",$authenty_arr);?>
                <?php else:?>
                    <?php echo $authenty;?>
                <?php endif;?>
            <?php endif;?>    
        </td>
    </tr>
    <?php if (strlen($sponsor)>5):?>
	<tr valign="top">
    	<td><?php echo t('sponsors');?></td>
        <td><?php echo $sponsor;?></td>
    </tr>
    <?php endif;?>

	<?php 
		//IE Template fields 
		if ($this->config->item("study_template")=='dime')
		{
			require_once('survey_summary_ie.php');
		}
	?>
    
    <?php /*if($this->harvested):?>
	<tr class="es">
    	<td><?php echo t('source');?></td>
        <td>Metadata provided by the <?php echo $this->harvested['repo_title']; ?>. <?php echo anchor($this->harvested['survey_url'],'Click here','target="_blank"'); ?> to access the data and related materials for this study.
        </td>
    </tr>
	<?php endif;*/?>

	<?php if (isset($this->collections) && is_array($this->collections)):?>
	<tr valign="top">
    	<td><?php echo t('collections');?></td>
        <td>
		<?php foreach($this->collections as $collection):?>
		<?php echo $collection;?> <br />
        <?php endforeach;?>
        </td>
    </tr>
	<?php endif;?>

	<?php $report_file=unix_path($this->survey_folder.'/ddi-documentation-'.$this->config->item("language").'-'.$id.'.pdf');?>
    <?php if (file_exists($report_file)):?>    
    <tr>
    	<td><?php echo t('metadata');?></td>
        <td class="links">                                
            <span class="link-col">
                <a href="<?php echo site_url()."/ddibrowser/$id/export/?format=pdf&generate=yes";?>" title="<?php echo t('pdf');?>" rel="nofollow">
                <img border="0" title="<?php echo t('link_pdf');?>" alt="PDF" src="images/pdf.gif" /> Documentation in PDF
                </a>
            </span>    
        </td>
    </tr>
	<?php endif;?>
    
    <tr>
    	<td><?php echo t('data');?></td>
        <td>
            <!--access policy -->
            <span class="link-col">
            <a id="ap-<?php echo $id;?>" class="accesspolicy"  title="<?php echo t('link_access_policy_hover');?>" href="<?php echo site_url().'/catalog/'.$id.'/accesspolicy/';?>">
                <span><img src="images/page_white_key.png" /> <?php echo t('link_access_policy');?></span>
            </a>
            </span>
            			
            <?php if ($this->harvested):?>
				<?php $obj_harvest=(object)$this->harvested;?>
                <!--data access -->
                <span class="link-col-2">
                <?php if($obj_harvest->accesspolicy=='direct'): ?>
                    <span><img src="images/form_direct.gif" /> <?php echo t('link_data_direct_hover');?></span>
                <?php elseif($obj_harvest->accesspolicy=='public'): ?>                    
                    <span><img src="images/form_public.gif" /> <?php echo t('link_data_public_hover');?></span>
                <?php elseif($obj_harvest->accesspolicy=='licensed'): ?>
                    <span><img src="images/form_licensed.gif" /> <?php echo t('link_data_licensed_hover');?></span>
                <?php elseif($obj_harvest->accesspolicy=='data_enclave'): ?>
                    <span><img src="images/form_enclave.gif" /> <?php echo t('link_data_enclave_hover');?></span>
                <?php elseif($obj_harvest->accesspolicy=='remote'): ?>
                        <span><img src="images/form_remote.gif" /> <?php echo t('link_data_remote_hover');?></span>
                <?php endif; ?>
                </span>            
			<?php else:?>
                <!--data access -->
                <?php if ($model!=''):?>
                <span class="link-col-2">
                <?php if($model=='direct'): ?>
                    <a href="<?php echo site_url().'/access_direct/'.$id;?>" class="accessform" title="<?php echo t('link_data_direct_hover');?>">
                    <span><img src="images/form_direct.gif" /> <?php echo t('link_data_direct_hover');?></span>
                    </a>                    
                <?php elseif($model=='public'): ?>                    
                    <a href="<?php echo site_url().'/access_public/'.$id;?>" class="accessform"  title="<?php echo t('link_data_public_hover');?>">
                    <span><img src="images/form_public.gif" /> <?php echo t('link_data_public_hover');?></span>
                    </a>                    
                <?php elseif($model=='licensed'): ?>
                    <a href="<?php echo site_url().'/access_licensed/'.$id;?>" class="accessform"  title="<?php echo t('link_data_licensed_hover');?>">
                    <span><img src="images/form_licensed.gif" /> <?php echo t('link_data_licensed_hover');?></span>
                    </a>                    
                <?php elseif($model=='data_enclave'): ?>
                    <a href="<?php echo site_url().'/access_enclave/'.$id;?>" class="accessform"  title="<?php echo t('link_data_enclave_hover');?>">
                    <span><img src="images/form_enclave.gif" /> <?php echo t('link_data_enclave_hover');?></span>
                    </a>                    
                <?php elseif($model=='remote'): ?>
                    <?php if (isset($link_da) && strlen($link_da)>1):?>
                        <a target="_blank" href="<?php echo $link_da;?>"  title="<?php echo t('link_data_remote_hover');?>">
                        <span><img src="images/form_remote.gif" /> <?php echo t('link_data_remote_hover');?></span>
                        </a>                    
                    <?php endif; ?>
                <?php endif; ?>
                </span>
                <?php endif;?>
            <?php endif;?>
                        
		</td>	            
	</tr>
    
    <?php if($link_indicator!='' || $link_study!=''): ?>
    <tr>
    <td></td>
    <td>
			<!-- indicators -->
            <span class="link-col">
			 <?php if($link_indicator!=''): ?>
                <a target="_blank"  href="<?php echo site_url().'/catalog/download/'.$id.'/'.base64_encode($link_indicator);?>" title="<?php echo t('link_indicators_hover');?>">
                    <img border="0" alt="<?php echo t('link_indicators');?>" src="images/page_white_database.png" /> <?php echo t('link_indicators_hover');?>
                </a>
            <?php endif; ?>
	        </span>
            
            <span class="link-col">
            <?php if($link_study!=''): ?>
                    <a  target="_blank" href="<?php echo site_url().'/catalog/download/'.$id.'/'.base64_encode($link_study);?>" title="<?php echo t('link_study_website_hover');?>">
                        <img border="0" title="<?php echo t('link_study_website_hover');?>" alt="<?php echo t('link_study_website');?>" src="images/page_white_world.png" /> <?php echo t('link_study_website');?>
                    </a>
            <?php endif; ?>
        	</span>
        </td>
    </tr>
    <?php endif;?>
</table>

<?php $this->load->view('catalog_search/survey_summary_resources',$resources);?>
<?php if ($citations):?>
<?php $this->load->view('catalog_search/survey_summary_citations',$citations);?>
<?php endif;?>
</div>


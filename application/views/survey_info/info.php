<?php 

    $sub_title=array();
    
    if ($survey['type']=='document' && isset($survey['metadata']['document_description']['authors'])){
        $sub_title[]= '<span id="dataset-country">'.$survey['authoring_entity'].'</span>';
    }else{        
        if ($survey['nation']!=''){
            $sub_title[]='<span id="dataset-country">'.$survey['nation'].'</span>';
        }
    }

    $dates=array_unique(array($survey['year_start'],$survey['year_end']));
    $dates=implode(" - ", $dates);

    if(!empty($dates)){
        $sub_title[]='<span id="dataset-year">'.$dates.'</span>';
    }
    
	$sub_title=implode(", ", $sub_title);
?>
<div class="row study-info">
	<?php if ($survey['owner_repo']['thumbnail']!='' || ( isset($survey['thumbnail']) && trim($survey['thumbnail'])!='')):?>
		<?php 
			$thumbnail=$survey['owner_repo']['thumbnail'];
			if(isset($survey['thumbnail']) && trim($survey['thumbnail'])!='' && file_exists('files/thumbnails/'.$survey['thumbnail'])){
				$thumbnail='files/thumbnails/'.$survey['thumbnail'];
			}
		?>
		<div class="col-md-2">
			<div class="collection-thumb-container">
				<a href="<?php echo site_url('catalog/'.$survey['owner_repo']['repositoryid']);?>">
				<img  src="<?php echo base_url().$thumbnail;?>?v=<?php echo $survey['changed']; ?>" class="mr-3 img-fluid img-thumbnail" alt="<?php echo $survey['owner_repo']['repositoryid'];?>" title="<?php echo $survey['owner_repo']['title'];?>"/>
				</a>
			</div>		
		</div>
	<?php endif;?>

	<div class="col">
		
		<div>
		    <h1 class="mt-0 mb-1" id="dataset-title">
                <span><?php echo $survey_title;?></span>
                <?php if (isset($survey['subtitle'])):?>
                    <div class="study-subtitle"><?php echo $survey['subtitle'];?></div>
                <?php endif;?>
            </h1>
            <div class="clearfix">
		        <h6 class="sub-title float-left" id="dataset-sub-title"><?php echo $sub_title;?></h6>
                <?php if(isset($data_access_type) && $data_access_type!='data_na' && $survey['type']=='survey'):?>
                <a  
                    href="<?php echo site_url("catalog/$sid/get-microdata");?>" 
                    class="get-microdata-btn badge badge-primary wb-text-link-uppercase float-left ml-3" 
                    title="<?php echo t('get_microdata');?>">					
                    <span class="fa fa-download"></span>
                    <?php echo t('get_microdata');?>
                </a>
                <?php endif;?>
                <?php if(isset($reproducibility_package) && $survey['type']=='script' && isset($reproducibility_package['_links']['type'])):?>
                    <?php
                        $link_type_class='fa fa-download';
                        if ($reproducibility_package['_links']['type']=='link'){
                            $link_type_class='fas fa-external-link-alt';
                        }
                    ?>

                <a  
                    href="<?php echo $reproducibility_package['_links']['download'];?>" 
                    class="get-microdata-btn badge badge-primary wb-text-link-uppercase float-left ml-3" 
                    target="_blank"
                    title="<?php echo t('get_reproducibility_package');?>">                    
                    <span class="<?php echo $link_type_class;?>"></span>
                    <?php echo t('get_reproducibility_package');?>
                </a>
                <?php endif;?>

            </div>
		</div>

		<div class="row study-info-content">
		
            <div class="col pr-5">

                <div class="row mt-4 mb-2 pb-2  border-bottom">
                    <div class="col-md-2">
                        <?php echo t('refno');?>
                    </div>
                    <div class="col">
                        <div class="study-idno">
                            <?php echo $survey['idno'];?>                            
                        </div>
                    </div>
                </div>

                <?php if (isset($survey['doi']) && !empty($survey['doi'])):?>
                <div class="row mb-2 pb-2  border-bottom">
                    <div class="col-md-2">
                        <?php echo t('DOI');?>
                    </div>
                    <div class="col">
                        <div class="study-doi">
                            <?php if (strtolower(substr($survey['doi'],0,4))=='http'):?>
                                <a target="_blank" href="<?php echo html_escape($survey['doi']);?>"><?php echo $survey['doi'];?></a>
                            <?php else:?>                                
                                <a target="_blank" href="<?php echo html_escape('https://doi.org/'.$survey['doi']);?>"><?php echo 'https://doi.org/'.$survey['doi'];?></a>
                            <?php endif;?>
                        </div>
                    </div>
                </div>
                <?php endif;?>
		
                <?php if ($survey['type']!='document' && isset($survey['authoring_entity']) && !empty($survey['authoring_entity'])):?>
                <div class="row mb-2 pb-2  border-bottom">
                    <div class="col-md-2">
                        <?php echo t('producers');?>
                    </div>
                    <div class="col">
                        <div class="producers">
                            <?php echo $survey['authoring_entity'];?>
                        </div>
                    </div>
                </div>
                <?php endif;?>
                
                <?php if ($survey['type']=='document' && isset($survey['metadata']['document_description']['abstract']) ):?>
                <div class="row mb-2 pb-2  border-bottom">
                    <div class="col-md-2">
                        <?php echo t('abstract');?>
                    </div>
                    <div class="col">
                        <div class="abstract" id="study-abstract">
                            <?php echo ($survey['metadata']['document_description']['abstract']);?>
                        </div>
                    </div>
                </div>
                <?php endif;?>

                <?php if (isset($survey['repositories']) && is_array($survey['repositories']) && count($survey['repositories'])>0): ?> 
                <div class="row  border-bottom mb-2 pb-2 mt-2">
                    <div class="col-md-2">
                        <?php echo t('collections');?>
                    </div>
                    <div class="col">
                        <div class="collections link-col">           
                            <?php foreach($survey['repositories'] as $repository):?>
                                <span class="collection">
                                    <a href="<?php echo site_url('collections/'.$repository['repositoryid']);?>">
                                        <span class="badge badge-primary"><?php echo $repository['title'];?></span>
                                    </a>                                    
                                </span>
                            <?php endforeach;?>
                        </div>
                    </div>
                </div>
                <?php endif;?>

                <div class="row border-bottom mb-2 pb-2 mt-2">
                    <div class="col-md-2">
                        <?php echo t('metadata');?>
                    </div>
                    <div class="col">
                        <div class="metadata">
                            <!--metadata-->
                            <span class="mr-2 link-col">
                                <?php $report_file=unix_path($survey['storage_path'].'/ddi-documentation-'.$this->config->item("language").'-'.$survey['id'].'.pdf');?>
                                <?php if (file_exists($report_file)):?>
                                    <a class="download" href="<?php echo site_url('catalog/'.$survey['id'].'/pdf-documentation');?>" title="<?php echo t('documentation_in_pdf');?>" >
                                        <span class="badge badge-success"><i class="fa fa-file-pdf-o" aria-hidden="true"> </i> <?php echo t('documentation_in_pdf');?></span>
                                    </a>
                                <?php endif;?>
                            
                                <?php if($survey['type']=='survey'):?>
                                    <a class="download" href="<?php echo site_url('metadata/export/'.$survey['id'].'/ddi');?>" title="<?php echo t('metadata_in_ddi_xml');?>">
                                        <span class="badge badge-primary"> <?php echo t('DDI/XML');?></span>
                                    </a>
                                <?php endif;?>

                                <a class="download" href="<?php echo site_url('metadata/export/'.$survey['id'].'/json');?>" title="<?php echo t('metadata_in_json');?>">
                                    <span class="badge badge-info"><?php echo t('JSON');?></span>
                                </a>
                            </span>	
                            <!--end-metadata-->
                        </div>
                    </div>
                </div>

                <?php if($survey['link_study']!='' || $survey['link_indicator']!=''): ?>
                <div class="row mb-2 pb-2 mt-2">
                    <div class="col-md-2">
                        
                    </div>
                    <div class="col">
                        <div class="study-links link-col">
                            <?php if($survey['link_study']!=''): ?>						
                                <a  target="_blank" href="<?php echo html_escape($survey['link_study']);?>" title="<?php echo t('link_study_website_hover');?>">
                                    <span class="mr-2">
                                        <i class="fa fa-globe-americas" aria-hidden="true"> </i> <?php echo t('link_study_website');?>
                                    </span>
                                </a>
                            <?php endif; ?>

                            <?php if($survey['link_indicator']!=''): ?>
                                <a target="_blank"  href="<?php echo html_escape($survey['link_indicator']);?>" title="<?php echo t('link_indicators_hover');?>">
                                    <span>
                                        <i class="fa fa-database" aria-hidden="true"> </i> <?php echo t('link_indicators_hover');?>
                                    </span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php /*
                <?php if (!empty($data_classification)):?>
                <div class="row  mb-2 pb-2 mt-2">
                    <div class="col-md-2">
                        <?php echo t('data_access');?>
                    </div>
                    <div class="col">
                        <span class="data-classification">
                            <?php echo t('data_class_note_'.$data_classification);?>
                        </span>
                    </div>
                </div>
                <?php endif;?>

                <?php if(isset($data_access_type) && $data_access_type!='data_na'):?>            
                <div class="row  mb-2 pb-2 mt-2">
                    <div class="col-md-2">
                        <?php echo t('license');?>
                    </div>
                    <div class="col">
                        <span class="license">
                            <!--<a href="<?php //echo site_url('licenses/'.$data_access_type);?>">-->
                            <?php echo t('legend_data_'.$data_access_type);?> <i class="fa fa-info-circle" aria-hidden="true"></i>
                            <!--</a>-->
                        <span>
                    </div>		
                </div>                
                <?php endif;?>
                */?>
	    </div>
	
	</div>

	</div>

    <div class="col-md-2 border-left">
		<!--right-->
		<div class="study-header-right-bar">
				<div class="stat">
					<div class="stat-label"><?php echo t('created_on');?> </div>
					<div class="stat-value"><?php echo date("M d, Y",$survey['created']);?></div>
				</div>

				<div class="stat">
					<div class="stat-label"><?php echo t('last_modified');?> </div>
					<div class="stat-value"><?php echo date("M d, Y",$survey['changed']);?></div>
				</div>
				
				<?php if ((int)$survey['total_views']>0):?>
					<div class="stat">
						<div class="stat-label"><?php echo t('page_views');?> </div>
						<div class="stat-value"><?php echo $survey['total_views'];?></div>
					</div>
				<?php endif;?>

				<?php if ((int)$survey['total_downloads']>0):?>
					<div class="stat">
						<div class="stat-label"><?php echo t('downloads');?> </div>
						<div class="stat-value"><?php echo $survey['total_downloads'];?></div>
					</div>				
				<?php endif;?>
		</div>		
		<!--end-right-->
	</div>

</div>

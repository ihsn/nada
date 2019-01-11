<?php 
if(!isset($active_tab)){
    $active_tab=$this->uri->segment(3);
}

$survey_id=$survey['id'];
$active_tab_class="active";
?>


<div class="row wb-tab-heading mt-lg-3 mb-3 study-metadata-info-bar">
        <!-- tab-heading -->

            <div class="col-12 col-sm-10">

              <div class="row">
				  <!-- collection logo -->
                <div class="col-12 col-sm-2 d-flex justify-content-center pt-2">					  
                    <?php if ($survey['owner_repo']['thumbnail']!=''):?>
                    <div class="collection-thumb-container">
                        <a href="<?php echo site_url('catalog/'.$survey['owner_repo']['repositoryid']);?>">
                        <img src="<?php echo $survey['owner_repo']['thumbnail'];?>" class="wb-table-img" alt="<?php echo $survey['owner_repo']['repositoryid'];?>" title="<?php echo $survey['owner_repo']['title'];?>"/>
                        </a>
                    </div>
                    <?php endif;?>
				</div>
				<!-- end collection logo -->

                <div class="col-12 col-sm-10">
                <table class="table table-sm wb-table-space survey-info" cellspacing="0">
                    <tr>
                        <td class="label"><?php echo t('refno');?></td>
                        <td class="value"><?php echo $survey['idno'];?></td>
                    </tr>
                    <tr>
                        <td class="label"><?php echo t('year');?></td>
                        <td class="value">
                        <?php 
                            $dates=array_unique(array($survey['year_start'],$survey['year_end']));                    
                                /*if ($year_start==$year_end){
                                    echo $year_start;
                                }
                                else{
                                    if ($year_start!=''){
                                        $dates[]=$year_start;
                                    }
                                    if ($year_end!=''){
                                        $dates[]=$year_end;
                                    }*/
                                    echo implode(" - ", $dates);
                                ?>
                        </td>
                    </tr>
                    <?php if ($survey['nation']!=''):?>
                    <tr>
                        <td class="label"><?php echo t('country');?></td>
                        <td class="value"><?php echo $survey['nation'];?></td>
                    </tr>
                    <?php endif;?>
                    <?php if (isset($survey['authoring_entity']) && !empty($survey['authoring_entity'])):?>
                    <tr valign="top">
                        <td class="label"><?php echo t('producers');?></td>
                        <td class="value">                            
                                <?php foreach($survey['authoring_entity'] as $pi):?>
                                <div>
                                    <?php 
                                    $auth=array();
                                    foreach($pi as $key=>$value){
                                        $auth[]=$value;
                                    }                    
                                    echo implode(", " ,array_filter($auth));
                                    ?>
                                </div>
                            <?php endforeach;?>                                
                        </td>
                    </tr>
                    <?php endif;?>
                    <?php if (isset($survey['fundag'])):?>
                    <tr valign="top">
                        <td class="label"><?php echo t('sponsors');?></td>
                        <td class="value">
                        <?php foreach($survey['fundag'] as $agency):?>
                            <div>
                                <?php 
                                $ag=array();
                                foreach($agency as $key=>$value){
                                    if($value){
                                        $ag[]=trim($value);
                                    }    
                                }                    
                                echo implode(", " ,array_filter($ag));
                                ?>
                            </div>
                        <?php endforeach;?>
                    </td>
                    </tr>
                    <?php endif;?>
                    
                    <?php if (isset($survey['repositories']) && is_array($survey['repositories']) && count($survey['repositories'])>0): ?>
                    <tr valign="top">
                        <td class="label"><?php echo t('collections');?></td>
                        <td class="value">
                        <?php foreach($survey['repositories'] as $repository):?>
                            <div class="collection"><?php echo anchor('catalog/'.$repository['repositoryid'],$repository['title']);?></div>
                        <?php endforeach;?>
                        </td>
                    </tr>
                    <?php endif;?>

                    <?php $report_file=unix_path($survey['storage_path'].'/ddi-documentation-'.$this->config->item("language").'-'.$survey['id'].'.pdf');?>
                    <?php if (file_exists($report_file)):?>
                    <tr>    
                        <td class="label"><?php echo t('metadata');?></td>
                        <td class="value links">            
                            <span class="link-col sep">
                                <a href="<?php echo site_url('catalog/'.$survey['id'].'/pdf-documentation');?>" title="<?php echo t('pdf');?>" >
                                <i class="fa fa-file-pdf-o" aria-hidden="true"> </i> <?php echo t('documentation_in_pdf');?>
                                </a> 
                            </span>            
                        </td>
                    </tr>
                    <?php endif;?>
                    
                    
                    <tr>
                    <td></td>
                    <td class="study-links">
                            
                            <?php if($survey['link_study']!=''): ?>
                                <span class="link-col">
                                    <a  target="_blank" href="<?php echo html_escape($survey['link_study']);?>" title="<?php echo t('link_study_website_hover');?>">
                                    <i class="fa fa-globe" aria-hidden="true"> </i> <?php echo t('link_study_website');?>
                                    </a>
                                    </span>
                            <?php endif; ?>
                            

                            <?php if($survey['link_indicator']!=''): ?>
                                <span class="link-col">
                                <a target="_blank"  href="<?php echo html_escape($survey['link_indicator']);?>" title="<?php echo t('link_indicators_hover');?>">
                                <i class="fa fa-database" aria-hidden="true"> </i> <?php echo t('link_indicators_hover');?>
                                </a>
                                </span>
                            <?php endif; ?>                            
                                                        

                            <?php if($survey['link_questionnaire']!=''): ?>
                                <span class="link-col">
                                <a target="_blank"  href="<?php echo html_escape($survey['link_questionnaire']);?>" >
                                <i class="fa fa-file-o" aria-hidden="true"> </i> <?php echo t('link_questionnaires');?>
                                </a>
                                </span>
                            <?php endif; ?>

                            <?php if($survey['link_report']!=''): ?>
                                <span class="link-col">
                                <a target="_blank"  href="<?php echo html_escape($survey['link_report']);?>" >
                                <i class="fa fa-book" aria-hidden="true"> </i>  <?php echo t('link_reports');?>
                                </a>
                                </span>
                            <?php endif; ?>
                            
                        </td>
                    </tr>
                                        
                </table>
                </div>

              </div> <!-- /row  -->
                

                <!-- Nav tabs -->
                <ul class="nav nav-tabs wb-nav-tab-space flex-wrap" role="tablist">
                    <?php foreach($page_tabs as $tab_name=>$tab):?>
                        <?php if ($tab['show_tab']==0){continue;};?>
                        <?php if($tab_name=='get_microdata'):?>
                            <li class="nav-item nav-item-get-microdata tab-<?php echo $tab_name;?>" >
                                <a href="<?php echo $tab['url'];?>" class="nav-link wb-nav-link wb-text-link-uppercase <?php echo ($tab_name==$active_tab) ? $active_tab_class : '';?>" role="tab" data-id="related-materials" title="<?php echo $tab['hover_text'];?>">
                                    <span class="get-microdata icon-da-<?php echo $data_access_type;?>"></span> <?php echo $tab['label'];?>
                                </a>
                            </li>                            
                        <?php else:?>
                            <li class="nav-item tab-<?php echo $tab_name;?> <?php echo ($tab_name==$active_tab) ? $active_tab_class : '';?>"  >
                                <a href="<?php echo $tab['url'];?>" class="nav-link wb-nav-link wb-text-link-uppercase <?php echo ($tab_name==$active_tab) ? $active_tab_class : '';?>" role="tab"  data-id="related-materials" title="<?php echo $tab['hover_text'];?>"><?php echo $tab['label'];?></a>
                            </li>
                        <?php endif;?>
                    <?php endforeach;?>
                    
                    <!--review-->
                    <?php if(isset($page_tabs['review_study']) && $page_tabs['review_study']===TRUE):?>                    
                        <li class="nav-item tab-<?php echo $tab_name;?> <?php echo ($tab_name==$active_tab) ? $active_tab_class : '';?>" >
                            <a href="<?php echo site_url('catalog/'.$survey_id.'/review');?>" class="nav-link wb-nav-link wb-text-link-uppercase <?php echo ($tab=='review') ? $active_tab_class : '';?>" role="tab"  data-id="review">
                                <?php echo t('review_study');?>
                            </a>
                        </li>
                    <?php endif;?>
                </ul>

                
                <!-- /Nav tabs -->

              </div>  

            <div class="col-12 col-sm-2 wb-study-statistics-box pb-2 hidden-sm-down">
              
            <div class="pt-3 mb-4">
                <small><?php echo t('created_on');?></small><br/>
                <strong class="value"><?php echo date("M d, Y",$survey['created']);?></strong>
            </div>

            <div class="mb-4">
                <small><?php echo t('last_modified');?></small><br/>
                <strong><?php echo date("M d, Y",$survey['changed']);?></strong>
            </div>

            <?php if ((int)$survey['total_views']>0):?>
            <div class="mb-4">
                <small><?php echo t('page_views');?></small><br/>
                <strong><?php echo $survey['total_views'];?></strong>
            </div>
            <?php endif;?>

            </div>
        <!-- /tab-heading -->
    </div>

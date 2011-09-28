<?php 
//get data access request form if study has a DA type attached
$data_access_link=$this->load->view('ddibrowser/data_request_link.php',NULL,TRUE); 
?>
<?php
$page_url='catalog'; //use ddibrowser or catalog

$menu_tech_info['sampling']			=anchor($page_url.'/'.$this->uri->segment(2).'/sampling',t('sampling'),array('class'=>'ajax'));
$menu_tech_info['questionnaires']	=anchor($page_url.'/'.$this->uri->segment(2).'/questionnaires',t('questionnaires'),array('class'=>'ajax'));
$menu_tech_info['datacollection']	=anchor($page_url.'/'.$this->uri->segment(2).'/datacollection',t('data_collection'),array('class'=>'ajax'));
$menu_tech_info['dataprocessing']	=anchor($page_url.'/'.$this->uri->segment(2).'/dataprocessing',t('data_processing'),array('class'=>'ajax'));
$menu_tech_info['dataappraisal']	=anchor($page_url.'/'.$this->uri->segment(2).'/dataappraisal',t('data_appraisal'),array('class'=>'ajax'));
//$menu_tech_info['othermaterials']	=anchor($page_url.'/'.$this->uri->segment(2).'/othermaterials','Other materials',array('class'=>'ajax'));

$overview_items = array();
if ($this->config->item("is_dime_catalog"))
{
	$overview_items['impact_evaluation'] = anchor($page_url.'/'.$this->uri->segment(2).'/impact_evaluation',t('impact_evaluation'),array('class'=>'ajax'));
	$overview_items['related_operations'] = anchor($page_url.'/'.$this->uri->segment(2).'/related_operations',t('related_operations'),array('class'=>'ajax'));
}	
?>

<!--overview-->
<div class="left-bar-section">
    <ul>
    <li class="item section-header"><?php echo anchor($page_url.'/'.$this->uri->segment(2),t('study_information'),array('class'=>'ajax')); ?></li>
    <li class="item section-header"><?php echo anchor($page_url.'/'.$this->uri->segment(2).'/overview',t('overview'),array('class'=>'ajax')); ?></li>
    </ul>
	<!-- IE -->
    <?php $ie_items='';	?>
	<?php foreach($overview_items as $key=>$value):?>
        <?php if(strpos($sidebar,$key)): ?>
        	<?php  $ie_items.='<li class="item">'.$value.'</li>';?>
        <?php endif;?>            
    <?php endforeach;?>
	
    <?php if ($ie_items!=''):?>
    <ul ><?php echo $ie_items;?></ul>
    <?php endif;?>        
</div>


<!--tech-info-->
<div class="section-header">
	<div><img src="images/database_table.png" border="0"/> <?php echo t('technical_information');?></div>
    <ul class="filetree">
        <?php foreach($menu_tech_info as $key=>$value):?>
            <?php if(strpos($sidebar,$key)): ?>
                <li><?php echo $value; ?></li>     
            <?php elseif($key=='questionnaires'  && in_array('questionnaires',$resources) ):?>
		        <li class="item"><?php echo $value; ?></li>
            <?php endif;?>            
        <?php endforeach;?>
        <!--technical documents -->
	    <?php if (in_array('technical',$resources)):?>
			<li ><?php echo anchor($page_url.'/'.$this->uri->segment(2).'/technicaldocuments',t('technical_documents'),array('class'=>'ajax')); ?></li>
        <?php endif; ?>        

        <!-- other materials-->
        <?php if (in_array('othermaterials',$resources)):?>
			<li ><?php echo anchor($page_url.'/'.$this->uri->segment(2).'/othermaterials',t('other_materials'),array('class'=>'ajax')); ?></li>
        <?php endif; ?>
    </ul> 	
</div>


<!-- Tables and reports -->
<?php if (in_array('tables',$resources) || in_array('reports',$resources) ):?>
<div class="section-header">
	<div><img src="images/database_table.png" border="0"/> <?php echo t('tabulation_and_analysis');?></div>
    <ul class="filetree" >
	    <?php if (in_array('tables',$resources)):?>
			<li><?php echo anchor($page_url.'/'.$this->uri->segment(2).'/stat_tables',t('statistical_tables'),array('class'=>'ajax')); ?></li>
        <?php endif; ?>
	    <?php if (in_array('reports',$resources)):?>
			<li><?php echo anchor($page_url.'/'.$this->uri->segment(2).'/reports',t('reports'),array('class'=>'ajax')); ?></li>
        <?php endif; ?>        
	    <?php if (in_array('analytical',$resources)):?>
			<li><?php echo anchor($page_url.'/'.$this->uri->segment(2).'/analytical',t('title_analytical'),array('class'=>'ajax')); ?></li>
        <?php endif; ?>        
    </ul> 	
</div>
<?php endif; ?>

<!--datasets-->
<div class="left-bar-section">
    <img src="images/folder_page_white.png" border="0"/> <?php echo t('datasets');?>
</div>    
<ul id="browser" class="filetree">
	<li>	
    	<!--access policy-->
	    <img src="images/page_white_key.png" border="0"/> <?php echo anchor($page_url.'/'.$this->uri->segment(2).'/accesspolicy',t('access_policy'),array('class'=>'ajax')); ?>
    </li>
    <?php if (trim(strip_tags($data_access_link))!=''):?>
    	<li><?php echo $data_access_link;?></li>
	<?php endif;?>	
    
	<?php if (isset($data_files) && is_array($data_files) && count($data_files)>0):?>
    <li>
    	<span class="folder">&nbsp;<a href="<?php echo site_url().'/'.$page_url.'/'.$this->uri->segment(2).'/datafiles';?>"><?php echo t('data_files');?></a></span>
        <ul>
            <?php foreach ($data_files as $key=>$file):?>
            <?php $file=trim($file);?>
            <li><?php echo anchor($page_url.'/'.$this->uri->segment(2).'/datafile/'.trim($key),utf8_wordwrap($file,20,'<BR>',1),array('class'=>'ajax', 'title'=>$file)); ?></li>
            <?php endforeach;?>
        </ul>
    </li>
	<?php endif;?>    
    <?php //variable groups ?>
    <?php if ($vargrp!==NULL):?>
      	<li><span class="folder"><?php echo t('variable_groups');?></span>
    		<?php echo $vargrp;?>
        </li>    
    <?php endif;?>
	<?php if (isset($data_files) && is_array($data_files) && count($data_files)>0):?>
    <li>
    	<!--variable search-->
	    <img src="images/page_white_find.png" border="0"/> <?php echo anchor($page_url.'/'.$this->uri->segment(2).'/search',t('variable_search'),array('class'=>'ajax')); ?>
    </li>
    <?php endif;?>
</ul>

<!-- export-->
<?php /*
<div class="left-bar-section">
    <img src="images/page_white.png" border="0"/> <?php echo anchor($page_url.'/'.$this->uri->segment(2).'/export',t('export_metadata'),array('class'=>'ajax')); ?>
</div>
*/
?>

<div class="left-bar-section" style="margin-top:5px;">
   <img border="0" title="<?php echo t('link_ddi');?>" alt="DDI" src="images/ddi-logo.gif" /> 
   <a href="<?php echo site_url().'/catalog/ddi/'.$this->uri->segment(2);?>" title="<?php echo t('link_ddi_hover');?>"  rel="nofollow">
             <?php echo t('download_metadata');?></a>
</div>
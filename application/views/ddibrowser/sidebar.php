<?php
$menu_tech_info['sampling']			=anchor('ddibrowser/'.$this->uri->segment(2).'/sampling',t('sampling'),array('class'=>'ajax'));
$menu_tech_info['questionnaires']	=anchor('ddibrowser/'.$this->uri->segment(2).'/questionnaires',t('questionnaires'),array('class'=>'ajax'));
$menu_tech_info['datacollection']	=anchor('ddibrowser/'.$this->uri->segment(2).'/datacollection',t('data_collection'),array('class'=>'ajax'));
$menu_tech_info['dataprocessing']	=anchor('ddibrowser/'.$this->uri->segment(2).'/dataprocessing',t('data_processing'),array('class'=>'ajax'));
$menu_tech_info['dataappraisal']	=anchor('ddibrowser/'.$this->uri->segment(2).'/dataappraisal',t('data_appraisal'),array('class'=>'ajax'));
//$menu_tech_info['othermaterials']	=anchor('ddibrowser/'.$this->uri->segment(2).'/othermaterials','Other materials',array('class'=>'ajax'));
?>

<!--home-->
<div class="left-bar-section">
    <img src="images/house.png" border="0"/> <?php echo anchor('catalog/'.$this->uri->segment(2).'',t('study_home')); ?>
</div>

<!--overview-->
<div class="left-bar-section">
    <img src="images/page_white.png" border="0"/> <?php echo anchor('ddibrowser/'.$this->uri->segment(2).'/overview',t('overview'),array('class'=>'ajax')); ?>
</div>

<!--tech-info-->
<div class="left-bar-section">
    <img src="images/report.png" border="0"/> <?php echo t('technical_information');?>
</div>
<div class="left-bar-section">
    <ul class="menu-item" >
        <?php foreach($menu_tech_info as $key=>$value):?>
            <?php if(strpos($sidebar,$key)): ?>
                <li><?php echo $value; ?></li>     
            <?php elseif($key=='questionnaires'  && in_array('questionnaires',$resources) ):?>
		        <li><?php echo $value; ?></li>
            <?php endif;?>            
        <?php endforeach;?>
        <!--technical documents -->
	    <?php if (in_array('technical',$resources)):?>
			<li><?php echo anchor('ddibrowser/'.$this->uri->segment(2).'/technicaldocuments',t('technical_documents'),array('class'=>'ajax')); ?></li>
        <?php endif; ?>        

        <!-- other materials-->
        <?php if (in_array('othermaterials',$resources)):?>
			<li><?php echo anchor('ddibrowser/'.$this->uri->segment(2).'/othermaterials',t('other_materials'),array('class'=>'ajax')); ?></li>
        <?php endif; ?>

    </ul> 	
</div>

<!-- Tables and reports -->
<?php if (in_array('tables',$resources) || in_array('reports',$resources) ):?>
<div class="left-bar-section">
    <img src="images/database_table.png" border="0"/> <?php echo t('tabulation_and_analysis');?>
</div>
<div class="left-bar-section">
    <ul class="menu-item" >
	    <?php if (in_array('tables',$resources)):?>
			<li><?php echo anchor('ddibrowser/'.$this->uri->segment(2).'/stat_tables',t('statistical_tables'),array('class'=>'ajax')); ?></li>
        <?php endif; ?>
	    <?php if (in_array('reports',$resources)):?>
			<li><?php echo anchor('ddibrowser/'.$this->uri->segment(2).'/reports',t('reports'),array('class'=>'ajax')); ?></li>
        <?php endif; ?>        
	    <?php if (in_array('analytical',$resources)):?>
			<li><?php echo anchor('ddibrowser/'.$this->uri->segment(2).'/analytical',t('title_analytical'),array('class'=>'ajax')); ?></li>
        <?php endif; ?>        

    </ul> 	
</div>
<?php endif; ?>

<!--datasets-->
<div class="left-bar-section">
    <img src="images/folder_page_white.png" border="0"/> <?php echo t('datasets');?>
</div>    

<!--data files-->
<ul id="browser" class="filetree left-bar-section" style="padding:5px;">
<?php $data_files=$this->DDI_Browser->get_datafiles_array($this->ddi_file); ?>
	<li>	
    	<!--access policy-->
	    <img src="images/page_white_key.png" border="0"/> <?php echo anchor('ddibrowser/'.$this->uri->segment(2).'/accesspolicy',t('access_policy'),array('class'=>'ajax')); ?>
    </li>
    <li><span class="folder"><?php echo t('data_files');?></span>
        <ul>
            <?php foreach ($data_files as $key=>$file):?>
            <?php $file=trim($file);?>
            <li><?php echo anchor('ddibrowser/'.$this->uri->segment(2).'/datafile/'.trim($key),$file,array('class'=>'ajax')); ?></li>
            <?php endforeach;?>
        </ul>
    </li>
    <?php //variable groups ?>
    <?php if ($vargrp!==NULL):?>
      	<li><span class="folder">Variable groups</span>
    		<?php echo $vargrp;?>
        </li>    
    <?php endif;?>
    <li>
    	<!--variable search-->
	    <img src="images/page_white_find.png" border="0"/> <?php echo anchor('ddibrowser/'.$this->uri->segment(2).'/search',t('variable_search'),array('class'=>'ajax')); ?>
    </li>
</ul>

<!--variable groups-->

<!-- export-->
<div class="left-bar-section">
    <img src="images/page_white.png" border="0"/> <?php echo anchor('ddibrowser/'.$this->uri->segment(2).'/export',t('export_metadata'),array('class'=>'ajax')); ?>
</div>
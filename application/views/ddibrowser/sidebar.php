<?php
$page_url='catalog'; //use ddibrowser or catalog

$menu_tech_info['sampling']			=anchor($page_url.'/'.$this->uri->segment(2).'/sampling',t('sampling'),array('class'=>'ajax','data-id'=>'sampling'));
$menu_tech_info['datacollection']	=anchor($page_url.'/'.$this->uri->segment(2).'/datacollection',t('data_collection'),array('class'=>'ajax','data-id'=>'data_collection'));
$menu_tech_info['dataprocessing']	=anchor($page_url.'/'.$this->uri->segment(2).'/dataprocessing',t('data_processing'),array('class'=>'ajax','data-id'=>'dataprocessing'));
$menu_tech_info['dataappraisal']	=anchor($page_url.'/'.$this->uri->segment(2).'/dataappraisal',t('data_appraisal'),array('class'=>'ajax','data-id'=>'dataappraisal'));


$overview_items = array();
?>


<div class="container">
    <ul class="study-items">
    	<?php if(isset($show_study_items) && $show_study_items==TRUE):?>
        <!--overview-->
    	<li class="item"><?php echo anchor($page_url.'/'.$this->uri->segment(2).'/overview',t('overview'),array('class'=>'ajax','data-id'=>'overview')); ?></li>
        <?php endif;?>

		<!--show enabled menu items-->		
		<?php foreach($menu_tech_info as $key=>$value):?>
            <?php if(strpos($sidebar_items,$key)): ?>
                <li class="item"><?php echo $value; ?></li>     
            <?php endif;?>
        <?php endforeach;?>

		<?php if(isset($resources)):?>
        <!--technical documents -->
	    <?php if (in_array('technical',$resources)):?>
			<li class="item"><?php echo anchor($page_url.'/'.$this->uri->segment(2).'/technicaldocuments',t('technical_documents'),array('class'=>'ajax','data-id'=>'technicaldocuments')); ?></li>
        <?php endif; ?>        

        <!-- other materials-->
        <?php if (in_array('othermaterials',$resources)):?>
			<li class="item"><?php echo anchor($page_url.'/'.$this->uri->segment(2).'/othermaterials',t('other_materials'),array('class'=>'ajax','data-id'=>'othermaterials')); ?></li>
        <?php endif; ?>

        <!-- Tables and reports -->
        <?php if (in_array('tables',$resources) || in_array('reports',$resources) ):?>
                <?php if (in_array('tables',$resources)):?>
                    <li class="item"><?php echo anchor($page_url.'/'.$this->uri->segment(2).'/stat_tables',t('statistical_tables'),array('class'=>'ajax','data-id'=>'stat_tables')); ?></li>
                <?php endif; ?>
                <?php if (in_array('reports',$resources)):?>
                    <li class="item"><?php echo anchor($page_url.'/'.$this->uri->segment(2).'/reports',t('reports'),array('class'=>'ajax','data-id'=>'reports')); ?></li>
                <?php endif; ?>        
                <?php if (in_array('analytical',$resources)):?>
                    <li class="item"><?php echo anchor($page_url.'/'.$this->uri->segment(2).'/analytical',t('title_analytical'),array('class'=>'ajax','data-id'=>'analytical')); ?></li>
                <?php endif; ?>        
        <?php endif; ?>
        <?php endif; ?>
    
        <?php if(isset($show_study_items) && $show_study_items==TRUE):?>
        <!--access policy-->
        <li class="item"><?php echo anchor($page_url.'/'.$this->uri->segment(2).'/accesspolicy',t('access_policy'),array('class'=>'ajax','data-id'=>'accesspolicy')); ?></li>
		<li class="item"><?php echo anchor($page_url.'/'.$this->uri->segment(2).'/export-metadata',t('export_metadata'),array('class'=>'ajax','data-id'=>'export-metadata')); ?></li>
        <?php endif;?>
    </ul>

    
    <ul class="data-items">	
	<?php if(isset($show_data_items) && $show_data_items==TRUE):?>
        <form method="get" action="<?php echo site_url('catalog/'.$this->uri->segment(2).'/search');?>" class="dictionary-search">        
        <div class="dictionary-search-wrap">
        <input type="text" name="vk" class="search-keywords" placeholder="Search dictionary" value="<?php echo form_prep($this->input->get('vk')); ?>" /><input type="submit" value="GO" class="btn-search"/>
        </div>
        </form>
    
		<?php if (isset($data_files) && is_array($data_files) && count($data_files)>0):?>
        <li class="filetree">
            <a href="<?php echo site_url().'/'.$page_url.'/'.$this->uri->segment(2).'/data-dictionary';?>"><?php echo t('data_dictionary');?></a>
            <ul>
                <!--variable search-->
                <?php if (1==2 && isset($data_files) && is_array($data_files) && count($data_files)>0):?>
                <li class="sub-item">    	
                    <?php echo anchor($page_url.'/'.$this->uri->segment(2).'/search',t('Search variable'),array('class'=>'ajax','title'=>t("search_data_dictionary") )); ?>
                </li>
                <?php endif;?>
    
                <?php foreach ($data_files as $key=>$file):?>
                <?php $file=trim($file);?>
                <?php $key=trim($key);?>
                <li class="sub-item"><?php echo anchor($page_url.'/'.$this->uri->segment(2).'/datafile/'.trim($key),utf8_wordwrap($file,20,'<BR>',1),array('class'=>'ajax', 'title'=>$file,'data-id'=>trim($key))); ?></li>
                <?php endforeach;?>
            </ul>
        </li>
        <?php endif;?>    
    
		 <?php if (isset($vargrp) && $vargrp!==NULL):?>
            <li class="filetree">
                <!--variable-groups-->
                    <div style="margin-top:15px;"><?php echo t('variable_groups');?></div>
                    <ul><?php echo $vargrp;?></ul>
            </li>	
        <?php endif;?>
            
    <?php endif;?>


</div>






<!-- export-->
<?php
return;
 /*
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
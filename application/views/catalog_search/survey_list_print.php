<style>
body{font-family:Geneva, Arial, Helvetica, sans-serif;font-size:1em;}
.survey-row{border-bottom:1px solid #CCCCCC;padding-bottom:10px;margin-top:10px;page-break-inside:avoid;}
h2{margin:0px;font-size:22px;}
.study-country{margin-bottom:10px;font-weight:bold;}
.survey-stats{margin-top:15px;color:gray;font-size:smaller;}
.search-count{background:#F0F0F0;border:1px solid gainsboro;padding:15px;}
.bookmark{margin-top:20px;}
</style>
<?php 
if ($this->input->get("view")=="v"){
	if($found==1) {
		$items_found=t('found_variable');
	}
	else{
		$items_found=t('found_variables');
	}
}	
else{

	$found=$surveys['found'];
	$total=$surveys['total'];
	if($found==1) {
		$items_found=t('found_study');
	}
	else{
		$items_found=t('found_studies');
	}
}
?>

<div class="search-count">
	<div><?php echo sprintf($items_found,$found,$total);?></div>
	<?php if($this->input->get("bookmark") ):?>
    	<div class="bookmark">URL: <?php echo $this->input->get("bookmark");?></div>
    <?php endif;?>
</div>

<?php 
	//current page url
	$page_url=site_url().$this->uri->uri_string();
	
	//total pages
	$pages=ceil($surveys['found']/$surveys['limit']);	
?>

<?php if (isset($surveys['rows']) && count($surveys['rows'])>0): ?>

<?php		
	//citations
	if ($surveys['citations']===FALSE)
	{
		$citations=array();
	}
	
	//current page url with query strings
	$page_url=site_url().'/catalog/';		
	
	//page querystring for variable sub-search
	$variable_querystring=get_sess_querystring( array('sk', 'vk', 'vf'),'search');
	
	//page querystring for variable sub-search
	$search_querystring='?'.get_sess_querystring( array('sk', 'vk', 'vf','view','topic','country'),'search');
?>


<?php foreach($surveys['rows'] as $row): ?>
	<div class="survey-row" data-url="<?php echo site_url(); ?>/catalog/<?php echo $row['id']; ?>">
            <h2 class="title">
                <a href="<?php echo site_url(); ?>/catalog/<?php echo $row['id']; ?>"  title="<?php echo $row['title']; ?>" >
                	<?php echo $row['title'];?>
                </a>
            </h2>
            <div class="study-country">
				<?php if ($this->regional_search=='yes'):?>
                        <?php echo $row['nation']. ',';?>
                <?php endif;?>
                <?php 
					$survey_year=NULL;
					$survey_year[$row['year_start']]=$row['year_start'];
					$survey_year[$row['year_end']]=$row['year_end'];
					$survey_year=implode('-',$survey_year);
				?>
                <?php echo $survey_year!=0 ? $survey_year : '';?>
			</div>
            <div class="sub-title">
            	<div><?php echo t('by');?> <?php echo $row['authoring_entity'];?></div>
				<?php if (isset($row['repo_title']) && $row['repo_title']!=''):?>
                    <div><?php echo t('catalog_owned_by')?>: <a href="<?php echo site_url('catalog/'.$row['repositoryid'].'/about');?>"><?php echo $row['repo_title'];?></a></div>
                <?php endif;?>
            </div>
			<div class="survey-stats">
            	<span>Created on: <?php echo date('M d, Y',$row['created']);?></span>
                <span>Last modified: <?php echo date('M d, Y',$row['changed']);?></span>
            </div>
		
        <?php if ( isset($row['var_found']) ): ?>
            <div class="variables-found" style="clear:both;">
                    <a class="vsearch" style="outline:none;" href="<?php echo site_url(); ?>/catalog/vsearch/<?php echo $row['id']; ?>/?<?php echo $variable_querystring; ?>">
                        <?php echo sprintf(t('variables_keywords_found'),$row['var_found'],$row['varcount']);?>
                        <img class="open-close" src="images/next.gif"/>
                    </a>
                    <span class="vsearch-result"></span>
            </div>
            <?php endif; ?>
    </div>    
<?php endforeach;?>

<?php else: ?>
	<div style="padding:10px;background:white;border:1px solid gainboro;margin-bottom:20px;"><?php echo t('search_no_results');?></div>
<?php endif; ?>
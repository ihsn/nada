<style>
	.about-photo{float:left;margin-right:10px;}
	.repository-about{clear:both;overflow:auto;}
	.repository-about .photo-big{float:left;margin-right:10px;}
	.visit-catalog a{background:#A01822;padding:8px;color:white;display:inline-block}
	.visit-catalog a:hover{background:#666666}
	.visit-catalog{margin-top:10px;}
	h3.da-access{margin:0px;float:left;}
	.survey-row{vertical-align:bottom;}
	a.request-button{color:white;background-color:#666666;border:1px solid black;padding:3px;float:right;cursor:pointer;font-weight:bold;}
	a.request-button:hover{text-decoration:none;color:white;}
	.repository-surveys{margin-top:50px;}
	.grid-table .survey-row-section td{padding-top:30px;}
</style>
<?php $regional_search=($this->config->item("regional_search")=='yes') ? TRUE : FALSE;?>
<h1><?php echo $repository->title;?></h1>
<div class="repository-about">
	<?php echo $repository->long_text;?>
	<div class="visit-catalog"><a href="<?php echo site_url(); ?>/catalog/<?php echo $repository->repositoryid; ?>"><?php echo t('visit_catalog');?></a></div>
</div>

<?php /* ?>
<div class="data-access">
<?php if(isset($repo_data_access)):?>
	<h2>Data availability by data access types</h2>
    <p>The collection contains 1 public use file(s), 13 licensed and 3 direct download files. To request access to data by collection, click on the request access by collection button. Access to individual study can be requested by accessing each study page and then using the request access link.</p>
	<table>
	<?php foreach($repo_data_access as $da):?>
    	<tr>
        	<td><?php echo t($da['da_type']);?></td>
            <td><?php echo $da['total'];?></td>
            <td>
				<?php if (isset($group_data_access) && $group_data_access===TRUE):?>
                <a href="#">Request data access to collection (<?php echo $da['total'];?> <?php echo t($da['da_type']);?>)</a>
                <?php endif;?>
            </td>
        </tr>
    <?php endforeach;?>
    </table>
<?php endif;?>
</div>
<?php */ ?>

<?php /* ?>
<div class="repository-surveys">
	<h2>Studies in the collection</h2>
    <table class="grid-table">
    <?php $k=1;foreach($surveys as $survey):?>
    	<tr class="survey-row">
        	<td><?php echo $k++;?></td>
        	<td><a href="<?php echo site_url('catalog/'.$survey['id']);?>" >
				<?php if ($regional_search):?>
					<?php echo $survey['nation'];?> - 
				<?php endif;?>
				<?php echo $survey['titl'];?>
                </a>
             </td>
            <td></td>
        </tr>
    <?php endforeach;?>
    </table>
</div>
<?php */ ?>


<div class="repository-surveys">
	<h2><?php echo t('Studies in the collection');?></h2>
    <table class="grid-table">
    <?php foreach($repo_data_access as $form_model):?>    
    <tr class="survey-row-section">
	    <td colspan="2">
        	<h3 class="da-access"><?php echo t($form_model['da_type'].'_title');?></h3>
            
			<?php if ($form_model['da_type']=='public'): ?>
            	<?php if(isset($repository->group_da_public) && $repository->group_da_public==='1') :?>
            		<a class="request-button" href="<?php echo site_url('access_public/by_collection/'.$this->uri->segment(2));?>"><?php echo t('Request data access');?></a>
                <?php endif;?>
			<?php elseif ($form_model['da_type']=='licensed'):?>
            	<?php if(isset($repository->group_da_licensed) && $repository->group_da_licensed==='1') :?>
	            	<a class="request-button" href="<?php echo site_url('access_licensed/by_collection/'.$this->uri->segment(2));?>"><?php echo t('Request data access');?></a>
    			<?php endif;?>        
            <?php endif;?>
        </td>
    </tr>
	<?php $k=1;foreach($surveys as $survey):?>
    <?php if ($form_model['da_type']==$survey['da_model']):?>
    	<tr class="survey-row">
        	<td><?php echo $k++;?></td>
        	<td><a href="<?php echo site_url('catalog/'.$survey['id']);?>" >
				<?php if ($regional_search):?>
					<?php echo $survey['nation'];?> - 
				<?php endif;?>
				<?php echo $survey['titl'];?>
                </a>
             </td>
        </tr>
    <?php endif;?>    
    <?php endforeach;?>
    <?php endforeach;?>
    </table>
</div>
<h1 class="xsl-title"><?php echo t('variable_search');?></h1>
<?php $url=site_url().'/ddibrowser/'.$this->uri->segment(2).'/search'; ?>
<form action="" id="form_vsearch">
	<input type="text" name="vk" value="<?php echo form_prep($this->input->get('vk')); ?>" size="60" maxlength="100"/>
    <input type="submit" value="<?php echo t('search');?>" name="search" onclick="vsearch('<?php echo $url;?>/ajax/');return false;"/>    
    <div>
    <input type="checkbox" name="vf[]" id="name" value="name" checked="checked"/><label for="label"><?php echo t('name');?></label>            
    <input type="checkbox" name="vf[]" id="label" value="labl" checked="checked"/><label for="label"><?php echo t('label');?></label>            
    <input type="checkbox" name="vf[]" id="question" value="qstn"  checked="checked"/><label for="question"><?php echo t('question');?></label>            
    <input type="checkbox" name="vf[]" id="categories" value="catgry"  checked="checked"/><label for="categories"><?php echo t('classification');?></label>
    </div>            

</form>
<div id="variable-list"></div>
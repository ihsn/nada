<h1 class="xsl-title"><?php echo t('search_data_dictionary');?></h1>
<?php $url=site_url('catalog/'.$sid.'/search'); ?>
<form action="" id="form_vsearch">
	<input type="text" name="vk" value="<?php echo form_prep($this->input->get('vk')); ?>" size="60" maxlength="100"/>
    <input type="submit" value="<?php echo t('search');?>" name="search" onclick="vsearch('<?php echo $url;?>/ajax/');return false;"/>    
    <?php if ($this->input->get("vk")):?>
    <a href="<?php echo site_url('catalog/'.$sid.'/data-dictionary');?>"><?php echo t('reset');?></a>
    <?php endif;?>
</form>
<div id="variable-list"></div>
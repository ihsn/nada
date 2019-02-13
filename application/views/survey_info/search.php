<h1 class="xsl-title"><?php echo t('search_data_dictionary');?></h1>
<?php $url=site_url('catalog/'.$sid.'/search'); ?>
<form action="" id="form_vsearch">

    <div class="input-group input-group-sm">            
        <input type="text" class="form-control" name="vk"  value="<?php echo form_prep($this->input->get('vk')); ?>" size="60" maxlength="100"/>
        <span class="input-group-btn">                
            <input type="submit" class="btn btn-outline-primary btn-sm" value="<?php echo t('search');?>" name="search" onclick="vsearch('<?php echo $url;?>/ajax/');return false;"/>    

            <?php if ($this->input->get("vk")):?>
    <a href="<?php echo site_url('catalog/'.$sid.'/data-dictionary');?>"><?php echo t('reset');?></a>
    <?php endif;?>
        </span>
    </div>
    
</form>
<div id="variable-list"></div>
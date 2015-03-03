<div class="filter-box keyword-search">

    <h3><?php echo t('search_by_keyword');?></h3> 

    <div class="study-search">
        <label><?php echo t('in_study_description');?></label>
        <input maxlength="100" type="text" id="sk" name="sk" value="<?php echo form_prep(isset($search_options->sk) ? $search_options->sk : '') ; ?>" /> 
    </div>    
    
    <div class="variable-search" >
        <label><?php echo t('in_variable_description');?></label>
        <input maxlength="100"  type="text" id="vk" name="vk" value="<?php echo form_prep(isset($search_options->vk) ? $search_options->vk : '') ; ?>" /> 

    </div>

    <div class="search-buttons" style="margin-top:5px;">
        <input class="btn-search" type="submit" id="btnsearch" name="search" value="<?php echo t('search');?>"/>
        <a id="reset" href="<?php echo site_url();?>/catalog/<?php echo $repoid;?>?reset=reset"><?php echo t('reset');?></a>
    </div>

</div>
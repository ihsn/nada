<div class="filter-box">

    <h3><?php echo t('search_by_keyword');?></h3> 
	<span class="search-help keyword-help"><img src="images/icon_question.gif" alt="help" title="<?php echo t('Help');?>" data-url="<?php echo site_url('catalog/help');?>"/></span>

    <div class="study-search">
        <?php echo t('in_study_description');?> <br/>
        <input maxlength="100" type="text" id="sk" name="sk" value="<?php echo isset($search_options->sk) ? $search_options->sk : '' ; ?>" style="width:90%;"/> 
    </div>    
    
    <div class="variable-search" style="margin-top:5px;">
        <?php echo t('in_variable_description');?> <br/> 
        <input maxlength="100"  type="text" id="vk" name="vk" value="<?php echo isset($search_options->vk) ? $search_options->vk : '' ; ?>" style="width:90%;"/> 

    </div>

    <div class="search-buttons" style="margin-top:5px;">
        <input class="btn-search" type="submit" id="btnsearch" name="search" value="<?php echo t('search');?>"/>
        <a id="reset" href="<?php echo site_url();?>/catalog/<?php echo $repoid;?>?reset=reset"><?php echo t('reset');?></a>
    </div>

</div>
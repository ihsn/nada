<div class="sidebar-filter wb-ihsn-sidebar-filter filter-box keyword-search">
    <h6 class="togglable"> <i class="fa fa-search pr-2"></i><?php echo t('search_by_keyword');?></h6>

    <div id="search-by-keyword" class="search-by-keyword sidebar-filter-entries">
        <div class="form-group study-search">
            <!--<label for="formGroupExampleInput">in study description </label>-->
            <input  maxlength="100" type="text" id="sk" name="sk" value="<?php echo form_prep(isset($search_options->sk) ? $search_options->sk : '') ; ?>" class="form-control" placeholder="<?php echo t('in_study_description');?>">
        </div>
        <div class="form-group variable-search">
            <!--<label for="formGroupExampleInput2">in variable description</label>-->
            <input maxlength="100"  type="text" id="vk" name="vk" value="<?php echo form_prep(isset($search_options->vk) ? $search_options->vk : '') ; ?>" class="form-control" placeholder="<?php echo t('in_variable_description');?>">
        </div>
        <button type="submit" id="btnsearch" name="search" value="search" class="btn btn-primary btn-sm wb-btn btn-search"><?php echo t('search');?></button>
        <span><a href="<?php echo site_url();?>/catalog/<?php echo $repoid;?>?reset=reset" class="btn btn btn-outline-primary btn-sm wb-btn-outline" id="reset"><i class="fa fa-refresh"></i><?php echo t('reset');?></a></span>
    </div>
</div>

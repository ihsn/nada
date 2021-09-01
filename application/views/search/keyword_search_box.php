<div class="nada-search-box wb-controls-wrapper">
    <div class="row mb-5 justify-content-center align-items-center">
        <div class="input-group col-md-12 search-box-container">            
        <input class="form-control form-control-lg py-2 search-keywords" id="search-keywords" name="sk" value="<?php echo form_prep($search_options->sk);?>" placeholder="Keywords ..."  >
        <span class="input-group-append">
            <button class="btn btn-primary btn-search-submit" type="submit" id="submit_search">
                <!-- <i class="fa fa-search"></i> -->
                Search
            </button>
            <a href="<?php echo site_url('catalog');?>" class="close clear-search-button" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </a>
        </span>
        </div>
        <span class="keyword-suggestions"></span>
    </div>    
</div>
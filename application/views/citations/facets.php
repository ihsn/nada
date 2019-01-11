<div class="col-md-3">
<form name="search_form" id="search_form" method="get" autocomplete="off">        
        <input type="hidden" id="ps" name="ps" value="">
        <input type="hidden" id="page" name="page" value="1">
        <input type="hidden" id="repo" name="repo" value="">

    <div class="refine-list filter-container">

        <div class="sidebar-filter wb-ihsn-sidebar-filter filter-box keyword-search">
            <h6 class="togglable"> <i class="fa fa-search pr-2"></i>Search by keyword</h6>
            <div id="search-by-keyword" class="search-by-keyword sidebar-filter-entries">
                <div class="form-group study-search">
                    <input maxlength="100" type="text" id="keywords" name="keywords" value="<?php echo form_prep($this->input->get('keywords')); ?>" class="form-control" placeholder="keywords">
                    <input type="hidden" name="collection" value="<?php echo form_prep($active_repo);?>"/>
                </div>
                <button type="submit" id="btnsearch" name="search" value="Search" class="btn btn-primary btn-sm wb-btn btn-search">Search</button>
                <span><a href="<?php echo site_url('citations?collection='.$active_repo);?>" class="btn btn btn-outline-primary btn-sm wb-btn-outline" id="reset"><i class="fa fa-refresh"></i>Reset</a></span>
            </div>
        </div>

        <div id="filter-by-access" class="sidebar-filter wb-ihsn-sidebar-filter filter-by-year filter-box">
            <h6 class="togglable"> <i class="fa fa-search pr-2"></i>Filter by Year</h6>
            <div class="sidebar-filter-entries">        
                      
                <div class="form-inline-x">
                    <div class="form-group col">
                        <label for="year_from">From</label>
                        <input type="number" min="1600" max="3000" name="from" class="form-control" placeholder="Start year" value="<?php echo form_prep($this->input->get('from'));?>">
                    </div>
                    <div class="form-group col">
                        <label for="year_to">To</label>
                        <input type="number" min="1600" max="3000"  name="to" class="form-control" placeholder="End year" value="<?php echo form_prep($this->input->get('to'));?>">
                    </div>                    
                </div>
                <div class="form-group col">
                    <button class="btn btn-primary btn-sm">Apply</button>
                </div>    
            </div>
        </div>

        <div id="filter-by-access" class="sidebar-filter wb-ihsn-sidebar-filter filter-by-access filter-box filter-by-dtype">
        <h6 class="togglable"> <i class="fa fa-filter pr-2"></i> Filter by Type</h6>
        <div class="sidebar-filter-entries filter-da items-container">
            <?php foreach($ctypes as $ctype=>$count):?>
            <?php $is_checked=in_array($ctype,$search['ctype']) ? 'checked="checked"' : '';?>
            <div class="form-check item inactive">
                <label class="form-check-label" for="ctype-<?php echo $ctype;?>" >
                    <input <?php echo $is_checked;?> class="form-check-input chk chk-ctype" type="checkbox" name="ctype[]" value="<?php echo $ctype;?>" id="ctype-<?php echo $ctype;?>">
                    <?php echo t($ctype);?> <span>(<?php echo $count;?>)</span>
                </label>
            </div>
            <?php endforeach;?>
        </div>
    </div>

</div>
</form>
</div>

<script type="text/javascript">  
    $(function(){
        $('.form-check-input').on('change',function(){
        $('#search_form').submit();
        });
    });
</script>
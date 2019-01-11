<div id="filter-by-access" class="sidebar-filter wb-ihsn-sidebar-filter filter-by-year filter-box">
    <h6 class="togglable"> <i class="fa fa-search pr-2"></i><?php echo t('filter_by_year');?></h6>

    <div class="sidebar-filter-entries">
        <input type="hidden"/>
        <p class="mb-0"><?php echo t('show_studies_conducted_between');?></p>
        <form>
            <div class="form-group mb-0">
                <input type="hidden"/>
                <?php echo form_dropdown('from', $years, ((isset($search_options->from) && $search_options->from!='') ? $search_options->from : end($years)), 'id="from"  class="form-control"'); ?>
            </div>
            <p class="mb-0"><?php echo t('and');?></p>
            <div class="form-group">
                <?php echo form_dropdown('to', $years, (isset($search_options->to) && $search_options->to!='') ? $search_options->to: '','id="to" class="form-control"'); ?>
            </div>
        </form>
    </div>

</div>

<div id="filter-by-access" class="sidebar-filter wb-ihsn-sidebar-filter filter-by-year filter-box">
    <h6 class="togglable"> <i class="fa fa-search pr-2"></i><?php echo t('filter_by_year');?></h6>

    <div class="sidebar-filter-entries">
        <p class="mb-0 pb-0"><?php echo t('from');?></p>
        <form>
            <div class="form-group mb-0">
                <?php echo form_dropdown('from', $years, ((isset($search_options->from) && $search_options->from!='') ? $search_options->from : current($years)), 'id="from"  class="form-control"'); ?>
            </div>
            <p class="mb-0 pb-0 mt-2"><?php echo t('to');?></p>
            <div class="form-group">
                <?php echo form_dropdown('to', $years, (isset($search_options->to) && $search_options->to!='') ? $search_options->to: '','id="to" class="form-control"'); ?>
            </div>
        </form>
    </div>

</div>

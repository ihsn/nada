<div class="filter-box">
    <h3><?php echo t('filter_by_year');?></h3> 

	<div>   	
        <input type="hidden"/>
        <div>
            <div><?php echo t('show_studies_conducted_between');?></div>
            <div>
                <input type="hidden"/><?php echo form_dropdown('from', $years, ((isset($search_options->from) && $search_options->from!='') ? $search_options->from : end($years)), 'id="from"'); ?>
                <?php echo t('and');?>
                <?php echo form_dropdown('to', $years, (isset($search_options->to) && $search_options->to!='') ? $search_options->to: '','id="to"'); ?>
            </div>    
        </div>
    </div>
    
</div>
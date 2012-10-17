
	<h3 class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-top">
    	<a href="#"><?php echo t('filter_by_center');?><span id="selected-centers" style="font-size:11px;padding-left:10px;"></span></a>
    </h3> 
	<div class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom" id="center-list" style="font-size:11px;">
    	<div class="flash">
            <div style="text-align:right;">
                <a  href="#" onclick="select_centers('all');return false;"><?php echo t('link_select_all');?></a> | 
                <a  href="#" onclick="select_centers('none');return false;"><?php echo t('link_clear');?></a> | 
                <a  href="#" onclick="select_centers('toggle');return false;"><?php echo t('link_toggle');?></a>
            </div>

            <div class="filter-center">            	
					<?php foreach($this->center_list as $key=>$center):?>
                        <div class="center">
                            <label title="<?php echo $center;?>" for="center-<?php echo $key;?>">
                                <input class="chk-center" type="checkbox"  value="<?php echo $key;?>" name="center[]" id="center-<?php echo $key;?>"/><span class="title"><?php echo $center;?></span><br/>
                            </label>
                        </div>    
                    <?php endforeach;?>
            </div>
            
        </div>
	</div>
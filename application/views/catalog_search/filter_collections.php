<h3 class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-top">
    <a href="#"><?php echo t('filter_by_collection');?><span id="selected-collections" style="font-size:11px;padding-left:10px;"></span></a>
</h3> 
<div class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom" id="center-list" style="font-size:11px;">
    <div class="flash">
        <div style="text-align:right;">
            <a  href="#" onclick="select_collections('all');return false;"><?php echo t('link_select_all');?></a> | 
            <a  href="#" onclick="select_collections('none');return false;"><?php echo t('link_clear');?></a> | 
            <a  href="#" onclick="select_collections('toggle');return false;"><?php echo t('link_toggle');?></a>
        </div>

        <div class="filter-collection">            	
                <?php foreach($this->collection_list as $key=>$value):?>
                    <div class="collection">
                        <label title="<?php echo $value;?>" for="coll-<?php echo $key;?>">
                            <input class="chk-collection" type="checkbox"  value="<?php echo $key;?>" name="collection[]" id="coll-<?php echo $key;?>"/><span class="title"><?php echo $value;?></span><br/>
                        </label>
                    </div>    
                <?php endforeach;?>
        </div>
        
    </div>
</div>
<div class="container topics-container" >

    	<div class="rows-container">
			<?php foreach($topics as $sub):if(!isset($sub['children'])){continue;};?>
            <div class="row" id="sub-topic-row-<?php echo $sub['tid'];?>">
                <div class="col-1">
                         <input class="chk-sub-topic parent" type="checkbox"                             
                            id="region-sub-<?php echo $sub['tid'];?>"
                            data-type="parent"
                         />
						<label for="region-sub-<?php echo $sub['tid'];?>">
                            <?php echo substr($sub['title'],0,strpos($sub['title'],'[',0)); ?>
                        </label>
                </div>
                <div class="col-2 cnt">
                <?php foreach($sub['children'] as $topic):?>
                    <div class="country item" >
                        <input class="chk-item" type="checkbox" 
                            value="<?php echo form_prep($topic['tid']); ?>" 
                            id="cr-<?php echo $sub['tid']?>-<?php echo form_prep($topic['tid']); ?>"
                            data-type="child"
                            data-name="c-<?php echo form_prep($topic['tid']); ?>"
                         />
                        <label for="cr-<?php echo $sub['tid']?>-<?php echo form_prep($topic['tid']); ?>">
                            <?php echo substr($topic['title'],0,strpos($topic['title'],'[',0)); ?> <span class="count">(<?php echo $topic['surveys_found']; ?>)</span>
                        </label>
                    </div>
                <?php endforeach;?>
                </div>
            </div>    
            <?php endforeach;?>
		</div>
    
</div>
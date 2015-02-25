<div class="container topics-container" >

    	<div class="rows-container">
			<?php if($topics):?>
			<?php foreach($topics as $sub):?>
                    <?php if(!isset($sub['children'])){continue;}?>
            <div class="row" id="sub-topic-row-<?php echo $sub['tid'];?>">
                <div class="col-1">
                         <input class="chk-sub-topic parent" type="checkbox"                             
                            id="region-sub-<?php echo $sub['tid'];?>"
                            data-type="parent"
                         />
						<label for="region-sub-<?php echo $sub['tid'];?>">
                            <?php $brac_pos=strpos($sub['title'],'[',0);?>
                            <?php if($brac_pos):?>
                                <?php echo substr($sub['title'],0,strpos($sub['title'],'[',0)); ?>
                            <?php else:?>
                                <?php echo $sub['title']; ?>
                            <?php endif;?>
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
                            <!--<?php echo substr($topic['title'],0,strpos($topic['title'],'[',0)); ?> <span class="count">(<?php echo $topic['surveys_found']; ?>)</span>-->
                            <?php $brac_pos=strpos($topic['title'],'[',0);?>
                            <?php if ($brac_pos):?>
                                <?php echo substr($topic['title'],0,strpos($topic['title'],'[',0)); ?>
                            <?php else:?>
                                <?php echo $topic['title']; ?>
                            <?php endif;?>
                            <span class="count">(<?php echo $topic['surveys_found']; ?>)</span>
                        </label>
                    </div>
                <?php endforeach;?>
                </div>
            </div>    
            <?php endforeach;?>
            <?php else:?>
            <?php echo t('n/a');?>
            <?php endif;?>
		</div>
    
</div>
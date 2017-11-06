<?php 
	if(!isset($topics)){return;}
	$item_limit=7;
?>

<div class="filter-box filter-by-topic">
<h3><?php echo t('filter_by_topic');?></h3> 
<span class="selected-items-count" ><?php echo count($topics);?></span>

<div id="topics-container">
    <div class="any">    	
        <input type="checkbox" class="chk-any" id="topic-any"  <?php echo $search_options->topic!="" ? '' : 'checked="checked"';?> />
        <label for="topic-any"><?php echo t('any');?></label>
    </div>
	<div class="items-container topic-items <?php //echo (count($topics)>10) ? 'scrollable' : ''; ?>">
	<?php if($topics):?>
	<?php $k=0;foreach($topics as $topic):$k++; ?>
    	<?php if($topic['pid']==0){continue;}?>
        <div class="topic item inactive">
            <input class="chk chk-topic" type="checkbox" name="topic[]" 
                value="<?php echo form_prep($topic['tid']); ?>" 
                id="tpc-<?php echo form_prep($topic['tid']); ?>"
                <?php if($search_options->topic!='' && in_array($topic['tid'],$search_options->topic)):?>
                checked="checked"
                <?php endif;?>
             />
            <label for="tpc-<?php echo form_prep($topic['tid']); ?>">
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
    <?php endif;?>
    </div>
        
    <div class="filter-footer">
    <input type="button" class="btn-select" value="<?php echo t('view_select_more');?>" id="btn-topic-selection" data-dialog-id="dialog-topics" data-dialog-title="<?php echo t('select_topics');?>" data-url="index.php/catalog/topic_selection/<?php echo $active_repo;?>"/>
    </div>
    
</div>
</div>

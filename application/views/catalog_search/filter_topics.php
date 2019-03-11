<?php 
	if(!isset($topics)){return;}
    $item_limit=7;
?>
<div id="filter-by-topic" class="sidebar-filter wb-ihsn-sidebar-filter filter-by-income filter-box filter-by-topic">

    <!-- By topic -->
    <h6 class="togglable"> <i class="fa fa-filter pr-2"></i><?php echo t('filter_by_topic');?></h6>
    <div class="sidebar-filter-index selected-items-count" data-toggle="tooltip" data-placement="top"><?php echo count($topics);?></div>
        <div class="form-check any">
            <label class="form-check-label">
                <input type="checkbox" class="form-check-input chk-any" id="topic-any"  <?php echo $search_options->topic!="" ? '' : 'checked="checked"';?>>
                <small><?php echo t('any');?></small>
            </label>
        </div>
    <div class="sidebar-filter-entries wb-sidebar-filter-collapse items-container topic-items">
        <?php if($topics):?>
            <?php $k=0;foreach($topics as $topic):$k++; ?>
                <?php //if($topic['pid']==0){continue;}?>
                <div class="form-check">
                    <label for="tpc-<?php echo form_prep($topic['tid']); ?>" class="form-check-label topic item inactive">
                        <input class="form-check-input chk chk-topic" type="checkbox" name="topic[]"
                               value="<?php echo form_prep($topic['tid']); ?>"
                               id="tpc-<?php echo form_prep($topic['tid']); ?>"
                            <?php if($search_options->topic!='' && in_array($topic['tid'],$search_options->topic)):?>
                                checked="checked"
                            <?php endif;?>>
                        <small>
                            <?php $brac_pos=strpos($topic['title'],'[',0);?>
                            <?php if ($brac_pos):?>
                                <?php echo substr($topic['title'],0,strpos($topic['title'],'[',0)); ?>
                            <?php else:?>
                                <?php echo $topic['title']; ?>
                            <?php endif;?>
                            <span class="count">(<?php echo $topic['surveys_found']; ?>)</span>
                        </small>
                    </label>
                </div>
            <?php endforeach;?>
        <?php endif;?>
    </div>
</div>

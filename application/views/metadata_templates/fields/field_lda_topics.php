<?php
/**
 * 
 *  Render LDA Topics
 * 
 * 
 */
?>
<?php if (isset($data) && $data !=''):?>
<div class="field field-lda_topics field-<?php echo $name;?> ">
    <div class="field-value">
        <?php foreach($data as $lda_topic):?>
            <!-- LDA model info -->
            <?php if(isset($lda_topic['model_info'])):?>
                <?php echo render_field($field_type='lda_model',$field_name='lda_model',$value=$lda_topic['model_info']);?>
            <?php endif;?>

            <!-- LDA topics -->
            <?php if(isset($lda_topic['topic_description'])):?>
                <?php echo render_field($field_type='lda_topics_score_table',$field_name='lda_topics_score',$value=$lda_topic['topic_description']);?>
            <?php endif;?>
        <?php endforeach;?>
    </div>
</div>
<?php endif;?>

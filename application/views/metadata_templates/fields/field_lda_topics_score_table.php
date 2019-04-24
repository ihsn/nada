<?php if (isset($data) && is_array($data) && count($data)>0 ):?>
<style>
    .topic-score{font-size:10px;}
    .topic-word{}
</style>
<div class="field field-<?php echo $name;?>">
    <div class="xsl-caption field-caption"><?php echo t($name);?></div>
    <div class="field-value">                
        
        <div class="row border-bottom">
            <div class="col-md-2 font-weight-bold">Topic</div>
            <div class="col-md-2 font-weight-bold">Score (%)</div>
            <div class="col-md-8 font-weight-bold">Top words</div>
        </div>

        <?php foreach($data as $row):?>
            <div class="border-bottom">
            <div class="row ">
                <div class="col-md-2">
                    Topic <?php echo $row['topic_id'];?>
                    <?php //echo $row['topic_score'];
                        $topic_score=ceil(floatval($row['topic_score'])*100);                        
                    ?>
                </div>
                <div class="col-md-2">
                    <div class="progress  mt-1">
                        <div class="progress-bar" 
                            style="width:<?php echo $topic_score;?>%" 
                            role="progressbar"  
                            aria-valuenow="<?php echo $topic_score;?>" aria-valuemin="0" 
                            aria-valuemax="100"  title="<?php echo $topic_score;?>%">
                        </div>                        
                    </div>                    
                    <div class="topic-score"><?php echo $topic_score .'%';// - '. $row['topic_score'];?></div>
                </div>
                <div class="col-md-8 topic-words">                
                    <?php foreach($row['topic_words'] as $topic_word):?>
                        <span class="topic-word"><?php echo $topic_word['word'];?></span>
                    <?php /*<div class="row">
                        <div class="col-6"><?php echo $topic_word['word'];?></div>
                        <div class="col-6">
                            <div class="progress">
                                <div class="progress-bar" style="width:<?php echo ceil(floatval($topic_word['weight'])*100);?>px" role="progressbar"  aria-valuenow="<?php echo ceil(floatval($topic_word['weight'])*100);?>" aria-valuemin="0" aria-valuemax="100"  title="<?php echo ceil(floatval($topic_word['weight'])*100);?>%"></div>
                            </div>
                        </div>
                    </div>
                    */ ?>
                    <?php endforeach;?>
                </div>
            </div>
            </div>
        <?php endforeach;?>         
    </div>
</div>
<?php endif;?>

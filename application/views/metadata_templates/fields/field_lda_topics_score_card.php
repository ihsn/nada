<?php if (isset($data) && is_array($data) && count($data)>0 ):?>
<div class="table-responsive field field-<?php echo $name;?>">
    <div class="xsl-caption field-caption"><?php echo t($name);?></div>
    <div class="field-value">                
        <div class="row">
        <?php foreach($data as $row):?>
            <div class="col-xl-4 col-md-6">
                <div class="card mb-4">
                <div class="card-header">
                    Topic <?php echo $row['topic_id'];?>
                    <div><?php echo $row['topic_score'];?></div>
                </div>
                <div class="card-body">
                    <?php foreach($row['topic_words'] as $topic_word):?>
                    <div class="row">
                        <div class="col-6"><?php echo $topic_word['word'];?></div>
                        <div class="col-6">
                            <div class="progress">
                                <div class="progress-bar" style="width:<?php echo ceil(floatval($topic_word['weight'])*100);?>px" role="progressbar"  aria-valuenow="<?php echo ceil(floatval($topic_word['weight'])*100);?>" aria-valuemin="0" aria-valuemax="100"  title="<?php echo ceil(floatval($topic_word['weight'])*100);?>%"></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach;?>
                </div>
                </div>
            </div>
        <?php endforeach;?> 
        </div>   
    </div>
</div>
<?php endif;?>

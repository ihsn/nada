<?php if ($data):?>
<?php 
    $columns=array(
        'source',
        'author',
        'version',
        'model_id',
        'nb_topics',
        'description',
        'corpus'
    );
?>
<div class="field field-<?php echo $name;?>">
    <div class="xsl-caption field-caption"><?php echo t($name);?></div>
    <div class="field-value">
        <?php foreach($data as $row):?>
            <?php foreach($columns as $column_name):?>
                <?php if(isset($row[$column_name])):?>
                    <?php if($column_name=='model_id'):?>
                        <div>
                            <span class="field-label"><?php echo t($name.'.'.$column_name);?></span>
                            <a href="<?php echo isset($row['uri']) ? $row['uri'] : '#';?>"><?php echo $row[$column_name];?></a>
                        </div>
                    <?php else:?>                    
                        <div><span class="field-label"><?php echo t($name.'.'.$column_name);?></span> <?php echo $row[$column_name];?></div>
                    <?php endif;?>
                <?php endif;?>
            <?php endforeach;?>
        <?php endforeach;?>
    </div>
</div>
<?php endif;?>

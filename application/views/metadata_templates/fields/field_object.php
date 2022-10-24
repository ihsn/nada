<?php
/**
 * 
 * Object field
 *
 */
?>
<?php if (isset($data) && is_array($data) && count($data)>0 ):?>
<div class="field field-<?php echo str_replace(".","__",$name);?>">    
    <div class="xsl-caption field-caption"><?php echo t($name);?></div>    
    <div class="field-value">                
        
        <div class="row mt-3">
               <?php foreach($data as $row_key=>$row):?>               
                    <div class="col col-auto"><strong><?php echo t($name.'.'.$row_key);?></strong><br/> <?php echo $row;?></div>               
               <?php endforeach;?>
        </div>
        
    </div>
</div>
<?php endif;?>

<?php
/**
 * 
 * iframe embeddings
 *
 */

?>

<?php if (isset($data) && is_array($data) && count($data)>0 ):?>
<div class="iframe_field field field-<?php echo str_replace(".","__",$name);?>">
    
    <div class="field-value">                
        <?php foreach($data as $row):?>
            <div class="xsl-caption field-caption"><?php echo $row['title'];?></div>
            <div class="iframe_content embed-responsive embed-responsive-4by3">
                <iframe class="embed-responsive-item" src="<?php echo site_url('embed/'.$row['uuid']);?>" title="<?php echo $row['title'];?>"></iframe>
            </div>
        <?php endforeach;?>        
    </div>
</div>
<?php endif;?>

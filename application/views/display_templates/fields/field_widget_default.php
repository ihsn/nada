<?php
/**
 * 
 * iframe embeddings
 *
 */
$data=$widgets;
?>
<?php if (isset($data) && is_array($data) && count($data)>0 ):?>    

<div class="iframe_field field field-<?php echo str_replace(".'","-",$template['key']);?>">
    
    <div class="field-value">                
        <?php foreach($data as $row):?>
            <div class="xsl-caption field-caption"><?php echo $row['title'];?></div>
            <div class="border iframe_content x-embed-responsive" class="min-height:200px;">
                <?php /*
                <!--<iframe class="embed-responsive-item" src="<?php echo site_url('embed/'.$row['uuid']);?>" title="<?php echo $row['title'];?>"></iframe>-->
                */?>

                <div id="widget-<?php echo $row['uuid'];?>"></div>
                <script>
                    var pymParent = new pym.Parent('widget-<?php echo $row['uuid'];?>', '<?php echo site_url('widgets/embed/'.$row['uuid']);?>', {});
                </script>

            </div>
        <?php endforeach;?>        
    </div>
</div>
<?php endif;?>

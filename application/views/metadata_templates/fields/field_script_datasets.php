<?php
/**
 * 
 * Script dataset field
 *
 *  options
 * 
 *  - hide_column_headings - hide column headings 
 */

 $hide_column_headings=false;

 if(isset($options['hide_column_headings'])){
     $hide_column_headings=$options['hide_column_headings'];
 }
?>
<?php if (isset($data) && is_array($data) && count($data)>0 ):?>
    <div class="xsl-caption field-caption">
        <?php echo t($name);?>
    </div>
    <div class="field-value">
            <?php foreach($data as $row):?>
                <div class="dataset-row border-bottom mb-3 pb-3">
                <h6 class="m-0">
                    <?php if (isset($row['uri'])):?>
                        <a targe="_blank" href="<?php echo $row['uri'];?>"><?php echo $row['name'];?></a>
                    <?php else:?>
                        <?php echo $row['name'];?>
                    <?php endif;?>
                </h6>
                <?php if (isset($row['idno']) || isset($row['access_type']) ):?>
                    <div>
                        <?php if (isset($row['idno'])):?>
                            <span class="text-secondary"><?php echo $row['idno'];?></span>
                        <?php endif;?>
                        <?php if (!empty($row['access_type'])):?>
                            <span class="ml-4 text-secondary">Data access type: <?php echo $row['access_type'];?></span>
                        <?php endif;?>
                    </div>
                <?php endif;?>    
                <div><?php echo $row['note'];?></div>
                </div>
            <?php endforeach;?>
    </div>
<?php endif;?>

<?php
/**
 * 
 * Table field
 *
 *  options
 * 
 *  - hide_column_headings - hide column headings 
 */

 $hide_column_headings=false;
 $hide_field_title=false;

 if(isset($options['hide_column_headings'])){
     $hide_column_headings=$options['hide_column_headings'];
 }
 
 if(isset($options['hide_field_title'])){
    $hide_field_title=$options['hide_field_title'];
 }
?>
<?php if (isset($data) && is_array($data) && count($data)>0 ):?>
<div class="table-responsive field field-<?php echo str_replace(".","__",$name);?>">
    <?php if ($hide_field_title!=true):?>
    <div class="xsl-caption field-caption"><?php echo t($name);?></div>
    <?php endif;?>
    <div class="field-value">                
        <?php if (isset($data[0]) && is_array($data[0])):?> 
        
        <?php $output=array();?>
        <?php foreach($data as $row):?>

            <?php if(get_field_value('individualName',$row)):?>
                <div>
                    <?php echo get_field_value('individualName',$row);?>

                    <?php if(get_field_value('role',$row)):?>
                    <span class="contact-role"> (<?php echo get_field_value('role',$row);?>)</span>
                    <?php endif;?>

                </div>
            <?php endif;?>
            
            <?php if(get_field_value('organisationName',$row)):?>
                <div><?php echo get_field_value('organisationName',$row);?></div>
            <?php endif;?>
            
            <?php if(get_field_value('positionName',$row)):?>
                <div><?php echo get_field_value('positionName',$row);?></div>
            <?php endif;?>            
            
            <div>
            <?php if(get_field_value('contactInfo.phone.voice',$row)):?>
                <span class="pr-2">Phone: <?php echo get_field_value('contactInfo.phone.voice',$row);?></span>
            <?php endif;?>            

            <?php if(get_field_value('contactInfo.phone.facsimile',$row)):?>
                <span class="pr-2">Fax: <?php echo get_field_value('contactInfo.phone.facsimile',$row);?></span>
            <?php endif;?>
            </div>

            <?php if(get_field_value('contactInfo.address.deliveryPoint',$row)):?>
                <div><?php echo get_field_value('contactInfo.address.deliveryPoint',$row);?></div>
            <?php endif;?>

            <div>
            <?php if(get_field_value('contactInfo.address.city',$row)):?>
                <span class="mr-2"><?php echo get_field_value('contactInfo.address.city',$row);?></span>
            <?php endif;?>

            <?php if(get_field_value('contactInfo.address.postalCode',$row)):?>
                <span><?php echo get_field_value('contactInfo.address.postalCode',$row);?></span>
            <?php endif;?>
            </div>

            <?php if(get_field_value('contactInfo.address.country',$row)):?>
                <div><?php echo get_field_value('contactInfo.address.country',$row);?></div>
            <?php endif;?>

            <?php if(get_field_value('contactInfo.address.elctronicMailAddress',$row)):?>
                <div><?php echo get_field_value('contactInfo.address.elctronicMailAddress',$row);?></div>
            <?php endif;?>

            <?php if(get_field_value('contactInfo.onlineResource.linkage',$row)):?>
                <div><?php echo get_field_value('contactInfo.onlineResource.linkage',$row);?></div>
            <?php endif;?>
        

            <?php if(get_field_value('contactInfo.onlineResource.name',$row)):?>
                <div><?php echo get_field_value('contactInfo.onlineResource.name',$row);?></div>
            <?php endif;?>

            <br class="border-bottom mb-2"/>

        <?php endforeach;?>

        <?php endif;?>

    </div>
</div>
<?php endif;?>

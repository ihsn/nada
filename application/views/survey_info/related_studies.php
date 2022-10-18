<?php 
    if (count($related_studies)==0){
        return;
    }
?>

<div class="related-studies">
    <h4 class="mb-4"><?php echo t('related_studies');?></h4>
    <?php foreach($related_studies as $related_study):?>
        <?php
                $nation_year=array();
                $nation_year=implode(", ",array_filter(array($related_study['nation'],$related_study['year_start'])));
        ?>
        <div class="related-study-row mb-2 pb-2 border-bottom" >
        <div><a href="<?php echo site_url('catalog/'.$related_study['id']);?>"><?php echo $related_study['title'];?></a></div>
        <div class="sub-info"><?php echo $nation_year;?></div>
        </div>
    <?php endforeach;?>
</div>

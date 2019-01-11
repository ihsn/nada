<?php 
    if (count($related_studies)==0){
        return;
    }
?>

<div class="related-studies">
    <h4><?php echo t('related_studies');?></h4>
    <?php foreach($related_studies as $related_study):?>
        <div class="related-study-row" >
        <div><a href="<?php echo site_url('catalog/'.$related_study['id']);?>"><?php echo $related_study['title'];?></a></div>
        <div class="sub-info"><?php echo $related_study['nation'];?>, <?php echo $related_study['year_start'];?></div>
        </div>
    <?php endforeach;?>
</div>

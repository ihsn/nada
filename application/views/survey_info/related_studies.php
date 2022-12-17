<?php 
    if (count($related_studies)==0){
        return;
    }
?>

<style>
    .secondary-text{
        font-size:small;
        color:gray;
        margin:0px;
    }
    .title-text{
        font-weight:bold;
    }
    .secondary-text, .sub-info{
        padding-left:10px;
    }
</style>

<div class="related-studies">
    <h4 class="mt-3 mb-4"><?php echo t('related_studies');?></h4>
    <?php foreach($related_studies as $related_study):?>
        <?php
                $nation_year=array();
                $nation_year=implode(", ",array_filter(array($related_study['nation'],$related_study['year_start'])));
        ?>
        <div class="related-study-row mb-3 pb-2 border-bottom" >
        <div class="title-text"><i class="fas fa-angle-right"></i> <a href="<?php echo site_url('catalog/'.$related_study['id']);?>"><?php echo $related_study['title'];?></a></div>
        <?php if(isset($related_study['subtitle']) && !empty($related_study['subtitle'])):?>
            <p class="secondary-text"><?php echo $related_study['subtitle'];?></p>
        <?php endif;?>
        <div class="sub-info"><?php echo $nation_year;?></div>
        </div>
    <?php endforeach;?>
</div>

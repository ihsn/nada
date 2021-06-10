<?php if (isset($data) && is_array($data) && count($data)>0 ):?>
<?php
    //resource fields
    $resource_fields=array(
    "title"=>'text',
    "file_name" =>'text',
    "description" =>'text',
    "resource_id" =>'text',
    "survey_id" =>'text'
);    
?>

<style>
.carousel-container{
    background:gainsboro;
    padding:5px;
}

.carousel-inner{
    max-height:300px;
    overflow:hidden !important;
    height:300px;
}

.carousel-img{
    max-height:300px;    
    width:auto;
}

.carousel-control-next:hover,
.carousel-control-prev:hover
{
    background:#80808036;
}




</style>

<div class="field resource-photo field-<?php echo $name;?>">
    <div class="xsl-caption field-caption"><?php echo t($name);?></div>
    <div class="field-value" >

    <div id="photoGallery" class="carousel-container carousel slide" data-ride="carousel" data-interval="9000">
    <ol class="carousel-indicators">

        <?php $counter=0;foreach($data as $index=>$resource):?>
            <?php if (in_array($resource['extension'],array('jpg','jpeg','gif','png'))):$counter++;?>
                <li data-target="#photoGallery" data-slide-to="<?php echo $counter-1;?>" class="<?php echo $counter==1 ? 'active': '';?>"></li>
            <?php endif;?>            
        <?php endforeach;?>

    </ol>
    <div class="carousel-inner">
        <?php $counter=0;foreach($data as $index=>$resource):?>
            <?php if (in_array($resource['extension'],array('jpg','jpeg','gif','png'))):$counter++;?>
                <div class="carousel-item <?php echo $counter==1 ? 'active' : '';?>">
                    <img src="<?php echo $resource['download_link'];?>" title="<?php echo $resource['title'];?>" class="d-block w-100 carousel-img" alt="<?php echo $resource['title'];?>">
                </div>                
            <?php endif;?>
            
        <?php endforeach;?>

    </div>

    <a class="carousel-control-prev" href="#photoGallery" role="button" data-slide="prev">
        <span class="carousel-control-prev-icon carousel-control" aria-hidden="true"></span>
        <span class="sr-only">Previous</span>
    </a>
    <a class="carousel-control-next" href="#photoGallery" role="button" data-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="sr-only">Next</span>
    </a>
    </div>

<?php /*
<div class="text-align-center">
    <a class="carousel-control-prevs" href="#photoGallery" role="button" data-slide="prev">
        <span class="carousel-control-prev-iconx" aria-hidden="true">Previous</span>
        <span class="sr-only">Previous</span>
    </a>
    <a class="carousel-control-nexts" href="#photoGallery" role="button" data-slide="next">
        <span class="carousel-control-next-iconx" aria-hidden="true">Next</span>
        <span class="sr-only">Next</span>
    </a>
</div>
*/?>

    
    </div>
</div>
<?php endif;?>
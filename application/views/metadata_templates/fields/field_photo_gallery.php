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


<?php 
//check if there is an image type resource
$image_exists=false;
foreach($data as $index=>$resource){
    if (in_array($resource['extension'],array('jpg','jpeg','gif','png'))){
        $image_exists=true;
        break;
    }
}
if (!$image_exists){return false;}
?>

<style>
.carousel-container{
    border:1px solid gainsboro;
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
    margin:auto;
}

.carousel-indicators {
    bottom: -5px;
}

/*.carousel-control-next:hover,
.carousel-control-prev:hover
{
    background:#80808036;
}*/

.icon-wrap{
    background: #545b62;
    padding: 10px;
    padding-bottom: 5px;
    border: 1px solid white;
}

.gallery-indicators li {
    height: 11px;
    border: 1px solid #ced4da;
    background-color: #6c757d;
}

.carousel-indicators .active{
    background-color:#f8f9fa;
}




</style>

<div class="field resource-photo field-<?php echo $name;?>">
    <div class="xsl-caption field-caption"><?php echo t($name);?></div>
    <div class="field-value" >

    <div id="photoGallery" class="carousel-container carousel slide" data-ride="carousel" data-interval="false">
    <ol class="carousel-indicators gallery-indicators">

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
                    <img src="<?php echo $resource['download_link'];?>" title="<?php echo $resource['title'];?>" class="carousel-img mx-auto d-block" alt="<?php echo $resource['title'];?>">
                </div>                
            <?php endif;?>
            
        <?php endforeach;?>

    </div>

    <a class="carousel-control-prev" href="#photoGallery" role="button" data-slide="prev">
        <span class="icon-wrap">
            <span class="carousel-control-prev-icon carousel-control" aria-hidden="true"></span>
        </span>
        <span class="sr-only">Previous</span>
    </a>
    <a class="carousel-control-next" href="#photoGallery" role="button" data-slide="next">
        <span class="icon-wrap">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
        </span>
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

    <div><?php echo t('Download');?></div>
    <ul>
        <?php foreach($data as $index=>$resource):?>
            <?php if (in_array($resource['extension'],array('jpg','jpeg','gif','png'))):$counter++;?>
                <li>
                    <a href="<?php echo $resource['download_link'];?>">
                        <i class="fa fa-download" aria-hidden="true"></i> <?php echo $resource['title'];?>
                    </a>
                </li>
            <?php endif;?>            
        <?php endforeach;?>
    </ul>

</div>
<?php endif;?>
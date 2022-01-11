
<?php if (isset($data) && is_array($data) && count($data)>0 ):?>

<style>
.feature-row {
    position:relative;
    color:#0071bc;
}
.feature-row:hover{
    background:#f0f0f0;
}

.icon-toggle{
    position: absolute;
    right: 20px;
    top: 8px;
    font-size: 20px;
}

.collapsed .up_arrow{
    display:none;
}

.show_feature .down_arrow{
    display:none;
}

.show_feature{
    background:#ced4da;
}

.feature-catalog-container .collapsed,
.feature-catalog-container .show_feature{
    cursor:pointer;
}
.text-truncate{
    margin-right:30px;
}
</style>

<script>
$(document).ready(function () {

    //toggle features
    $(document.body).on("click",".feature-row", function(){
        $(this).toggleClass("show_feature");
    });

});

</script>

<?php
$row_counter=0;
?>

<div class="feature-catalog-container table-responsive field field-<?php echo str_replace(".","__",$name);?>">
    <?php if (isset($data['name'])):?>
    <div class="xsl-caption field-caption"><?php echo t($data['name']);?></div>
    <?php endif;?>

    <div class="field-value">                
        
        <?php foreach($data['featureType'] as $features):?>
            <h3 class="border-bottom mt-5">
                <i class="fa fa-file-o" aria-hidden="true"></i>
                <?php echo $features['typeName'];?>
            </h3> 
            <p><?php echo $features['definition'];?></p>
                
            <div class="row feature-row-header border-bottom p-2">
                <div class="col-md-3 font-weight-bold"><?php echo t('name');?></div>
                <div class="col font-weight-bold"><?php echo t('description');?></div>
            </div>
            <?php foreach($features['carrierOfCharacteristics'] as $feature):$row_counter++;?>
                <div class="row feature-row border-bottom p-2 collapsed" data-toggle="collapse" href="#feature-<?php echo $row_counter;?>" role="button" aria-expanded="false" aria-controls="feature-<?php echo $row_counter;?>">
                    <div class="icon-toggle" >
                        <i class="up_arrow fa fa-angle-up" aria-hidden="true"></i>
                        <i class="down_arrow fa fa-angle-down" aria-hidden="true"></i>
                    </div>
                    <div class="col-md-3"><?php echo $feature['memberName'];?></div>
                    <div class="col text-truncate"><?php echo $feature['definition'];?></div>
                </div>
                <div class="row collapse bg-light p-3" id="feature-<?php echo $row_counter;?>">
                    <!--<div class="row"><pre><?php print_r($feature);?></pre>
                    </div>-->
                    

                    <div class="col-md-12">                        
                        <div>
                            <h5><?php echo $feature['memberName'];?></h5>
                            <p><?php echo $feature['definition'];?></p>
                        </div>
                    </div>


                    <?php if (isset($feature['cardinality'])):?>
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-3 font-weight-bold">Cardinality</div>
                            <div class="col-md-9">
                                Lower: <?php echo $feature['cardinality']['lower'];?>
                                Upper: <?php echo $feature['cardinality']['upper'];?>                            
                            </div>
                        </div>
                    </div>
                    <?php endif;?>

                    <?php if (isset($feature['valueMeasurementUnit'])):?>
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-3 font-weight-bold">Measurement unit</div>
                            <div class="col"><?php echo $feature['valueMeasurementUnit'];?></div>
                        </div>
                    </div>
                    <?php endif;?>

                    <?php if (isset($feature['valueType'])):?>
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-3 font-weight-bold">Value type</div>
                            <div class="col"><?php echo $feature['valueType'];?></div>
                        </div>
                    </div>
                    <?php endif;?>

                    
                    <?php if (isset($feature['listedValue'])):?>
                    <div class="col-md-12">
                        <div class="border-bottom mt-3 font-weight-bold">Listed values</div>
                        
                            <?php foreach($feature['listedValue'] as $row):?>
                                <div class="row border-bottom">
                                    <div class="col-md-2"><?php echo $row['label'];?></div>
                                    <div class="col"><?php echo $row['definition'];?></div>
                                </div>
                            <?php endforeach;?>
                        
                    </div>
                    <?php endif;?>

                </div>
            <?php endforeach;?>
        
        <?php endforeach;?>
        
    </div>
</div>
<?php endif;?>

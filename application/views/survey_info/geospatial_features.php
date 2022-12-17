<style>
    .fld-inline .fld-name{color:gray;}
    .float-left {width:35%;float:left;}
    .fld-container,.clear{clear:both;}

    .var-breadcrumb{
        list-style:none;
        clear:both;
        margin-bottom:25px;
        color:gray;
    }

    .var-breadcrumb li{display:inline;}
</style>


<?php
$core_fields=array(
    "fid",
    "vid",
    "name",
    "labl");
?>


<h5><?php echo $variable['labl'] . ' ('. $variable['name'].')';?></h5>
<h5 class="var-file">Data File: <a href="<?php echo site_url('catalog/'.$file['sid'].'/data-dictionary/'.$file['file_id']);?>"><?php echo $file['file_name'];?></a></h5>

<!-- Overview -->
<?php echo render_group('overview',
    $fields=array(
        "fid"=>'text',
        "vid"=>'text',
        "name"=>'text',
        "labl"=>'text'
    ),
    $variable['metadata']);

    $feature=$variable['metadata'];
?>

<div class="row p-3" >
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
                            <div class="col-auto font-weight-bold">Cardinality</div>
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
                            <div class="col-auto font-weight-bold">Measurement unit</div>
                            <div class="col"><?php echo $feature['valueMeasurementUnit'];?></div>
                        </div>
                    </div>
                    <?php endif;?>

                    <?php if (isset($feature['valueType'])):?>
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-auto font-weight-bold">Value type</div>
                            <div class="col"><?php echo $feature['valueType'];?></div>
                        </div>
                    </div>
                    <?php endif;?>

                    
                    <?php if (isset($feature['listedValue'])):?>
                    <div class="col-md-12">
                        <div class="border-bottom mt-3 font-weight-bold">Listed values</div>
                        
                            <?php foreach($feature['listedValue'] as $row):?>
                                <div class="row border-bottom">
                                    <div class="col-md-2 text-break"><?php echo $row['label'];?></div>
                                    <div class="col"><?php echo $row['definition'];?></div>
                                </div>
                            <?php endforeach;?>
                        
                    </div>
                    <?php endif;?>

                </div>
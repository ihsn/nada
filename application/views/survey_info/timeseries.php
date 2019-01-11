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

<?php /* ?>
<div class="var-breadcrumb">
    <ul>
        <li  class="var-breadcrumb-item">
        <a href="<?php echo site_url('catalog/'.$file['sid'].'/data-dictionary/');?>"><?php echo t('data_dictionary');?></a>
        </li>
        <li  class="var-breadcrumb-item">/</li>
        <li  class="var-breadcrumb-item"><a href="<?php echo site_url('catalog/'.$file['sid'].'/data-dictionary/'.$file['file_id']);?>"><?php echo $file['file_name'];?></a></li>
        <li  class="var-breadcrumb-item">/</li>
        <li  class="var-breadcrumb-item"><?php echo $variable['labl'] . ' ('. $variable['name'].')';?></li>
    </ul>
</div>
<!--<pre><?php //var_dump($variable); ?></pre>-->
<?php */ ?>


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
?>

<?php 
$other_fields=array();
foreach($variable['metadata'] as $key=>$value):?>
    <?php 
    if(in_array($key,$core_fields)){continue;}
    if(is_array($value)){
        $other_fields[$key]="array";
    }
    else{
        $other_fields[$key]="text";
    }
    ;?>
<?php endforeach;?>

<!-- others -->
<?php echo render_group('other',
    $fields=$other_fields,
    $variable['metadata']);
?>

<div style="text-align:right;margin-top:200px;">
<?php echo $website_title;?>
</div>

<div style="width:100%;background-color:#66CC66;">

    <div style="padding:10px;padding-top:300px;text-align:right;font-size:3em;color:white;">
    <?php echo $study_title;?>
    </div>
    
</div>

<div style="text-align:right">

    <div style="margin-top:20px;font-size:12pt;color:#669900;font-weight:bold;">
    <?php echo $publisher; ?>
    </div>
    
    <div style="margin-top:5px;font-size:12pt;color:gray;">
    <?php echo t('report_generated_on');?>: <?php echo date("F j, Y",date("U")); ?>
    </div>

    <div style="margin-top:50px;font-size:12pt;color:gray;">
    <?php echo t('visit_data_catalog_at');?>: <?php echo anchor($website_url);?>
    </div>

</div>
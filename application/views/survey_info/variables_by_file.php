<?php
$fmt_count=function($value){
    return is_numeric($value) ? number_format((float)$value) : html_escape((string)$value);
};
?>
<div class="container-fluid dd-file-header" id="datafile-container">
    <div class="dd-file-label"><?php echo t('data_file');?></div>
    <h3 class="dd-file-name text-break"><?php echo html_escape($file['file_name']);?></h3>

    <?php if(trim((string)$file['description'])!==''):?>
        <p class="dd-file-desc"><?php echo nl2br(html_escape($file['description']));?></p>
    <?php endif;?>

    <dl class="dd-file-info">
        <?php if ($file['case_count']):?>
            <div><dt><?php echo t('cases');?></dt><dd><?php echo $fmt_count($file['case_count']);?></dd></div>
        <?php endif;?>

        <?php if ($file['var_count']):?>
            <div><dt><?php echo t('variables');?></dt><dd><?php echo $fmt_count($file['var_count']);?></dd></div>
        <?php endif;?>

        <?php if(isset($file['producer']) && !empty($file['producer'])):?>
            <div><dt><?php echo t('producer');?></dt><dd><?php echo html_escape($file['producer']);?></dd></div>
        <?php endif;?>
    </dl>

    <?php if(isset($file['notes']) && !empty($file['notes'])):?>
        <div class="dd-file-notes">
            <div class="dd-file-label"><?php echo t('notes');?></div>
            <?php echo nl2br(html_escape($file['notes']));?>
        </div>
    <?php endif;?>
</div>


<div class="container-fluid variables-container" id="variables-container">
    <h4><?php echo t('variables');?></h4>

    <div class="container-fluid table-variable-list data-dictionary">
        <?php foreach($variables as $variable):?>
            <?php $var_url=site_url("catalog/$sid/variable/$file_id/{$variable['vid']}").'?name='.urlencode($variable['name']); ?>
            <div class="row var-row">
                <div class="icon-toggle"><i class="collapased_ fa fa-angle-down" aria-hidden="true"></i><i class="expanded_ fa fa-angle-up" aria-hidden="true"></i></div>
                <div class="col-md-3">
                    <div class="var-td">
                        <a class="var-id text-break" id="<?php echo md5($variable['vid']);?>" href="<?php echo $var_url;?>"><?php echo html_escape($variable['name']);?></a>
                    </div>
                </div>
                <div class="col">
                    <div class="var-label"><?php echo html_escape($variable['labl']);?></div>
                </div>
            </div>
            <div class="row var-info-panel" id="pnl-<?php echo md5($variable['vid']);?>">
                <div class="panel-td"></div>
            </div>
        <?php endforeach;?>
    </div>

    <?php if (trim((string)$variable_pagination)!==''):?>
    <div class="row">
        <div class="col-md-3">
            <?php echo t('total');?>: <?php echo $fmt_count($file_variables_count);?>
        </div>
        <div class="col-md-9">
            <div class="pagination float-right">
                <?php echo $variable_pagination;?>
            </div>
        </div>
    </div>
    <?php endif;?>
</div>

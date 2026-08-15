<style>
    .data-file-bg1 tr,.data-file-bg1 td {vertical-align: top;}
    .data-file-bg1 {margin-bottom:20px;}
</style>

<div class="container-fluid" id="datafile-container">
    <h4><?php echo t('feature_type');?>: <?php echo html_escape($file['file_name']);?></h4>
    <?php if ($file['description'] != ''): ?>
        <p><?php echo nl2br($file['description']);?></p>
    <?php endif; ?>

    <?php
    $attr_count = !empty($file['var_count']) ? $file['var_count'] : (isset($file_variables_count) ? $file_variables_count : 0);
    ?>
    <?php if ($attr_count): ?>
    <table class="data-file-bg1">
        <tr>
            <td style="width:100px;"><?php echo t('attributes');?>: </td>
            <td><?php echo (int) $attr_count;?></td>
        </tr>
    </table>
    <?php endif; ?>
</div>

<div class="container-fluid variables-container" id="variables-container">
    <h4><?php echo t('attributes');?></h4>
    <div class="container-fluid table-variable-list data-dictionary">
        <?php foreach ($variables as $variable): ?>
            <div class="row var-row">
                <div class="icon-toggle"><i class="collapased_ fa fa-angle-down" aria-hidden="true"></i><i class="expanded_ fa fa-angle-up" aria-hidden="true"></i></div>
                <div class="col-md-3">
                    <div class="var-td p-1">
                    <a class="var-id text-break" id="<?php echo md5($variable['vid']);?>" href="<?php echo site_url("catalog/$sid/variable/$file_id/{$variable['vid']}");?>?name=<?php echo urlencode($variable['name']);?>"><?php echo html_escape($variable['name']);?></a>
                    </div>
                </div>
                <div class="col">
                    <div class="p-1 pr-3">
                        <a class="var-id" id="<?php echo md5($variable['vid']);?>" href="<?php echo site_url("catalog/$sid/variable/$file_id/{$variable['vid']}");?>?name=<?php echo urlencode($variable['name']);?>">
                            <?php echo html_escape($variable['labl']);?>
                        </a>
                    </div>
                </div>
            </div>
            <div class="row var-info-panel" id="pnl-<?php echo md5($variable['vid']);?>">
                <div class="panel-td p-4"></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row">
        <div class="col-md-3">
            <?php echo t('total');?>: <?php echo $file_variables_count;?>
        </div>
        <div class="col-md-9">
            <div class="pagination float-right">
                <?php echo $variable_pagination;?>
            </div>
        </div>
    </div>
</div>

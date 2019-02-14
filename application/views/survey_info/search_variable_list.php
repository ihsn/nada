<div class="variables-container" id="variable-list" >
<?php if ($variables): ?>
<h5 style="margin-top:20px;margin-bottom:25px;"><?php echo sprintf(t('variable_search_match_found'),count($variables));?></h5>
    <div class="container-fluid table-variable-list data-dictionary ">
        <?php foreach($variables as $variable):?>
            <?php $tr_class=""; //if($tr_class=="row-color1") {$tr_class="row-color2";} else{ $tr_class="row-color1"; } ?>
            <div class="row var-row <?php echo $tr_class;?>" >
            <div class="icon-toggle"><i class="collapased_ fa fa-angle-down" aria-hidden="true"></i><i class="expanded_ fa fa-angle-up" aria-hidden="true"></i></div>            
                <div class="col-md-3 var-td p-1">
                    <a class="var-id" id="<?php echo md5($variable['vid']);?>" href="<?php echo site_url("catalog/$sid/variable/{$variable['vid']}");?>?name=<?php echo urlencode($variable['name']);?>"><?php echo html_escape($variable['name']);?></a>
                </div>
                <div class="col">
                    <div class="p-1">
                        <a class="var-id" id="<?php echo md5($variable['vid']);?>" href="<?php echo site_url("catalog/$sid/variable/{$variable['vid']}");?>?name=<?php echo urlencode($variable['name']);?>">
                            <?php echo html_escape($variable['labl']);?>
                        </a>
                    </div>                            
                </div>                    
            </div>
            <div class="row var-info-panel" id="pnl-<?php echo md5($variable['vid']);?>">
                <div class="panel-td p-4"></div>
            </div>                
        <?php endforeach;?>
    </div>    
<?php else: ?>
	<?php echo t('no_records_found');?>
<?php endif; ?>
</div>
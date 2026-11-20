<?php
/**
 * ISO 19110 listedValue list for a feature attribute.
 *
 * Each value may carry any of code, label and definition. Only columns that
 * hold data in at least one row are shown. A list of bare codes is shown as a
 * compact multi-column list. Long lists show the first rows with a toggle.
 *
 * @var array $listed_values
 */
$rows=array();
$cols=array('code'=>false,'label'=>false,'definition'=>false);
if (isset($listed_values) && is_array($listed_values)){
    foreach($listed_values as $lv){
        if (!is_array($lv)){
            continue;
        }
        $row=array();
        foreach(array_keys($cols) as $col){
            $row[$col]=isset($lv[$col]) && is_scalar($lv[$col]) ? trim((string)$lv[$col]) : '';
            if ($row[$col]!==''){
                $cols[$col]=true;
            }
        }
        if (implode('',$row)!==''){
            $rows[]=$row;
        }
    }
}
$cols=array_keys(array_filter($cols));
$total=count($rows);
$single_col=count($cols)===1;
$visible=$single_col ? 24 : 15;
$collapsible=$total>$visible+5;
?>
<?php if ($total): ?>
<div class="col-md-12">
<div class="geo-lv">
    <div class="geo-lv-title"><?php echo t('listed_values'); ?> <span class="geo-count">(<?php echo $total; ?>)</span></div>

    <?php if ($single_col): ?>
        <ul class="geo-lv-codes">
            <?php foreach($rows as $i=>$row): ?>
            <li class="text-break<?php echo $collapsible && $i>=$visible ? ' geo-lv-extra' : ''; ?>"><?php echo html_escape($row[$cols[0]]); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <table class="table table-sm">
            <thead>
                <tr>
                    <?php foreach($cols as $col): ?>
                        <th<?php echo $col==='code' ? ' class="geo-lv-code"' : ''; ?>><?php echo t('listed_value_'.$col); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach($rows as $i=>$row): ?>
                <tr<?php echo $collapsible && $i>=$visible ? ' class="geo-lv-extra"' : ''; ?>>
                    <?php foreach($cols as $col): ?>
                        <td class="<?php echo $col==='code' ? 'geo-lv-code' : 'text-break'; ?>"><?php echo html_escape($row[$col]); ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if ($collapsible): ?>
    <button type="button" class="btn btn-link geo-lv-toggle" onclick="this.parentNode.classList.toggle('geo-lv-expanded');return false;">
        <span class="geo-lv-more"><?php echo sprintf(t('show_all_n'), $total); ?></span>
        <span class="geo-lv-less"><?php echo t('show_fewer'); ?></span>
    </button>
    <?php endif; ?>
</div>
</div>
<?php endif; ?>

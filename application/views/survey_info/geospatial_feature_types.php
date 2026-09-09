<style>
.table-data-files td{
    cursor:pointer;
    border-bottom:1px solid gainsboro;
}
</style>

<?php
$files = is_array($files) ? $files : array();
$attr_count_col = array_filter(array_column($files, 'var_count'));
?>

<?php $this->load->view('survey_info/feature_catalogue_header', array('feature_catalogue' => isset($feature_catalogue) ? $feature_catalogue : array())); ?>

<h3><?php echo t('feature_types');?></h3>
<table class="table table-data-files ddi-table data-dictionary">
    <tbody>
    <tr>
        <th><?php echo t('feature_type');?></th>
        <?php if (!empty($attr_count_col)): ?>
            <th><?php echo t('attributes');?></th>
        <?php endif; ?>
    </tr>
    <?php foreach ($files as $file): ?>
    <tr class="data-file-row row-color1" data-url="<?php echo site_url("catalog/$sid/data-dictionary/{$file['file_id']}");?>">
        <td>
            <a href="<?php echo site_url("catalog/$sid/data-dictionary/{$file['file_id']}");?>?file_name=<?php echo html_escape($file['file_name']);?>"><?php echo html_escape($file['file_name']);?></a>
            <div class="file-description"><?php echo nl2br($file['description']);?></div>
        </td>
        <?php if (!empty($attr_count_col)): ?>
        <td><?php echo $file['var_count'];?></td>
        <?php endif; ?>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<style>
.table-data-files td{
    cursor:pointer;
    border-bottom:1px solid gainsboro;
}

</style>

<?php
    $case_counts_col=array_filter(array_column($files,'case_count'));
    $var_count_col=array_filter(array_column($files,'var_count'));
?>

<h3><?php echo t('data_dictionary');?></h3>
<table class="table table-data-files ddi-table data-dictionary" >
    <tbody>
    <tr>
        <th><?php echo t('data_file');?></th>

        <?php if(!empty($case_counts_col)):?>
            <th><?php echo t('cases');?></th>
        <?php endif;?>
        
        <?php if(!empty($case_counts_col)):?>
            <th><?php echo t('variables');?></th>
        <?php endif;?>
    </tr>
    <?php foreach($files as $file):?>
    <tr class="data-file-row row-color1" data-url="<?php echo site_url("catalog/$sid/data-dictionary/{$file['file_id']}");?>">
        <td>
            <a href="<?php echo site_url("catalog/$sid/data-dictionary/{$file['file_id']}");?>?file_name=<?php echo html_escape($file['file_name']);?>"><?php echo $file['file_name'];?></a>
            <?php /*
            <a href="<?php echo site_url("study/data_file/$sid/{$file['file_id']}");?>?file_name=<?php echo html_escape($file['file_name']);?>"><?php echo $file['file_name'];?></a>
            */ ?>
        
            <div class="file-description"><?php echo nl2br($file['description']);?></div>
        </td>
        <?php if(!empty($case_counts_col)):?>
            <td><?php echo $file['case_count'];?></td>
        <?php endif;?>
        <?php if(!empty($case_counts_col)):?>
        <td><?php echo $file['var_count'];?></td>
        <?php endif;?>
    </tr>
    <?php endforeach;?>
    </tbody>
</table>
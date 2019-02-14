<style>
.table-data-files td{
    cursor:pointer;
    border-bottom:1px solid gainsboro;
}

</style>

<h3><?php echo t('data_dictionary');?></h3>
<table class="table table-data-files ddi-table data-dictionary" >
    <tbody>
    <tr>
        <th><?php echo t('data_file');?></th>
        <th><?php echo t('cases');?></th>
        <th><?php echo t('variables');?></th>
    </tr>
    <?php foreach($files as $file):?>
    <tr class="data-file-row row-color1" data-url="<?php echo site_url("catalog/$sid/data-dictionary/{$file['file_id']}");?>">
        <td>
            <a href="<?php echo site_url("catalog/$sid/data-dictionary/{$file['file_id']}");?>?file_name=<?php echo html_escape($file['file_name']);?>"><?php echo $file['file_name'];?></a>
            <!--<a href="<?php echo site_url("study/data_file/$sid/{$file['file_id']}");?>?file_name=<?php echo html_escape($file['file_name']);?>"><?php echo $file['file_name'];?></a>-->
        
        <div class="file-description"><?php echo nl2br($file['description']);?></div>
    </td>
        <td><?php echo $file['case_count'];?></td>
        <td><?php echo $file['var_count'];?></td>
    </tr>
    <?php endforeach;?>
    </tbody>
</table>
<h2>data files</h2>

<?php if(count($rows)==0):?>
No data files found
<?php return;?>
<?php endif;?>

<table class="table table-striped table-bordered">
    <tr>
        <th>Data file</th>
        <th>External resource(s)</th>
        <th></th>
    </tr>    
<?php foreach($rows as $file_id=>$row):?>
<tr>
    <td><?php echo $row[0]['file_name'];?></td>
    <td>
    <ul>
        <?php foreach($row as $resource):?>
        
        <?php if(!$resource['resource_id']){continue;} ?>
        <li><?php echo $resource['filename'];?></li>
        <?php endforeach;?>
    </ul>
    </td>
    <td>        
        <a href="<?php echo site_url('admin/catalog/attach_data_file_resources/'.$row[0]['sid'].'/'.$row[0]['file_id']);?>">        
        <?php echo t('edit');?>
        </a>
    </td>
</tr>
<?php endforeach;?>
</table>
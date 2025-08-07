<style>
.table-header {
    background-color: #f8f9fa;
    font-weight: bold;
}
.table th {
    padding: 8px;
    border: 1px solid #dee2e6;
    background-color: #f8f9fa;
    font-weight: 600;
}
.table td {
    padding: 8px;
    border: 1px solid #dee2e6;
    vertical-align: top;
}
.table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(0,0,0,.05);
}
</style>

<table style="width:100%;">
<tr>
	<td><h1><?php echo t('public_requests');?></h1></td>
    <td style="text-align:right;"><?php $this->load->view('reports/download_options'); ?></td>
</tr>
</table>

<?php 
// Load custom fields configuration to get field titles
$this->load->config('public_request_fields');
$custom_fields_config = $this->config->item('public_request_fields');
$enabled_custom_fields = array();

// Filter enabled custom fields
if ($custom_fields_config) {
    foreach ($custom_fields_config as $field_key => $field_config) {
        if (isset($field_config['enable']) && $field_config['enable'] === true) {
            $enabled_custom_fields[$field_key] = $field_config;
        }
    }
}
?>

<?php if ($rows):?>
    <table class="table table-striped table-bordered" style="width:100%;">
    	<tr class="table-header">
            <th style="width:200px;"><?php echo t('title');?></th>
            <th><?php echo t('username');?></th>
            <th width="120px"><?php echo t('organization');?></th>
            <th><?php echo t('country');?></th>
            <th><?php echo t('dated');?></th>
            <th><?php echo t('intended_use');?></th>
            <?php foreach ($enabled_custom_fields as $field_key => $field_config): ?>
                <th><?php echo $field_config['title']; ?></th>
            <?php endforeach; ?>            
        </tr>
    
    <?php foreach($rows as $row):?>        	            	
        <tr>
	        <td><div style="width:200px;"><?php echo ($row['title']) ? $row['title'] : $row['survey_title'];?></div></td>
            <td><?php echo $row['username'];?></td>
            <td><?php echo $row['company'];?></td>
            <td><?php echo $row['country'];?></td>
            <td><?php echo date("m/d/y",$row['posted']);?></td>
            <td style="width:200px;">
				<?php echo substr($row['abstract'],0,150);?> 
                <a target="_blank" href="<?php echo site_url();?>/admin/public_requests/<?php echo $row['id'];?>"><?php echo t('details');?></a>
            </td>
            <?php foreach ($enabled_custom_fields as $field_key => $field_config): ?>
                <td>
                    <?php 
                    $field_name = isset($field_config['name']) ? $field_config['name'] : $field_key;
                    $field_value = isset($row[$field_name]) ? $row[$field_name] : '';
                    
                    // Format the value based on field type
                    if ($field_config['type'] == 'select' && isset($field_config['enum'])) {
                        // Display enum label instead of value
                        echo isset($field_config['enum'][$field_value]) ? $field_config['enum'][$field_value] : $field_value;
                    } elseif ($field_config['type'] == 'checkbox') {
                        // Display Yes/No for checkboxes
                        echo $field_value == '1' ? 'Yes' : 'No';
                    } else {
                        // Display raw value for other types
                        echo htmlspecialchars($field_value);
                    }
                    ?>
                </td>
            <?php endforeach; ?>            
        </tr>
    <?php endforeach;?>
    </table>
<?php else:?>    
<?php echo t('no_records_found');?>
<?php endif;?>
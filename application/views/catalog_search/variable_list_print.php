<style>
h3{margin:0px;}
</style>

<?php if (isset($rows)): ?>
<?php if ($rows): ?>

<?php 
	//current page url
	$page_url=site_url().$this->uri->uri_string();
	
	//total pages
	$pages=ceil($found/$limit);	
?>

<div class="pagination">
<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr valign="middle">
	<td>
         <?php echo sprintf(t('showing_variables'),
							(($limit*$current_page)-$limit+1),
							($limit*($current_page-1))+ count($rows),
							$found);?>
     </td>
    <td align="right"></td>
</tr>
</table>
</div>

<?php $tr_class=""; ?>
	<table class="grid-table" cellpadding="0" cellspacing="0" width="100%">
        	<tr class="header">
            <td><?php echo t('name');?></td>
            <td><?php echo t('label');?></td>
        </tr>	

	<?php foreach($rows as $row):?>
  		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr  class="<?php echo $tr_class; ?>" valign="top">
            <td><?php echo $row['name'];?></td>
            <td>
				<h3 class="labl" ><?php echo ($row['labl']!=='') ? $row['labl'] : $row['name']; ?></h3>
				<div style="color:#666666"><?php echo $row['nation']. ' - '.$row['titl']; ?></div>
            </td>
        </tr>
    <?php endforeach;?>
	</table>

<div class="pagination">
<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr valign="middle">
	<td>
         <?php echo sprintf(t('showing_variables'),
							(($limit*$current_page)-$limit+1),
							($limit*($current_page-1))+ count($rows),
							$found);?>
     </td>
    <td align="right"></td>
</tr>
</table>
</div>

<?php else: ?>
	<?php echo t('no_records_found');?>
<?php endif; ?>
<?php endif; ?>
<?php $this->load->view('tracker/tracker');?>
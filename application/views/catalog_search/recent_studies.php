<div class="recent-studies">
<h3>Latest Additions</h3>
<?php if (isset($rows) && count($rows)>0): ?>
<table style="width:100%;">
	<?php 
		$total_rows=count($rows);
		$counter=0;
	?>
	<?php foreach($rows as $row): ?>
    <?php $counter++; ?>
    <?php //var_dump($row);exit;?>
    <tr  class="survey-row <?php if($counter==$total_rows) { echo "last";}?> ">
    <td>
            <div class="title">
                <a href="<?php echo site_url(); ?>/catalog/<?php echo $row['id']; ?>"  title="<?php echo $row['title']; ?>" >
                        <?php echo $row['nation']. ' - '. $row['title'];?>
                </a>
            </div>
            
            <div class="created"><?php echo date("M d, Y",$row['created']);?></div>
            
            <?php if (isset($row['repo_title']) && $row['repo_title']!=''):?>
                <div class="sub-title"><?php echo t('catalog_owned_by')?>: <?php echo $row['repo_title'];?></div>
            <?php endif;?>
	</td>
    <td>	
       		<?php if ($row['form_model']!=''):?>
	            <a href="<?php echo site_url(); ?>/catalog/<?php echo $row['id']; ?>"  title="<?php echo $row['title']; ?>" >        
		        <?php if($row['form_model']=='direct'): ?>
                    <span title="<?php echo t('link_data_direct_hover');?>"><img src="images/form_direct.gif" /></span>                    
                <?php elseif($row['form_model']=='public'): ?>                    
                    <span  title="<?php echo t('link_data_public_hover');?>"><img src="images/form_public.gif" /></span>
                <?php elseif($row['form_model']=='licensed'): ?>
                    <span title="<?php echo t('link_data_licensed_hover');?>"><img src="images/form_licensed.gif" /></span>
                <?php elseif($row['form_model']=='data_enclave'): ?>
                    <span title="<?php echo t('link_data_enclave_hover');?>"><img src="images/form_enclave.gif" /></span>
                <?php elseif($row['form_model']=='remote'): ?>
                	<?php //if (isset($row['link_da']) && strlen($row['link_da'])>1):?>
                    <span title="<?php echo t('link_data_remote_hover');?>"><img src="images/form_remote.gif" /></span>
                	<?php //endif; ?>
				<?php endif; ?>
                </a>
            <?php endif;?> 
	</td>
    </tr>
	<?php endforeach;?>
</table>    
<?php else: ?>
	<div><?php echo t('no_records_found');?></div>
<?php endif; ?>
</div>
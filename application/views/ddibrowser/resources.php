<style>
	.abstract{height:200px;border:1px solid silver; overflow:scroll; overflow-x: hidden;display:none;padding:5px;margin-bottom:10px;background:white; }
	.resource{background-color:#F4F4F4;padding:5px;margin-bottom:5px;}
</style>
<h1 class="xsl-title"><?php echo $title; ?></h1>
<?php if($resources):?>
	<?php foreach($resources as $row):?>
    <div class="resource">    	
        <div>
            <?php echo isset($row['author']) ? '<b>'.$row['title'].'</b>, '.$row['author'] : '<b>'.$row['title'].'</b>'; ?>
            <?php echo $row['language']; ?>
            <?php if (substr($row['filename'],0,4)=='www.' || substr($row['filename'],0,7)=='http://' || substr($row['filename'],0,8)=='https://' || substr($row['filename'],0,6)=='ftp://'):?>
				<div><a target="_blank" href="<?php echo prep_url($row['filename']);?>"><?php echo prep_url($row['filename']);?></a></div>
            <?php elseif (check_resource_file($this->survey_folder.'/'.$row['filename'])!==FALSE): ?>
            	<div><?php echo anchor('ddibrowser/'.$this->uri->segment(2).'/download/'.$row['resource_id'],end(explode('/',get_filename($row['filename'])))); ?></div>
            <?php endif; ?>
        </div>
        <?php if ($row['description']!='' || $row['abstract']!=''  || $row['toc']!='' ):?>
	        <div>
            <?php echo t('show_more_info');?>:
			<?php if ($row['description']!=''):?>
            <input type="checkbox" onclick="toggle_resource('des_<?php echo $row['resource_id'];?>')"/><?php echo t('description');?>
            <?php endif;?>
            <?php if ($row['abstract']!=''):?>
                    <input type="checkbox" onclick="toggle_resource('abstract_<?php echo $row['resource_id'];?>')"/><?php echo t('abstract');?>
            <?php endif;?>
            <?php if ($row['toc']!=''):?>
                    <input type="checkbox" onclick="toggle_resource('toc_<?php echo $row['resource_id'];?>')"/><?php echo t('table_of_contents');?>
            <?php endif;?>
            </div>
        <?php endif;?>
    
        <?php if ($row['description']!=''):?>
                <div id="des_<?php echo $row['resource_id']?>" class="abstract"><?php echo nl2br($row['description']); ?></div>
        <?php endif;?>
        <?php if ($row['abstract']!=''):?>
                <div id="abstract_<?php echo $row['resource_id']?>" class="abstract"><?php echo nl2br($row['abstract']); ?></div>
        <?php endif;?>
        <?php if ($row['toc']!=''):?>
                <div id="toc_<?php echo $row['resource_id']?>" class="abstract"><?php echo nl2br($row['toc']); ?></div>
        <?php endif;?>
     </div>
    <?php endforeach;?>
<?php else:?>
<?php echo t('no_records_found');?>
<?php endif;?>

<?php 
function check_resource_file($file_path)
{
	$file_path=unix_path($file_path);
	
	if (file_exists($file_path))
	{
		return $file_path;
	}
	return FALSE;
}
?>
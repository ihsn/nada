<style>
td.caption{
	text-align:left;width:200px;
}
</style>
<?php if(!$resources_microdata):?>
	<h3>No data is available</h3>
	<div class="error">No data is available for download
	</div>
	<?php return;?>
<?php endif;?>
<div style="padding-top:20px;overflow:hidden;">
<h2><?php echo t('study_data_files');?></h2>
<div class="resources data-files">
    <?php $class="data-file";$count=0;$show_rows=150; ?>
	<?php foreach($resources_microdata as $row):?>
		<?php 
		$count++; 
		
		//clean up fields
		$row['country']=strip_brackets($row['country']);
		$row['language']=strip_brackets($row['language']);
		
		$url=NULL;
		$file_size='';
		$file_basename='';

		//check file/URL
		if (substr($row['filename'],0,4)=='www.' || substr($row['filename'],0,7)=='http://' || substr($row['filename'],0,8)=='https://' || substr($row['filename'],0,6)=='ftp://'){
			$url=prep_url($row['filename']);
			$file_basename=$url;
		}
		elseif (trim($row['filename'])!=='' && file_exists(unix_path($storage_path.'/'.$row['filename']))!==FALSE ){
			$url=site_url('catalog/'.$sid.'/download/'.$row['resource_id']);
			$file_size=format_bytes(filesize(unix_path($storage_path.'/'.$row['filename'])),2);
			$file_basename=form_prep(basename($row['filename']));
			$url_with_name=$url.'?filename='.urlencode($file_basename);
		}
		//get file extension
		$ext=get_file_extension($row['filename']);

		if($class=="resource") {
			$class="resource alternate";
		} 
		else{ 
			$class="resource"; 
		}
		
		if ($count>=$show_rows){
			$class.=" hidden";
		}
		?>
		
		<div class="<?php echo $class;?>" data-file-type="microdata" >
			<div class="resource-left-col">
				<span class="resource-info" class="resource-info" 
					title="<?php echo t('click_to_view_information');?>" 
					alt="<?php echo t('view_more_information');?>" 
					id="<?php echo $row['resource_id'];?>">
					<i class="fa fa-plus-square-o icon-expand" aria-hidden="true"></i>
					<i class="fa fa-minus-square-o icon-collapsed" aria-hidden="true"></i>
					<?php echo $row['title'];?>                                 
				</span>
				<div class="resource-right-col float-right">
					<?php if($url!='' || $file_size!=''):?>
					<?php
						$download_str=array();
						$download_str[]=strtoupper($ext);                                            
						$download_str[]=$file_size;

						$download_str=array_filter($download_str);

						if ($file_size!=''){
							$download_str=t('download'). " [". implode(", ",$download_str)."]";
						}
						else{
							$download_str=t('download');
						}

					?>
						<a  target="_blank" 
							href="<?php echo $url;?>" 
							title="<?php echo basename($row['filename']);?>"
							title="<?php echo html_escape(basename($row['filename']));?>"
							data-filename="<?php echo html_escape(basename($row['filename']));?>"
							data-dctype="<?php echo html_escape($row['dctype']);?>"
							data-extension="<?php echo html_escape($ext);?>"
							data-sid="<?php echo $row['survey_id'];?>" 
							class="download btn btn-outline-primary btn-sm">
								<i class="fa fa-arrow-circle-down" aria-hidden="true"></i> 
								<?php echo $download_str;?>
						</a>
					<?php endif;?>
					</div>
			</div>
			
			

			<?php if ($row['description']!='' || $row['title']!=''  || $row['toc']!='' ):?>                    
				<div id="info_<?php echo $row['resource_id'];?>" class="abstract">
				
				<?php $fields_arr=array(
							'author'=>		t('authors'),
							'subtitle'=>	t('subtitle'),
							'dcdate'=>		t('date'),
							'country'=>		t('country'),
							'language'=> 	t('language'),
							'contributor'=> t('contributors'),
							'publisher'=>	t('publishers'),
							'rights'=>		t('rights'),
							'description'=> t('description'),
							'abstract'=>	t('abstract'),
							'toc'=>			t('table_of_contents'),
							'subjects'=>	t('subjects')
							);
				?>
				
				<table class="table table-sm wb-table-sm grid-table tbl-resource-info" >
					<?php foreach ($row as $key=>$value):?>
						<?php if (trim($value)!=""):?>
							<?php if (array_key_exists($key,$fields_arr)):?>
							<tr valign="top">
								<td  class="caption"><?php echo $fields_arr[$key];?></td>
								<td><?php echo nl2br($value);?></td>
							</tr>
							<?php endif;?>
						<?php endif;?>
					<?php endforeach;?>
					<?php if($file_basename):?>
					<tr>
						<td class="caption"><?php echo t('filename');?></td>
						<td><?php echo $file_basename;?></td>
					</tr>
					<?php endif;?>
					
					<?php if ($url):?>
					<tr>						
						<td class="caption"><?php echo t('download');?></td>
						<td><?php echo ($url==="") ? "N/A" : '<a data-file-id="'.$row['resource_id'].'" class="download" title="'.$file_basename.'" href="'.$url.'">'.$url.'</a>';?></td>
					</tr> 
					<?php endif;?>
				</table>
				
				</div>
			<?php endif;?>
		
			</div>
		<?php endforeach;?>
		<?php if ($count>$show_rows):?>
		<div style="text-align:right;margin-right:10px;"><a href="#" class="show-datafiles"><?php echo t('show_all_files');?></a></div>
		<?php endif;?>
</div>
</div>
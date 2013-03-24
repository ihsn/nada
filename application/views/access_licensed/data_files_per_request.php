<?php
/**
* List of microdata files available for downloading for a given request
*
**/
?>
<?php if(!$resources_microdata){return false;}?>
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
						
                        //check file/URL
                        if (substr($row['filename'],0,4)=='www.' || substr($row['filename'],0,7)=='http://' || substr($row['filename'],0,8)=='https://' || substr($row['filename'],0,6)=='ftp://') 
                        {
                            $url=prep_url($row['filename']);
                        }
                        elseif (trim($row['filename'])!=='' && check_resource_file($this->survey_folder.'/'.$row['filename'])!==FALSE )
                        {
                            $url=site_url().'/access_licensed/download/'.$request_id.'/'.$row['resource_id'];
							$file_size=format_bytes(filesize($this->survey_folder.'/'.$row['filename']),2);
                        }
						//get file extension
						$ext=get_file_extension($row['filename']);
               
               			if($class=="resource") {$class="resource alternate";} else{ $class="resource"; }
						
						if ($count>=$show_rows)
						{
							$class.=" hidden";
						}
				?>
               
                <div class="<?php echo $class;?>">    	
                    <div class="resource-left-col">
                    		<div class="resource-info" class="resource-info" title="<?php echo t('click_to_view_information');?>" alt="<?php echo t('view_more_information');?>" id="<?php echo $row['resource_id'];?>"><?php echo $row['title'];?></div>
                    </div>
                    <div class="resource-right-col">
                    	<span class="resource-file-size">
							<?php 
								$link_text= '<img src="'.get_file_icon($ext).'" alt="'.$ext.'"  title="'.basename($row['filename']).'"/> ';
								//$link_text.= strtoupper($ext);
                            	if ($file_size!='')
								{									
									$link_text.= ' &nbsp; '.$file_size;
                            	}
								
								if ($url!='')
								{
									$link_text= '<a target="_blank" href="'.$url.'" title="'.basename($row['filename']).'" class="download">'.$link_text.'</a>';
								}
								else
								{
									$link_text="";
								}
								
								echo $link_text;
								?>
                        </span>        
                    </div>
                    <div style="float:right;font-size:11px;">
                    </div>
                    <br style="clear:both;"  />

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
                        
                        <table class="grid-table tbl-resource-info" >
							<?php foreach ($row as $key=>$value):?>
                                <?php if ($value!=""):?>
									<?php if (array_key_exists($key,$fields_arr)):?>
                                    <tr valign="top">
                                        <td  class="caption"><?php echo $fields_arr[$key];?></td>
                                        <td><?php echo nl2br($value);?></td>
                                    </tr>
                                    <?php endif;?>
                                <?php endif;?>
                            <?php endforeach;?>
                            <tr>
                                <td class="caption"><?php echo t('download');?></td>
                                <td><?php echo ($link_text==="") ? "N/A" : '<a class="download" title="'.basename($row['filename']).'" href="'.$url.'">'.$url.'</a>';?></td>
                            </tr>                        
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
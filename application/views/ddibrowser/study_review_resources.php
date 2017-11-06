<div style="padding-top:20px;">
<h2><?php echo t('study_resources');?></h2>
<?php if($resources):?>
<div class="resources">
    <?php $class="resource"; ?>
	<?php foreach($resources as $key=>$resourcetype):?>
		<?php if (count($resourcetype)>0):?>
        <fieldset>
        <legend>
			<?php 
				switch($key) 
				{
					case 'technical':
						echo t('technical_documents');
					break;
					
					case 'reports':
						echo t('reports');
					break;
					
					case 'questionnaires':
						echo t('questionnaires');
					break;
					
					case 'other':
					default:
						echo t('other_materials');
					break;
				}
			?>
        </legend>
				<?php foreach($resourcetype as $row):?>
                <?php 
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
                            $url=site_url().'/catalog/'.$this->uri->segment(2).'/review/download/'.$row['resource_id'];
							$file_size=format_bytes(filesize($this->survey_folder.'/'.$row['filename']),2);
                        }
						//get file extension
						$ext=get_file_extension($row['filename']);
                ?>
                <?php if($class=="resource") {$class="resource alternate";} else{ $class="resource"; } ?>
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
									$link_text= '<a target="_blank" href="'.$url.'" data-file-id="'.$row['resource_id'].'" title="'.basename($row['filename']).'" class="download">'.$link_text.'</a>';
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
    </fieldset>
    <?php endif;?>
    <?php endforeach;?>
</div>
<?php else:?>
	<?php echo t('no_resources_attached');?>
<?php endif;?>
</div>

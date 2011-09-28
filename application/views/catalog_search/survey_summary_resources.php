<?php if($resources):?>
<style>
	.resources h3{font-weight:bold;padding-top:10px;font-size:12px;}
	.abstract{display:none;margin-bottom:10px;background-color:none;}
	.resources .alternate, .resources .resource{border-bottom:1px solid #C1DAD7;padding:5px;width:98%;overflow:auto;}
	.resources .alternate{background-color:#FBFBFB;}
	.resources .alternate:hover, .resources .resource:hover{background-color:#EAEAEA}
	.resources fieldset {border:0px;border-top:4px solid gainsboro;margin-top:20px;margin-bottom:10px;padding-top:5px;color:#333333;font-size:12px;}
	.resources fieldset legend{font-weight:bold;;padding:5px;text-transform:capitalize;margin-left:10px;}	
	.resource-info{cursor:pointer;color:maroon;}
	.resource-right-col{float:right;font-size:11px;width:15%;}
	.resource-left-col{float:left;width:85%;}
	.resource-file-size{display:inline-block;width:100px;text-align:left;color:#999999;}
</style>
<div style="padding-top:20px;">
<h2><?php echo t('study_resources');?></h2>
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
                        $url=NULL;
						$file_size='';
                        //check file/URL
                        if (substr($row['filename'],0,4)=='www.' || substr($row['filename'],0,7)=='http://' || substr($row['filename'],0,8)=='https://' || substr($row['filename'],0,6)=='ftp://') 
                        {
                            $url=prep_url($row['filename']);
                        }
                        elseif (check_resource_file($this->survey_folder.'/'.$row['filename'])!==FALSE )
                        {
                            $url=site_url().'/ddibrowser/'.$this->uri->segment(2).'/download/'.$row['resource_id'];
							$file_size=format_bytes(filesize($this->survey_folder.'/'.$row['filename']),2);
                        }
						//get file extension
						$ext=get_file_extension($row['filename']);
                ?>
                <?php if($class=="resource") {$class="alternate";} else{ $class="resource"; } ?>
                <div class="<?php echo $class;?>">    	
                    <div class="resource-left-col">
                        	<?php
								//build title
								$title_text=array();
                            	$title_text[]=$row['title'];
								if (isset($row['author']) && $row['author']!='')
								{
									$title_text[]=$row['author'];
								}
								if (isset($row['language']) && $row['language']!='')
								{
									$title_text[]=$row['language'];
								}
							?>	
                        <?php if ($row['description']!='' || $row['abstract']!=''  || $row['toc']!='' ):?>
                    		<span class="resource-info" class="resource-info" title="<?php echo t('click_to_view_information');?>" alt="<?php echo t('view_more_information');?>" id="<?php echo $row['resource_id'];?>"><?php echo implode(", ",$title_text);?></span>
                        <?php else:?>
	                        <span><?php echo implode(", ",$title_text);?></span>
                        <?php endif;?>
                    </div>
                    <div class="resource-right-col">
                    	<span class="resource-file-size">
							<?php 
								$link_text= '<img src="'.get_file_icon($ext).'" alt="'.$ext.'"  title="'.$ext.'"/> ';
								//$link_text.= strtoupper($ext);
                            	if ($file_size!='')
								{									
									$link_text.= ' - '.$file_size;
                            	}
								if ($url!='')
								{
									echo '<a target="_blank" href="'.$url.'" title="'.$url.'" class="download">'.$link_text.'</a>';
								}
								else
								{
									//echo $link_text;
								}
								?>
                        </span>        
                    </div>
                    <div style="float:right;font-size:11px;">
                    </div>
                    <br style="clear:both;"  />

                    <?php if ($row['description']!='' || $row['abstract']!=''  || $row['toc']!='' ):?>
						<div id="info_<?php echo $row['resource_id'];?>" class="abstract">
							<?php if ($row['description']!=''):?>                       				
                                    <div>
	                                    <h3><?php echo t('description');?></h3>
										<?php echo nl2br($row['description']); ?>
                                    </div>
                            <?php endif;?>
                            <?php if ($row['abstract']!=''):?>
                                    <div>
									<h3><?php echo t('abstract');?></h3>
									<?php echo nl2br($row['abstract']); ?>
                                    </div>
                            <?php endif;?>
                            <?php if ($row['toc']!=''):?>
                                    <div>
									<h3><?php echo t('table_of_contents');?></h3>
									<?php echo nl2br($row['toc']); ?></div>
                            <?php endif;?>
                        
                        </div>
                    <?php endif;?>
                
                 </div>
                <?php endforeach;?>
    </fieldset>
    <?php endif;?>
    <?php endforeach;?>
</div>
</div>
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
<script type="text/javascript">
	//show/hide resource
	function toggle_resource(element_id){
		$("#"+element_id).toggle();
	}
	
	$(document).ready(function () { 
		bind_behaviours();
	});	
	
	function bind_behaviours() {
		//show variable info by id
		$(".resource-info").unbind('click');
		$(".resource-info").click(function(){
			if($(this).attr("id")!=''){
				toggle_resource('info_'+$(this).attr("id"));
			}
			return false;
		});	
	}
</script>
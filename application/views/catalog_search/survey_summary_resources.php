<?php if($resources):?>
<style>
	.resources h3{font-weight:bold;padding-top:10px;font-size:12px;}
	.abstract{display:none;margin-bottom:10px;background-color:none;}
	.resources .alternate, .resources .resource{border-bottom:1px solid #C1DAD7;padding:5px;}
	.resources .alternate{background-color:#FBFBFB;}
	.resources fieldset {border:0px;border-top:4px solid gainsboro;margin-top:20px;margin-bottom:10px;padding-top:5px;color:#333333;font-size:12px;}
	.resources fieldset legend{font-weight:bold;;padding:5px;text-transform:capitalize;margin-left:10px;}	
	.resource-info{cursor:pointer}
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
                        //check file/URL
                        if (substr($row['filename'],0,4)=='www.' || substr($row['filename'],0,7)=='http://' || substr($row['filename'],0,8)=='https://' || substr($row['filename'],0,6)=='ftp://') 
                        {
                            $url=prep_url($row['filename']);
                        }
                        elseif (check_resource_file($this->survey_folder.'/'.$row['filename'])!==FALSE )
                        {
                            $url=site_url().'/ddibrowser/'.$this->uri->segment(2).'/download/'.$row['resource_id'];
                        }				
                ?>
                <?php if($class=="resource") {$class="alternate";} else{ $class="resource"; } ?>
                <div class="<?php echo $class;?>">    	
                    <div>
                        <?php if ($url!=NULL):?>
                            <a target="_blank" href="<?php echo $url;?>" title="<?php echo $url;?>">
                                <?php echo isset($row['author']) ? $row['title'].', '.$row['author'] : $row['title']; ?>
                                <?php echo $row['language']; ?>
                            </a>
                        <?php else:?>
                            <?php echo isset($row['author']) ? $row['title'].', '.$row['author'] : $row['title']; ?>
                            <?php echo $row['language']; ?>            
                        <?php endif;?>
                        <?php if ($row['description']!='' || $row['abstract']!=''  || $row['toc']!='' ):?>
                    	<img src="images/icon_play.gif" class="resource-info" title="<?php echo t('click_to_view_information');?>" alt="<?php echo t('view_more_information');?>" align="absbottom" id="<?php echo $row['resource_id'];?>" />
                        <?php endif;?>
                    </div>

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
		$(".resource-info").click(function(){
			if($(this).attr("id")!=''){
				toggle_resource('info_'+$(this).attr("id"));
			}
			return false;
		});	
	}
</script>
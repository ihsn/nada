<style>
	.resources h3{font-weight:bold;padding-top:10px;font-size:12px;}
	.abstract{display:none;margin-bottom:10px;background-color:none;}
	.resources .alternate, .resources .resource{border-bottom:1px solid #C1DAD7;padding:5px;width:98%;overflow:auto;}
	.resources .alternate{background-color:#FBFBFB;}
	.resources .alternate:hover, .resources .resource:hover{background-color:#EAEAEA}
	.resources fieldset {border:0px;border-top:4px solid gainsboro;margin:0px;padding:0px;margin-top:20px;margin-bottom:10px;padding-top:5px;color:#333333;font-size:12px;}
	.resources fieldset legend{font-weight:bold;;padding:5px;text-transform:capitalize;margin-left:10px;}	
	.resource-info{cursor:pointer;}
	.resource-right-col{float:right;font-size:11px;width:15%;}
	.resource-left-col{float:left;width:85%;}
	.resource-file-size{display:inline-block;width:100px;text-align:left;color:#999999;}
	.tbl-resource-info{padding:0px;margin:0px; border-collapse:collapse}
	.resource-info{padding-left:20px;background:url('images/blue-add.png') no-repeat left top;}
	.active .resource-info{font-weight:bold;margin-bottom:10px;background:url('images/blue-remove.png') no-repeat left top;}
	.resources .active{border:1px solid gainsboro;margin-bottom:20px;}
	.resource .caption{font-weight:bold;}
</style>
<div class="resources">
<h3><?php echo $title; ?></h3>
<?php if($resources):?>
	 <?php $class="resource"; ?>
	<?php foreach($resources as $row):?>
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
            $url=site_url().'/catalog/'.$this->uri->segment(2).'/download/'.$row['resource_id'];
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
<?php else:?>
<?php echo t('no_records_found');?>
<?php endif;?>
</div>
<script type="text/javascript">
	function toggle_resource(element_id){
		$("#"+element_id).parent(".resource").toggleClass("active");
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
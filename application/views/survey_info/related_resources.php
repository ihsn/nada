<?php

$legend_labels=array(

    'technical'=>t('technical_documents'),
    'reports'=>t('reports'),
    'questionnaires'=>t('questionnaires'),
    'other'=>t('other_materials')
);

$fields_arr=array(
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

<style>
.study-metadata .resource-info{padding-left:0px;}
</style>

<?php if(!$resources):?>
    <div>No documentation is available</div>
    <?php return;?>
<?php endif;?>

<div style="padding-top:20px;">
    <h2><?php echo t('study_resources');?></h2>
    <div class="subtext"><?php echo t('study_documentation_text');?></div>
    <div class="resources">
        <?php $class="resource"; ?>
        <?php foreach($resources as $key=>$resourcetype):?>
            <?php if (count($resourcetype)>0):?>
                <fieldset>
                    <legend>
                        <?php echo isset($legend_labels[$key]) ? $legend_labels[$key] : $legend_labels['other'];?>
                    </legend>
                    <?php foreach($resourcetype as $row):?>
                        <?php
                        //clean up fields
                        $row['country']=strip_brackets($row['country']);
                        $row['language']=strip_brackets($row['language']);

                        $url=NULL;
                        $file_size='';
                        $link_text='';
                        $is_url=false;

                        //check file/URL
                        if (substr($row['filename'],0,4)=='www.' 
                            || substr($row['filename'],0,7)=='http://' 
                            || substr($row['filename'],0,8)=='https://' 
                            || substr($row['filename'],0,6)=='ftp://')
                        {
                            $url=prep_url($row['filename']);
                            $is_url=true;
                        }
                        elseif (trim($row['filename'])!=='' 
                            && check_resource_file($survey_folder.'/'.$row['filename'])!==FALSE )
                        {
                            $url=site_url().'/catalog/'.$sid.'/download/'.$row['resource_id'];
                            $file_size=format_bytes(filesize($survey_folder.'/'.$row['filename']),2);
                        }

                        //get file extension
                        $ext=get_file_extension($row['filename']);
                        ?>
                        <?php if($class=="resource") {$class="resource alternate";} else{ $class="resource"; } ?>
                        <div class="colx <?php echo $class;?>">
                            <div class="resource-left-colx row">
                                <div class="col-md-8 col-lg-9">
                                <span class="resource-info" 
                                    title="<?php echo t('click_to_view_information');?>" 
                                    alt="<?php echo t('view_more_information');?>" 
                                    id="<?php echo $row['resource_id'];?>">
                                    <i class="fa fa-plus-square-o icon-expand" aria-hidden="true"></i>
                                    <i class="fa fa-minus-square-o icon-collapsed" aria-hidden="true"></i>
                                    <?php echo $row['title'];?> 
                                    <?php //var_dump($row);?>                                
                                </span>
                                </div>

                                <div class="col-md-4 col-lg-3">
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
                                        title="<?php echo html_escape(basename($row['filename']));?>"
                                        data-filename="<?php echo html_escape(basename($row['filename']));?>"
                                        data-dctype="<?php echo html_escape($row['dctype']);?>"
                                        data-isurl="<?php echo (int)$is_url;?>"
                                        data-extension="<?php echo html_escape($ext);?>"
                                        data-sid="<?php echo $row['survey_id'];?>"
                                        class="download btn btn-outline-primary btn-sm btn-block">
                                            <i class="fa fa-arrow-circle-down" aria-hidden="true"></i> 
                                            <?php echo $download_str;?>
                                    </a>

                                    <?php //echo '<a target="_blank" href="'.$url.'" title="'.basename($row['filename']).'" class="download">'.$download_str.'</a>'; ?>

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

                                    //echo $link_text;
                                    ?>
                                
                                <?php endif;?>
                                </div>
                            
                            
                            </div>
                            <?php if ($row['description']!='' || $row['title']!=''  || $row['toc']!='' ):?>
                                <div id="info_<?php echo $row['resource_id'];?>" class="abstract">

                                    <table class="table table-striped grid-table tbl-resource-info" >
                                        <?php foreach ($row as $key=>$value):?>
                                            <?php if ($value!=""):?>
                                                <?php if (array_key_exists($key,$fields_arr)):?>
                                                    <tr valign="top">
                                                        <td  class="caption" ><?php echo $fields_arr[$key];?></td>
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
</div>

<!--survey summary resources-->
<script type="text/javascript">
	function toggle_resource(element_id){
		$("#"+element_id).parent(".resource").toggleClass("active");
		$("#"+element_id).toggle();
	}
	
	$(document).ready(function () { 
		bind_behaviours();
		
		$(".show-datafiles").click(function(){
			$(".data-files .hidden").removeClass("hidden");
			$(".show-datafiles").hide();
			return false;
		});

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
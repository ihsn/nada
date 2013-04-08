<?php if($resources):?>
<div style="padding-top:20px;">
<h1><?php echo t('study_resources');?></h1>
<div class="resources">
    <?php $class="resource"; ?>
	<?php foreach($resources as $key=>$resourcetype):?>
		<?php if (count($resourcetype)>0):?>
        <div>
        <h2>
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
        </h2>
				<?php foreach($resourcetype as $row):?>
                <?php 
						//clean up fields
						$row['country']=strip_brackets($row['country']);
						$row['language']=strip_brackets($row['language']);						
                ?>
                <?php if($class=="resource") {$class="resource alternate";} else{ $class="resource"; } ?>
                <div class="<?php echo $class;?>" style="border-bottom:1px solid gainsboro;">
                    		<h3 class="xsl-subtitle" ><?php echo $row['title'];?></h3>
                    <?php if ($row['description']!='' || $row['title']!=''  || $row['toc']!='' ):?>                    
						<div id="info_<?php echo $row['resource_id'];?>" class="abstract">
                        
                        <?php $fields_arr=array(
									'title'=>		t('title'),
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
									'subjects'=>	t('subjects'),
									'filename'=>	t('filename')
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
                        </table>
                        
                        </div>
                    <?php endif;?>
                
                 </div>
                <?php endforeach;?>
    </div>
    <?php endif;?>
    <?php endforeach;?>
</div>
</div>
<?php endif;?>
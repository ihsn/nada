<?php 
/**
 * 
 * Custom template for the Scripts section
 *
 *  options
 * 
 *  - hide_column_headings - hide column headings 
 */

 $columns=$template['props'];
 $name=$template['title'];
 $hide_field_title=false;
 $hide_column_headings=false;
 $data= array_remove_empty($data);
 if (count($data)<1 ){return false;}

 $readme='';
 if(isset($resources)){
  foreach($resources as $resource){
      $filename = pathinfo($resource['filename'], PATHINFO_FILENAME);
      
      if (strcasecmp($filename,'readme')==0){
        $readme=$resource;
        break;
      }
    }
}  
?>

<div id="<?php echo str_replace(".","_",$template['key']);?>" class="mb-3 field-accordion">  
<div class="field-title"><?php echo t($template['title']);?></div>

<div class="row mb-1">
      <div class="col">
        <div class="text-muted" ></div>
      </div>
      <div class="col-auto">
        <?php if ($readme):?>
              <a href class="get-microdata-btn badge badge-success wb-text-link-uppercase float-left" data-toggle="collapse" data-target="#readmepreview" aria-expanded="false" aria-controls="reamepreview">
                <i class="fab fa-readme"></i> <?php echo t('Readme');?>
              </a>            
        <?php endif;?>

        <?php //GET REPRODUCIBILITY PACKAGE BUTTON ?>
          <?php foreach($resources as $resource):?>
            <?php 
              //find resource of type PRG
              if (stristr($resource['dctype'],'[prg]')==false){
                continue;
              }
            ?>

            <?php if(isset($resource['_links']['type'])):?>
              <?php
                  $link_type_class='fa fa-download';
                  if ($resource['_links']['type']=='link'){
                      $link_type_class='fas fa-external-link-alt';
                  }
              ?>
              <a  
                  href="<?php echo $resource['_links']['download'];?>" 
                  class="get-microdata-btn badge badge-primary wb-text-link-uppercase float-left ml-3" 
                  target="_blank"
                  title="<?php echo t('get_reproducibility_package');?>">                    
                  <span class="<?php echo $link_type_class;?>"></span>
                  <?php echo t('get_reproducibility_package');?>
              </a>
              <?php break; //only show one package ?>
            <?php endif;?>
            <?php endforeach;?>
        <?php //END GET REPRODUCIBILITY PACKAGE BUTTON ?>          

      </div>
    </div>

  <?php if ($readme):?>
    <div class="collapse" id="readmepreview">
      <div>Link: <a target="_blank" href="<?php echo $readme['_links']['download'];?>"><?php echo $readme['_links']['download'];?></a></div>
      <iframe src="https://docs.google.com/gview?url=<?php echo $readme['_links']['download'];?>&embedded=true" class="bg-secondary p-2 mb-3" style="width:100%; height:500px;" frameborder="0"></iframe>
    </div>
  <?php endif;?>

  <?php foreach($data as $idx=>$row):?>
  <div class="card mb-1 scripts-section">
    <div class="card-header-x card-heading bg-light border-bottom p-2" id="heading-<?php echo str_replace(".","_",$template['key'].$idx);?>">
      
        <a href class="collapsed d-block" data-toggle="collapse" data-target="#collapse-<?php echo str_replace(".","_",$template['key'].$idx);?>" aria-expanded="false" aria-controls="collapseOne">          
          <i class="fa mt-1 float-right" aria-hidden="true"></i>
          <?php if (isset($template['display_options']['header_fields'])):?>
            <?php foreach($template['display_options']['header_fields'] as $header_field):?>
              <?php if (isset($row[$header_field]) && trim($row[$header_field])!=''):?>
                <?php echo isset($row[$header_field]) ? $row[$header_field] : '';?>
                <?php break;?>
              <?php endif;?>
            <?php endforeach;?>
          <?php else:?>
            <?php echo $template['title'];?>
          <?php endif;?>          
        </a>
      
    </div>

    <div id="collapse-<?php echo str_replace(".","_",$template['key'].$idx);?>" class="collapse " aria-labelledby="headingOne" xdata-parent="#<?php echo str_replace(".","_",$template['key']);?>">
      <div class="card-body">
          <?php foreach($columns as $column):?>        
            <div>
                <?php if (in_array($column['type'],array('array','nested_array','simple_array','nested_section'))):?>
                    <?php 
                        $column['hide_column_headings']=false;
                        $column['hide_field_title']=false;
                        $column['parent_key']=$template['key'];
                        $display_field=isset($template['display_field']) ? $template['display_field'] : '';
                    ?>                    
                    <?php  echo $this->load->view('display_templates/fields/field_'.$column['type'],
                      array(
                        'data'=>$column['type']=='nested_section' ? $row : array_data_get($row, $column['key']), //isset($row[$column['key']]) ? $row[$column['key']] : [] ,
                        'template'=>$column),true);?>
                <?php elseif ($column['type']=='widget'):?>
                    <?php echo $this->load->view('display_templates/fields/field_'.$column['type'],
                      array(
                        'data'=>$column['type']=='nested_section' ? $row : array_data_get($row, $column['key']), //isset($row[$column['key']]) ? $row[$column['key']] : [] ,
                        'template'=>$column),true);?>

                <?php else:?>
                    
                    <?php if(isset($row[$column['key']])):?>
                    <div class="mb-3">
                      <div class="font-weight-bold field-label"><?php echo $column['title'];?></div>
                      <div><?php echo isset($row[$column['key']]) ? $row[$column['key']] : '';?></div>
                    </div>
                    <?php endif;?>    
                <?php endif;?>
            </div>
            <?php endforeach;?>        
      </div>
    </div>
  </div>
  <?php endforeach;?>
  
</div>

<style>
  .btn-xs{
    font-size:small;
  }

  .scripts-section [data-toggle="collapse"] i:before{
        content: "\f139";
    }
    
  .scripts-section  [data-toggle="collapse"].collapsed i:before{
        content: "\f13a";
    }
</style>
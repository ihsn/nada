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
?>

<div id="<?php echo str_replace(".","_",$template['key']);?>" class="mb-3 field-accordion">  
<div class="field-title"><?php echo t($template['title']);?></div>

  <?php /*
    <div class="row">
      <div class="col">
        <div class="field-title"><?php echo t($template['title']);?></div>
      </div>
      <div class="col">
        <div class="float-right">
          <button class="btn btn-sm btn-xs btn-outline-primary"><i class="fa fa-download" aria-hidden="true"></i> Download package</button>
        </div>
      </div>
    </div>
  */ ?>

<div class="row mb-1">
      <div class="col">
        <div class="text-muted" ></div>
      </div>
      <div class="col-auto">
        <div class="float-right">
          
        </div>


        <?php if(isset($resources) && count($resources)>0):?>
          <?php $resource=$resources[0];?>
          <?php if($resource['_links']['type']):?>
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
          <?php endif;?>
        <?php endif;?>


      </div>
    </div>

  <?php foreach($data as $idx=>$row):?>
  <div class="card mb-1">
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

  [data-toggle="collapse"] i:before{
        content: "\f139";
    }
    
    [data-toggle="collapse"].collapsed i:before{
        content: "\f13a";
    }
</style>
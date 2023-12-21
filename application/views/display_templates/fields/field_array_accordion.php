<?php 
/**
 * 
 * nested repeatd field
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
  <?php foreach($data as $idx=>$row):?>
  <div class="card mb-1">
    <div class="card-header-x card-heading bg-light border-bottom p-2" id="heading-<?php echo str_replace(".","_",$template['key'].$idx);?>">
      
        <a href class="collapsed d-block" data-toggle="collapse" data-target="#collapse-<?php echo str_replace(".","_",$template['key'].$idx);?>" aria-expanded="true" aria-controls="collapseOne">
          <i class="fa float-right mt-1" aria-hidden="true"></i>

          <?php if (isset($template['display_options']['header_fields'])):?>
            <?php foreach($template['display_options']['header_fields'] as $header_field):?>
              <?php if (isset($row[$header_field]) && trim($row[$header_field])!=''):?>
                <?php echo isset($row[$header_field]) ? $row[$header_field] : '';?>
                <?php break;?>
              <?php endif;?>
            <?php endforeach;?>
          <?php else:?>
            <?php echo tt($template['title']);?>
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
                      <div class="font-weight-bold field-label"><?php echo tt($column['title']);?></div>
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
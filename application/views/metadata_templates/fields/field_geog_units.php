<?php if (isset($data) && is_array($data) && count($data)>0 ):?>
<div class="field field-<?php echo $name;?>">
    <div class="xsl-caption field-caption"><?php echo t($name);?></div>
    <div class="field-value">                
        <?php if (isset($data[0]) && is_array($data[0])):?>
        <?php
            if(!isset($columns)){
             $columns=array_keys($data[0]);
            }            
        ?>
        <table class="table table-bordered table-striped table-condensed xsl-table table-grid">
            <tr>
                <?php foreach($columns as $column_name):?>
                    <th><?php echo t($column_name);?></th>
                <?php endforeach;?>
            </tr>
            
            <?php foreach($data as $row):?>
                <tr>
                    <?php foreach($row as $key=>$value):?>
                    <td>
                        <?php if(is_array($value)):?>
                            <?php echo $this->load->view('metadata_templates/fields/field_array', array('name'=>$key,'data'=>$value),true );?>
                        <?php else:?>
                            <?php echo $value;?>
                        <?php endif;?>                        
                    </td>    
                    <?php endforeach;?>
                </tr>
            <?php endforeach;?>
        </table>
        <?php else:?>
        <table class="table xsl-table table-grid">            
               <?php foreach($data as $row):?>
               <tr>
                    <td>                                       
                        <?php echo $row;?>
                    </td>
               </tr>
               <?php endforeach;?>
        </table>
        <?php endif;?>

    </div>
</div>
<?php endif;?>
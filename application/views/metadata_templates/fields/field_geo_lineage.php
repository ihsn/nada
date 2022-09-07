<?php if (isset($data) && is_array($data) && count($data)>0 ):?>
<div class="field field-<?php echo str_replace(".","__",$name);?>">    
    <div class="xsl-caption field-caption"><?php echo t($name);?></div>
    
    <div class="field-value">
    <?php foreach($data as $row):?>
        <div class="mb-3">
        <?php if (isset($row['description'])):?>
            <div class="mb-2"><?php echo nl2br(html_escape($row['description']));?></div>
        <?php endif;?>

        <?php if (isset($row['dateTime'])):?>
            <div class="mb-3"><span class="font-weight-bold"><?php echo t('Date');?>:</span> <?php echo $row['dateTime'];?></div>    
        <?php endif;?>

        <?php if (isset($row['processor'])):?>
        <div>
            <?php echo render_field($field_type='geog_contact',$field_name=$name.'.processor',$row['processor'], array('hide_column_headings'=>false));?>            
        </div>
        <?php endif;?>

        <?php if(isset($row['source'])):?>
            <div class="xsl-caption field-caption"><?php echo t('Sources');?></div>

            <table class="table table-striped  table-sm">
                <tr>
                    <th><?php echo t('Description');?></th>
                    <th><?php echo t('Citation source');?></th>
                    <th><?php echo t('Organization');?></th>
                </td>
            <?php foreach($row['source'] as $source):?>
                <tr>
                    <td><?php echo $source['description'];?></td>
                    <td><?php echo get_field_value('sourceCitation.title',$source);?></td>
                    <td>                
                    <?php echo render_field($field_type='geog_contact',$field_name=$name.'.source',get_field_value('sourceCitation.citedResponsibleParty',$source),
                        array(
                            'hide_column_headings'=>false,
                            'hide_field_title'=>true
                        ));?>
                    </td>
                </tr>
            <?php endforeach;?>
            </table>
        <?php endif;?>

        </div>

    <?php endforeach;?>
    </div>

</div>
<?php endif;?>

<?php
/**
 * 
 *  File download links table
 * 
 * @options 
 *  - url_column - name of the array column for download links
 *  - title_column - name of array column for title
 *  - note_column - (optional) - name of the column for notes 
 *  
 * @data - associated array
 * 
 */
?>
<?php if (isset($data) && is_array($data) && count($data)>0 ):?>

<?php /*?>
<pre>
<?php var_dump($options);?>
<?php var_dump($data);?>
</pre>
<?php //return;?>
<?php */ ?>

<div class="table-responsive field field-<?php echo $name;?>">
    <div class="xsl-caption field-caption"><?php echo t($name);?></div>    
    <div class="field-value ">    
        <?php foreach($data as $row):?>
            <div class="d-flex flex-row">
                <div class="d-flex justify-content-start"><?php echo $row[$options['title_column']];?></div>
                <div class="d-flex justify-content-end"><a class="btn btn-primary btn-outline-primary btn-sm" href="<?php echo $row[$options['url_column']];?>">Download</a></div>
            </div>    
        <?php endforeach;?>
    
    </div>
</div>
<?php endif;?>

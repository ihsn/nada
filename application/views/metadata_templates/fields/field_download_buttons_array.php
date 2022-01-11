<?php
/**
 * 
 *  File download links buttons
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

<?php 
     $max_links=5; //max number of download buttons to show
?>

<?php /*?>
<pre>
<?php var_dump($options);?>
<?php var_dump($data);?>
</pre>
<?php //return;?>
<?php */ ?>

<div class="table-responsive field field-<?php echo $name;?>">    
    <div class="field-value ">    
        <?php $k=0;foreach($data as $row):$k++;?>
            <?php if ($k>$max_links){continue;}  //?>
            <?php $ext=get_file_extension($row[$options['url_column']]);?>
            <?php $file_info=get_file_extension_info($ext);?>
            <span class="">
                <a class="btn btn-primary btn-outline-primary btn-sm" target="_blank"
                    href="<?php echo $row[$options['url_column']];?>">
                    <i class="fa fa-download" aria-hidden="true"> </i>
                    <?php if($this->form_validation->valid_url($row[$options['url_column']])):?>
                        <?php echo t($file_info['link_type']);?> <?php echo t('File');?>
                    <?php else:?>
                        <?php echo t($file_info['link_type']);?> <?php echo strtoupper($file_info['ext']);?>
                    <?php endif;?>
                </a>
            </span>
        <?php endforeach;?>
    </div>
</div>
<?php endif;?>

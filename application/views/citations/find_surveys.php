<?php if ($surveys): ?>
    <div class="items-found">Found: <?php echo count($surveys);?></div>


    <?php foreach ($surveys as $survey):?>
        <label for="s-<?php echo $survey['id'];?>">
            <div class="survey-row" >

                <div class="col1">
                    <input class="chk" type="checkbox" name="sid[]" value="<?php echo $survey['id'];?>" id="s-<?php echo $survey['id'];?>" />
                </div>
                <div class="col2">
                    <div class="survey-title"><?php echo $survey['title'];?></div>
            <span>
            	<span class="country"><?php echo $survey['nation'];?></span>, 
            	<span class="year"><?php $years=array_unique(array($survey['year_start'],$survey['year_end']));
                    echo implode(" - ",$years);  ?>
            	</span>
                <a target="_blank" title="<?php echo t('Display survey information');?>" href="<?php echo site_url('catalog/'.$survey['id']);?>"><i class="icon-globe"></i></a>
            </span>
                </div>

            </div>
        </label>
    <?php endforeach; ?>
    <?php return;?>


    <table class="grid-table custom-short-font" cellpadding="0" cellspacing="0" id="related-surveys-table" >
        <tbody>
        <?php foreach ($surveys as $survey):?>
            <tr align="left" class="survey-row" valign="top">
                <td><input class="chk" type="checkbox" name="sid[]" value="<?php echo $survey['id'];?>" id="s-<?php echo $survey['id'];?>" /></td>
                <td>
                    <label for="s-<?php echo $survey['id'];?>"?>
                        <div class="survey-title"><?php echo $survey['title'];?></div>
            <span>
            	<span class="country"><?php echo $survey['nation'];?></span>, 
            	<span class="year"><?php $years=array_unique(array($survey['year_start'],$survey['year_end']));
                    echo implode(" - ",$years);  ?>
            	</span>
            </span>
                    </label>

                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

<?php else:?>
    No records found.
<?php endif;?>
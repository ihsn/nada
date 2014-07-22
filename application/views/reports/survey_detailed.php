<table style="width:100%;">
<tr>
	<td><h1><?php echo t('most_viewed_studies_detailed');?></h1></td>
    <td style="text-align:right;"><?php $this->load->view('reports/download_options'); ?></td>
</tr>
</table>
<?php if ($rows):?>
    <table class="report-table" style="width:100%;">
	<?php 
		$prev_survey=''; 
		$section_total=0;
		$k=0;
	?>
    <?php foreach($rows as $parent_row):?>
		<?php foreach($parent_row as $row): $k++;?>
            <?php if ($prev_survey!=$row['id']):?>
                <?php if ($k>1):?>
                <tr class="sub-section">
                    <td><?php echo t('total_hits');?></td>
                    <td><?php echo $section_total; $section_total=0;?></td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>                
                <?php endif;?>
                <tr class="section-title">
                    <td><?php echo $row['country'];?> - <?php echo $row['title'];?>, <?php echo $row['year'];?></td>
                    <td><?php echo t('hits');?></td>
                </tr>
                <?php $prev_survey=$row['id'];?>
            <?php endif;?>
    
            <?php 
                //increment totals
                $section_total+=$row['visits'];
            ?>        
            <tr>
                <td><?php echo (strlen($row['section'])<2) ? t('sec_overview') : t('sec_'.strtolower($row['section']));?></td>
                <td><?php echo $row['visits'];?></td>
            </tr>
        <?php endforeach;?>
    <?php endforeach;?>
        <tr class="sub-section">
            <td>Total hits</td>
            <td><?php echo $section_total; $section_total=0;?></td>
        </tr>    
    </table>
<?php else:?>    
No results found
<?php endif;?>
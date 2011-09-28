<?php if ($rows):?>
	
    <table class="report-table">
    	<tr>
        	<th>Keyword</th>
            <th>Hits</th>
        </tr>
	<?php foreach($rows as $row):?>
    	<tr>
        	<td><?php echo $this->security->xss_clean($row['keyword']);?></td>
            <td><?php echo $row['visits'];?></td>
        </tr>
    <?php endforeach;?>    
    </table>
<?php else:?>    
No results found
<?php endif;?>
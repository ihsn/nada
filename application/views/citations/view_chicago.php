<?php
/* Format a single citation to Chicago style */
?>
<?php if ($ctype=='book' || $ctype=='website' ):?>

	<?php if (is_array($authors)):?>
		<?php echo format_author($authors);?>
	<?php endif;?>	
	
    <?php if (isset($title)):?>
	    <em><?php echo anchor('citations/'.$id,$title); ?>. </em>
    <?php endif;?>
    
    <?php if ($place_publication!=''):?>
	    <?php echo format_place($place_publication, $place_state,$publisher, $pub_year); ?>
    <?php endif;?>
    
<?php elseif ($ctype=='journal'):?>
	
	<?php if (is_array($authors)):?>
		<?php echo format_author($authors);?>
	<?php endif;?>	
	
    <?php if (isset($title)):?>
	    <em><?php echo anchor('citations/'.$id,$title); ?>. </em>
    <?php endif;?>
    
    <?php if ($place_publication!=''):?>
	    <?php echo format_place($place_publication, $place_state,$publisher, $pub_year); ?>
    <?php endif;?>
    
    <?php echo format_date($pub_day,$pub_month, $pub_year);?>

<?php else: ?>
	
	<?php if (is_array($authors)):?>
		<?php echo format_author($authors);?>
	<?php endif;?>	
	
    <?php if (isset($title)):?>
	    <em><?php echo anchor('citations/'.$id,$title); ?>. </em>
    <?php endif;?>
    
    <?php if ($place_publication!=''):?>
	    <?php echo format_place($place_publication, $place_state,$publisher,""); ?>
    <?php endif;?>
    <?php echo format_date($pub_day,$pub_month, $pub_year);?>
<?php endif;?>

<?php if($ie_program):?>
	 <tr>
		<td style="width:120px;">Thematic Area(s)</td>
		<td><?php echo trim($ie_program); ?></td>
	 </tr>	
<?php endif;?>

<?php if($ie_project_name):?>
<tr>
	<td>Impact Evaluation</td>
	<td>
		<?php echo $ie_project_name; ?>
		<?php if($ie_project_id):?>
		<?php 
		if($ie_project_uri)
		{
			echo '('.anchor(prep_url(trim($ie_project_uri)), $ie_project_id,array('target'=>'_blank', 'title'=>prep_url(trim($ie_project_uri)))).')';
		}
		else
		{
			echo '('.$ie_project_id.')';
		} 
		?>
		<?php endif;?>
	</td>
</tr>	
<?php endif;?>    

<?php $leaders=parse_ie_team_leaders($ie_team_leaders); if($leaders):?>
<tr>
	<td>Lead Evaluator(s)</td>
	<td><?php echo $leaders; ?></td>
</tr>	
<?php endif;?>

<?php if($project_name):?>
<tr>
	<td>Related Operation</td>
	<td>
	<?php echo $project_name; ?>
	<?php if($project_id):?>
		<?php 
		if($project_uri)
		{
			echo '('.anchor(prep_url(trim($project_uri)), $project_id, array('target'=>'_blank','title'=>prep_url(trim($project_uri)))).')';
		}
		else
		{
			echo '('.$project_id.')';
		} 
		?>
	<?php endif;?>
	</td>
</tr>	
<?php endif;?>

    
<?php 
function parse_ie_team_leaders($str)
{
	if ($str=='' or $str==NULL)
	{
		return FALSE;
	}
	
	$leaders=unserialize($str);
	if (!is_array($leaders))
	{
		return FALSE;
	}
	
	$output=array();	
	foreach($leaders['leader'] as $leader)
	{
		if (isset($leader['URI']))
		{
			$output[]= anchor($leader['URI'], $leader['name'], array('target'=>'_blank'));	
		}
		else
		{
			$output[]=$leader['name'];
		}	
	}
	
	return implode(", ", $output);
}
?>
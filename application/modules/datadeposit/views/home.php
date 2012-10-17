<script type="text/javascript">
    $(function() {
        /* Delete Operation */
     /*  $('a.delete').attr('href', undefined);
       $('a.delete').click(function() {
            event.preventDefault();
            var id = $(this).attr('id');
        var response=confirm("<?php echo t("js_confirm_delete"); ?>");
       if (response) {
            url = "<?php echo site_url(); ?>/datadeposit/delete/" + id;
            $.get(url);
            $("#" + id).slideUp(1000, function() {
                $(this).remove();
                }
 			);
        }
    }); */     

    });
</script>
<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>
<h1 class="page-title"><?php echo t('My Projects'); ?></h1>
<div style="font-size:11pt;margin-bottom:6px;text-align:left">
    <a href="<?php echo site_url(); ?>/datadeposit/create" ><?php echo t('New Project');?></a>
</div>
<?php if (isset($projects[0]->id)): ?>

<table class="grid-table" style="width:50%" cellspacing="0" cellpadding="0">

		<tr valign="top" align="left" style="height:5px" class="header">


						<th style="text-align:left;width:200px" >

				                <a href="<?php echo site_url('datadeposit/projects'); ?>?sort_by=title&sort_order=<?php if (isset($_GET['sort_order'])) echo ($_GET['sort_order']=='asc') ? 'desc' : 'asc';?>">Title</a>			</th>



						<th style="text-align:left">

				                <a href="<?php echo site_url('datadeposit/projects'); ?>?sort_by=status&sort_order=<?php if (isset($_GET['sort_order'])) echo ($_GET['sort_order']=='asc') ? 'desc' : 'asc';?>">Status</a>			</th>

			            <th style="text-align:left">Actions</th>

		</tr>

		<tbody>
			<?php foreach($projects as $project): ?>
			<tr id="<?php echo $project->id; ?>">
				<?php foreach($fields as $field_name => $field_display): ?>
                <?php if ($field_name == 'created_on') {
					$change = &$project->$field_name;
					$change = explode(' ', $change);
					$change = $change[0]; 
				}?>
				<?php if(strcmp($field_name,'title')===0):?>
					<?php if($project->status != 'Pending'):?>
                    <td>
                    <a href="<?php echo site_url('datadeposit');?>/study/<?php echo $project->id;?>"><?php echo isset($_POST['title'])? $_POST['title']:$project->title; ?></a>
                    </td>
                    <?php else: ?>
                    <td><?php echo $project->title; ?></td>
                    <?php endif; ?>
                <?php elseif(strcmp($field_name,'access')===0):?>
                	 <td><?php echo str_replace(",","<br/>",$project->access); ?></td>
                <?php elseif(strcmp($field_name,'status')===0):?>
                	   <?php if($project->status != 'Draft'):?>
                       <td><?php echo isset($project->status)?$project->status:'&nbsp;';?></td>
                       <?php else: ?>
                       <td><?php echo isset($project->status)?$project->status:'&nbsp;';?></td>
                       <?php endif; ?>
                <?php else: ?>
				<td>
					<?php echo ($project->$field_name != '')?$project->$field_name:'N/A'; ?>
				</td>
                <?php endif;?>
				<?php endforeach; ?>
                
                <?php if($project->status != 'Pending'):?>
                <td nowrap="nowrap">
                <a href="<?php echo site_url('datadeposit');?>/update/<?php echo $project->id;?>"><?php echo t('edit');?></a> | 
            	<a href="<?php echo site_url('datadeposit');?>/confirm/<?php echo $project->id;?>" id="<?php echo $project->id;?>" class="delete"><?php echo t('delete');?></a>
            	</td>
                <?php else: ?>
                <td nowrap="nowrap">
                <?php echo t('edit');?> | 
            	<?php echo t('delete');?>
            	</td>
                <?php endif; ?>
			</tr>
            <!-- This is when user is new , with no projects in his list -->
            <?php if(sizeof($projects)===0):?>
            <tr><td></td><td></td><td></td><td></td></tr>
            <?php endif;?>
			<?php endforeach; ?>			
		</tbody>
</table>
<?php endif; ?>
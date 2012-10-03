<h1 style="margin-bottom:30px" class="page-title"><?php echo t('datadeposit'); ?></h1>
<table class="grid-table" width="100%" cellspacing="0" cellpadding="0">

		<thead class="header">

						<th >

				                <a href="<?php echo site_url('admin/datadeposit/');?>?sort_by=title&sort_order=<?php if (isset($_GET['sort_order'])) echo ($_GET['sort_order']=='asc') ? 'desc' : 'asc';?>">Title</a>			</th>

						<th >

				                <a href="<?php echo site_url('admin/datadeposit/');?>?sort_by=shortname&sort_order=<?php if (isset($_GET['sort_order'])) echo ($_GET['sort_order']=='asc') ? 'desc' : 'asc';?>">Shortname</a>			</th>

						<th >

				                <a class="selected" href="<?php site_url('admin/datadeposit/');?>?sort_by=created_on&sort_order=<?php if (isset($_GET['sort_order'])) echo ($_GET['sort_order']=='asc') ? 'desc' : 'asc';?>">Created on <img src="images/arrow-asc.png" alt="ASC" border="0"/></a>			</th>

						<th >

				                <a href="<?php echo site_url('admin/datadeposit/');?>?sort_by=created_by&sort_order=<?php if (isset($_GET['sort_order'])) echo ($_GET['sort_order']=='asc') ? 'desc' : 'asc';?>">Created by</a>			</th>

						<th >

				                <a href="<?php echo site_url('admin/datadeposit/');?>?sort_by=collaborators&sort_order=<?php if (isset($_GET['sort_order'])) echo ($_GET['sort_order']=='asc') ? 'desc' : 'asc';?>">Collaborators</a>			</th>

						<th >

				                <a href="<?php echo site_url('admin/datadeposit/');?>sort_by=status&sort_order=<?php if (isset($_GET['sort_order'])) echo ($_GET['sort_order']=='asc') ? 'desc' : 'asc';?>">Status</a>			</th>

		</thead>

		<tbody>
			<?php foreach($projects as $project): ?>
			<tr id="<?php echo $project->id; ?>">
				<?php foreach($fields as $field_name => $field_display): ?>
                <?php if(strcmp($field_name,'title')===0):?>
					<?php if($project->status != 'Pending'):?>
                    <td>
                    <a href="<?php echo site_url('admin/datadeposit/id/');?><?php echo '/', $project->id;?>"><?php echo isset($_POST['title'])? $_POST['title']:$project->title; ?></a>
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
                
                <?php /*if($project->status != 'this_wont_work'):
                <td nowrap="nowrap">
                <a href="<?php echo site_url('admin/datadeposit/id/');?><?php echo $project->id;?>"><?php echo t('edit');?></a> | 
            	<a href="<?php echo site_url('admin/datadeposit/id/');?><?php echo $project->id;?>" id="<?php echo $project->id;?>" class="delete"><?php echo t('delete');?></a>
            	</td>
                <?php else: ?>
                <td nowrap="nowrap">
                <?php echo t('edit');?> | 
            	<?php echo t('delete');?>
            	</td>
                <?php endif; ?> */ ?>
			</tr>
            <!-- This is when user is new , with no projects in his list -->
            <?php if(sizeof($projects)===0):?>
            <tr><td></td><td></td><td></td><td></td></tr>
            <?php endif;?>
			<?php endforeach; ?>			
		</tbody>
</table>

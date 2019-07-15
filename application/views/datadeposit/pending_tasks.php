<div class="dd-sidebar-box dd-sidebar-pending-tasks" id="dd_project_pending_tasks">
    <div class="box-header">
        <h2>Pending Tasks</h2>
    </div>
    
    <div class="box-body">
    
    	<div class="task">
        	<h3>Study Description</h3>
            <?php if ($incomplete_study_fields>0):?>            
            	<div class="task-pending"><?php printf('Fill %s mandatory field(s)',$incomplete_study_fields);?></div>
            <?php else:?>
            	<div class="task-completed"><?php echo t('no_pending_tasks');?></div>
            <?php endif;?>
        </div>

    	<div class="task">
        	<h3>Upload Files</h3>
	        <?php if ($attached_files>0):?>            
            	<div class="task-completed"><?php printf('%s files attached',$attached_files);?></div>
            <?php else:?>
            	<div class="task-pending"><?php echo t('no_files_uploaded');?></div>
            <?php endif;?>
        </div>

    	<div class="task">
        	<h3>Citations</h3>
            <?php if ($attached_citations>0):?>
            	<div class="task-completed"><?php printf('%s citation(s) attached',$attached_citations);?></div>
            <?php else:?>
            	<div class="task-pending"><?php echo t('no_citations_attached');?></div>
            <?php endif;?>
        </div>
        
   </div>
</div>
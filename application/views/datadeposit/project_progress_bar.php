<?php
	if (!isset($this->active_project[0])){
		return;
	}
?>
<div id="steps">
<div class="dd-progress-bar2" id="dd-progress-bar2" >

    <?php
        $step_proj_css=($this->uri->segment(2)=='update') ? 'active' : '';
        $step_proj_css.=' '. $pending_tasks_arr['incomplete_study_fields']==0 ? ' completed' : '';

        $step_study_css=($this->uri->segment(2)=='study') ? 'active' : '';
        $step_study_css.=' '. $pending_tasks_arr['incomplete_study_fields']==0 ? ' completed' : '';

        $step_files_css=($this->uri->segment(2)=='datafiles') ? 'active' : '';
        $step_files_css.=' '. $pending_tasks_arr['attached_files']>0 ? ' completed' : '';

        $step_citations_css=(in_array($this->uri->segment(2),array('citations','add_citations','edit_citations'))) ? 'active' : '';
        $step_citations_css.=' '. $pending_tasks_arr['attached_citations']>0 ? ' completed' : '';

    ?>
        <div class="step  <?php echo $step_proj_css;?>" >
            <div class="step-number">1</div>
            <div class="step-title">
                <a href="<?php echo site_url('/datadeposit/update/'.$this->active_project[0]->id);?>"><?php echo t('project_info'); ?></a>
            </div>
            <div class="step-message">(You are here)</div>
        </div>

        <div class="step-sep"></div>
        
        <div class="step <?php echo $step_study_css;?>" >
            <div class="step-number">2</div>
		<?php if ($pending_tasks_arr['incomplete_study_fields']==0):?>		
			<div class="task-completed completed"></div>
		<?php endif;?>
            <div class="step-title">
            <a href="<?php echo site_url();?>/datadeposit/study/<?php echo $this->active_project[0]->id; ?>"><?php echo t('study_desc'); ?></a>
            </div>
            <div class="step-message">(You are here)</div>
        </div>
        
        <div class="step-sep"></div>
        
        <div class="step <?php echo $step_files_css;?>" >
            <div class="step-number">3</div>
		<?php if ($pending_tasks_arr['attached_files']>0):?>		
			<div class="task-completed completed"></div>
		<?php endif;?>
            <div class="step-title">
            <a href="<?php echo site_url();?>/datadeposit/datafiles/<?php echo $this->active_project[0]->id; ?>"><?php echo t('datafiles'); ?></a>
            </div>
            <div class="step-message">(You are here)</div>
        </div>
        
        <div class="step-sep"></div>
        
        <div class="step <?php echo $step_citations_css;?>" >
            <div class="step-number">4</div>
            <?php if ($pending_tasks_arr['attached_citations']>0):?>
			<div class="task-completed completed"></div>
		<?php endif;?>
            <div class="step-title">
                <a href="<?php echo site_url();?>/datadeposit/citations/<?php  echo $this->active_project[0]->id; ?>"><?php echo t('citation_option'); ?></a>
            </div>
            <div class="step-message">(You are here)</div>
        </div>
        
        <div class="step-sep"></div>
        
        <div class="step <?php if ($this->uri->segment(2)=='submit_review'){echo 'active';}?>" >
            <div class="step-number">5</div>
            <div class="step-title">
            <a href="<?php echo site_url();?>/datadeposit/submit_review/<?php echo $this->active_project[0]->id; ?>">
			<?php //echo ($this->active_project[0]->access == 'owner' || $this->uri->segment(2) == 'create') ? t('metadata_review2') : t('review'); ?> Review and Submit</a>
            </div>
            <div class="step-message">(You are here)</div>
        </div>
                
</div>
</div>
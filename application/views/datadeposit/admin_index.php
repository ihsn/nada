<?php

$filter=(string)$this->input->get("filter",true);

$status_codes=array(
    'all' =>'View all',
    'draft'=>'Draft',
    'submitted'=>'Submitted',
    'processed'=>'Processed',
    'accepted'=>'Accepted',
    'closed'=>'Closed'
);

$task_codes=array(
    '0'=>'Work in progress',
    '1'=>'Completed'
);

if (!array_key_exists($filter,$status_codes))
{
    $filter='all';
}

?>

<style>
.page-datadeposit-index .dd-status-badge {
    display: inline-block;
    min-width: 5.5rem;
    padding: 0.3rem 0.65rem;
    border-radius: 50rem;
    font-size: 0.72rem;
    font-weight: 600;
    line-height: 1.2;
    text-align: center;
    text-transform: capitalize;
    letter-spacing: 0.02em;
    white-space: nowrap;
    border: 1px solid transparent;
}
.page-datadeposit-index .dd-status--draft {
    background: #e9ecef;
    color: #495057;
    border-color: #ced4da;
}
.page-datadeposit-index .dd-status--submitted {
    background: #cfe2ff;
    color: #084298;
    border-color: #9ec5fe;
}
.page-datadeposit-index .dd-status--processed {
    background: #fff3cd;
    color: #664d03;
    border-color: #ffecb5;
}
.page-datadeposit-index .dd-status--accepted {
    background: #d1e7dd;
    color: #0f5132;
    border-color: #a3cfbb;
}
.page-datadeposit-index .dd-status--closed {
    background: #e2e3e5;
    color: #41464b;
    border-color: #c4c8cb;
}
.page-datadeposit-index .dd-status--default {
    background: #f8f9fa;
    color: #6c757d;
    border-color: #dee2e6;
}

.page-datadeposit-index .dd-task-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.75rem;
    height: 1.75rem;
    border-radius: 50%;
    font-size: 0.7rem;
    font-weight: 700;
    line-height: 1;
    text-decoration: none;
}
.page-datadeposit-index .dd-task-badge--0 {
    background: #fff3cd;
    color: #664d03;
    border: 1px solid #ffecb5;
}
.page-datadeposit-index .dd-task-badge--1 {
    background: #d1e7dd;
    color: #0f5132;
    border: 1px solid #a3cfbb;
}

.page-datadeposit-index .grid-table td { vertical-align: middle; }
.page-datadeposit-index .grid-table .shortname { font-size: smaller; color: gray; }

.page-datadeposit-index .task-team-container .person { border-bottom: 1px solid #dcdcdc; padding: 5px; }
.page-datadeposit-index .task-team-container .person .input-radio { display: none; }
.page-datadeposit-index .task-team-container .person { position: relative; }
.page-datadeposit-index .task-team-container .person .btn-assign { position: absolute; right: 10px; top: 15px; }
.page-datadeposit-index .task-team-container .person:hover { background: #dcdcdc; }

.page-datadeposit-index .datadeposit-tabs a:link,
.page-datadeposit-index .datadeposit-tabs a:visited {
    color: #007bff;
}
</style>

<?php
$status_styles = array(
    'draft'     => 'dd-status--draft',
    'submitted' => 'dd-status--submitted',
    'processed' => 'dd-status--processed',
    'accepted'  => 'dd-status--accepted',
    'closed'    => 'dd-status--closed',
);
?>

<div class="container-fluid page-datadeposit-index">

<h1 class="page-title"><?php echo t('Data Deposit Projects');?></h1>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>


<form class="left-pad" style="margin-bottom:30px;" method="GET" id="user-search" >
    <input type="text" size="40" name="keywords" id="keywords" value="<?php echo form_prep($this->input->get("keywords",true));?>">
    <input type="hidden" name="filter" value="<?php echo form_prep($filter);?>"/>
    <input type="submit" value="Search" name="search">
    <?php if ($this->input->get("keywords")):?>
        <a href="<?php echo site_url('admin/datadeposit');?>">Reset</a>
    <?php endif;?>
</form>


<ul class="nav nav-tabs datadeposit-tabs">
<?php foreach($status_codes as $code=>$status):?>
    <li class="nav-item">
        <a <?php echo ($code==$filter) ? 'class="nav-link active"' : 'class="nav-link"';?> href="<?php echo site_url('admin/datadeposit?filter='.$code);?>" ><?php echo $status;?></a>
    </li>
<?php endforeach;?>
</ul>

<?php		
		$sort_by=$this->input->get("sort_by");
		$sort_order=$this->input->get("sort_order");			
?>

<?php if (count($projects)==0):?>
    <div class="m-2">No projects were found.</div>
    <?php return;?>
<?php endif;?>

<div style="font-weight:bold;" class="m-2">
    Total projects found: <span><?php echo count($projects);?></span>
</div>

<table class="grid-table table table-sm table-striped" width="100%" cellspacing="0" cellpadding="0">
  <thead class="header">
  	<th> <?php echo create_sort_link($sort_by,$sort_order,'status',t('Status'),current_url(),array('filter')); ?>  </th>
    <th> <?php echo create_sort_link($sort_by,$sort_order,'title',t('title'),current_url(),array('filter')); ?> </th>
    <!--<th> <?php echo create_sort_link($sort_by,$sort_order,'shortname',t('Short name'),current_url(),array('filter')); ?>  </th>-->
    <th> <?php echo create_sort_link($sort_by,$sort_order,'last_modified',t('Changed'),current_url(),array('filter')); ?>  </th>
    <th nowrap="nowrap"> <?php echo create_sort_link($sort_by,$sort_order,'created_on',t('Created'),current_url(),array('filter')); ?>  </th>
    <th nowrap="nowrap"> <?php echo create_sort_link($sort_by,$sort_order,'created_by',t('Creator'),current_url(),array('filter')); ?>  </th>
    <th></th>
    <th nowrap="nowrap"></th>
    </thead>
  <tbody>
    <?php foreach($projects as $project): ?>
    <?php
        $status_key = strtolower((string) $project->status);
        $status_class = isset($status_styles[$status_key]) ? $status_styles[$status_key] : 'dd-status--default';
        $status_label = isset($status_codes[$status_key]) ? $status_codes[$status_key] : ucfirst($status_key);
    ?>
    <tr>
    	<td>
            <span class="dd-status-badge <?php echo $status_class; ?>"><?php echo html_escape($status_label); ?></span>
        </td>
        <td>
            <div><a href="<?php echo site_url('admin/datadeposit/id/'.$project->id);?>"><?php echo $project->title;?></a></div>
            <div class="shortname">
                <?php echo $project->shortname;?>
            </div>
        </td>
        <td nowrap="nowrap"><?php echo date("m-d-Y",$project->last_modified);?></td>
        <td nowrap="nowrap"><?php echo date("m-d-Y",$project->created_on);?></td>
        <td><?php echo $project->created_by;?></td>
        <td><?php if(isset($project->task_user)):?>
                <a href="<?php echo site_url('admin/datadeposit/tasks/info/'.$project->task_id);?>">
                    <span class="dd-task-badge dd-task-badge--<?php echo (int) $project->task_status; ?>" title="<?php echo html_escape(@$task_codes[$project->task_status]. ' - '. $project->task_user); ?>">
                        <?php
                            $user=$project->task_user;
                            $name_parts=explode(" ",$user);
                            foreach($name_parts as $part)
                            {
                                echo strtoupper(substr($part,0,1));
                            }
                        ?>
                    </span>
                </a>
            <?php endif;?>
        </td>
        <td nowrap="nowrap">
            <?php if (!empty($can_edit)): ?>
            <a class="assign" href="<?php echo site_url('admin/datadeposit/assign/'.$project->id);?>" data-id="<?php echo $project->id;?>">Assign</a> |
            <a href="<?php echo site_url('admin/datadeposit/id/'.$project->id);?>">Edit</a>
            <?php else: ?>
            <a href="<?php echo site_url('admin/datadeposit/id/'.$project->id);?>">View</a>
            <?php endif; ?>
            <?php if (!empty($can_delete)): ?>
             | <a href="<?php echo site_url('admin/datadeposit/delete/'.$project->id);?>">Delete</a>
            <?php endif; ?>
            </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
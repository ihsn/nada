<style>
    .table-form td{padding:5px;vertical-align: top;}
    .label-0{background-color:orange}
    .label-1{background-color:#00CC00}
</style>

<?php
$task_codes=array(
    '0'=>'Work in progress',
    '1'=>'Completed'
);

?>

<div class="content-container">

    <h1 class="page-title"><?php echo t('My Tasks'); ?></h1>



    <table class="grid-table" width="100%" cellspacing="0" cellpadding="0">

        <tr class="header">
            <th>Title</th>
            <th>Task status</th>
            <th>Assigned to</th>
            <th>Assigned by</th>
            <th>Assigned on</th>
            <th>Completed on</th>
        </tr>
        <?php foreach($tasks as $task):$task=(object)$task;?>

        <tr>
            <td><a href="<?php echo site_url('admin/datadeposit/id/'.$task->project_id);?>"><?php echo $task->project_title;?></a></td>
            <td><a href="<?php echo site_url('admin/datadeposit/tasks/info/'.$task->id);?>"><span class="label label-<?php echo $task->status;?>"><?php echo $task_codes[$task->status];?></span><a/></td>
            <td><?php echo $task->task_user;?></td>
            <td><?php echo $task->assigner;?></td>
            <td><?php echo date("M-d-Y",$task->date_assigned);?></td>
            <td><?php echo ($task->date_completed !=NULL) ? date("M-d-Y",$task->date_completed) : '-';?></td>
        </tr>

        <?php endforeach;?>

    </table>

    <br/>

    <h1>Tasks assigned to others</h1>
    <table class="grid-table" width="100%" cellspacing="0" cellpadding="0">

        <tr class="header">
            <th>Title</th>
            <th>Task status</th>
            <th>Assigned to</th>
            <th>Assigned by</th>
            <th>Assigned on</th>
            <th>Completed on</th>
        </tr>
        <?php foreach($assigner_tasks as $task):$task=(object)$task;?>

            <tr>
                <td><a href="<?php echo site_url('admin/datadeposit/id/'.$task->project_id);?>"><?php echo $task->project_title;?></a></td>
                <td><a href="<?php echo site_url('admin/datadeposit/tasks/info/'.$task->id);?>"><span class="label label-<?php echo $task->status;?>"><?php echo $task_codes[$task->status];?></span><a/></td>
                <td><?php echo $task->task_user;?></td>
                <td><?php echo $task->assigner;?></td>
                <td><?php echo date("M-d-Y",$task->date_assigned);?></td>
                <td><?php echo ($task->date_completed !=NULL) ? date("M-d-Y",$task->date_completed) : '-';?></td>
            </tr>

        <?php endforeach;?>

    </table>

</div>

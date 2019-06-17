<?php
$task_codes=array(
    '0'=>'Work in progress',
    '1'=>'Completed'
);

?>

<p><b>[<?php echo $task_user->username;?>]</b> has created a new task</p>
<hr/>
<table class="table-form">

    <tr>
        <td><label>Project:</label></td>
        <td>
            <a href="<?php echo $project->id;?>"><?php echo $project->title;?></a>
        </td>
    </tr>

    <tr>
        <td><label>Status:</label></td>
        <td>
            <span class="label label-<?php echo $task['status'];?>"><?php echo $task_codes[$task['status']];?></span>
        </td>
    </tr>

    <tr>
        <td><label>Assigned to:</label></td>
        <td><?php echo $assignee->username;?></td>
    </tr>

    <tr>
        <td><label>Assigned by:</label></td>
        <td><?php echo $task_user->username;?></td>
    </tr>

</table>

<a href="<?php echo site_url('admin/datadeposit/tasks/my_tasks');?>">Click here to view your tasks</a>
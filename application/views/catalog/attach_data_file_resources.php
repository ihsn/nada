<nav class="navbar navbar-default" style="background:#337ab7;">
  <div class="container-fluid">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="navbar-header" >
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <div class="navbar-brand">
				<a style="color:white;" href="<?php echo site_url('admin/catalog/edit/'.$survey['id'].'/related-data');?>">
					<span class="glyphicon glyphicon-arrow-left" aria-hidden="true"></span> <?php echo $survey['title'];?></a>
			 </div>
    </div>

		<div class="navbar-right" style="margin-right:10px;">
		<a type="button" class="btn btn-info navbar-btn" href="<?php echo site_url('admin/catalog/edit/'.$survey['id'].'/data-files');?>">
			<span class="glyphicon glyphicon-arrow-left" aria-hidden="true"></span> Return to edit page</a>
	</div>

</div>
</nav>

<div class="body-container container-fluid">

	<h2>
        <span><?php echo t('Link external resources for the data file');?></span>     
        <span style="font-size:20px;" class="badge badge-primary"><?php echo $file['file_name'];?></span>
    </h2>

    <table class="table table-bordered">
        <tr>
            <td>Data file</td>
            <td><?php echo $file['file_name'];?></td>
        </tr>

        <tr>
            <td>Description</td>
            <td><?php echo $file['description'];?></td>
        </tr>
    </table>

<form method="post" action="<?php echo site_url('admin/catalog/attach_data_file_resources_post/'.$sid.'/'.$file_id);?>" >
    <h5>Select all the external resources that are used by this data file</h5>    
    <table class="table table-striped table-bordered">
        <tr>
            <th>Select/Deselect</th>
            <th>External resource</th>
            <th>File format</th>            
        </tr>
        <?php $k=0;foreach($resources as $resource):$k++;?>
        <?php 
            $resource_matched=array_key_exists($resource['resource_id'],$attached_resources);

            //matched file format
            $format_matched=false;

            if($resource_matched){
                $format_matched=$attached_resources[$resource['resource_id']]['file_format'];
            }
        ?>
        <tr>
            <td style="width:150px;">
               <!-- <input type="checkbox" name="resource_id[<?php echo $k;?>]" value="<?php echo $resource['resource_id'];?>"/>-->
            <?php
                $data = array(
                    'name'          => "resource_id[$k]",
                    'value'         => $resource['resource_id'],
                    'checked'       => $resource_matched,
            );
            
            echo form_checkbox($data);
            ?>
            </td>
            <td><?php echo $resource['filename'];?></td>
            <td>
                <?php

                $options = array(
                    ''    => '--select format--',
                    'spss'    => 'SPSS File',
                    'stata'   => 'STATA File',
                    'r'       => 'R File',
                    'csv'     => 'Comma separated value',
                    'txt'      => 'Text file'
                );

                echo form_dropdown('format['.$k.']', $options, $format_matched);


                ?>
            </td>
        </tr>
        <?php endforeach;?>
    </table>

<button type="submit" class="btn btn-primary">Update</button>
<a type="button" class="btn btn-info navbar-btn" href="<?php echo site_url('admin/catalog/edit/'.$survey['id'].'/data-files');?>">
Cancel
</a>
</form>

</div>

<?php var_dump($attached_resources);?>
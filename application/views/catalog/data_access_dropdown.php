<select name="formid"  id="select-data-access" class="form-control data-access-dropdown">
    <option value="0" >- Select -</option>
    <?php foreach($da_list as $data_access):?>
        <option 
            value="<?php echo $data_access['formid'];?>"
            <?php echo ($selected==$data_access['formid']) ? 'selected="selected"' :'';?>
        ><?php echo t($data_access['fname']);?></option>
    <?php endforeach;?>
</select>
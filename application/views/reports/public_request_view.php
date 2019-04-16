<style>
table tr{border-top:1px solid gainsboro;}
table td{padding:5px;}
</style>

<h1><?php echo t('public_request');?></h1>
<table class="report-table" width="100%">
    <tr valign="top">
        <td><?php echo t('request_id');?></td>
        <td><?php echo $id;?></td>
    </tr>
    <tr valign="top">
        <td><?php echo t('request_date');?></td>
        <td><?php echo date("F, dS Y H:i:s",$posted);?></td>
    </tr>
    <tr valign="top">
        <td><?php echo t('study_title');?></td>
        <td><?php echo $title;?> - <?php echo $nation;?> - <?php echo $year_start;?></td>
    </tr>
    <tr valign="top">
        <td><?php echo t('study_id');?></td>
        <td><?php echo $idno;?></td>
    </tr>
    <tr valign="top">
        <td><?php echo t('full_name');?></td>
        <td><?php echo $first_name;?> <?php echo $last_name;?></td>
    </tr>
    <tr valign="top">
        <td><?php echo t('username');?></td>
        <td><?php echo $username;?></td>
    </tr>
    <tr valign="top">
        <td><?php echo t('email');?></td>
        <td><?php echo $email;?></td>
    </tr>
    <tr valign="top">
        <td><?php echo t('organization');?></td>
        <td><?php echo $company;?></td>
    </tr>
    <tr valign="top">
        <td><?php echo t('phone');?></td>
        <td><?php echo $phone;?></td>
    </tr>
    <tr valign="top">
        <td><?php echo t('country');?></td>
        <td><?php echo $country;?></td>
    </tr>

    <tr valign="top">
        <td><?php echo t('intended_data_use');?></td>
        <td><?php echo nl2br($abstract);?></td>
    </tr>

</table>
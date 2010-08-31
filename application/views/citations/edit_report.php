<div class="field">
	<label for="title"><?php echo t('title');?></label>
	<input name="title" type="text" id="title" class="input-flex" value="<?php echo get_form_value('title',isset($title) ? $title : ''); ?>"/>
</div>

<?php echo form_author_field("author",'Author(s)'); ?>

<table border="0" class="inline-fields">
	<tr>
    <td>	
	<div class="field">
        <label for="edition"><?php echo t('edition');?></label>
        <input name="edition" type="text" id="editon" size="50" class="input-flex"  value="<?php echo get_form_value('edition',isset($edition) ? $edition: ''); ?>"/>
    </div>
    </td>
    <td>    
    <div class="field">
        <label for="volume"><?php echo t('volume');?></label>
        <input name="volume" type="text" id="volume" size="50" class="input-flex" value="<?php echo get_form_value('volume',isset($volume) ? $volume : ''); ?>"/>
    </div>
    </td>

	<td>&nbsp;&nbsp;&nbsp;</td>
    <td>
    <div class="field">
        <label><?php echo t('publication_day_month_year');?></label>
        <input name="pub_day" class="input-flex" style="width:20px" type="text" maxlength="2" size="2" value="<?php echo get_form_value('pub_day',isset($pub_day) ? $pub_day: ''); ?>"/>&nbsp;-&nbsp;
		<input name="pub_month" type="text" class="input-flex" style="width:70px" maxlength="20"  size="10" value="<?php echo get_form_value('pub_month',isset($pub_month) ? $pub_month: ''); ?>"/>&nbsp;-&nbsp;
        <input name="pub_year" type="text" class="input-flex" style="width:40px" size="2" maxlength="4"  value="<?php echo get_form_value('pub_year',isset($pub_year) ? $pub_year: ''); ?>"/>
    </div>
    </td>
    <td>&nbsp;&nbsp;&nbsp;</td>
    <td>
    <div class="field">
        <label for="idnumber"><?php echo t('periodical_number');?></label>
        <input name="idnumber" type="text" id="idnumber" size="10" maxlength="45" class="input-flex" value="<?php echo get_form_value('idnumber',isset($idnumber) ? $idnumber: ''); ?>"/>
    </div>
    </td>

</tr>
</table>
<table border="0" class="inline-fields">
	<tr>    
    <td>
    <div class="field">
        <label for="publisher"><?php echo t('publisher');?></label>
        <input name="publisher" type="text" id="publisher" size="50" class="input-flex" value="<?php echo get_form_value('publisher',isset($publisher) ? $publisher : ''); ?>"/>
    </div>
    </td>
    <td>
    <div class="field">
        <label for="place_publication"><?php echo t('publication_city');?></label>
        <input name="place_publication" type="text" id="place_publication" size="50" class="input-flex" value="<?php echo get_form_value('place_publication',isset($place_publication) ? $place_publication : ''); ?>"/>
    </div>
    </td>
    <td>
    <div class="field">
        <label for="place_state"><?php echo t('publication_state_country');?></label>
        <input name="place_state" type="text" id="place_state" size="50" class="input-flex" value="<?php echo get_form_value('place_state',isset($place_state) ? $place_state : ''); ?>"/>
    </div>
    </td>
    </tr>
</table>


<div class="field">
	<label for="url"><?php echo t('url');?></label>
	<input name="url" type="text" id="url" size="50" class="input-flex"  value="<?php echo get_form_value('url',isset($url) ? $url : ''); ?>"/>
</div>
<style type="text/css">
input, textarea, select {
	margin-bottom: 15px;
}
</style>

<h1 >Edit Resource</h1>



	<?php echo form_open("datadeposit/managefiles/{$file[0]->id}");?>


<input name="resource_id" type="hidden" id="resource_id" value=""/>

<div class="field">

	<label for="dctype">Type</label>

    <select name="dctype">
<option value="<?php echo $file[0]->dctype; ?>">--SELECT--</option>
<option value="Document, Administrative [doc/adm]">Document, Administrative [doc/adm]</option>
<option value="Document, Analytical [doc/anl]">Document, Analytical [doc/anl]</option>
<option value="Document, Other [doc/oth]">Document, Other [doc/oth]</option>
<option value="Document, Questionnaire [doc/qst]">Document, Questionnaire [doc/qst]</option>
<option value="Document, Reference [doc/ref]">Document, Reference [doc/ref]</option>
<option value="Document, Report [doc/rep]">Document, Report [doc/rep]</option>
<option value="Document, Technical [doc/tec]">Document, Technical [doc/tec]</option>
<option value="Audio [aud]">Audio [aud]</option>
<option value="Database [dat]">Database [dat]</option>
<option value="Map [map]">Map [map]</option>
<option value="Microdata File [dat/micro]">Microdata File [dat/micro]</option>
<option value="Photo [pic]">Photo [pic]</option>
<option value="Program [prg]">Program [prg]</option>
<option value="Table [tbl]">Table [tbl]</option>
<option value="Video [vid]">Video [vid]</option>
<option value="Web Site [web]">Web Site [web]</option>
</select></div>



<div class="field">

<label for="title">Title</label>

<input name="title" type="text" id="title" class="input-flex" value="<?php echo $file[0]->title; ?>"/>

</div>


<?php /*
<div class="field">

<label for="author">Author</label>

<input name="author" type="text" id="author" class="input-flex" value="<?php echo $file[0]->author; ?>"/>

</div>



<div class="field">

<label for="dcdate">Date</label>

<input name="dcdate" type="text" id="dcdate" size="50" class="input-flex" disabled="disabled" value="<?php echo $file[0]->created; ?>"/>

</div>
*/ ?>

<div class="field">

	<label for="description">Description</label>

	<textarea name="description" cols="30" rows="4" id="description" class="input-flex" ><?php echo $file[0]->description; ?></textarea>

</div>


   <div class="field" style="flaot:left;">

    <input type="hidden" name="update" value="Submit"  class="button"/>

               <div class="button">
        <span>Save</span>
    </div>

        <a class="btn_cancel" style="position:relative;top:10px;font-size:14px" href="<?php echo site_url('datadeposit'); ?>/datafiles/<?php echo $file[0]->project_id; ?>">Cancel</a>

    <!--<a class="btn_cancel" href="http://localhost/datadeposit/datadeposit/index.php/projects/summary/1">Cancel</a>-->

    </div>



<?php echo form_close(); ?>

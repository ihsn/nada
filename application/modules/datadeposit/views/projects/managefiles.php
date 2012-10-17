<style type="text/css">
input[type="text"], textarea {
	width: 50% !important;
	margin-top: 30px;
}
</style>

<h1 class="page-title">Edit Resource</h1>



	<?php echo form_open("projects/managefiles/{$file[0]->id}");?>


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



<div class="field">

<label for="author">Author</label>

<input name="author" type="text" id="author" class="input-flex" value="<?php echo $file[0]->author; ?>"/>

</div>



<div class="field">

<label for="dcdate">Date</label>

<input name="dcdate" type="text" id="dcdate" size="50" class="input-flex" disabled="disabled" value="<?php echo $file[0]->created; ?>"/>

</div>


<div class="field">

	<label for="description">Description</label>

	<textarea name="description" cols="30" rows="4" id="description" class="input-flex" ><?php echo $file[0]->description; ?></textarea>

</div>



<div class="field">

	<label for="filename">URL or Relative path to the resource</label>

	<input name="filename" type="text" disabled="disabled" id="filename" size="50" class="input-flex"  value="<?php echo $file[0]->filename; ?>"/>

</div>



<div class="field">

	<input type="submit" name="submit" id="submit" value="Submit" />

	<a href="http://localhost/nada/index.php/projects/datafiles/<?php echo $file[0]->project_id; ?>" class="button">Cancel</a></div>

<?php echo form_close(); ?>

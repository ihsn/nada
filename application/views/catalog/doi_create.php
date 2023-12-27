
<div class="card mt-4">
  <div class="card-header">
    <strong>Create a new DOI</strong>
  </div>

  <div class="card-body">

    <?php echo form_open('admin/catalog/doi_create',array('class'=>'create-doi-form'));?>
  
    <div class="form-group row">
        <label for="prefix" class="col-sm-2 col-form-label">Prefix</label>
        <div class="col-sm-10">
        <input type="text" class="form-control" id="prefix" placeholder="Prefix" name="prefix" v-model="doi_prefix" disabled="disabled">
        </div>
    </div>

    <div class="form-group row">
        <label for="doi" class="col-sm-2 col-form-label">DOI</label>
        <div class="col-sm-10">
        <input type="text" class="form-control" id="doi" placeholder="DOI" name ="doi" disabled="disabled" v-model="doi_form.idno">
        </div>
    </div>

    <div class="form-group row">
        <label for="state" class="col-sm-2 col-form-label">State</label>
        <div class="col-sm-10">
        <input type="text" class="form-control" id="state" placeholder="State" name="state" value="publish" disabled="disabled">
        </div>
    </div>

    <div class="form-group row">
        <label for="state" class="col-sm-2 col-form-label">URL</label>
        <div class="col-sm-10">
        <input type="text" class="form-control" placeholder="URL" name="url" v-model="doi_form.url">
        </div>
    </div>

    <div class="form-group row">
        <label for="state" class="col-sm-2 col-form-label">Creator</label>
        <div class="col-sm-10">
        <input type="text" class="form-control" placeholder="Creator" v-model="doi_form.creator" >
        </div>
    </div>

    <div class="form-group row">
        <label for="state" class="col-sm-2 col-form-label">Title</label>
        <div class="col-sm-10">
        <input type="text" class="form-control" placeholder="Title" v-model="doi_form.title" >
        </div>
    </div>

    <div class="form-group row">
        <label for="state" class="col-sm-2 col-form-label">Publisher</label>
        <div class="col-sm-10">
        <input type="text" class="form-control" v-model="doi_form.publisher"  >
        </div>
    </div>

    <div class="form-group row">
        <label for="state" class="col-sm-2 col-form-label">Publication Year</label>
        <div class="col-sm-10">
        <input type="text" class="form-control" v-model="doi_form.publication_year" >
        </div>
    </div>

    <div class="form-group row">
        <label for="state" class="col-sm-2 col-form-label">Resource type</label>
        <div class="col-sm-10">
        <input type="text" class="form-control" disabled="disabled" value="dataset">
        </div>
    </div>

    <div class="form-group row">
        <label for="state" class="col-sm-2 col-form-label">Alternate Identifier</label>
        <div class="col-sm-10">
        <input type="text" class="form-control" id="state" placeholder="other identifier" value="<?php echo $dataset['idno'];?>">
        </div>
    </div>


  <div class="form-group row">
   <label for="state" class="col-sm-2 col-form-label"></label>
    <div class="col-sm-10">
      
      
      <button :disabled="doiExists == true" type="button" class="btn btn-primary" @click="submitForm" >Create & Attach</button>

      
        <span v-if="doiExists == true">
        <button type="button" class="btn btn-primary" @click="attachDoi" >Attach</button>
        </span>
      

      <div v-if="api_response.status">
        <pre>{{api_response}}</pre>
      </div>  
    </div>
  </div>
<?php echo form_close();?>

  </div>
</div>
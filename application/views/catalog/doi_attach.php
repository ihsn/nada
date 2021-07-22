<div class="card mt-4">
  <div class="card-header">
    <strong>Query DOI</strong>
  </div>
  <div class="card-body">
    
    <div class="row">
        <div class="col-12">
        <label for="title"><?php echo t('DOI');?><span class="required">*</span> <span class="small"></span></label>
        </div>
        <div class="col">        
            <div class="form-group">                
                <input class="form-control" name="doi" type="text" id="doi" placeholder="DOI Handle" v-model="doi" @keyup="validateDoi"/>
                <span v-show="doi_validation==false" class="text-danger">DOI contains invalid characters - Allowed values - [a-Z], [0-9], [._-\]</span>
            </div>
        </div>
        <div class="col">
        <button type="button" class="btn btn-secondary" @click="searchByDoi">Find</button>
        </div>
    </div>
    <div v-if="doi_info">
        <div v-if="!doi_info.id" class="text-danger">DOI was not found</div>
        <div v-else>
            <div v-if="doi_info.id">
                <h5>DOI info</h5>
                <table class="table table-sm table-striped">
                    <tr>
                        <td>DOI ID#</td>
                        <td>{{doi_info.id}}</td>
                    </tr>
                    <tr>
                        <td>URL</td>
                        <td><a target="_blank" :href="doi_info.attributes.url">{{doi_info.attributes.url}}</a></td>
                    </tr>
                    <tr>
                        <td>Title</td>
                        <td><div v-for="doi_title in doi_info.attributes.titles">{{doi_title.title}}</div></td>
                    </tr>
                    <tr>
                        <td>Publisher</td>
                        <td>{{doi_info.attributes.publisher}}</td>
                    </tr>

                    <tr>
                        <td>Publication year</td>
                        <td>{{doi_info.attributes.publicationYear}}</td>
                    </tr>

                    <tr>
                        <td>Creators</td>
                        <td><div v-for="doi_creator in doi_info.attributes.creators">{{doi_creator.name}}</div></td>
                    </tr>

                    <tr>
                        <td>Identifiers</td>
                        <td><div v-for="doi_identifier in doi_info.attributes.identifiers">{{doi_identifier.identifier}}</div></td>
                    </tr>

                    <tr v-for="(attribute,att_key) in doi_info.attributes">                        
                        <template v-if="att_key!='xml'" >
                        <td  class="text-capitalize">{{att_key}}</td>
                        <td>{{attribute}}</td>
                        </template>
                    </tr>

                    <tr v-for="(attribute,att_key) in doi_info.meta">
                        <template v-if="att_key!='xml'" >
                        <td class="text-capitalize">{{att_key}}</td>
                        <td>{{attribute}}</td>
                        </template>
                    </tr>

                </table>

            </div>
        </div>
    </div>

    

  </div>
</div>
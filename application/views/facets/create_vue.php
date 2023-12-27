
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/vue@2"></script>

    <style type="text/css">
      .field_selection{
          max-width:200px;
      }

      .data-type{
          text-transform: capitalize;
          font-weight:bold;
      }
      label{
          font-weight:bold
      }
    </style>

<?php require_once 'links.php';?>


<div id="app" class="container-fluid">

     <h1 class="pb-4">Create Facet</h1> 

    <div class="row">
        <div class="col-md-2">
        <div class="form-group">
            <label for="title"><?php echo t('Name');?><span class="required">*</span></label>
            <input class="form-control" name="title" type="text" id="title"  v-model="name" placeholder="A short name with no spaces"/>
        </div>
        </div>

        <div class="col-md-4">
        <div class="form-group">
            <label for="weight"><?php echo t('Title');?><span class="required">*</span></label>
            <input class="form-control" name="weight" type="text" id="weight"  v-model="title" placeholder="Title"/>
        </div>
        </div>

        <div class="col-md-2">
        <div class="form-group">
            <label for="enabled"><?php echo t('Status');?><span class="required">*</span></label>        
            <select id="enabled" class="form-control" v-model="enabled">
                <option value="1" selected>Enabled</option>
                <option value="0">Disabled</option>
            </select>
        </div>
        </div>
    </div>

    <label for="weight"><?php echo t('Mappings');?><span class="required">*</span></label>
    <table class="table table-striped">
    <tr>
        <td></td>
        <td>Field</td>
        <td>Subfield (for composite types)</td>
        <td>Filter</td>
        <td>Filter value</td>
    </tr>
    <tr v-for="(item, index) in data_types">
        <td class="data-type">{{ item }}</td>
        <td>
            <select @change="fieldSelectionOnChange(item,$event)" v-model="options[item].field" class="field_selection">
                <option  value="">Select</option>
                <template v-for="(field, field_key) in fields[item]">
                {{field}}
                    <template v-if="field.items">
                    <option :value="field_key">{{field_key}}*</option>
                    <?php /*<optgroup :label="field_key">                    
                        <option v-for="(subfield,subfield_key) in field.items">{{field_key}}:{{subfield_key}}</option>
                    </optgroup> */ ?>
                    </template>
                    <template v-else-if="field.type=='string'">
                        <option>{{field_key}}</option>
                    </template>
                </template>
            </select>
        </td>
        
        <td>
        <template v-if="getSubfields(item, options[item].field)">
        
            <select v-model="options[item].subfield" class="field_selection">
                <template v-for="(subfield,subfield_key) in getSubfields(item, options[item].field)">
                    <option>{{subfield_key}}</option>
                </template>
            </select>
        
        </template>
        <template v-else>
            -
        </template>

        </td>
        <td>
        <template v-if="getSubfields(item, options[item].field)">
        
            <select v-model="options[item].filter" class="field_selection">
                    <option value="">None</option>
                <template v-for="(subfield,subfield_key) in getSubfields(item, options[item].field)">
                    <option>{{subfield_key}}</option>
                </template>
            </select>
        
        </template>
        </td>        
        <td>
            <template v-if="getSubfields(item, options[item].field)">
                <input type="text" value="" placeholder="" v-model="options[item].filter_value"/>
            </template>
        </td>
    </tr>

</table>







<div class="border-top mt-3 mb-3 pt-3">
    <button type="button" class="btn btn-primary" @click="submitForm">Save</button>
    <a href="<?php echo site_url('admin/facets');?>">Cancel</a>
</div>

</div>



<script>
<?php if (isset($facet['mappings']) && !empty($facet['mappings'])):?>
    let options=<?php echo ($facet['mappings']);?>;
<?php else:?>
    <?php 
        $options=array();
        
        foreach($data_types as $type){
            $options[$type]=array(
                "field"=>"",
                "subfield"=>"",
                "filter"=>"",
                "filter_value"=>""    
            );
        }
    ?>    

    let options=<?php echo json_encode($options);?>
<?php endif;?>

var app = new Vue({
  el: '#app',
  data: {    
    name:'<?php echo isset($facet['name']) ? $facet['name'] : '';?>',
    title:'<?php echo isset($facet['title']) ? $facet['title'] : '';?>',
    enabled:'<?php echo isset($facet['enabled']) ? $facet['enabled'] : 0;?>',
    data_types: <?php echo json_encode($data_types);?>,
    fields: <?php echo json_encode($fields);?>,
    //field_selection:mappings.field,
    //subfield_selection:mappings.subfield,
    //filter_column:mappings.filter_column,
    //filter_value:mappings.filter_value,
    options: options
  },
  async mounted() {
    //this.search();
  },
  /*mounted: function(){
      this.search();
      //this.renderMap();
  },*/
  methods:{
    getSubfields: function(data_type,field_key){
        if (field_key=='undefined'){
            return false;
        }

        if (typeof(field_key)=='object'){
            return false;
        }

        if (typeof(this.fields[data_type][field_key])=='undefined'){
            return false;
        }

        if (this.fields[data_type][field_key]['type']!=='array'){
            return false;
        }

        if (typeof(this.fields[data_type][field_key]["items"]["properties"])!=='undefined'){                
            return this.fields[data_type][field_key].items.properties;
        }
        
        return false;
    },
    fieldSelectionOnChange: function(data_type,event) {
        console.log(data_type,event.target.value);
        field_key=event.target.value;

        if (this.fields[data_type][field_key]["type"]=='array'){
            try{
                let keys_=Object.keys(this.fields[data_type][field_key].items.properties);
                if (keys_.length>0){
                    this.options[data_type].subfield=keys_[0];
                }
            }
            catch(err){
                this.options[data_type].subfield='';    
            }
        }else{
            this.options[data_type].subfield='';
        }        
    },
    submitForm: function () {
        var url = '<?php echo site_url('api/facets');?>';

        console.log(url);

        let vm=this;
        //form_data=JSON.parse(JSON.stringify(vm.formData))
        let data={
            "title":this.title,
            "name":this.name,
            "facet_type":"user",
            "enabled":this.enabled,
            "mappings":this.options,
            '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
        }
        console.log(data);

        $.ajax
        ({
            type: "POST",
            url:  url,
            contentType: 'application/json',
            dataType: 'json',
            data: JSON.stringify(data),
            //async: false,
            success: function (data) {
                console.log(data);
                window.location.replace("<?php echo site_url('admin/facets');?>");
            },
            error: function(e){
                console.log(e);
                alert("failed" + e);
            }
        })
    }
}

    
});
</script>

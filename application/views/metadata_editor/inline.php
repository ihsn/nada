<html>

<head>            
    <script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
    <script src="https://unpkg.com/vuex@3.4.0/dist/vuex.js"></script>
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.20/lodash.min.js"></script>
    <!--<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>-->
    
    <script src="https://cdn.jsdelivr.net/npm/vue-deepset@0.6.3/vue-deepset.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css" crossorigin="anonymous" />
   
    <!--<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ajv/6.12.2/ajv.bundle.js" integrity="sha256-u9xr+ZJ5hmZtcwoxwW8oqA5+MIkBpIp3M2a4AgRNH1o=" crossorigin="anonymous"></script>

    <script src="https://cdn.jsdelivr.net/npm/deepdash/browser/deepdash.standalone.min.js"></script>
    
    <!--
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
-->
    <script src="https://cdn.jsdelivr.net/npm/vue-scrollto"></script>
    <!--
        <script src="https://unpkg.com/vee-validate@latest"></script>
-->

    <script src="https://cdnjs.cloudflare.com/ajax/libs/vee-validate/3.4.0/vee-validate.full.min.js" integrity="sha512-owFJQZWs4l22X0UAN9WRfdJrN+VyAZozkxlNtVtd9f/dGd42nkS+4IBMbmVHdmTd+t6hFXEVm65ByOxezV/Qxg==" crossorigin="anonymous"></script>
    

    <script>
        console.log(deepdash.eachDeep); // --> all the methods just work

<?php /*
        //initialize schema validator
        var ajv = Ajv({
                allErrors : true
            });

        survey_schema=<?php echo file_get_contents('application/schemas/survey-schema.json');?>;
        ddi_schema=<?php echo file_get_contents('application/schemas/ddi-schema.json');?>;
        datafile_schema=<?php echo file_get_contents('application/schemas/datafile-schema.json');?>;  
        variable_schema=<?php echo file_get_contents('application/schemas/variable-schema.json');?>;
        provenance_schema=<?php echo file_get_contents('application/schemas/provenance-schema.json');?>;    
        ajv.addSchema(ddi_schema,'http://ihsn.org/schemas/ddi-schema.json');
        ajv.addSchema(datafile_schema,'http://ihsn.org/schemas/datafile-schema.json');
        ajv.addSchema(variable_schema,'http://ihsn.org/schemas/variable-schema.json');
        ajv.addSchema(provenance_schema,'http://ihsn.org/schemas/provenance-schema.json');
        //ajv.addSchema(survey_schema,'survey-schema');

        var validate_x = ajv.compile(survey_schema);
*/ ?>



    </script>


    <script>
        let sid='<?php echo $sid;?>';
        let form_template=<?php echo $metadata_template;?>;

        //json schema
        let metadata_schema=<?php echo $metadata_schema;?>;
    </script>

    <?php //die();?>

    
    <style>
        .bg-light-2{
            background:#e9ecef!important;
        }
        .metadata-form-container .field-type-section{
            border:0px;
            margin-bottom:5px;
            background:#f3f3f3;
            padding-bottom:5px;
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important
        }

        .metadata-form-container .required-label{
            font-weight:bold;
            color:red;
        }

        .metadata-form-container .form-field-textarea{
            min-height:200px;
        }

        /*.grid-button-delete,
        .section-toggle-icon
        {
            margin-top:4px;
        }*/

        .form-field-table{
            margin-top: 20px;
            margin-bottom: 20px;
            margin-right: 15px
        }

        .form-field-table th{
            font-size:12px;
        }

        .metadata-form-container .form-field{
            margin-left:15px;
            margin-right:15px;
            margin-top:15px;
            margin-bottom:30px;
        }

        .metadata-form-container .field-type-section .field-type-section{
            margin-left:15px;
            margin-right:15px;
        }

        .form-node .form-node,
        .form-tree .form-tree,
        .tree-menu .tree-menu {
            margin-left: 15px;            
        }

        .tree-node{
            cursor:pointer;
        }

        .form-container{
            border:1px solid #0062cc;
        }

        .form-section {
            background: #03A9F4;
            color: #ffffff;
            font-size: 14px;
            font-weight: bold;
            padding: 10px;
        }

        label{
            font-size: 14px;
                font-weight: bold;
                margin-bottom: .1rem;
            }
        

        .sidebar{            
            /*overflow-y: scroll;*/
            border:1px solid gainsboro;
            padding-left:10px;
        }

        .sidebar-content{
            padding:15px;
        }

        .sidebar-header{
            padding:10px;
            background: #F1F1F1;
            border-bottom:1px solid gainsboro;            
        }

        .main-content{
            /*overflow-y: scroll;*/
            height:100%;
            padding-top:10px;
        }

        .form-tree{
            color:#545b62;
        }
        .form-tree .active{
            color:#0062cc;            
        }

        .lvl-0 .field-type-section{
            margin-top:15px;
        }

        .field-type-nested_array{
            margin:15px;
        }        

        .error{
            background:transparent;
            color:red;
            border:0px;
        }

        .grid-component{
            background:white;
            padding:5px;
        }

        html,body {
            height: 100%;
        }
        .container-fluid {
            height: 100vh;
        }

        .nav-link{
            color:black;
        }
        .nav-active{
            font-weight:bold;
            color:#007bff;
        }



.sub-header{
    display:none;
}


.metadata-form-container .field-type-section {
    border: 1px solid gainsboro;
    margin-bottom: 5px;
    background: transparent;
    padding-bottom: 0px;
}


.form-section {
    background: #f6f8fa;
    color: #000000;
    font-size: 14px;
    border-bottom: 1px solid gainsboro;
    font-weight: bold;
    padding: 10px;
}

.list-group-item{
    padding:.3rem 1.25rem;
    font-size:14px;
}

.sticky-offset {
    top: 56px;
}



#sidebar-container .list-group .list-group-item[aria-expanded="false"] .submenu-icon .fa-caret-right{
    display:none;
}
#sidebar-container .list-group .list-group-item[aria-expanded="true"] .submenu-icon .fa-caret-down{
    display:none;
}

.sidebar-submenu{
    font-size:smaller;
}
    </style>
</head>

<body>
    <!-- Bootstrap NavBar -->


<div id="app">
<!-- Bootstrap row -->
<div class="row" id="body-row">


            
    <!-- Sidebar -->
    <?php $this->load->view('metadata_editor/sidebar');?>

    <!-- MAIN -->
    <div class="col py-3">

        

        <!--start-->
        <div class="border-bottom py-2">
                <h3 style="margin:0px;">Metadata</h3>
            
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-sliders-h"></i> Options <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a href="#">All fields</a></li>
                        <li role="separator" class="divider"></li>
                        <li><a href="#">Recommended fields</a></li>
                        <li role="separator" class="divider"></li>
                        <li><a href="#">Mandatory fields</a></li>
                    </ul>
                </div>

                <div class="btn-group" role="group" aria-label="...">
                    <button type="button" class="btn btn-default btn-sm"><i class="far fa-copy"></i> Import</button>                
                </div>

                <div class="float-right">
                    <button type="button" class="btn btn-primary btn-sm" @click="submitForm">Save</button>
                    <a href="<?php echo site_url('admin/catalog/edit/'.$sid);?>" class="btn btn-danger btn-sm" >Cancel</a>
                </div>
                <div v-if="form_errors.length>0 || schema_errors.length>0" style="margin-bottom:15px;" class="pl-2">
                    <div style="color:red;font-weight:bold;">Please correct the following errors:</div>
                        <div style="color:red;" v-if="form_errors.length>0">
                            <div v-for="error in form_errors">
                                <span v-if="error.message">
                                    <i class="fas fa-times-circle"></i> {{ error.message }}
                                    <span class="label label-warning">{{error.property}}{{error.dataPath}}</span>
                                </span>
                                <span v-if="!error.message"><i class="fas fa-times-circle"></i> {{ error }}</span>
                            </div>
                        </div>
                        <div style="color:red;" v-if="schema_errors.length>0">
                            <div v-for="error in schema_errors">
                                <span v-if="error.message">
                                    <i class="fas fa-times-circle"></i> {{ error.message }}
                                    <span class="label label-warning">{{error.property}}{{error.dataPath}}</span>
                                </span>
                                <span v-if="!error.message"><i class="fas fa-times-circle"></i> {{ error }}</span>
                            </div>
                        </div>
                </div>
            
            </div>

            <div class="main-content" id="main-content">                
                <validation-observer ref="form" v-slot="{ invalid }">                
                    <metadata-form :field="form_template" :items="form_template.items" :depth="0" :css_class="'metadata-form-container'"></metadata-form>
                </validation-observer>
            </div>
        <!--end-->



        

    </div>
    <!-- Main Col END -->


    </div><!--end app -->

</div>
<!-- body-row END -->








<!-- Modal -->
<div class="modal fade" id="app_dialog" tabindex="-1" role="dialog" aria-labelledby="DialogBox" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLongTitle">{{dialog_box_option.title}}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div>{{dialog_box_option.content}}</div>
        <div v-if="dialog_box_option.errors" > 
            <div style="color:red;">
                <div style="font-weight:bold;">{{dialog_box_option.errors.message}}</div>
                <li v-for="error in dialog_box_option.errors.errors">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span v-if="error.message">{{ error.message }}</span>
                    <span v-if="!error.message">{{ error }}</span>
                </li>
            </div>
            <div class="mt-3" style="font-weight:bold">Error:</div>
            <pre style="max-height:200px;overflow:auto;">{{dialog_box_option.errors}}</pre>        
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>




    <script>
        Vue.use(Vuex)
        Vue.use(VueDeepSet)        

        window.bus = new Vue();

        Vue.mixin({
            methods: {
                normalizeClassID: function(class_id){
                    return class_id.replace(/\./g, "-");
                }                
            }
        })
        
        <?php 
            //tree view component
            echo $this->load->view("metadata_editor/vue-form-tree.js",null,true);

            //metadata form component
            echo $this->load->view("metadata_editor/vue-metadata-form-component.js",null,true);

            //metadata grid component
            echo $this->load->view("metadata_editor/vue-grid-component.js",null,true);

            //nested
            echo $this->load->view("metadata_editor/vue-nested-section-component.js",null,true);

            echo $this->load->view("metadata_editor/vue-simple-array-component.js",null,true);
        ?>

        
        <?php if (empty($metadata)):?>
            var project_metadata={};
        <?php else:?>
            var project_metadata=<?php echo json_encode($metadata);?>;
        <?php endif;?>

        <?php if (empty($survey)):?>
            var project_sid=null;
        <?php else:?>
            var project_sid=<?php echo isset($survey['id']) ? $survey['id'] : 'null';?>;
        <?php endif;?>
        
        let project_idno='<?php echo isset($survey['idno']) ? $survey['idno'] : '';?>';
        let project_type='<?php echo isset($survey['type']) ? $survey['type'] : '';?>';

        var store = new Vuex.Store({
            state: {
                formData: project_metadata,
                active_node: {
                    id: 'table_description.title_statement.table_number'
                }
            },
            mutations: VueDeepSet.extendMutation({
                // other mutations
                data_model (state,data) {
                    //this.$vuexSet('testing',"another value");
                    console.log("value added");                    
                }
            })
            /*: {
                data_model (state,data) {
                    state.formData=data;
                }
            }*/
            //mutations: VueDeepSet.extendMutation()
        })


        // Add a rule.
  VeeValidate.extend('secret', {
    validate: value => value === 'example',
    message: 'example'
  });


  const isUniqueIDNO = (value) => {
        let sid='<?php echo $sid;?>';
        let url='<?php echo site_url('/api/datasets/check_idno/');?>' + value + '/' + sid;
        
        return axios.get(url)
        .then(function (response) {
            console.log(response);
            
            if (response.status==200 && response.data.id==sid){
                return {
                    valid: true,
                    data:{
                        message: 'IDNO is valid'
                    }
                }
            }

            return {
                valid: response.status==404,
                data:{
                    message: 'IDNO exists'
                }
            }
        })
        .catch(function (error) {                        
            console.log(error);                      
            return {
                valid: error.response.status==404,//valid if statuscode==404
                data:{
                    message: 'IDNO not found'
                }
            }
        });        
  };

  
  VeeValidate.extend('idno', {
    validate: isUniqueIDNO,
    getMessage: (field, params, data) => {
        return data.message;
    },
    message: 'Please enter a unique value.'
  });

  VeeValidate.extend('required', {
    validate (value) {
        return {
            required: true,
            valid: ['', null, undefined].indexOf(value) === -1
        };        
    },
    computesRequired: true
    //message: 'This is a required field'
  });

        Vue.component('ValidationProvider', VeeValidate.ValidationProvider);
        Vue.component('ValidationObserver', VeeValidate.ValidationObserver);



        var app = new Vue({
            el: '#app',
            store,
            schema_validator: null,
            data:{
                email:'',
                obj:{},
                dataset_id:project_sid,
                dataset_idno:project_idno,
                dataset_type:project_type,
                form_template: form_template,
                metadata_schema: metadata_schema,
                dialog_box_option:{
                    'title': '',
                    'content': '',
                    'erorrs': {}                    
                },
                form_errors:[],
                schema_errors:[]
            },
            mounted: function () {
                console.log(this.form_template);
                vm=this;
                bus.$on('tree-node-click', function (node_id) {
                    vm.scrollToElement('#main-content','#field-'+node_id,500);
                    $("#field-"+node_id).focus();
                });

                //initialize schema validator
                var ajv = Ajv({
                    allErrors : true
                });

                provenance_schema=<?php echo file_get_contents('application/schemas/provenance-schema.json');?>;        
                ajv.addSchema(provenance_schema,'http://ihsn.org/schemas/provenance-schema.json');

                if (vm.dataset_type=='survey'){
                    survey_schema=<?php echo file_get_contents('application/schemas/survey-schema.json');?>;
                    ddi_schema=<?php echo file_get_contents('application/schemas/ddi-schema.json');?>;
                    datafile_schema=<?php echo file_get_contents('application/schemas/datafile-schema.json');?>;  
                    variable_schema=<?php echo file_get_contents('application/schemas/variable-schema.json');?>;    
                    ajv.addSchema(ddi_schema,'http://ihsn.org/schemas/ddi-schema.json');
                    ajv.addSchema(datafile_schema,'http://ihsn.org/schemas/datafile-schema.json');
                    ajv.addSchema(variable_schema,'http://ihsn.org/schemas/variable-schema.json');                    
                    //ajv.addSchema(survey_schema,'survey-schema');
                    this.schema_validator= ajv.compile(survey_schema);
                }
                else if (vm.dataset_type=='image'){
                    image_schema=<?php echo file_get_contents('application/schemas/image-schema.json');?>;
                    iptc_schema=<?php echo file_get_contents('application/schemas/iptc-pmd-schema.json');?>;
                    iptc_shared_schema=<?php echo file_get_contents('application/schemas/iptc-phovidmdshared-schema.json');?>;
                    ajv.addSchema(iptc_schema,'iptc-pmd-schema.json');
                    ajv.addSchema(iptc_shared_schema,'https://www.iptc.org/std/photometadata/specification/iptc-phovidmdshared-schema.json');
                    this.schema_validator= ajv.compile(image_schema);
                }
                else{
                    this.schema_validator= ajv.compile(this.metadata_schema);
                }
                
                /*for (let i = 0; i < form_template.array_elements.length; i++) {
                    console.log(form_template.array_elements[i]);
                    this.$vuexSet(form_template.array_elements[i],[{}]);
                };*/
                //this.loadData('test');                
            },
            computed: {
                formData () {
                    return this.$deepModel('formData')
                },
                activeNode (){
                    return this.$deepModel('active_node')
                },
                /*formValidationErrors(){
                    form_data=JSON.parse(JSON.stringify(this.formData))
                    this.removeEmpty(form_data);
                    valid=this.validateData(form_data);

                    if(valid!==true){
                        console.log(valid);
                        return valid;                        
                    }
                }*/
                
            },
            methods: {    
                scrollToElement: function(container,element,duration){
                    var options = {
                        container: container,
                        easing: 'ease-in',
                        offset: -100,
                        force: true,
                        cancelable: true,

                        onStart: function(element) {
                        // scrolling started
                        },

                        onDone: function(element) {
                        // scrolling is done
                        },

                        onCancel: function() {
                        // scrolling has been interrupted
                        },

                        x: false,
                        y: true
                    }
                    var cancelScroll = VueScrollTo.scrollTo(element, duration, options)
                }, 
                treeNodeClickHandler: function(el){
                    
                },           
                getModelValue: function(path){
                    console.log("getModelValue",path);
                    console.log(this.formData[path]);
                    //this.$vueSet (this.formData, path, "new value" );

                    if(!this.formData[path]){
                        console.log("empty path", path);

                        //set value using vuex store
                        //this.$vuexSet('message',"another value");
                        
                        //using vueset
                        //this.$vueSet (this.formData, path, "set" );
                    }
                    
                    return this.formData[path];
                },
                addRow: function (data,path){
                    this.$vueSet (this.formData, path, data);
                },
                //https://stackoverflow.com/questions/23774231/how-do-i-remove-all-null-and-empty-string-values-from-a-json-object/23774287
                //author: https://stackoverflow.com/users/1612318/rotareti
                /*removeEmpty: function(obj) {
                    vm=this;
                    Object.keys(obj).forEach(function(key) {
                        (obj[key] && typeof obj[key] === 'object') && vm.removeEmpty(obj[key]) ||
                        (obj[key] === '' || obj[key] === null) && delete obj[key]
                    });
                    return obj;
                },*/

                removeEmpty: function (obj) {
                    vm=this;
                    try {
                    $.each(obj, function(key, value){
                        console.log("no error");
                        if (value === "" || value === null || ($.isArray(value) && value.length === 0) ){
                            delete obj[key];
                        } else if (JSON.stringify(value) == '[{}]' || JSON.stringify(value) == '[[]]'){
                            delete obj[key];
                        } else if (Object.prototype.toString.call(value) === '[object Object]') {
                            vm.removeEmpty(value);
                        } else if ($.isArray(value)) {
                            $.each(value, function (k,v) { vm.removeEmpty(v); });
                        }
                    });
                    }catch (error) {
                    console.error(error);
                    // expected output: ReferenceError: nonExistentFunction is not defined
                    // Note - error messages will vary depending on browser
                    }
                },
                
                setErrors: function(errors){
                    this.form_errors=[];
                    
                },
                validateData: function(data){         
                    
                    if (this.schema_validator == undefined){
                        return true;
                    }
                    
                    if (!this.schema_validator(data)){
                        return this.schema_validator.errors
                    }

                    return true;                    
                },
                submitForm: function(){
                    vm=this;
                    vm.form_errors=[];
                    vm.schema_errors=[];
                    this.$refs.form.validateWithInfo().then(({ isValid, errors, $refs })=> {
                        console.log('isValid', isValid); // false
                        console.log(errors);
                        vm.form_errors=Object.values(errors).flat();                                                
                        if (vm.form_errors=='' || vm.form_errors.length==0){
                            vm.saveForm();
                        }                    
                    });
                },
                saveForm: function(){
                    vm=this;
                    
                    let url=CI.base_url + '/api/datasets/update/'+vm.dataset_type+'/' + vm.dataset_idno;
                    
                    form_data=JSON.parse(JSON.stringify(vm.formData))
                    console.log(form_data);
                    vm.removeEmpty(form_data);
                    console.log(form_data);

                    validation_result=this.validateData(form_data);
                    console.log(validation_result);

                    //remove additionalProperties not supported by schema
                    validation_errors=[];
                    if(validation_result!==true){
                        for (let i = 0; i < validation_result.length; i++) {
                            if (validation_result[i]['keyword'] == "additionalProperties"){
                                elem_path=validation_result[i].dataPath + '.' + validation_result[i].params.additionalProperty
                                if(elem_path[0]=='.'){
                                    elem_path=elem_path.substring(1);
                                }
                                _.unset(form_data, elem_path);
                            }else{
                                validation_errors.push( validation_result[i]);
                            }                            
                        }                        
                    }

                    if (validation_errors.length>0){   
                        vm.schema_errors=validation_errors;    
                        return false;
                    }

                    /*if (!vm.project_sid){
                        vm.removeEmpty(form_data);
                        console.log(form_data);
                    }*/

                    axios.post(url, 
                        form_data
                        /*headers: {
                            "xname" : "value"
                        }*/
                    )
                    .then(function (response) {
                        console.log(response);
                        vm.dataset_id=response.data.dataset.id;
                        vm.dataset_idno=response.data.dataset.idno;
                        alert("Your changes were saved");
                    })
                    .catch(function (error) {
                        console.log(error);
                        vm.schema_errors=error.response.data.errors;
                        
                        /*console.log(error.response.data);
                        vm.dialog_box_option.title=error;
                        vm.dialog_box_option.errors=error.response.data;
                        $('#app_dialog').modal('show');
                        */
                    })
                    .then(function () {
                        // always executed
                        console.log("request completed");
                    });
                },

                refreshData: function(){
                    console.log(deepdash.paths(temp1.data));

                    //this.$vueSet (this.formData, 'doc_desc.producers', "new value" );
                    this.$vueSet (this.$store.state.formData, 'doc_desc.producers[0].name', 'new value');
                }
            }

        })
    </script>


    
</body>

</html>


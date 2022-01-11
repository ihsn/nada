<html>

<head>            
    <script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
    <script src="https://unpkg.com/vuex@3.4.0/dist/vuex.js"></script>
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <!--<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>-->
    
    <script src="https://cdn.jsdelivr.net/npm/vue-deepset@0.6.3/vue-deepset.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css" crossorigin="anonymous" />
   
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ajv/6.12.2/ajv.bundle.js" integrity="sha256-u9xr+ZJ5hmZtcwoxwW8oqA5+MIkBpIp3M2a4AgRNH1o=" crossorigin="anonymous"></script>

    <script src="https://cdn.jsdelivr.net/npm/deepdash/browser/deepdash.standalone.min.js"></script>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>

    <script src="https://cdn.jsdelivr.net/npm/vue-scrollto"></script>

    


    <script>
    console.log(deepdash.eachDeep); // --> all the methods just work
    </script>

    <script>
        let form_template=<?php echo $metadata_template;?>;
    </script>

    <?php //die();?>

    
    <style>
        .bg-light-2{
            background:#e9ecef!important;
        }
        .metadata-form-container .field-type-section{
            border:1px solid #e9ecef;
            margin-bottom:3px;
            background:white;
        }

        .metadata-form-container .required-label{
            font-weight:bold;
            color:red;
        }

        .metadata-form-container .form-field-textarea{
            height:200px;            
        }

        .grid-button-delete,
        .section-toggle-icon
        {
            margin-top:4px;
        }

        .form-field-table{
            margin-top: 20px;
            margin-bottom: 20px;
            margin-right: 15px
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

        .form-section{
            background:#607D8B;
            color:white;
            padding:10px;
        }

        .sidebar{            
            overflow-y: scroll;
            height:100%;           
            padding-top:10px; 
        }

        .main-content{
            overflow-y: scroll;
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
    </style>
</head>

<body>

<div id="app">

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
                    {{ error.message }}
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


    <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom sticky-top shadow-sm">
        
        <div class="p-3 m-1 " style="font-size:20px;">Metadata editor [beta]</div>
        
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>        
    </nav>


    <div class="container-fluid h-100" >
        <div class="row h-100" >
            <div class="col-md-3 sidebar-container h-100">
                <div class="sidebar" @tree-node-click="treeNodeClickHandler()">
                <form-tree  
                    :node="form_template" 
                    :title="form_template.title"  
                    :depth="0" 
                    :css_class="'lvl-0'"                    
                    @tree-node-click="treeNodeClickHandler()"                    
                ></form-tree>

                <pre style="overflow:auto;height:200px;border:1px solid red;">{{formData}}</pre>

                </div>

            </div> 
            <div class="col-md-9 h-100  bg-light-2">                
                <div class="main-content" id="main-content">

                    <div class="bg-lightx my-3 p-3">

                        <div  class="ml-auto p-2 bd-highlight pull-right float-right">
                            <button type="button" class="btn btn-sm btn-primary" @click="saveForm">Save</button>
                            <a type="button" class="btn btn-sm btn-secondary ml-2" href="<?php echo site_url('admin/catalog/edit/'.$sid);?>">Close</a>
                        </div>

                        <h3><?php echo $survey['title'];?></h3>
                        <div>Type: <?php echo $survey['type'];?></div>
                        <div>Status: <?php echo ($survey['published'] ==1) ? 'Published' : 'Draft';?></div>

                    </div>

                    <metadata-form :field="form_template" :items="form_template.items" :depth="0" :css_class="'metadata-form-container'"></metadata-form>
                </div>
            </div>
        </div>        
    </div>


</div>


    <script>

       

        Vue.use(Vuex)
        Vue.use(VueDeepSet)        


        window.bus = new Vue()

        Vue.mixin({
            methods: {
                normalizeClassID: function(class_id){
                    return class_id.replace(/\./g, "-");
                }                
            }
        })
        
        <?php 
            //tree view component
            echo $this->load->view("metadata_editor/vue-form-tree",null,true);

            echo $this->load->view("metadata_editor/vue-simple-array-component.js",null,true);
        ?>

        //Metadata Form ///////////////////////////////////////////////////
        Vue.component('metadata-form', {
            props: ['title', 'items', 'depth', 'css_class','path', 'field'],
            data() {
                return {
                    showChildren: true
                }
            },
            methods: {
                toggleChildren() {
                    this.showChildren = !this.showChildren;
                },
                toggleNode(event){
                    alert("event toggleNode");
                }
            },
            computed: {
                toggleClasses() {
                    return {
                        'fa-angle-down': !this.showChildren,
                        'fa-angle-up': this.showChildren
                    }
                },
                hasChildrenClass() {
                    return {
                        'has-children': this.nodes
                    }
                },
                formData () {
                    return this.$deepModel('formData')
                }
            },
            template: `
                <div :class="'metadata-form ' + css_class + ' ' + 'field-type-' + field.type" >
                    <div v-if="depth>0" class="label-wrapper" @click="toggleChildren">

                        <div v-if="field.type=='section'" class="tree-node form-section" :class="hasChildrenClass">                            
                            {{ title }}
                            <span class="float-right section-toggle-icon"><i class="fas" :class="toggleClasses"></i></span>
                        </div>

                        <div v-if="field.type=='array'">
                            <div class="form-group form-field form-field-table">
                                <label :for="'field-' + normalizeClassID(path)">{{title}}</label>
                                <grid-component
                                    :id="'field-' + normalizeClassID(path)" 
                                    :value="formData[field.key]"                                         
                                    :columns="field.props"
                                    :path="field.key">
                                </grid-component>  
                            </div>    
                        </div>

                        <div v-if="field.type=='nested_array'">
                            <label :for="'field-' + normalizeClassID(field.key)">{{title}}</label>
                            <nested-section 
                                :value="formData[field.key]"                                         
                                :columns="field.props"
                                :path="field.key">
                            </nested-section>  
                        </div>

                        <div v-if="field.type=='textarea'">

                            <div class="form-group form-field" :class="['field-' + field.key, field.class] ">
                                <label :for="'field-' + normalizeClassID(field.key)">{{title}}</label>
                                <textarea
                                    v-model="formData[field.key]"        
                                    class="form-control form-field-textarea" 
                                    :id="'field-' + normalizeClassID(field.key)"                                     
                                ></textarea>
                                <small class="help-text form-text text-muted">{{field.help_text}}</small>                            
                            </div>

                        </div> 


                        <div v-if="field.type=='dropdown'">

                            <div class="form-group form-field" :class="['field-' + field.key, field.class] ">
                                <label :for="'field-' + normalizeClassID(field.key)">{{title}}</label>
                                <select 
                                    v-model="formData[field.key]" 
                                    class="form-control form-field-dropdown"
                                    :id="'field-' + normalizeClassID(field.key)" 
                                >
                                    <option value="">Select</option>
                                    <option v-for="(option_key,option_value) in field.enum" v-bind:value="option_value">
                                        {{ option_key }}
                                    </option>
                                </select>
                                <small class="help-text form-text text-muted">{{formData[field.key]}}</small>
                                <small class="help-text form-text text-muted">{{field.help_text}}</small>
                            </div>

                        </div>  

                        <div v-if="field.type=='text' || field.type=='string' ">

                            <div class="form-group form-field" :class="['field-' + field.key, field.class] ">
                                <label :for="'field-' + normalizeClassID(field.key)">
                                    {{title}} 
                                    <span class="small" v-if="field.help_text" role="button" data-toggle="collapse" :data-target="'#field-toggle-' + normalizeClassID(field.key)" ><i class="far fa-question-circle"></i></span>
                                    <span v-if="field.required==true" class="required-label"> * </span>
                                </label>
                                <input type="text"
                                    v-model="formData[field.key]"
                                    class="form-control" 
                                    :id="'field-' + normalizeClassID(field.key)"                                     
                                >
                                <small :id="'field-toggle-' + normalizeClassID(field.key)" class="collapse help-text form-text text-muted">{{field.help_text}}</small>                            
                            </div>

                        </div>  


                        
                    </div>
                    <metadata-form
                        v-show="showChildren" 
                        v-for="item in items" 
                            :items="item.items" 
                            :title="item.title"
                            :depth="depth + 1"
                            :path="item.key"
                            :field="item"
                            :css_class="'lvl-' + depth" 
                    >
                    </metadata-form>
                </div>
            `
        })

        Vue.component('grid-component', {
            props:['value','columns','path'],
            data: function () {    
                return {
                    field_data: this.value,
                    key_path: this.path,
                    store: this.$store
                }
            },
            watch: { 
                field_data: function(newVal, oldVal) {
                    console.log('Prop changed: ', newVal, ' | was: ', oldVal)
                    //console.log(this.key_path);                    
                    this.$vueSet (this.$store.state.formData, this.key_path, newVal);
                }
            
            },
            
            mounted: function () {
                //set data to array if empty or not set
                if (!this.field_data){
                    this.field_data=[];
                }
            },
            computed: {
                localColumns(){
                    return this.columns;
                }
            },  
            template: `
                    <!--vuejs template for grid -->

                    <div class="grid-component">

                    <table class="table table-striped table-sm">
                        <thead class="thead-light">
                        <tr>
                            <th v-for="(column,idx_col) in columns" scope="col">
                                {{column.title}}
                            </th>
                            <th scope="col">               
                            </th>
                        </tr>
                        </thead>

                        <!--start-v-for-->
                        <tbody>
                        <tr  v-for="(item,index) in field_data">
                            <td v-for="(column,idx_col) in localColumns" scope="row">
                                <div>
                                    <input  
                                        v-model="field_data[index][column.key]" 
                                        class="form-control form-control-sm"  
                                        type="text" >
                                </div>
                            </td>
                            <td scope="row">        
                                <button type="button"  class="btn btn-sm btn-danger grid-button-delete float-right" v-on:click="remove(index)"><i class="fas fa-trash-alt"></i></button>
                            </td>
                        </tr>
                        <!--end-v-for -->
                        </tbody>
                    </table>

                    <div class="d-flex justify-content-center">
                        <button type="button" class="btn btn-light btn-block btn-sm" @click="addRow" >Add row</button>    
                    </div>

                    </div>  `,
            methods:{
                countRows: function(){
                    return this.field_data.length;
                },
                addRow: function (){    
                    this.field_data.push({});
                    this.$emit('adding-row', this.field_data);
                },
                remove: function (index){
                    this.field_data.splice(index,1);
                }  
            }
        })

        Vue.component('nested-section', {
            props:['value','columns','path'],
            data: function () {    
                return {
                    field_data: this.value,
                    key_path: this.path
                }
            },
            watch: { 
                field_data: function(newVal, oldVal) {
                    //console.log('Prop changed: ', newVal, ' | was: ', oldVal)
                    //console.log(this.key_path);
                    this.$vueSet (this.$store.state.formData, this.key_path, newVal);
                }
            },
            mounted: function () {
                //set data to array if empty or not set
                if (!this.field_data){
                    this.field_data=[{}];
                    
                }
            },
            computed: {
                localColumns(){
                    return this.columns;
                }
            },  
            template: `
                    <div class="nested-section">                                            
                        <template  v-for="(item,index) in field_data">
                            <div v-for="(column,idx_col) in localColumns" scope="row">
                                
                                    <div  v-if="column.type!=='array'">

                                        <div class="form-group form-field" :class="['field-' + column.key] ">
                                            <label :for="'field-' + normalizeClassID(path + '-' + column.key)">
                                                {{column.title}} 
                                                <span class="small" v-if="column.help_text" role="button" data-toggle="collapse" :data-target="'#field-toggle-' + normalizeClassID(path + ' ' + column.key)" ><i class="far fa-question-circle"></i></span>
                                                <span v-if="column.required==true" class="required-label"> * </span>
                                            </label>
                                            <input type="text"
                                                v-model="field_data[index][column.key]"
                                                class="form-control" 
                                                :id="'field-' + normalizeClassID(path + '-' + column.key)"                                     
                                            >
                                            <small :id="'field-toggle-' + normalizeClassID(path + '-' + column.key)" class="collapse help-text form-text text-muted">{{column.help_text}}</small>                            
                                        </div>
                                        
                                    </div>

                                    <div v-if="column.type=='array'">
                                        <div class="form-group form-field form-field-table">
                                            <label :for="'field-' + path">{{column.title}}</label>                                      
                                            <grid-component 
                                                :value="field_data[index][column.key]"   
                                                :columns="column.props"
                                                :path="path + '['+index+']'+ column.key"
                                                >
                                            </grid-component>  
                                        </div>
                                    </div>
                                
                            </div>    
                            <div>        
                                <button type="button"  class="btn btn-sm btn-danger float-right" v-on:click="remove(index)"><i class="fa fa-trash-o" aria-hidden="true"></i></button>
                            </div>
                        </template>

                    <div class="d-flex justify-content-center">
                        <button type="button" class="btn btn-light btn-block btn-sm" @click="addRow" >Add row</button>    
                    </div>

                    </div>  `,
            methods:{
                countRows: function(){
                    return this.field_data.length;
                },
                addRow: function (){    
                    this.field_data.push({});
                    this.$emit('adding-row', this.field_data);
                },
                remove: function (index){
                    this.field_data.splice(index,1);
                }    
            }
        })

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
                    //state.formData=JSON.parse(JSON.stringify(data));

                    /*state.formData=data;
                    this.store.state.formData["study_desc"]["authoring_entity"]=[{
                        "name": "Partners for Health Reformplus Project",
                        "affiliation": "PdHRplus XYXYXYXYYX"
                    }];

                    var author={
                        "name": "Partners for Health Reformplus Project",
                        "affiliation": "PdHRplus XYXYXYXYYX"
                    };
                */
                    //this.$vuexSet (this.$store.state.formData, 'study_desc.authoring_entity[0]', author);

                    this.$vuexSet('testing',"another value");
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

        var app = new Vue({
            el: '#app',
            store,
            data:{
                obj:{},
                dataset_id:project_sid,
                form_template: form_template,
                dialog_box_option:{
                    'title': '',
                    'content': '',
                    'erorrs': {}                    
                }
            },
            mounted: function () {
                console.log(this.form_template);
                vm=this;
                bus.$on('tree-node-click', function (node_id) {
                    vm.scrollToElement('#main-content','#field-'+node_id,500);
                    $("#field-"+node_id).focus();
                });

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
                }
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
                    $.each(obj, function(key, value){
                        if (value === "" || value === null || ($.isArray(value) && value.length === 0) ){
                            delete obj[key];
                        } else if (Object.prototype.toString.call(value) === '[object Object]') {
                            vm.removeEmpty(value);
                        } else if ($.isArray(value)) {
                            $.each(value, function (k,v) { vm.removeEmpty(v); });
                        }
                    });
                },


                loadData: function(dataset_idno){
                    vm=this;
                    let url='https://dev.ihsn.org/nada/index.php/metadata/export/74138/json';
                    axios.get(url, {
                        params: {
                            IDx: 12345
                        }
                        /*headers: {
                            "xname" : "value"
                        }*/
                    })
                    .then(function (response) {
                        console.log(response);
                        //get a flat array with path and value
                        data=deepdash.index(response.data)
                        console.log(data);

                        console.log(Object.keys(data));
                        paths=Object.keys(data);
                        
                        //update each value using path/values
                        for (let i = 0; i < paths.length; i++) {
                            //console.log(paths[i]);
                            //console.log(data[paths[i]]);
                            vm.$vuexSet('formData.' + paths[i],data[paths[i]]);
                        };
                        
                        //vm.$vuexSet('formData.study_desc.authoring_entity[0]',response.data['study_desc']['authoring_entity'][0]);
                    })
                    .catch(function (error) {                        
                        console.log(error);                        
                    })
                    .then(function () {
                        // always executed
                        console.log("request completed");
                    });
                },                
                saveForm: function(){                    
                    vm=this;
                    let url='<?php echo $post_url;?>';

                    form_data=JSON.parse(JSON.stringify(vm.formData))

                    if (!vm.project_sid){
                        vm.removeEmpty(form_data);
                        console.log(form_data);
                    }

                    axios.post(url, 
                        form_data
                        /*headers: {
                            "xname" : "value"
                        }*/
                    )
                    .then(function (response) {
                        console.log(response);
                        vm.dataset_id=response.data.dataset.id;
                        alert("Your changes were saved");
                    })
                    .catch(function (error) {
                        console.log(error);
                        console.log(error.response.data);
                        vm.dialog_box_option.title=error;
                        vm.dialog_box_option.errors=error.response.data;
                        $('#app_dialog').modal('show');
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
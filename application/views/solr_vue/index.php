<html>

<head>            
    <script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.20/lodash.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css" crossorigin="anonymous" />          
</head>

<body>
<div id="app">

    <div class="container-fluid">
        <div class="row" id="body-row">
            <!-- Sidebar -->
            <div class="col col-md-2">                
                <button type="button" class="btn btn-primary btn-sm btn-block" v-on:click="toggleTab('index-datasets')">Index datasets</button>
                <button type="button" class="btn btn-primary btn-sm btn-block" v-on:click="toggleTab('index-variables')">Index  variables</button>
                <button type="button" class="btn btn-secondary btn-sm btn-block">Index citations</button>
                <!--<button type="button" class="btn btn-primary btn-sm btn-block">Index single document</button>-->
                <button type="button" class="btn btn-primary btn-sm btn-block" v-on:click="commitSolr">Commit</button>
                <button type="button" class="btn btn-danger btn-sm btn-block" v-on:click="clearSolr">Clear index</button>
                <button type="button" class="btn btn-info btn-sm btn-block" v-on:click="pingSolr">Ping SOLR</button>
            </div>

            <!-- MAIN -->
            <div class="col">
                <!--start-->
                    <div class="main-content" id="main-content">                
                    
                    <!-- index datasets -->
                    <div v-if="active_container=='index-datasets'" class="index-datasets">
                
                    <div class="card">
                        <h5 class="card-header">Index Datasets</h5>
                        <div class="card-body">
                            <div class="card-text">
                                <p>Reindex all entries in the catalog</p>
                                <div class="form-groupx row">
                                    <div class="col-2">Rows</div>
                                    <div class="col"><input class="form-controlx" size="6" type="text" v-model="dataset_rows_limit" /> <span class="text-secondary">No. of documents to process per request</span></div>
                                </div>
                                <div class="row">
                                    <div class="col-2">Start row#</div>
                                    <div class="col-md-1"><input class="form-controlx" size="6" type="text" v-model="dataset_start_row" /></div>
                                </div>
                            </div>
                            <hr>

                            <div class="row">
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-primary btn-sm" v-on:click="indexDatasetOnClick">Start</button>
                                </div>

                                <div class="col">
                                    <div v-if="indexing_processed">
                                        <div>Indexed {{indexing_status}} - last row# : {{dataset_last_row_processed}}</div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    </div>
                    <!-- end index datasets -->



                    <!-- index variables -->
                    <div v-if="active_container=='index-variables'" class="index-variables">
                
                    <div class="card">
                        <h5 class="card-header">Index Variables</h5>
                        <div class="card-body">
                            <div class="card-text">
                                <p>Reindex all variable entries in the catalog</p>
                                <div class="form-groupx row">
                                    <div class="col-2">Rows</div>
                                    <div class="col"><input class="form-controlx" size="6" type="text" v-model="var_rows_limit" /> <span class="text-secondary">No. of documents to process per request</span></div>
                                </div>
                                <div class="row">
                                    <div class="col-2">Start row#</div>
                                    <div class="col-md-1"><input class="form-controlx" size="6" type="text" v-model="var_start_row" /></div>
                                </div>
                            </div>
                            <hr>

                            <div class="row">
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-primary btn-sm" v-on:click="indexVariablesOnClick">Start</button>
                                </div>

                                <div class="col">
                                    <div v-if="indexing_processed">
                                        <div>Indexed {{indexing_status}} - last row# : {{variable_last_row_processed}}</div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    </div>
                    <!-- end index variables -->

                                        
                    </div>
                <!--end-->
            </div>
            <!-- Main Col END -->

            <div class="col col-md-2">
                
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Solr status</h5>
                    <div class="card-text">
                        <div v-if="ping_status.data">Solr Status: {{ping_status.data.result.status}}</div>
                        
                        <hr>
                        <div v-if="index_counts.data">
                            <div>SOLR Index:</div>
                            <div>Documents: {{index_counts.data.result.datasets}}</div>
                            <div>Variables: {{index_counts.data.result.variables}}</div>
                            <div>Citations: {{index_counts.data.result.citations}}</div>
                            <div>Last dataset ID: {{index_counts.data.result.last_dataset}}</div>
                            <div>Last variable ID: {{index_counts.data.result.last_variable}}</div>
                            <hr>
                        </div>

                        <div v-if="db_counts.data">
                            <div>Database:</div>
                            <div>Documents: {{db_counts.data.result.datasets}}</div>
                            <div>Variables: {{db_counts.data.result.variables}}</div>
                            <div>Citations: {{db_counts.data.result.citations}}</div>
                        </div>
                    </div>                    
                </div>
            </div>

            
            </div>

        </div><!--end app -->
    </div>
</div>






<script>

        var app = new Vue({
            el: '#app',
            data:{
                solr_base_url:'',
                ping_status:{},
                index_counts:{},
                db_counts:{},
                active_container:'index-datasets',
                errors:[],
                dataset_rows_limit:15,
                dataset_start_row:0,
                dataset_last_row_processed:0,
                indexing_running:false,
                indexing_status:0,
                indexing_processed:0,
                
                var_rows_limit:1000,
                var_start_row:0,
                variable_last_row_processed:0,

            },
            mounted: function () {
               this.pingSolr();
            },
            computed: {
                
            },
            methods: {    
                
                pingSolr: function (){
                    let url=CI.base_url + '/api/solr/ping';
                    vm=this;
                    axios.get(url)
                    .then(function (response) {
                        console.log(response);                        
                        vm.ping_status=response;
                        vm.getIndexCounts();
                        vm.getDbCounts();
                    })
                    .catch(function (error) {
                        console.log(error);
                    })
                    .then(function () {
                        // always executed
                        console.log("request completed");
                    });
                },
                commitSolr: function (){
                    let url=CI.base_url + '/api/solr/commit';
                    vm=this;
                    axios.get(url)
                    .then(function (response) {
                        console.log(response);
                        vm.pingSolr();
                    })
                    .catch(function (error) {
                        console.log(error);
                    })
                    .then(function () {
                        // always executed
                        console.log("request completed");
                    });
                },
                clearSolr: function (){
                    if (!confirm("Are you sure you want to clear the index? This will remove all documents from the index?")){
                        return false;
                    }
                    
                    let url=CI.base_url + '/api/solr/clear_index';
                    vm=this;
                    axios.get(url)
                    .then(function (response) {
                        console.log(response);
                        vm.pingSolr();
                        alert("All documents have been removed from the index");
                    })
                    .catch(function (error) {
                        console.log(error);
                    })
                    .then(function () {
                        // always executed
                        console.log("request completed");
                    });
                },
                getIndexCounts: function(){
                    let url=CI.base_url + '/api/solr/index_counts';
                    vm=this;
                    axios.get(url)
                    .then(function (response) {
                        console.log(response);                        
                        vm.index_counts=response;                        
                    })
                    .catch(function (error) {
                        console.log(error);
                    })
                    .then(function () {
                        // always executed
                        console.log("request completed");
                    });
                },
                getDbCounts: function(){
                    let url=CI.base_url + '/api/solr/db_counts';
                    vm=this;
                    axios.get(url)
                    .then(function (response) {
                        console.log(response);                        
                        vm.db_counts=response;                        
                    })
                    .catch(function (error) {
                        console.log(error);
                    })
                    .then(function () {
                        // always executed
                        console.log("request completed");
                    });
                },
                toggleTab: function(tab){
                    this.active_container=tab;
                    this.indexing_processed=0;
                },
                clearAll: function(){
                    alert('clear all');
                },
                indexDatasetOnClick: function()
                {
                    this.indexing_processed=0;
                    this.indexDatasets(this.dataset_start_row,this.dataset_rows_limit);
                },
                indexDatasets: function (start_row=0, limit=5, processed=0)
                {
                    let url=CI.base_url + '/api/solr/full_import_surveys/'+start_row + '/'+limit;
                    vm=this;
                    this.indexing_running=true;
                    axios.get(url)
                    .then(function (response) {
                        last_row_id=response.data.result.last_row_id;
                        rows_processed=response.data.result.rows_processed;                        

                        if(last_row_id>0){
                            processed+=rows_processed;
                            vm.indexing_processed=processed;
                            vm.indexing_status=processed;
                            vm.dataset_last_row_processed=last_row_id;
                            vm.indexDatasets(last_row_id,vm.dataset_rows_limit,processed);
                        }
                        else{
                            vm.indexing_status=processed;
                            vm.indexing_running=false;
                            vm.commitSolr();
                            return true;
                        }                        
                    })
                    .catch(function (error) {
                        console.log(error);
                    })
                    .then(function () {
                        // always executed
                        console.log("request completed");
                    });
                },
                indexVariablesOnClick: function()
                {
                    this.indexing_processed=0;
                    this.indexVariables(this.var_start_row,this.var_rows_limit);
                },
                indexVariables: function (start_row=0, limit=5, processed=0)
                {
                    let url=CI.base_url + '/api/solr/full_import_variables/'+start_row + '/'+limit;
                    vm=this;
                    this.indexing_running=true;
                    axios.get(url)
                    .then(function (response) {
                        last_row_id=response.data.result.last_row_id;
                        rows_processed=response.data.result.rows_processed;                        

                        if(last_row_id>0){
                            processed+=rows_processed;
                            vm.indexing_processed=processed;
                            vm.indexing_status=processed;
                            vm.variable_last_row_processed=last_row_id;
                            vm.indexVariables(last_row_id,vm.var_rows_limit,processed);
                        }
                        else{
                            vm.indexing_status=processed;
                            vm.indexing_running=false;
                            vm.commitSolr();
                            return true;
                        }                        
                    })
                    .catch(function (error) {
                        console.log(error);
                    })
                    .then(function () {
                        // always executed
                        console.log("request completed");
                    });
                }
            }

        })
    </script>


    
</body>

</html>


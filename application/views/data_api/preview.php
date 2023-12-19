<script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
<script src="https://unpkg.com/vuex@3.4.0/dist/vuex.js"></script>
<script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@6.x/css/materialdesignicons.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>

<style>
  .v-pagination{
    display: -ms-flexbox;
    display: flex;
    padding-left: 0;
    list-style: none;
    border-radius: 0.25rem;
  }

  .v-pagination li button{
    position: relative;
    display: block;
    margin-left: -1px;
    line-height: 1.25;
    color: #007bff;
    background-color: #fff;
    border: 1px solid #dee2e6;
    padding: 0.25rem 0.5rem;
    font-size: .875rem;
  }

  .theme--light.v-pagination .v-pagination__item--active{
    z-index: 1;
    color: #fff;
    background-color: #007bff;
    border-color: #007bff;
  }

  .v-pagination__more{
    padding:6px;
  }

  .table-sm td,
  .table-sm th {
    font-size:12px;
  }

  .options-container{
    background:#e9ecef;
  }


  /* sticky table header */
  .sticky-table-header {
   overflow-y: auto;
   height: 15em;
  }

  .sticky-table-header thead th {
    position: sticky;
    top: 0;
    background:white;
  }

  table.sticky-table-header:focus {
    border: #f00 solid 2px !important;
  }

</style>


<div id="app" class="container mt-3" data-app>
    
    <div ref="app_is_loading">Loading...</div>
    <template v-if="table_info && table_info.result && table_info.result.metadata">
      <h2><?php echo t('Dataset API');?></h2>
      
      <div v-if="table_info.result"><strong><?php echo t('Dataset');?></strong>: {{table_info.result.metadata.title}}</div>
      <div v-if="rows.total && table_info.result">
        <span><strong><?php echo t('Observations');?>:</strong> {{rows.total}}</span>
      </div>

      <div class="mt-3 mb-3" style="white-space:pre-line">{{table_info.result.metadata.description}}</div>

      <div class="mt-5 mb-3">
        <?php $this->load->view('data_api/api_usage.php');?>
      </div>
      
      <div id="data_explorer">
        <div class="p-1">
        <?php $this->load->view('data_api/data_explorer.php');?>
        </div>
      </div>

      <div class="pt-3">
          <?php $this->load->view('data_api/features.php');?>    
      </div>
    </template>

</div>

<script>

  let api_base_url="<?php echo site_url('api/tables');?>";
  let site_url="<?php echo site_url();?>";
  let db_id="<?php echo $db_id;?>";
  let table_id="<?php echo $table_id;?>";

  new Vue({
    el: "#app",
    vuetify: new Vuetify(),
    data: {      
      input: "",
      message:"",
      api_base_url:api_base_url,
      site_url:site_url,
      db_id: db_id,
      table_id:table_id,
      table_info:[],
      page:1,
      rows:[],
      tables:[],
      is_searching:false,
      selected_columns:[],
      data_loading:0,
      page_offset:0,
      page_limit:15,
      table_columns:[],
      table_columns_search:"",
      filter_op:["=",">","contains","does not contain"],
      pagesize_options:["15","50","100"],
      items: [],
      filters:[],
      query_url:""
    },
    mounted: function(){
      this.loadTableInfo();
      this.search(true);      
    },
    computed: {
      apiDatasetInfoUrl: function () {
        return this.api_base_url + '/info/' + this.db_id + '/' + this.table_id;
      },
      apiDatasetDataUrl: function () {
        return this.api_base_url + '/data/' + this.db_id + '/' + this.table_id;
      },
      tableColumns: function () {
        if (this.table_info.result && this.table_info.result.metadata){
          return this.table_info.result.metadata.data_dictionary.map(function (item) {
            return item.name;
          });
        }

      },
      tableColumnsDictionary: function () {
        if (this.table_info.result && this.table_info.result.metadata){
          let dict={};
          this.table_info.result.metadata.data_dictionary.forEach(function (item) {
            dict[item.name]=item;
          });

          return dict;
        }
      },
      tableColumnsDictionaryWithSelected: function () {
        if (this.selected_columns.length>0){
          let dict={};
          let vm=this;
          this.table_info.result.metadata.data_dictionary.forEach(function (item) {
            if(vm.selected_columns.includes(item.name)){
              dict[item.name]=item;
            }
          });

          return dict;
        }
        else{
          return this.tableColumnsDictionary;
        }
      },
      tableColumnsDictionaryWithSearch: function () {
        if(this.table_columns_search!=""){
          let dict={};
          let vm=this;
          this.table_info.result.metadata.data_dictionary.forEach(function (item) {
            if(item.name.toLowerCase().match(vm.table_columns_search.toLowerCase())){
              dict[item.name]=item;
            }
          });

          return dict;
        }
        else{
          return this.tableColumnsDictionary;
        }
      },
    },
    watch: {
      page: function (val) {
        if(val==1){
          this.page_offset=0;
        }
        else{
          this.page_offset=(val - 1) * this.page_limit;
        }
        this.search(false);
      },
      page_limit: function (val)
      {
        this.search(false);
      },
      selected_columns: function (val)
      {
        this.search(false);
      },
      /*filters: function (val)
      {
        this.search(false);
      },*/
      filters: {
            handler: function(newValue) {
                console.log("filter updated", newValue)
                this.page_offset=0;
                this.search(false);
            },
            deep: true
        }
    },    
    methods: {
      CopyQueryUrlToClipboard: function()
      {
        var $temp = $("<input>");
        $("body").append($temp);
        $temp.val(this.query_url).select();
        document.execCommand("copy");
        $temp.remove();
        alert("Copied to clipboard");
      },

      CopyJsonToClipboard: function()
      {
        var $temp = $("<input>");
        $("body").append($temp);
        $temp.val(JSON.stringify(this.rows)).select();
        document.execCommand("copy");
        $temp.remove();
        alert("Copied to clipboard");
      },

      addFilter(){
        this.filters.push({column: '', op: '' , value: ''});
      },

      removeFilter(index){
        this.filters.splice(index, 1);
      },
      
      getFiltersQuerystring: function()
      {
        var output="";
        this.filters.forEach(function (filter, index) {
          if(filter.value!==""){
            output+="&"+filter.column+"="+filter.value;
          }
        });

        return output;
      },

      getSelectedColumns: function()
      {
        let output=this.selected_columns.join();

        if (output!=""){
          return '&fields='+output;
        }

        return "";
      },

      columnsSelectAll: function()
      {
        this.selected_columns=[];
        vm=this;
        this.tableColumns.forEach(function (column_name, index) {
          vm.selected_columns.push(column_name);
        });        
      },

      columnsClear: function()
      {
        this.selected_columns=[];
      },

      search: function (first_load=true) 
      {
        this.is_searching=true;
        //this.rows=[];
        let url = `${this.api_base_url}/data/${this.db_id}/${this.table_id}?limit=${this.page_limit}&offset=${this.page_offset}` + this.getFiltersQuerystring() + this.getSelectedColumns();
        this.query_url=url;

        let vm=this;

          $.ajax
          ({
              type: "GET",
              url:  url,
              contentType: 'application/json',
              dataType: 'json',
              success: function (data) {
                vm.rows=data;
                vm.is_searching=false;
                vm.active_row=[];                
                /*if(first_load==true){
                  vm.table_columns=vm.get_table_columns();
                }*/
              },
              error: function(e){
                vm.is_searching=false;
                console.log(e);
                alert("failed" + e);                
              }
          })
        
      },
      loadTableInfo: function () 
      {
        let url = `${this.api_base_url}/info/${this.db_id}/${this.table_id}`;
        let vm=this;

          $.ajax
          ({
              type: "GET",
              url:  url,
              contentType: 'application/json',
              dataType: 'json',
              /*async: false,*/
              success: function (data) {
                vm.table_info=data;
                vm.$refs.app_is_loading.style.display="none";
              },
              error: function(e){
                  console.log(e);
                  vm.$refs.app_is_loading.style.display="none";
                  alert("failed to load table info" + e);
              }
          })
      },

    }
  });
</script>


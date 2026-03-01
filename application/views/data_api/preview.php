<script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>

<style>

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


  .pagination-sm .page-link {
    padding: 0.15rem 0.4rem;
    font-size: 0.75rem;
    line-height: 1.3;
  }

  .pagination-sm .page-item {
    margin: 0 0.1rem;
  }

  .pagination-sm .page-link i {
    font-size: 0.7rem;
  }

  /* Table elevation and scrollbars */
  .table-data-container {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-radius: 4px;
    overflow-x: scroll;
    overflow-y: scroll;
    scrollbar-width: thin;    
  }
  
  .table-data-container .table {
    margin-bottom: 0;
    min-width: 100%;
  }

  .table-data-container::-webkit-scrollbar {
    width: 11px;
    height: 11px;
    -webkit-appearance: none;
    display: block;
  }

  .table-data-container::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.15);
    border-radius: 6px;
    -webkit-box-shadow: inset 0 0 6px rgba(0,0,0,0.1);
  }

  .table-data-container::-webkit-scrollbar-thumb {
    background-color: rgba(0, 0, 0, 0.4);
    border-radius: 6px;
    border: 1px solid rgba(0, 0, 0, 0.15);
    -webkit-box-shadow: inset 0 0 6px rgba(0,0,0,0.2);
  }

  .table-data-container::-webkit-scrollbar-thumb:hover {
    background-color: rgba(0, 0, 0, 0.5);
  }

  .table-data-container::-webkit-scrollbar-corner {
    background: rgba(0, 0, 0, 0.15);
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

      <div class="mt-5 mb-3">
        <?php $this->load->view('data_api/bulk_downloads.php');?>
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
  let study_idno="<?php echo isset($idno) ? $idno : '';?>";
  let study_sid="<?php echo isset($sid) ? $sid : '';?>";

  new Vue({
    el: "#app",
    data: {      
      input: "",
      message:"",
      api_base_url:api_base_url,
      site_url:site_url,
      db_id: db_id,
      table_id:table_id,
      study_idno:study_idno,
      study_sid:study_sid,
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
      query_url:"",
      bulk_downloads:[],
      bulk_downloads_loading:false,
      table_fields:[],
      fields_loading:false
    },
    mounted: function(){
      this.loadTableInfo();
      this.search(true);
      this.loadBulkDownloads();
    },
    computed: {
      apiDatasetInfoUrl: function () {
        return this.api_base_url + '/info/' + this.db_id + '/' + this.table_id + '?data_dictionary=true';
      },
      apiDatasetDataUrl: function () {
        return this.api_base_url + '/data/' + this.db_id + '/' + this.table_id;
      },
      apiBulkDownloadsUrl: function () {
        if (!this.study_idno) return '';
        return this.site_url + '/api/downloads/' + this.study_idno + '/files?type=data';
      },
      tableColumns: function () {
        // Use data_dictionary from metadata (backward compatibility) or table_fields
        const dataDict = this.getDataDictionary();
        if (dataDict && Array.isArray(dataDict)){
          return dataDict.map(function (item) {
            return item.name;
          });
        }
        return [];
      },
      tableColumnsDictionary: function () {
        // Use data_dictionary from metadata (backward compatibility) or table_fields
        const dataDict = this.getDataDictionary();
        if (dataDict && Array.isArray(dataDict)){
          let dict={};
          dataDict.forEach(function (item) {
            dict[item.name]=item;
          });
          return dict;
        }
        return {};
      },
      tableColumnsDictionaryWithSelected: function () {
        if (this.selected_columns.length>0){
          let dict={};
          let vm=this;
          const dataDict = this.getDataDictionary();
          if (dataDict && Array.isArray(dataDict)){
            dataDict.forEach(function (item) {
              if(vm.selected_columns.includes(item.name)){
                dict[item.name]=item;
              }
            });
          }
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
          const dataDict = this.getDataDictionary();
          if (dataDict && Array.isArray(dataDict)){
            dataDict.forEach(function (item) {
              if(item.name.toLowerCase().match(vm.table_columns_search.toLowerCase())){
                dict[item.name]=item;
              }
            });
          }
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
      // Get data dictionary from metadata (backward compatibility) or from separate fields
      getDataDictionary: function() {
        // First try backward compatibility: data_dictionary in metadata
        if (this.table_info.result && 
            this.table_info.result.metadata && 
            this.table_info.result.metadata.data_dictionary &&
            Array.isArray(this.table_info.result.metadata.data_dictionary) &&
            this.table_info.result.metadata.data_dictionary.length > 0) {
          return this.table_info.result.metadata.data_dictionary;
        }
        // Fallback to separate fields array
        if (this.table_fields && Array.isArray(this.table_fields) && this.table_fields.length > 0) {
          return this.table_fields;
        }
        // Return empty array if neither exists
        return [];
      },
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
                
                // If data_dictionary is not in metadata, load fields separately
                if (!data.result || 
                    !data.result.metadata || 
                    !data.result.metadata.data_dictionary ||
                    !Array.isArray(data.result.metadata.data_dictionary) ||
                    data.result.metadata.data_dictionary.length === 0) {
                  vm.loadTableFields();
                }
              },
              error: function(e){
                  console.log(e);
                  vm.$refs.app_is_loading.style.display="none";
                  alert("failed to load table info" + e);
              }
          })
      },
      loadTableFields: function () 
      {
        // Only load if fields haven't been loaded yet
        if (this.fields_loading || (this.table_fields && this.table_fields.length > 0)) {
          return;
        }

        this.fields_loading = true;
        let url = `${this.api_base_url}/fields/${this.db_id}/${this.table_id}`;
        let vm=this;

        $.ajax
        ({
            type: "GET",
            url:  url,
            contentType: 'application/json',
            dataType: 'json',
            success: function (data) {
              if (data.status === 'success' && data.fields && Array.isArray(data.fields)) {
                vm.table_fields = data.fields;
              } else {
                vm.table_fields = [];
              }
              vm.fields_loading = false;
            },
            error: function(e){
                console.log('Failed to load table fields:', e);
                vm.table_fields = [];
                vm.fields_loading = false;
            }
        })
      },

      loadBulkDownloads: function()
      {
        if (!this.study_idno) {
          return;
        }

        this.bulk_downloads_loading = true;
        let url = `${this.site_url}/api/downloads/${this.study_idno}/files?type=data`;
        let vm = this;

        $.ajax({
          type: "GET",
          url: url,
          dataType: 'json',
          success: function(data) {
            if (data.status === 'success' && data.files) {
              vm.bulk_downloads = data.files;
            }
            vm.bulk_downloads_loading = false;
          },
          error: function(e) {
            vm.bulk_downloads_loading = false;
            console.log('No bulk downloads available', e);
          }
        });
      },

      getFormatIcon: function(format)
      {
        if (!format) return 'fas fa-file';
        
        const formatLower = format.toLowerCase();
        
        if (formatLower.includes('spss')) return 'fas fa-file-alt';
        if (formatLower.includes('stata') || formatLower.includes('dta')) return 'fas fa-file-alt';
        if (formatLower.includes('csv')) return 'fas fa-file-csv';
        if (formatLower.includes('zip') || formatLower.includes('compress')) return 'fas fa-file-archive';
        if (formatLower.includes('pdf')) return 'fas fa-file-pdf';
        if (formatLower.includes('excel') || formatLower.includes('xls')) return 'fas fa-file-excel';
        if (formatLower.includes('word') || formatLower.includes('doc')) return 'fas fa-file-word';
        if (formatLower.includes('sas')) return 'fas fa-file-code';
        if (formatLower.includes('text') || formatLower.includes('txt')) return 'fas fa-file-alt';
        
        return 'fas fa-file';
      },

      getFormatLabel: function(filename)
      {
        if (!filename) return '';
        
        // Extract file extension
        const ext = filename.split('.').pop().toLowerCase();
        
        return ext.toUpperCase();
      },

      formatDate: function(dateString)
      {
        if (!dateString) return '';
        
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString;
        
        return date.toLocaleDateString();
      }

    }
  });
</script>


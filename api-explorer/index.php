<!DOCTYPE html>
<html>
<head>
  <link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400,500,700,900" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/@mdi/font@4.x/css/materialdesignicons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Material+Icons" rel="stylesheet">

  <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.4.1.min.js"  integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo="  crossorigin="anonymous"></script>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">

  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui">

  <style>
  .row-container{
    border-bottom:1px solid gainsboro;
    margin-bottom:15px;
    padding-left:10px;
  }

  .table-info-container{
    padding:15px;
    background:#9e9e9e14;
    font-size:smaller;
  }
  
  </style>
</head>
<body>
  <div id="app">
  <v-app id="inspire">
    <v-app-bar
      app
      clipped-left
      color="amber"
    >
      <v-app-bar-nav-icon @click="drawer = !drawer"></v-app-bar-nav-icon>
      <span class="title ml-3 mr-5">API&nbsp;<span class="font-weight-light">Explorer</span></span>
      <v-text-field
        solo-inverted
        flat
        hide-details
        label="Search"
        prepend-inner-icon="search"
      ></v-text-field>
      <v-spacer></v-spacer>
    </v-app-bar>

    <v-navigation-drawer
      v-model="drawer"
      app
      clipped
      color="grey lighten-4"
      width="300px"
    >
      
	  
	  
	  
	  <v-list
        dense
        class="grey lighten-4"
      >
      
      <template>
     <!-- tables -->
     <div class="row-container" v-for="(table, index) in tables">
              <div class="row-body">
                  <h6 v-on:click="tableInfo(table.dataset,table._id)">{{table.title}}</h6>
                  <h6>{{table.dataset}} {{table.description}} <span>{{table._id}}</span></h6>
                  {{selected_table_id}}
                  
              </div>
            </div>  
            <!--end tables-->
    </template>
        
      </v-list>
    </v-navigation-drawer>

    <v-content>
      <v-container
        fluid
        fill-heightz
        class="grey lighten-4"
      >
	  
	 
	  
	  <template>
		  <div class="table-info-container" v-show="selected_table_id!=null" class="collapse" >
        <v-row>
          <v-col cols="12" md="12" v-if="selected_table !==null">

            <h2>Table: {{selected_table_id}}</h2>

            <div>
            <v-text-field
            label="Regular"
            placeholder="Placeholder"
          ></v-text-field>

          <v-text-field
            label="Regular"
            placeholder="Placeholder"
          ></v-text-field>

          <v-text-field
            label="Regular"
            placeholder="Placeholder"
          ></v-text-field>
            </div>



            <!-- indicator  -->
            <h6>Indicator</h6>
            <table class="table tables-sm table-hover">
            <tr>
              <th>Code</th>
              <th>Label</th>
              <th>Measurement unit</th>
            </tr>
            <tr v-for="indicator in selected_table.result_.indicator">                        
              <td><input type="checkbox" v-model='indicator.code'/></td>
              <td>{{indicator.code}}</td>
              <td>{{indicator.label}}</td>
              <td>{{indicator.measurement_unit}}</td>
            </tr>
            </table>

            <h6>Features</h6>
            <!-- features info -->
            
            <div v-for="feature in selected_table.result_.features">
              
              <div><strong>{{feature.feature_label}}</strong> [<small>{{feature.feature_name}} </small>]</div>
            
                <table class="table table-sm table-hover">
                <!-- feature code lists -->
                <tr v-for="code in feature.code_list">
                  <td><input type="checkbox" v-model='code.code'/></td>
                  <td>{{code.code}}</td>
                  <td>{{code.label}} </td>
                </tr>
                <!-- end feature code lists -->
                </table>
            </div>

          </v-col>
        </v-row>
      </div>

	</template>
	   
        
      </v-container>
    </v-content>
  </v-app>
</div>

  <script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
  
  
  <script>
    new Vue({
  el: '#app',
  vuetify: new Vuetify(),
  data: {
    databases: null,
    tables:[],
    tables_storage:null,
    states:null,
    drawer:null,
    selected_table:null,
    selected_table_toggle:false,
    selected_table_id:null,
    ajax_completed:false,
    districts:null,
    api_base_url:'https://dev.ihsn.org/orgi/digital-library/index.php/api/'
	},
  mounted: function() {
    this.getTablesList();
  },
  computed: {    
    tablesFound(){
      return this.tables.length;
    }
  },
  watch: {
    // whenever question changes, this function will run
    selected_table: function (new_, old_) {
      this.selected = 'Waiting for you to stop typing...'      
    }
  },
  methods: {
    getDatabases: function () {
      return ['2011','2001']
    },
    tableInfo: function(database,table_id){
      let vm=this;
      
      $.ajax
        ({
            type: "GET",
            //the url where you want to sent the userName and password to
            url: vm.api_base_url+'tables/info/'+database+'/'+table_id,
            contentType: 'application/json',
            dataType: 'json',
            async: false,
            success: function (data) {
              vm.selected_table=data.result;
              vm.selected_table_id=data.result.result_._id;
              vm.ajax_completed=true;
              console.log(data);
              console.log(vm.selected_table_id);
              //vm.resetState();
            },
            error: function(e){
                console.log(e);
                alert("failed" + e);
            }
        })
    },
    getTablesList: function (){
      
      let vm=this;
      
      $.ajax
        ({
            type: "GET",
            //the url where you want to sent the userName and password to
            url: vm.api_base_url+'tables/list/2011',
            contentType: 'application/json',
            dataType: 'json',
            async: false,
            success: function (data) {
              vm.tables=data.tables;
              vm.tables_storage=data.tables_storage;
              //vm.resetState();
            },
            error: function(e){
                console.log(e);
                alert("failed" + e);
            }
        })
    
    
    }

  }
})
  </script>
  
</body>
</html>
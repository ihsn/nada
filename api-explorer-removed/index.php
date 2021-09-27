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
                  <h6 v-on:click="tableInfo(table.db_id,table.table_id)">{{table.title}}</h6>
                  <div>
                    <span>{{table.dataset}} {{table.description}}</span> 
                    <span>{{table.db_id}}/{{table.table_id}}</span>
                    <span>{{table.storage_size}}</span>
                  </div>
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

            <pre>
            {{states}}
            </pre>

            <div style="background:gainsboro;padding:15px;">
              <v-text-field
                label="API URL"
                placeholder="API URL"
                v-model="GetApiUrl"
              >
              </v-text-field>
            </div>

            <template>
            <div>
              <vue-tags-input
                v-model="state"
                :tags="states"
                :autocomplete-items="autocompleteStates"
                :add-only-from-autocomplete="true"
                @tags-changed="updateStates"
              />
            </div>
          </template>

          <template>
          <div>
          <vue-tags-input
                v-model="district"
                :tags="districts"
                :autocomplete-items="autocompleteDistricts"
                :add-only-from-autocomplete="true"
                @tags-changed="updateDistricts"
              />
            </div>
          </template>

            <div>
            <v-text-field
            label="State"
            placeholder="State name"
          ></v-text-field>

          <v-text-field
            label="District"
            placeholder="District"
          ></v-text-field>

          <v-text-field
            label="Subdistrict"
            placeholder="Subdistrict"
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
              <td><input type="checkbox" :value="indicator.code" v-model='indicator.isSelected'/></td>
              <td>{{indicator.code}}</td>
              <td>{{indicator.label}}</td>
              <td>{{indicator.measurement_unit}}</td>
            </tr>
            </table>

            <h6>Features</h6>
            <!-- features info -->
            
          
            <feature-component :feature="feature" v-for="feature in selected_table.result_.features"></feature-component>

<pre>{{selected_table.result_.features}}</pre>
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
  <script src="https://unpkg.com/@johmun/vue-tags-input/dist/vue-tags-input.js"></script>
  

  
  <script>

  Vue.component('feature-component', {
      props: ["feature"],
      template: `        
          <div>
          <div><strong>{{feature.feature_label}}</strong> [<small>{{feature.feature_name}} </small>]</div>
              
          <table class="table table-sm table-hover">
          <!-- feature code lists -->
          <tr v-for="code in feature.code_list">
            <td><input type="checkbox" :value="code.code" v-model='code.isSelected'/></td>
            <td>{{code.code}}</td>
            <td>{{code.label}} </td>
          </tr>
          <!-- end feature code lists -->
          </table>
          </div>
          `
          ,
      watch: {
          person: {
              handler: function(newValue) {
                  console.log("Person with ID:" + newValue.id + " modified")
                  console.log("New age: " + newValue.age)
              },
              deep: true
          }
      }
  });


    new Vue({
  el: '#app',
  vuetify: new Vuetify(),
  data: {
    databases: null,
    tables:[],
    tables_storage:null,
    state:'',
    states:[],
    autocompleteStates: [],
    district:'',
    districts:[],
    autocompleteDistricts: [],
    debounce: null,
    
    drawer:null,
    selected_table:null,
    selected_table_options:{
        'indicators':[],
        'features':[],
        'state':'',
        'geo_level':''
    },
    selected_table_toggle:false,
    selected_table_id:null,
    ajax_completed:false,
    api_base_url:'http://digital-library.census.ihsn.org/index.php/api/'
	},
  mounted: function() {
    this.getTablesList();
  },
  computed: {    
    tablesFound(){
      return this.tables.length;
    },

    GetApiUrl(){
      data=this.selectedTableOptions;
      options={}
      for(item in data){
        option=data[item];
        console.log(item);
        options[item]=option.join(",");
      }
      return this.api_base_url + 'tables/data/2011/' + this.selected_table_id + '/?' + $.param(options);
    },
    selectedTableOptions()
    {
      options={};
      for(feature_idx in this.selected_table.result_.features){
        feature=this.selected_table.result_.features[feature_idx];
        
        for(code_idx in feature["code_list"]){
          code=feature["code_list"][code_idx];
          console.log(code);
          if (code["isSelected"]!== undefined && code["isSelected"]==true){
            if(options[feature["feature_name"]]== undefined){
              options[feature["feature_name"]]=[];
            }            
            options[feature["feature_name"]].push(code["code"]);
          }
        }        
      }

      for(indicator_idx in this.selected_table.result_.indicator){
        indicator=this.selected_table.result_.indicator[indicator_idx];
        console.log("indicator");
        console.log(indicator);

        if (indicator["isSelected"]!== undefined && indicator["isSelected"]==true){
            if(options["indicator"]== undefined){
              options["indicator"]=[];
            }            
            options["indicator"].push(indicator["code"]);
          }        
      }

      return options;
    }
  },
  watch: {
    // whenever question changes, this function will run
    xselected_table: function (new_, old_) {
      this.selected = 'Waiting for you to stop typing...'      
    },
    state: 'fetchStates',
    district: 'fetchDistricts',
    selected_table: {
            handler: function(newValue) {
                console.log("Person with ID:" + newValue + " modified")
                console.log(newValue)
            },
            deep: true
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
              vm.selected_table_id=data.result.result_.table_id;
              vm.ajax_completed=true;
              console.log(data);
              console.log(vm.selected_table_id);

              vm.selected_table_options.features={};
              for(feature in vm.selected_table.result_.features){
                console.log(feature);
                vm.selected_table_options.features[vm.selected_table.result_.features[feature]["feature_name"]]=[];
              }

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
    
    
    },



    updateStates(newStates) {
      this.autocompleteStates = [];
      this.states = newStates;
    },
    updateDistricts(newDistricts) {
      this.autocompleteDistricts = [];
      this.districts = newDistricts;
    },
    fetchStates() {
      if (this.state.length < 2) return;
      const url = `${this.api_base_url}tables/geosearch/2011?areaname=${this.state}&limit=6`;

      console.log(url);

      let vm=this;

      clearTimeout(this.debounce);
      this.debounce = setTimeout(() => {
        
        $.ajax
        ({
            type: "GET",
            //the url where you want to sent the userName and password to
            url:  `${this.api_base_url}tables/geosearch/2011?level=state&areaname=${vm.state}&limit=6`,
            contentType: 'application/json',
            dataType: 'json',
            async: false,
            success: function (data) {
              vm.autocompleteStates = data.data.map(a => {
                return { text: a.areaname, id:a.state };
              });
            },
            error: function(e){
                console.log(e);
                alert("failed" + e);
            }
        })
      }, 600);
    },

    fetchDistricts() {
      if (this.district.length < 2) return;
      var url = `${this.api_base_url}tables/geosearch/2011?level=district&areaname=${this.district}&limit=6`;      

      //filter by states if any selected
      if (this.states.length > 0){
        states_list=[];
        for(idx in this.states){
          states_list.push(this.states[idx]["id"]);
        }
        url+="&state="+states_list.join(",");
      }

      console.log(url);

      let vm=this;

      clearTimeout(this.debounce);
      this.debounce = setTimeout(() => {
        
        $.ajax
        ({
            type: "GET",
            //the url where you want to sent the userName and password to
            url:  url,
            contentType: 'application/json',
            dataType: 'json',
            async: false,
            success: function (data) {
              vm.autocompleteDistricts = data.data.map(a => {
                return { text: a.areaname, id:a.district };
              });
            },
            error: function(e){
                console.log(e);
                alert("failed" + e);
            }
        })


      }, 600);
    },

  }
})
  </script>
  
</body>
</html>
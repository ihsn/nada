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
    >
      
	  
	  
	  
	  <v-list
        dense
        class="grey lighten-4"
      >
      
      <template>
      <v-list-item>
        <v-list-item-content>
          <v-list-item-title>Single-line item</v-list-item-title>
        </v-list-item-content>
    </v-list-item>

    <v-list-item>
        <v-list-item-content>
          <v-list-item-title>Single-line item</v-list-item-title>
        </v-list-item-content>
    </v-list-item>
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
	  <v-container class="grey lighten-5">

    <h1>Found: {{tablesFound}}</h1>
		
		<v-row>
		  <v-col cols="12" md="8">

            <!-- tables -->
            <v-row class="row-container" v-for="(table, index) in tables">
              <div class="row-body">
                  <h5>{{table.title}}</h5>
                  <h6>{{table.dataset}} {{table.description}}</h6>
              </div>
            </v-row>  
            <!--end tables-->

		  </v-col>

		  <v-col cols="6" md="4">			md-4
		  </v-col>
		</v-row>

		

		
	  </v-container>
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
  data: () => ({
    databases: null,
    tables:[],
    tables_storage:null,
    states:null,
    drawer:null,
    districts:null,
    api_base_url:'https://dev.ihsn.org/orgi/digital-library/index.php/api/'
	}),
  mounted: function() {
    this.getTablesList();
  },
  computed: {    
    tablesFound(){
      return this.tables.length;
    }
  },
  methods: {
    getDatabases: function () {
      return ['2011','2001']
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
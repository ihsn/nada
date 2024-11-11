<html>
<head>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.7.16/dist/vue.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
    <link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400,500,700,900" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@6.x/css/materialdesignicons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">


    <style>
        .table-header-th{
            font-weight:bold!important;            
            font-family:"monospace", monospace, sans-serif, serif;
            font-size:larger;
        }
        .th-secondary-text{
            color:grey;
            font-family:"monospace", monospace, sans-serif, serif;
        }
        .th-secondary-header{
            background-color:#f5f5f5;
            font-family:"monospace", monospace, sans-serif, serif;
        }
        .font-mono{
            font-family:"monospace", monospace, sans-serif, serif;
        }

        .data-grid tbody tr:nth-of-type(odd) {
            background-color: rgb(158 158 158 / 7%);
            }
    </style>
</head>
<body>
    <div id="app">
        <v-app>

        <v-card class="ma-2 elevation-4">            
            <v-card-text>
            <div v-if="geographies.length > 0">                                        
                    <v-chip small close outlined pill @click="geographies=[]" @click:close="geographies=[]">
                        <v-icon small>mdi-filter-outline</v-icon>
                        <strong>{{dsdGeography}}:</strong>&nbsp; {{GeographiesString}}
                    </v-chip>                
            </div>
                <v-data-table          
                    :headers="getHeaders"  
                    :items="dataset.data"
                    :items-per-page="15"
                    class="font-mono data-grid"
                    :loading="dataset_isloading"
                    loading-text="Loading... Please wait"
                    hide-default-header
                    :server-items-length="dataset.found"
                    :options.sync="options"
                    :footer-props="{
                        'items-per-page-options': [5, 15, 30, 40, 50]
                    }"
                    fixed-header
                    height="85vh"
                >
                <template v-slot:header="{ props: { headers } }">
                <thead>
                <tr>
                    <th v-for="h in headers" >
                        <span class="table-header-th">{{h.text}}</span>
                    </th>
                </tr>
                <tr>
                    <th v-for="h in headers" class="th-secondary-header">
                        <div class="th-secondary-text">{{h.label}}<br/>
                        ({{h.data_type}})                        
                    </th>
                </tr>
                </thead>
                </template>
            
                </v-data-table>
        </v-card-text>
        </v-card>

        </v-app>
    </div>

    <script>
        new Vue({
            el: '#app',
            vuetify: new Vuetify(),
            data: {
                db_id:'',
                series_id:'',
                series_title:'',
                dataset:[],
                dataset_isloading:false,
                dsd:[],
                api_base_url:'https://data-compass.ihsn.org/index.php/api/',
                options: {},
                grid_height: '500px',
                geographies: [],
                filters: [],
            },
            async mounted() {
                //load db_id and series_id from url query string
                this.db_id = new URLSearchParams(window.location.search).get('db_id');
                this.series_id = new URLSearchParams(window.location.search).get('series_id');
                let countries = new URLSearchParams(window.location.search).get('geography');   
                let grid_height = new URLSearchParams(window.location.search).get('grid_height');

                if (grid_height){
                    this.grid_height = grid_height;
                }

                if (countries){
                    this.geographies = countries.split('|');
                }


                this.loadDataStructure();
            },
            watch:{
                options: {
                    handler () {
                        this.loadData();
                    },
                    deep: true,
                },
                geographies: {
                    handler () {
                        this.loadData();
                    },
                    deep: true,
                }
            },
            methods:{   
                removeGeography: function(geo){
                    this.geographies = this.geographies.filter(item => item !== geo);
                },    
                getPaginationOffset: function(){
                    return (this.options.page-1)*this.options.itemsPerPage;
                },
                loadData: function(){
                    this.dataset_isloading = true;

                    const { sortBy, sortDesc, page, itemsPerPage } = this.options;
                    
                    let url = this.api_base_url + 'timeseries/data/'+this.db_id+'/'+this.series_id;
                    url += '?limit=' + itemsPerPage + '&offset=' + this.getPaginationOffset();

                    if (this.geographies.length > 0 && this.dsdGeography){
                        url += '&c[' + this.dsdGeography + ']=' + this.geographies.join('|');
                    }
                    
                    axios.get(url)
                    .then(response => {
                        this.dataset_isloading = false;
                        if (response.data && response.data.data){
                            this.dataset = response.data.data;
                        }
                        console.log(response.data);
                    })
                },
                loadDataStructure: function()
                {
                    this.dataset_isloading = true;
                    let url = this.api_base_url + 'timeseries/data_structure/'+this.db_id+'/'+this.series_id;
                    axios.get(url)
                    .then(response => {
                        console.log("data structure",response.data);
                        if (response.data && response.data.data_structure){
                            //this.series_title = response.data.data[0].title;
                            this.dsd = response.data.data_structure;
                            this.loadData();
                        }                        
                    })
                },
            },
            computed: {
                getHeaders: function(){
                    let headers = [];
                    /*format = {
                        text: 'Dessert (100g serving)',
                        align: 'start',
                        sortable: false,
                        value: 'ds_id',
                    }*/

                    this.dsd.forEach(function (field, index) {
                        headers.push({
                            text: field.name,
                            value: field.name,
                            label: (field.label) ? field.label: field.name,
                            data_type: field.data_type,
                            align: (field.data_type=='string') ? 'left':'right',
                        });
                    });

                    return headers;
                },
                dsdGeography: function(){                    
                    let item= this.dsd.filter(item => item.column_type.toLowerCase() == 'geography');
                    if (item.length > 0){
                        return item[0].name;
                    }
                },
                GeographiesString: function(){
                    return this.geographies.join(' | ');
                }
            }
        });
    </script>
</body>
</html>
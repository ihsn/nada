<html>
<head>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.7.16/dist/vue.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/echarts/dist/echarts.min.js"></script>

    <style>
        .chart-panel {    
            box-sizing: border-box;
            box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 20px;
            border-radius: 5px;
            background: #fff;
            overflow: hidden;
            padding: 10px;
            width: 100%;
            height:100%;
            position: relative;
        }
    </style>
</head>
<body>
    <div id="app">
        <div class="chart-panel">
            <div id="chart" :style="'width: ' + chart_width + '; height: ' + chart_height + ';'"></div>
        </div>
    </div>

    <script>
        new Vue({
            el: '#app',
            data: {
                db_id:'',
                series_id:'',
                geographies:[],                
                dataset:[],
                dsd:[],
                //computed from dataset
                dataset_timeperiods:[],
                dataset_geographies:[],
                dataset_geographies_labels:[],
                dataset_values:[],
                //geographies referenced by series
                series_geographies:[],
                api_base_url:'https://data-compass.ihsn.org/index.php/api/',
                chart_height:'100%',
                chart_width:'100%'
            },
            async mounted() {
                //load db_id and series_id from url query string
                this.db_id = new URLSearchParams(window.location.search).get('db_id');
                this.series_id = new URLSearchParams(window.location.search).get('series_id');
                let countries = new URLSearchParams(window.location.search).get('geography');

                let chart_height = new URLSearchParams(window.location.search).get('height');
                let chart_width = new URLSearchParams(window.location.search).get('width');

                if (this.validateChartWidthOrHeight(chart_height)){
                    this.chart_height = chart_height;
                }

                if (this.validateChartWidthOrHeight(chart_width)){
                    this.chart_width = chart_width;
                }

                await this.loadAvailableGeographies();

                if (countries){
                    this.geographies = countries.split('|');
                }else{
                    this.geographies=[];
                    this.geographies.push(this.randomGeography);
                }
                
                this.loadDataStructure();
            },
            watch:{
                dataset: function(){                    
                    this.computeDatasetTimeperiods();
                    this.computeDatasetGeographies();
                    this.computeDatasetValues();
                    this.drawChart();
                }
            },
            methods:{   
                validateChartWidthOrHeight: function(val){

                    if (!val){
                        return false;
                    }

                    if (!val.endsWith('px') && !val.endsWith('em') && !val.endsWith('%') && !val.endsWith('vh') && !val.endsWith('vw')){
                        return false;
                    }
                    
                    let value=parseInt(val.slice(0, -2));

                    if (isNaN(value) || value < 0){
                        return false;
                    }

                    return true;

                },             
                drawChart(){
                        var chart = echarts.init(document.getElementById('chart'));

                        // Specify chart configuration
                        var option = {
                            title: {
                                text: ''
                            },
                            toolbox: {
                                feature: {
                                saveAsImage: {}
                                }
                            },
                            tooltip: {
                                trigger:'axis'
                            },
                            legend: {
                                //data: this.dataset_geographies//['Sales', 'Expenses', 'Profit']
                                data: this.dataset_geographies_labels
                            },
                            xAxis: {
                                data: this.dataset_timeperiods//['A', 'B', 'C', 'D', 'E', 'F']
                            },
                            yAxis: {},
                            series: this.dataset_values /*[
                                {
                                    name: 'Sales',
                                    type: 'line',
                                    data: [5, 20, 136, 10, 10, 20]
                                },
                                {
                                    name: 'Expenses',
                                    type: 'line',
                                    data: [15, 30, 6, 120, 20, 30]
                                },
                                {
                                    name: 'Profit',
                                    type: 'line',
                                    data: [10, 25, 40, 15, 15, 25]
                                }
                            ]*/,
                            dataZoom: [
                                {
                                    type: 'slider', // This is the default type
                                    start: 0,      // Start position of the data window
                                    end: 100       // End position of the data window
                                },
                                {
                                    type: 'inside' // Enables zooming by dragging or scrolling inside the chart
                                }
                            ]
                        };

                        // Use the specified configuration to display the chart
                        chart.setOption(option);

                        // Make the chart responsive
                        window.addEventListener('resize', function() {
                            chart.resize();
                        });
                },

                loadAvailableGeographies: async function(){
                    try {
                        let url = this.api_base_url + 'timeseries/geographies/'+this.db_id+'/'+this.series_id;
                        const response = await axios.get(url);
                        if (response.data && response.data.data){
                            this.series_geographies=response.data.data;
                        }                        
                    } catch (error) {
                        console.error('Error fetching data:', error);
                    }
                },
                loadData: function(){
                    let url = this.api_base_url + 'timeseries/data/'+this.db_id+'/'+this.series_id;
                    
                    //build query string and should look like this: ?c[dsdGeography]=Kenya|Uganda|Tanzania
                    url += '?limit=2000&c['+this.dsdGeography+']='+ encodeURIComponent(this.geographies.join('|'));
                    
                    //add fields
                    url += '&fields='+this.dsdTimeperiod+','+this.dsdValue+','+this.dsdGeography;

                    axios.get(url)
                    .then(response => {
                        if (response.data && response.data.data){
                            this.dataset = response.data.data;
                        }
                        console.log(response.data);
                    })
                },
                loadDataStructure: function()
                {
                    let url = this.api_base_url + 'timeseries/data_structure/'+this.db_id+'/'+this.series_id;
                    axios.get(url)
                    .then(response => {
                        if (response.data && response.data.data_structure){
                            this.dsd = response.data.data_structure;
                            this.loadData();
                        }
                        console.log(response.data);
                    })
                },
                computeDatasetTimeperiods: function()
                {
                    if (this.dataset.length == 0 || !this.dataset.data){
                        this.dataset_timeperiods = [];
                    }
                    
                    //get unique time periods
                    //this.dataset_timeperiods = [...new Set(this.dataset.data.map(item => item[this.dsdTimeperiod]))];                

                    //get min and max time period
                    let min_timeperiod = Math.min(...this.dataset.data.map(item => item[this.dsdTimeperiod]));
                    let max_timeperiod = Math.max(...this.dataset.data.map(item => item[this.dsdTimeperiod]));

                    //create time periods
                    this.dataset_timeperiods = Array.from({length: max_timeperiod - min_timeperiod + 1}, (_, i) => min_timeperiod + i);                    
                },
                computeDatasetGeographies: function()
                {
                    if (this.dataset.length == 0 || !this.dataset.data){
                        this.dataset_geographies = [];
                    }
                    
                    //get unique geographies
                    this.dataset_geographies = [...new Set(this.dataset.data.map(item => item[this.dsdGeography]))];
                    
                    //geographies labels
                    this.dataset_geographies_labels = this.dataset_geographies.map(item => this.findGeographyLabelByCode(item));
                },
                computeDatasetValues: function()
                {
                    if (this.dataset.length == 0 || !this.dataset.data){
                        this.dataset_values = [];
                    }

                    //transform data to format
                    /*{
                                    name: 'Sales',
                                    type: 'line',
                                    data: [5, 20, 136, 10, 10, 20]
                                },
                    */

                    //foreach all unique time periods and add the value or empty if not found
                    //map dataset obsvalue to 

                    let geography_column_name=this.dsdGeography;
                    let timeperiod_col_name=this.dsdTimeperiod;
                    let obs_value_col_name=this.dsdValue;

                    vm=this;
                    let values=[];

                    let series=[];

                    //iterate each geography
                    this.dataset_geographies.forEach((geography) =>{
                        //get values for the geography
                        let tmp_=[];
                        vm.dataset.data.forEach((item) => {    
                            if (item[geography_column_name] == geography){                                                            
                                tmp_[item[timeperiod_col_name]]=item[obs_value_col_name];
                            }
                        });

                        let geography_data=[];
                        this.dataset_timeperiods.forEach((timeperiod)=>{
                            let value_= (tmp_[timeperiod]) ? tmp_[timeperiod] : null;
                            geography_data.push(value_);
                        });
                        
                        //create series
                        series.push(
                            {
                                name: this.findGeographyLabelByCode(geography),
                                type: 'line',
                                data: geography_data
                            }
                        );

                    });
                    
                    this.dataset_values=series;
                    
                },
                findGeographyLabelByCode: function(code)
                {
                    if (!this.dsdGeographyCodeList){
                        return code;
                    }

                    let label = '';
                    this.dsdGeographyCodeList.forEach(item => {
                        if (item.code === code) {
                            label = item.label;
                        }
                    });
                    return label;
                }
            },
            computed: {
                dsdGeography: function(){                    
                    let item= this.dsd.filter(item => item.column_type.toLowerCase() == 'geography');
                    if (item.length > 0){
                        return item[0].name;
                    }
                },
                dsdGeographyCodeList: function(){                    
                    let item= this.dsd.filter(item => item.column_type.toLowerCase() == 'geography');
                    if (item.length > 0){
                        return item[0].code_list;
                    }
                },
                dsdTimeperiod: function(){
                    let item= this.dsd.filter(item => item.column_type.toLowerCase() == 'time_period');
                    if (item.length > 0){
                        return item[0].name;
                    }
                },
                dsdValue: function(){
                    let item= this.dsd.filter(item => item.column_type.toLowerCase() == 'observation_value');
                    if (item.length > 0){
                        return item[0].name;
                    }
                },
                randomGeography: function(){
                    if (this.series_geographies.length > 0) {
                        let item= this.series_geographies[Math.floor(Math.random() * this.series_geographies.length)];
                        console.log("random geo", item);
                        if (item.value){
                            return item.value;
                        }
                    } else {
                        return null;
                    }
                },
                geographyLabels: function(){
                    let labels=[];
                    this.geographies.forEach((item) => {
                        let label=this.findGeographyLabelByCode(item);
                        if (label){
                            labels.push(label);
                        }else{
                            labels.push(item);                            
                        }
                    });
                    return labels;
                }

            }

        });
    </script>
</body>
</html>
//vue grid component
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
            this.field_data.push({});
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
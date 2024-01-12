<div class="query_options">

    <div class="options-container mt-3 mb-3 p-3">

        <div class="filter-container border p-3 mt-3 bg-light">
            <h5><?php echo t('Filters');?></h5>

            <template v-for="(filter,filter_idx) in filters">
                <div class="row">
                    <div class="col">
                        <select class="form-control form-control-sm" v-model="filter.column">
                            <option v-for="(column,column_name) in tableColumnsDictionary" v-bind:value="column_name">
                                {{ column_name }}
                            </option>
                        </select>
                        <!--<span>Selected: {{ filter }}</span>-->
                    </div>

                    <!--
                        <div class="col">
                        <select class="form-control form-control-sm" v-model="filter.op">
                        <option v-for="operator in filter_op" v-bind:value="operator">
                            {{ operator }}
                        </option>
                        </select>                    
                    </div>
                    -->

                    <div class="col">
                        <input type="text" class="form-control form-control-sm" v-model="filter.value" placeholder="<?php echo t('Enter value');?>" />
                    </div>

                    <div class="col-md-1">
                        <v-btn @click="removeFilter(filter_idx)" icon color="black">
                            <v-icon dark center small>fas fa-trash</v-icon>
                        </v-btn>
                    </div>

                </div>
            </template>

            <button v-if="filters.length>0" class="btn btn-primary btn-sm mt-3" @click="addFilter"> <v-icon dark left x-small>fas fa-filter</v-icon> Add filter</button>

            <div v-if="filters.length == 0" class="mt-3">
                <button class="btn btn-outline-primary btn-sm" @click="addFilter"><?php echo t('Add filter');?></button>
            </div>
        </div>

        <div class="border p-3 my-3 bg-light">
            <div class="float-right">
                <button class="btn btn-outline-primary btn-sm" @click="columnsSelectAll"><?php echo t('Select all');?></button>
                <button class="btn btn-outline-primary btn-sm" @click="columnsClear"><?php echo t('Clear');?></button>
            </div>
            <h5><?php echo t('Fields');?></h5>

            <div class="input-group mb-3" style="width:300px">
                <input type="text" class="form-control form-control-sm" v-model="table_columns_search" placeholder="Search..." style="width:250px" />
                <div class="input-group-append">
                    <span v-if="table_columns_search.length>0" @click="table_columns_search=''" class="pl-2">
                        <button type="button" class="close" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </span>
                </div>
            </div>

            <div style="max-height:300px;overflow:auto;">
                <div class="row no-gutters">

                    <div class="col-md-3" v-for="(column,column_name) in tableColumnsDictionaryWithSearch">
                        <span>                            
                            <label :for="column_name" :title="column.label">
                                <span class="d-table-cell pr-1"><input type="checkbox" :id="column_name" :value="column_name" v-model="selected_columns"></span>
                                <span class="d-table-cell text-truncate" style="max-width: 150px;">
                                    {{column_name}}
                                </span>
                            </label>
                        </span>
                    </div>

                </div>
            </div>
        </div>

        <div>
            <?php /*
                <div class="border p-3 my-3 bg-light">
                  <label>Pagination:</label>
                  <select class="form-control" v-model="page_limit">
                    <option value="15">15</option>
                    <option value="30">30</option>
                    <option value="50">50</option>
                    <option value="50">100</option>                    
                  </select>

                  <select class="form-control" v-model="page_offset">
                    <option value="0">0</option>  
                    <option value="15">15</option>
                    <option value="30">30</option>
                    <option value="50">50</option>
                    <option value="50">100</option>
                    <option value="1000">1000</option>   
                  </select>
                  
                </div>
                */ ?>

            <div class="border p-3 my-3 bg-light">
                <div class="float-right">
                    <a href="#" title="Copy" @click="CopyQueryUrlToClipboard"><i class="far fa-clone"></i></a>
                </div>
                <label><strong><?php echo t('API query URL');?>:</strong></label>
                <div class="border p-1" style="font-size:small;overflow:auto">
                    <div class="text-monospace">{{query_url}}</div>
                </div>
            </div>


            <div class="border p-3 my-3 bg-light">
                <label><strong><?php echo t('API JSON response');?>:</strong></label>
                <div class="float-right">
                    <a href="#" title="Copy" @click="CopyJsonToClipboard"><i class="far fa-clone"></i></a>
                </div>
                <div class="border p-1" style="max-height:300px;overflow:auto;font-size:small;">
                    <pre style="word-wrap: break-word">{{rows}}</pre>
                </div>
            </div>

            <button type="button" class="btn btn-outline-primary btn-sm" @click="search"><?php echo t('Apply');?></button>
        </div>



    </div>
</div>
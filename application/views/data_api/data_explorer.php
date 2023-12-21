<div class="table-container" v-if="rows">

    <div class="row">
      <div class="col-10">
      <h3><?php echo t('Data explorer');?></h3>
      </div>

      <div class="col-2">
        <button class="btn btn-default btn-sm float-right" type="button" data-toggle="collapse" data-target="#queryOptions" aria-expanded="false" aria-controls="queryOptions">
        <?php echo t('API options');?> <v-icon small>fas fa-cog</v-icon>
        </button>
      </div>

      </div>

      <div class="collapse" id="queryOptions">

        <?php $this->load->view('data_api/api_query_options.php');?>
      </div>


        <div v-if="rows.total">
            <div class="float-right"><?php echo t('Total');?>: <strong>{{rows.total}}</strong> </div>
            <div>
            Showing <strong>{{rows.offset+1}}</strong> - <strong>{{rows.offset+rows.rows_count}}</strong> of <strong>{{rows.found}}</strong>
            </div>
        
        </div>
      <div v-if="is_searching">
        <span class="badge badge-info"><?php echo t('Loading data ...');?></span>
        <v-skeleton-loader
            type="table-tbody, table-tfoot"
            ></v-skeleton-loader>
      </div>
            
      <div v-if="rows.data" class="mh-100 overflow-auto py-2">
            <div class="table-data-container table-responsive" style="max-height:600px;">
                <table class="table table-striped table-bordered table-sm sticky-table-header">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th v-for="(column,column_name) in tableColumnsDictionaryWithSelected">
                            <div>{{column_name}}</div>
                        </th>
                    </tr>
                    <tr>
                        <th></th>
                        <th v-for="(column,column_name) in tableColumnsDictionaryWithSelected">
                            <div class="font-weight-normal mt-1">{{column.label}}</div>
                        </th>
                    </tr>
                    </thead>
                    <tr v-for="(row,row_index) in rows.data">
                        <td class="bg-light">{{row_index+1+page_offset}}</td>
                        <td v-for="(column,column_name) in tableColumnsDictionaryWithSelected"  class="text-break text-truncate" style="max-width:120px;">
                            <span>{{row[column_name]}}</span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <template v-if="rows.found">
          <div class="d-flex justify-content-between">
            <div class="col-md-4" style="font-size:small">
            <?php echo t('Page size');?>: <select v-model="page_limit">
                    <option v-for="option in [15,50,100]" :value="option">{{option}}</option>
                </select>                
            </div>
            <div v-if="rows.total" class="col-md-8">
                <div class="text-align-right">
                    <v-pagination
                        v-model="page"
                        class=""
                        :length="Math.ceil(rows.found/rows.limit)"
                        :total-visible="10"
                    ></v-pagination>
                </div>
            </div>
          </div>
        </template>

    </div>
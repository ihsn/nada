<div class="table-container" v-if="rows">

    <div class="row">
      <div class="col-10">
      <h3><?php echo t('Data explorer');?></h3>
      </div>

      <div class="col-2">
        <button class="btn btn-default btn-sm float-right" type="button" data-toggle="collapse" data-target="#queryOptions" aria-expanded="false" aria-controls="queryOptions">
        <?php echo t('API options');?> <i class="fas fa-cog"></i>
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
        <div class="text-center py-4">
          <div class="spinner-border text-primary" role="status">
            <span class="sr-only"><?php echo t('Loading...');?></span>
          </div>
        </div>
      </div>
            
      <div v-if="rows.data" class="mh-100 py-2">
            <div class="table-data-container" style="max-height:600px;">
                <table class="table table-striped table-bordered table-sm sticky-table-header mb-0">
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
                <nav aria-label="Page navigation">
                    <ul class="pagination pagination-sm justify-content-end mb-0">
                        <li class="page-item" :class="{disabled: page === 1}">
                            <a class="page-link" href="#" @click.prevent="page = 1" :title="'<?php echo t('First page');?>'">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        </li>
                        <li class="page-item" :class="{disabled: page === 1}">
                            <a class="page-link" href="#" @click.prevent="page = Math.max(1, page - 1)"><?php echo t('Previous');?></a>
                        </li>
                        <li class="page-item" v-for="n in Math.min(10, Math.ceil(rows.found/rows.limit))" :key="n" :class="{active: page === n}">
                            <a class="page-link" href="#" @click.prevent="page = n">{{n}}</a>
                        </li>
                        <li class="page-item" :class="{disabled: page >= Math.ceil(rows.found/rows.limit)}">
                            <a class="page-link" href="#" @click.prevent="page = Math.min(Math.ceil(rows.found/rows.limit), page + 1)"><?php echo t('Next');?></a>
                        </li>
                        <li class="page-item" :class="{disabled: page >= Math.ceil(rows.found/rows.limit)}">
                            <a class="page-link" href="#" @click.prevent="page = Math.ceil(rows.found/rows.limit)" :title="'<?php echo t('Last page');?>'">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
          </div>
        </template>

    </div>
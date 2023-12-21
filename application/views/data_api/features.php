<!-- features info -->
<div class="features-container mt-3">


<div class="row">
    <div class="col-10">
    <h3><?php echo t('Data dictionary');?></h3>
    </div>

    <div class="col-2">
      <button class="btn btn-default btn-sm float-right" type="button" >
        <a target="_blank" :href="apiDatasetInfoUrl"><?php echo t('JSON');?></a>
      </button>
    </div>
</div>

<table class="table" v-if="table_info.result && table_info.result.metadata">
    <tr>
        <th>#</th>
        <th><?php echo t('Name');?></th>
        <th><?php echo t('Label');?></th>
        <th><?php echo t('Codelist');?></th>
    </tr>
<tr v-for="(column,row_index,index) in tableColumnsDictionary">
  <td>{{index+1}}</td>
  <td>{{column.name}}</td>    
  <td>{{column.label}}</td>
  <td>
      <table class="table table-sm table-striped" v-if="column.categories">
        <tr>
            <th><?php echo t('Value');?></th>
            <th><?php echo t('Label');?></th>
        </tr>
        <tr v-for="category in column.categories">
            <td>{{category.value}}</td>
            <td>{{category.label}}</td>
        </tr>
      </table>
  </td>          
</tr>
</table>

</div>
<!-- end features info -->
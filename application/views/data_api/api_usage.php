<h2><?php echo t('API usage');?></h2>

<div>
    <strong><?php echo t('Metadata');?></strong>
</div>
<div  class="mb-3">
    <a target="_blank" :href="apiDatasetInfoUrl">{{apiDatasetInfoUrl}}</a>
</div>

<div>
    <strong><?php echo t('Data');?></strong> 
</div>
<div>
    <a target="_blank" :href="apiDatasetDataUrl">{{apiDatasetDataUrl}}</a>
</div>
    


<h5 class="mt-5"><?php echo t('Data API query parameters');?></h5>

<table class="table table-sm">
    <tr>
        <th><?php echo t('Parameter');?></th>
        <th><?php echo t('Description');?></th>
    </tr>    
    <tr>
        <td><?php echo t('fields');?></td>
        <td><?php echo t('A comma separated list of field/variable names');?></td>
    </tr>
    <tr>
        <td><?php echo t('filter');?></td>
        <td><?php echo t('Filter options');?></td>
    </tr>
    <tr>
        <td><?php echo t('format');?></td>
        <td><?php echo t('JSON or CSV. Default is JSON');?></td>
    </tr>
    <tr>
        <td><?php echo t('limit');?></td>
        <td><?php echo t('Number of rows. Default is 15 with max value of 1000');?></td>
    </tr>
    <tr>
        <td><?php echo t('offset');?></td>
        <td><?php echo t('Offset for pagination');?></td>
    </tr>    
</table>

<h5 class="mt-5" ><?php echo t('Examples')?></h5>
<div class="mb-5 api-examples">
<div>
    <div><strong><?php echo t('Get first 15 results');?></strong></div>
    <div> 
        <a :href="apiDatasetDataUrl + '?limit=15'">{{apiDatasetDataUrl}}?<strong>limit=15</strong></a>
    </div>
</div>

<div class="mt-3">
    <div><strong><?php echo t('Use offset to paginate results');?></strong></div>
    <div> 
        <a :href="apiDatasetDataUrl + '?limit=15&offset=15'">{{apiDatasetDataUrl}}?<strong>limit=15&offset=15</strong></a>
    </div>
</div>


<div class="mt-3">
    <div><strong><?php echo t('Get results as CSV');?></strong></div>
    <div> 
        <a :href="apiDatasetDataUrl + '?format=csv'">{{apiDatasetDataUrl}}?limit=15&offset=15&<strong>format=csv</strong></a>
    </div>
</div>

</div>



<h2>API usage</h2>

<div>
    <strong>Dataset information</strong>
</div>
<div  class="mb-3">
    <a target="_blank" :href="apiDatasetInfoUrl">{{apiDatasetInfoUrl}}</a>
</div>

<div>
    <strong>Dataset data</strong>
</div>
<div>
    <a target="_blank" :href="apiDatasetDataUrl">{{apiDatasetDataUrl}}</a>
</div>
    


<h5 class="mt-5">Dataset query parameters</h5>

<table class="table table-sm">
    <tr>
        <th>Parameter</th>
        <th>Description</th>        
    </tr>    
    <tr>
        <td>fields</td>
        <td>A comma separated list of variable names</td>
    </tr>
    <tr>
        <td>filter</td>
        <td>Filter options</td>
    </tr>
    <tr>
        <td>format</td>
        <td>JSON or CSV. Default is JSON</td>
    </tr>
    <tr>
        <td>limit</td>
        <td>Number of rows. Default is 15 with max value of 1000</td>
    </tr>
    <tr>
        <td>offset</td>
        <td>Offset for pagination</td>
    </tr>    
</table>

<h5 class="mt-5" >Examples</h5>
<div class="mb-5 api-examples">
<div>
    <div><strong>Get first 15 results</strong></div>
    <div> 
        <a :href="apiDatasetDataUrl + '?limit=15'">{{apiDatasetDataUrl}}?<strong>limit=15</strong></a>
    </div>
</div>

<div class="mt-3">
    <div><strong>Use offset to paginate results</strong></div>
    <div> 
        <a :href="apiDatasetDataUrl + '?limit=15&offset=15'">{{apiDatasetDataUrl}}?<strong>limit=15&offset=15</strong></a>
    </div>
</div>


<div class="mt-3">
    <div><strong>Get results as CSV</strong></div>
    <div> 
        <a :href="apiDatasetDataUrl + '?format=csv'">{{apiDatasetDataUrl}}?limit=15&offset=15&<strong>format=csv</strong></a>
    </div>
</div>

</div>



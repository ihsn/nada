<div class="container">

<div class="row">
    <div class="col-md-3">
        <button id="btn-ping" data-command="ping" type="button" class="btn btn-primary btn-block">Ping SOLR</button>
        <button type="button" class="btn btn-primary btn-block" data-toggle="collapse" href="#index-datasets">Index all datasets</button>
        <button type="button" class="btn btn-primary btn-block" data-toggle="collapse" href="#index-variables">Index all variables</button>
        <button type="button" class="btn btn-primary btn-block">Index all citations</button>
        <button type="button" class="btn btn-primary btn-block">Index single document</button>
        <button type="button" class="btn btn-primary btn-block">Commit</button>
        <button type="button" class="btn btn-danger btn-block">Clear index</button>
    </div>

    <div class="col-md-9">
        <div class="panel panel-default">
            <div class="panel-heading">
            <h3 class="panel-title">Index statistics</h3>
            </div>
            <div class="panel-body">
                <div>
                    <h5>SOLR index</h5>
                <?php foreach($solr_stats as $key=>$value):?>
                    <span><?php echo $key;?>: <?php echo number_format($value);?></span>
                <?php endforeach;?>
                </div>
                <hr/>
                <div>
                <h5>Database records</h5>
                <?php foreach($db_stats as $key=>$value):?>
                    <span><?php echo $key;?>: <?php echo number_format($value);?></span>
                <?php endforeach;?>
                </div>

            </div>
        </div>


        <div class="collapse" id="index-datasets">
        
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Index datasets - <?php echo number_format($db_stats['datasets']);?></h3>
                </div>
                <div class="panel-body">
                    <button type="button" class="btn btn-primary btn-index-datasets">Index</button>
                    <span class="index-status"></span>
                </div>
            </div>

        </div>

        <div class="collapse" id="index-variables">
        
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Index Variables - <?php echo number_format($db_stats['variables']);?></h3>
                </div>
                <div class="panel-body">
                    <button type="button" class="btn btn-primary btn-index-variables">Index</button>
                    <span class="index-status"></span>
                </div>
            </div>

        </div>

        <div class="collapse" id="index-citations">
        
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Index Citations - <?php echo number_format($db_stats['citations']);?></h3>
                </div>
                <div class="panel-body">
                    <button type="button" class="btn btn-primary btn-index-citations">Index</button>
                    <span class="index-status"></span>
                </div>
            </div>

        </div>


    </div>


</div>

</div>


<script>

var $total_surveys=<?php echo $db_stats['datasets'];?>;
var $total_variables=<?php echo $db_stats['variables'];?>;
var $citations=<?php echo $db_stats['citations'];?>;

$( ".btn-index-datasets" ).on( "click", function() {
    $(".index-status").text("indexing...");
    index_datasets();
    return;
    var jqxhr = $.get( "example.php", function(data) {
        console.log(data);
        $(".index-status").text("completed" + data.responseText);
    })
    .fail(function(data) {
        console.log(data);
        $(".index-status").text("ERROR: "+data.responseText);
    })
});

$( ".btn-index-variables" ).on( "click", function() {
    $(".index-status").text("indexing...");
    index_variables();
});



function ping()
{
    var jqxhr = $.get( "<?php echo site_url('api/solr/ping');?>", function(data) {
        console.log(data);
        $(".index-status").text("completed" + JSON.stringify(data));
    })
    .fail(function(data) {
        console.log(data);
        $(".index-status").text("ERROR: "+data.responseText);
    })
}

function index_datasets(start_row=0, limit=5, processed=0)
{
    var jqxhr = $.get( "<?php echo site_url('api/solr/full_import_surveys/');?>"+start_row + '/'+limit, function(data) {
        console.log(data);
        last_row_id=data.result.last_row_id;
        rows_processed=data.result.rows_processed;
        console.log();

        if(last_row_id>0){
            processed+=rows_processed;
            $(".index-status").html("indexing ... " + '<span class="badge badge-success">'+ processed +'</span>' );
            index_datasets(last_row_id,limit,processed);
        }
        else{
            $(".index-status").html("completed - total indexed = " + '<span class="badge badge-success">'+ processed +'</span>' );
            return true;
        }
        
    })
    .fail(function(data) {
        console.log(data);
        $(".index-status").text("ERROR: "+data.responseText);
    })
}


function index_variables(start_row=0, limit=5000, processed=0)
{
    var jqxhr = $.get( "<?php echo site_url('api/solr/full_import_variables/');?>"+start_row + '/'+limit, function(data) {
        console.log(data);
        last_row_id=data.result.last_row_id
        rows_processed=data.result.rows_processed
        console.log(rows_processed);
        
        if(last_row_id>0){
            processed+=rows_processed;
            $(".index-status").html("indexing ... " + '<span class="badge badge-success">'+ processed +'</span>' );
            index_variables(last_row_id,limit, processed);
        }
        else{
            $(".index-status").html("completed");
            return true;
        }
        
    })
    .fail(function(data) {
        console.log(data);
        $(".index-status").text("ERROR: "+data.responseText);
    })
}
    
</script>
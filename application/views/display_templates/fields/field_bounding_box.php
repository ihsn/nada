<?php if (isset($data) && is_array($data) && count($data)>0 ):?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.3.4/dist/leaflet.css"
  integrity="sha512-puBpdR0798OZvTTbP4A8Ix/l+A4dHDD0DGqYW6RQ+9jxkRFclaxxQb/SJAWZfWAkuyeQUytO7+7N4QKrDh+drA=="
  crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.3.4/dist/leaflet.js"
  integrity="sha512-nMMmRyTVoLYqjP9hrbed9S+FzjZHW5gY1TWCHA5ckwXZBadntCNs8kEqAWdrb9O7rxbCaA4lKTIWjDXZxflOcA=="
  crossorigin=""></script>


<div id="map-canvas" style="height:300px;background:gainsboro;" class="mb-3"></div>

<?php
  $columns=array(
    'place'=>'Place',
    'east'=>'East',
    'west'=>'West',
    'north'=>'North',
    'south'=>'South'
  );

  $place = array_column($data, 'place');

  if(!$place){
    unset($columns['place']);
  }

  $show_table=isset($options['show_table']) ? $options['show_table'] : true;
?>

<?php if($show_table):?>
<div class="extent-geographic-container mt-2">
<table class="table table-bordered table-striped table-condensed xsl-table table-grid">
  <tr>
    <?php foreach($columns as $col_key=>$col_label):?>
      <th><?php echo $col_label;?></th>
    <?php endforeach;?>
  </tr>
  <?php foreach($data as $row):?>
    <tr>
      <?php foreach($columns as $col_key=>$col_label):?>
        <td><?php echo isset($row[$col_key]) ? $row[$col_key] : 'x';?></td>    
      <?php endforeach;?>
    </tr>
  <?php endforeach;?>
  </table>
</div>
<?php endif;?>



<script>
  mymap = new L.Map('map-canvas');
	var osmUrl='https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
	var osmAttrib='Map data © <a href="https://openstreetmap.org">OpenStreetMap</a> contributors';
	var osm = new L.TileLayer(osmUrl, {minZoom: 2, maxZoom: 20, attribution: osmAttrib});	

  <?php $k=0;foreach($data as $bounds):$k++;?>
    var bounds = <?php echo json_encode($bounds);?>;
    
    // define rectangle geographical bounds
    var bounds_arr = [[bounds.south, bounds.west], [bounds.north, bounds.east]];
    
    mymap.addLayer(osm);
    L.rectangle(bounds_arr, {color: "red", weight: 1}).addTo(mymap);
    
    <?php if ($k==1): //set focus to first bounding box ?>
      mymap.setView(new L.LatLng(bounds.south, bounds.west),13);

      //zoom the map to the rectangle bounds
      mymap.fitBounds(bounds_arr);
    <?php endif;?>

  <?php endforeach;?>  
</script>

<?php return;?>

<script>
  mymap = new L.Map('map-canvas');
	var osmUrl='https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
	var osmAttrib='Map data © <a href="https://openstreetmap.org">OpenStreetMap</a> contributors';
	var osm = new L.TileLayer(osmUrl, {minZoom: 2, maxZoom: 20, attribution: osmAttrib});	

  <?php foreach($data as $bounds):?>
    var bounds = <?php echo json_encode($bounds);?>;
    <?php break;?>
  <?php endforeach;?>

  //var southWest = L.latLng(bounds.south,bounds.west);
  //var northEast = L.LatLng(bounds.north, bounds.east);

  // define rectangle geographical bounds
  var bounds_arr = [[bounds.south, bounds.west], [bounds.north, bounds.east]];

  mymap.setView(new L.LatLng(bounds.south, bounds.west),13);
  //mymap = L.map('map-canvas').setView([51.505, -0.09], 13);
  mymap.addLayer(osm);

  L.rectangle(bounds_arr, {color: "red", weight: 1}).addTo(mymap);
  //zoom the map to the rectangle bounds
  mymap.fitBounds(bounds_arr);
</script>


<?php endif;?>
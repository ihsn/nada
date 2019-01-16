<link rel="stylesheet" href="https://unpkg.com/leaflet@1.3.4/dist/leaflet.css"
  integrity="sha512-puBpdR0798OZvTTbP4A8Ix/l+A4dHDD0DGqYW6RQ+9jxkRFclaxxQb/SJAWZfWAkuyeQUytO7+7N4QKrDh+drA=="
  crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.3.4/dist/leaflet.js"
  integrity="sha512-nMMmRyTVoLYqjP9hrbed9S+FzjZHW5gY1TWCHA5ckwXZBadntCNs8kEqAWdrb9O7rxbCaA4lKTIWjDXZxflOcA=="
  crossorigin=""></script>


<div id="map-canvas" style="height:300px;background:gainsboro;"></div>

<?php
  $columns=array(
    'east'=>'East',
    'west'=>'West',
    'north'=>'North',
    'south'=>'South',
  );
?>


<div class="row extent-geographic-element">
<?php foreach($data as $row):?>
<?php foreach($row as $key=>$value):?>
	<div class="col-3 col-md-1">
	<div><?php echo $columns[$key];?></div>
	<div><?php echo $value;?></div>
</div>
<?php endforeach;?>
<?php endforeach;?>
</div>


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


<?php return;?>






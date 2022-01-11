<?php if (isset($data) && is_array($data) && count($data)>0 ):?>

<?php 
$options=$options[$name];

//map field required options
$api_key=$options['api_key'];
$field_lat=$options['latitude'];
$field_lng=$options['longitude'];
$field_info=$options['loc_info'];

$location_info='';

$map_lat_lng=array();
foreach($data as $row){
  if(isset($row[$field_lat])){
    $map_lat_lng['lat']=$row[$field_lat];
  }
  if(isset($row[$field_lng])){
    $map_lat_lng['lng']=$row[$field_lng];
  }

  $location_info=$row[$field_info];
}

$field_name=str_replace(".","_",$name);
?>
    
    <script src="https://polyfill.io/v3/polyfill.min.js?features=default"></script>
    <script
      src="https://maps.googleapis.com/maps/api/js?key=<?php echo $api_key;?>&callback=initMap&libraries=&v=weekly"
      defer
    ></script>
    <style type="text/css">
      /* Always set the map height explicitly to define the size of the div
       * element that contains the map. */
      #map {
        height: 100%;
      }

      /* Optional: Makes the sample page fill the window. */
      html,
      body {
        height: 100%;
        margin: 0;
        padding: 0;
      }
    </style>

    <script>
      function initMap() {
        const latLng = <?php echo json_encode($map_lat_lng);?>;//{ lat: -25.363, lng: 131.044 };
        const map = new google.maps.Map(document.getElementById("<?php echo $field_name;?>"), {
          zoom: 4,
          center: latLng,
        });
        new google.maps.Marker({
          position: latLng,
          map,
          title: "<?php echo $location_info;?>",
        });
      }
    </script>
  

<div class="field-map-container mt-2">
    <div class="xsl-caption field-caption"><?php echo t($name);?></div>
    <div id="<?php echo $field_name;?>" style="height:300px;background:gainsboro;"></div>
    <?php echo render_field('array',$name,$data,$options=array('hide_field_title'=>true));?>
</div>

<?php endif;?>
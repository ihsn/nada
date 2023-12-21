<?php
/**
 * 
 * Geospatial geographicElement
 * 
 */
/* Data JSON format
 "geographicElement": [
    {
    "geographicBoundingBox": {
    "southBoundLatitude": 12.1079168196,
    "westBoundLongitude": 41.807083181,
    "northBoundLatitude": 19.007916792,
    "eastBoundLongitude": 54.5404164634
    },
    "geographicDescription": "Yemen"
    },
    {
    "geographicBoundingBox": {
    "westBoundLongitude": "12",
    "eastBoundLongitude": "13",
    "southBoundLatitude": "13",
    "northBoundLatitude": "13"
    },
    "geographicDescription": "some description"
    }
*/
?>
<?php 
$bbox=array();
foreach($data as $row){
    $bbox[]=array(        
        'place'=>array_data_get($row, 'geographicDescription'),
        'east'=>array_data_get($row, 'geographicBoundingBox.eastBoundLongitude'),
        'west'=>array_data_get($row, 'geographicBoundingBox.westBoundLongitude'),
        'north'=>array_data_get($row, 'geographicBoundingBox.northBoundLatitude'),
        'south'=>array_data_get($row, 'geographicBoundingBox.southBoundLatitude'),
    );
}

$columns=array(
    'place'=>t('Place'),
    'east'=>t('East'),
    'west'=>t('West'),
    'north'=>t('North'),
    'south'=>t('South'),
);


$this->load->view('display_templates/fields/field_bounding_box',array(
    'data'=>$bbox,
    'columns'=>$columns,
    'options'=>array(
        'show_table'=>false
    )
));
?>
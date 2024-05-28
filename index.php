<!DOCTYPE html>
<html>


<head>

<title>Geojson Map application</title>
<link rel="stylesheet" href="plugins/boostrap/boostrap.min.css" />
<link rel="stylesheet" href="plugins/leaflet/leaflet.css" />


<style>


#map { height: 600px; }
 .category, .species { border: 1px solid #ccc; padding: 10px; margin: 5px; }
        .category { background: #f9f9f9; }
        .species { background: #fff; }
</style>
</head>
<body>
<div class="container">
        <div class="row">
            <div class="col-8">
                <div id="map"></div>
            </div>
            <div class="col-4">
                <div id="controls">
                    <h5>Categories</h5>
                    <div id="categories" class="mb-4"></div>
                    <h5>Species</h5>
                    <div id="species"></div>
                </div>
            </div>
        </div>
    </div>
</body>


<script src="plugins/jquery.js"></script>
<script src="plugins/boostrap/boostrap.min.js"></script>
<script src="plugins/leaflet/leaflet.js"></script>
</html>

<script>

$(document).ready(function () {
    var map = L.map('map').setView([0, 0], 2);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
    }).addTo(map);

    function loadGeoJSON(url) {
        $.getJSON(url, function (data) {
            var geojsonLayer = L.geoJSON(data, {
                onEachFeature: function (feature, layer) {
                    // Optional: Add popup or other interactions
                }
            }).addTo(map);

            map.fitBounds(geojsonLayer.getBounds());
        });
    }

    // Load initial GeoJSON data
    loadGeoJSON('path/to/initial.geojson');





});
// function loadCategories() {
//     $.get('https://providence.listentothedeep.com/local/get_categories.php', function (data) {
//         var categories = data.categories;
//         categories.forEach(function (category) {
//             $('#categories').append('<div class="category" data-category="' + category[0] + '">' +
//                 '<img src="' + category[1] + '" alt="' + category[0] + '">' + category[0] + '</div>');
//         });
//     });
// }

// function loadSpecies() {
//     $.get('https://providence.listentothedeep.com/local/get_species.php', function (data) {
//         var speciesList = data.species_list;
//         speciesList.forEach(function (species) {
//             $('#species').append('<div class="species" data-class-id="' + species.class_id + '">' +
//                 '<img src="' + species.image + '" alt="' + species.common_name + '">' + species.common_name + '</div>');
//         });
//     });
// }

// $(document).ready(function () {
//     loadCategories();
//     loadSpecies();
// });

</script>
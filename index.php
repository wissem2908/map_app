<!DOCTYPE html>
<html>


<head>

<title>Geojson Map application</title>
<link rel="stylesheet" href="plugins/boostrap/bootstrap.min.css" />
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
    <!------------------------------------------------------------------------------------------------>
<div class="form-group">
    <label for="historySlider">Select Historical Map:</label>
    <input type="range" class="form-control-range" id="historySlider" min="0" max="10" step="1">
</div>
 <!------------------------------------------------------------------------------------------------>
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
    <!------------------------------------------------------------------------------------------------>
        <div id="lastSelectedSpecies"></div>
    </div>
</body>


<script src="plugins/jquery.js"></script>
<script src="plugins/boostrap/bootstrap.min.js"></script>
<script src="plugins/leaflet/leaflet.js"></script>
</html>

<script>

/****************************************************************************************** */
//initialize the Leaflet map
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
    //loadGeoJSON('path/to/initial.geojson');





});

/************************************************************************************************ */
//Fetch categories and species data from the server
function loadCategories() {
    $.get('https://providence.listentothedeep.com/local/get_categories.php', function (data) {
        var categories = data.categories;
        categories.forEach(function (category) {
            $('#categories').append('<div class="category" data-category="' + category[0] + '">' +
                '<img src="' + category[1] + '" alt="' + category[0] + '">' + category[0] + '</div>');
        });
    });
}

function loadSpecies() {
    $.get('https://providence.listentothedeep.com/local/get_species.php', function (data) {
        var speciesList = data.species_list;
        speciesList.forEach(function (species) {
            $('#species').append('<div class="species" data-class-id="' + species.class_id + '">' +
                '<img src="' + species.image + '" alt="' + species.common_name + '">' + species.common_name + '</div>');
        });
    });
}

$(document).ready(function () {
    loadCategories();
    loadSpecies();
});
/*************************************************************************************************************** */
//Enable drag-and-drop functionality to add species to categories
$(document).ready(function () {
    $('.species').draggable({
        helper: 'clone'
    });

    $('.category').droppable({
        accept: '.species',
        drop: function (event, ui) {
            var species = ui.helper.clone();
            if ($(this).find('.species').length < 10) {
                $(this).append(species);
                // Optional: Add logic to handle species addition to category
            }
        }
    });
});
/***************************************************************************************************************** */
//Integrate heatmap display based on user-selected parameters
function displayHeatmap(type, classIds) {
    $.get('https://providence.listentothedeep.com/local/get_map.php', { type: type, class: classIds.join(',') }, function (data) {
        // Assuming the map URL is returned in data
        $.getJSON(data.url, function (geojson) {
            L.heatLayer(geojson.features.map(function (feature) {
                return [feature.geometry.coordinates[1], feature.geometry.coordinates[0], feature.properties.parameter];
            }), { radius: 25 }).addTo(map);
        });
    });
}
/****************************************************************************************************************** */
//Add a slider to select historical maps
$(document).ready(function () {
    $('#historySlider').on('input', function () {
        var selectedTime = $(this).val();
        // Load the corresponding historical map based on selectedTime
        loadHistoricalMap(selectedTime);
    });
});

function loadHistoricalMap(time) {
    // Fetch and display historical map based on the selected time
    $.get('https://providence.listentothedeep.com/local/get_available_maps.php', { type: 'species', class: selectedClassId }, function (data) {
        var maps = data.available.maps;
        var selectedMap = maps[time];
        if (selectedMap) {
            $.getJSON(selectedMap.url, function (geojson) {
                // Clear existing layers and add new geojson layer
                map.eachLayer(function (layer) {
                    if (layer instanceof L.GeoJSON) {
                        map.removeLayer(layer);
                    }
                });
                L.geoJSON(geojson).addTo(map);
            });
        }
    });
}
/************************************************************************************************************************ */
//Implement dynamic selection and categorization of species:
$(document).on('click', '.species', function () {
    var $this = $(this);
    var classId = $this.data('class-id');
    if ($this.hasClass('selected')) {
        $this.removeClass('selected');
    } else {
        $this.addClass('selected');
    }
});

$(document).on('click', '.category', function () {
    var selectedSpecies = $('.species.selected');
    if (selectedSpecies.length + $(this).find('.species').length <= 10) {
        selectedSpecies.each(function () {
            var species = $(this).clone();
            $(this).removeClass('selected');
            $(this).remove();
            $(this).appendTo($(this));
            // Add logic to handle adding species to category
        });
    }
});
/********************************************************************************** */
//Show the common name of the species when an icon is clicked
$(document).on('click', '.species', function () {
    var commonName = $(this).text();
    $('#lastSelectedSpecies').text(commonName);
});
</script>



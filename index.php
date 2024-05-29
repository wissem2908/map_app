<!DOCTYPE html>
<html>


<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Geojson Map application</title>
<link rel="stylesheet" href="plugins/boostrap/bootstrap.min.css" />
<link rel="stylesheet" href="plugins/leaflet/leaflet.css" />

<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">


<style>


#map { height: 600px; }
.category {
        width: 200px;
        border: 1px solid #ccc;
        margin: 10px;
        float: left;
        padding: 10px;
        transition: border-color 0.3s;
    }
    .species {
        border: 1px solid #ccc;
        margin: 5px;
        float: left;
        padding: 5px;
        cursor: move;
        display: flex;
        align-items: center;
    }
    .species .drag-icon {
        margin-right: 10px;
    }
    .category.over {
        border-color: blue;
    }
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
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
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
                    '<img src="https://providence.listentothedeep.com' + category[1] + '" alt="' + category[0] + '" width="70px"> &nbsp;' + category[0] + '</div>');
            });
            // Make categories droppable after they are added to the DOM
            $(".category").droppable({
                over: function(event, ui) {
                    $(this).addClass('over');
                },
                out: function(event, ui) {
                    $(this).removeClass('over');
                },
                drop: function(event, ui) {
                    $(this).removeClass('over');
                    $(this).append(ui.draggable);
                    ui.draggable.css({top: 0, left: 0});
                }
            });
        });
    }
    function loadSpecies() {
        $.get('https://providence.listentothedeep.com/local/get_species.php', function (data) {
            var speciesList = data.species_list;
            speciesList.forEach(function (species) {
                $('#species').append('<div class="species" data-class-id="' + species.class_id + '">' +
                    '<i class="fas fa-arrows-alt drag-icon"></i>' +
                    '<img src="https://providence.listentothedeep.com' + species.image + '" alt="' + species.common_name + '" width="70px"> &nbsp;' + species.common_name + '</div>');
            });
            // Make species draggable after they are added to the DOM
            $(".species").draggable({
                revert: true
            });
        });
    }
    loadCategories();
    loadSpecies();
/***************************************************************************************************************
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



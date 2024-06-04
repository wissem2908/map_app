<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geojson Map Application</title>
    <link rel="stylesheet" href="plugins/bootstrap/bootstrap.min.css" />
    <link rel="stylesheet" href="plugins/leaflet/leaflet.css" />
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">


    <style>
        #map {
            height: 600px;
        }

        .category {
            width: 100%;
            border: 1px solid #b9b9b9;
            margin: 10px;
            float: left;
            padding: 10px;
            transition: border-color 0.3s;
            background: #dc354514;
        }

        .species {
            width: 100%;
            border: 1px solid #ccc;
            margin: 5px;
            float: left;
            padding: 5px;
            cursor: move;
            display: flex;
            align-items: center;
            background: #d1e4ff;
        }

        .species .drag-icon {
            margin-right: 10px;
        }

        .species.selected {
            border-color: blue;
            background-color: #f0f8ff;
        }

        #species {
            background: #ededed;
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            overflow: scroll;
            height: 450px;
        }

        #categories {
            background: #ededed;
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            /* overflow: scroll; */
            /* height: 450px; */
        }

        .category.over {
            border-color: blue;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-7">
                <div id="map"></div>
            </div>
            <div class="col-5">
                  <div id="controls">
        <label for="drawSelect">Select Draw Option:</label>
        <select id="drawSelect"></select>
    </div>
                <div>
                    <label>Map Type</label>
                    <select id="map_type" class="form-control">
                        <option></option>
                    </select>
                    <br />
                </div>
                <div id="controls" class="row">
                    <div class="col-lg-6">
                    <div id="categories" class="mb-4">
                        <h5>Categories</h5>
                    </div>
                    </div>
                    <div class="col-lg-6">
                    <div id="species">
                        <h5>Species</h5>
                    </div>
                    </div>
                   
                  
                </div>
                <button class="btn btn-primary" id="available_map">Get available map</button>
            </div>
        </div>
        <div id="lastSelectedSpecies"></div>
    </div>

    <script src="plugins/jquery.js"></script>
    <script src="plugins/bootstrap/bootstrap.min.js"></script>
    <script src="plugins/leaflet/leaflet.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script src="https://unpkg.com/leaflet.heat/dist/leaflet-heat.js"></script>

    <script>
        $(document).ready(function () {
            var map = L.map('map').setView([0, 0], 2);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
            }).addTo(map);

            var geojsonLayer;
           

function getPolygonCentroid(coords) {
    var x = 0, y = 0, n = coords.length;
    for (var i = 0; i < n; i++) {
        x += coords[i][0];
        y += coords[i][1];
    }
    return [y / n, x / n]; // Return in [lat, lng] format
}
            function loadGeoJSON(url) {
                $.getJSON(url, function (data) {
                    console.log(data)
                    if (geojsonLayer) {
                        map.removeLayer(geojsonLayer);
                    }
                    geojsonLayer = L.geoJSON(data).addTo(map);

        // Extract heatmap data
        var heatData = data.features.map(function (feature) {
            var coords = feature.geometry.coordinates;
            if (feature.geometry.type === "Point") {
                return [coords[1], coords[0], feature.properties.intensity || 1];
            } else if (feature.geometry.type === "Polygon") {
                var centroid = getPolygonCentroid(coords[0]);
                return [centroid[0], centroid[1], feature.properties.intensity || 1];
            } else {
                console.warn("Unsupported geometry type for heatmap:", feature.geometry.type);
                return null;
            }
        }).filter(function(item) {
            return item !== null;
        });

        var heatmap = L.heatLayer(heatData, {
            radius: 20,
            blur: 10,
            gradient: {
                0.5: 'blue',
                1: 'red'
            }
        }).addTo(map);
                    map.fitBounds(geojsonLayer.getBounds());
                });
            }

            var selectedSpecies = [];
            var speciesCategories = {};
            var classIds = [];

            function loadCategories() {
                $.get('https://providence.listentothedeep.com/local/get_categories.php', function (data) {
                    var categories = data.categories;
                    categories.forEach(function (category) {
                        $('#categories').append('<div class="category" data-category="' + category[0] + '">' +
                            '<img src="https://providence.listentothedeep.com' + category[1] + '" alt="' + category[0] + '" width="70px"> &nbsp;' + category[0] + '</div>');
                    });

                    $(".category").droppable({
                        accept: ".species",
                        over: function (event, ui) {
                            $(this).addClass('over');
                        },
                        out: function (event, ui) {
                            $(this).removeClass('over');
                        },
                        drop: function (event, ui) {
                            $(this).removeClass('over');
                            var $species = ui.draggable;
                            var classId = $species.data('class-id');
                            var $category = $(this);
                            var $speciesClone = $species.clone();
                            $speciesClone.draggable({ revert: true });
                            $speciesClone.appendTo($category).css({ top: 0, left: 0 });

                            speciesCategories[classId] = speciesCategories[classId] || [];
                            if (!speciesCategories[classId].includes($category.data('category'))) {
                                speciesCategories[classId].push($category.data('category'));
                            }
                        }
                    });

                    $('#categories').on('click', '.category', function () {
                        var category = $(this).data('category');
                        $('.category').css('border', '1px solid #ccc');
                        $(this).css('border', '1px solid blue');
                        console.log('Selected Category:', category);

                        classIds = [];
                        $(this).find('.species').each(function () {
                            var classId = $(this).data('class-id');
                            classIds.push(classId);
                        });

                        console.log('Class IDs in selected category:', classIds);

                        selectedSpecies.forEach(function (species) {
                            $('div[data-class-id="' + species + '"]').appendTo('div[data-category="' + category + '"]');
                        });

                        selectedSpecies = [];
                        $(".species").removeClass('selected');
                    });
                });
            }

            function loadSpecies() {
                $.get('https://providence.listentothedeep.com/local/get_species.php', function (data) {
                    var speciesList = data.species_list;
                    speciesList.forEach(function (species) {
                        $('#species').append('<div class="species" data-class-id="' + species.class_id + '" data-common-name="' + species.common_name + '">' +
                            '<i class="fas fa-arrows-alt drag-icon"></i>' +
                            '<img src="https://providence.listentothedeep.com' + species.image + '" alt="' + species.common_name + '" width="70px"> &nbsp;' + species.common_name + '</div>');
                    });

                    $(".species").draggable({
                        revert: true
                    });

                    $("#species").droppable({
                        accept: ".species",
                        drop: function (event, ui) {
                            var $species = ui.draggable;
                            $species.appendTo("#species").css({ top: 0, left: 0 });
                            var classId = $species.data('class-id');
                            for (var category in speciesCategories[classId]) {
                                speciesCategories[classId] = speciesCategories[classId].filter(function (cat) {
                                    return cat !== category;
                                });
                            }
                        }
                    });

                    $('#species').on('click', '.species', function () {
                        var classId = $(this).data('class-id');
                        var commonName = $(this).data('common-name');
                        $('#lastSelectedSpecies').text('Common Name: ' + commonName);

                        if ($(this).hasClass('selected')) {
                            $(this).removeClass('selected');
                            selectedSpecies = selectedSpecies.filter(function (id) {
                                return id !== classId;
                            });
                        } else {
                            $(this).addClass('selected');
                            selectedSpecies.push(classId);
                        }
                    });

                    $('#species').on('dblclick', '.species', function () {
                        var $species = $(this);
                        $species.appendTo("#species").css({ top: 0, left: 0 });
                        var classId = $species.data('class-id');
                        for (var category in speciesCategories[classId]) {
                            speciesCategories[classId] = speciesCategories[classId].filter(function (cat) {
                                return cat !== category;
                            });
                        }
                    });
                });
            }

            loadCategories();
            loadSpecies();

            $.ajax({
                url: 'https://providence.listentothedeep.com/local/get_map_types.php',
                method: 'get',
                success: function (response) {
                    var mapTypes = response.map_types;
                    var selectOptions = "";

                    Object.keys(mapTypes).forEach(function (key) {
                        selectOptions += "<option value='" + key + "'>" + key + "</option>";
                    });

                    $('#map_type').append(selectOptions);
                }
            });

            function getAvailableMaps(mapType, classId) {
                $.get('https://providence.listentothedeep.com/local/get_available_maps.php', { type: mapType, class: classId }, function (data) {
                    var availableMaps = data.available.maps;

                    availableMaps.sort(function (a, b) {
                        return new Date(a.created) - new Date(b.created);
                    });

                    availableMaps.forEach(function (map) {
                        loadGeoJSON("https://providence.listentothedeep.com/"+map.url);
                    });
                
                    
                }).fail(function () {
                    console.error('Failed to fetch available maps.');
                });
            }

            $('#available_map').click(function (e) {
                e.preventDefault();
                var mapType = $('#map_type').val();
                console.log(classIds);
                classIds.forEach(function (classId) {
                    getAvailableMaps(mapType, classId);
                });
            });
        });
    </script>
</body>

</html>

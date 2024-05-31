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
            background:#d1e4ff;
        }
        
        .species .drag-icon {
            margin-right: 10px;
        }
        
        .species.selected {
            border-color: blue;
            background-color: #f0f8ff;
            /* optional: change background color for better visibility */
        }
        
        #species {
            background: #ededed;
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            /* Adjust alignment as needed */
        }
        
        #categories {
            background: #ededed;
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            /* Adjust alignment as needed */
        }
        
        .category.over {
            border-color: blue;
        }
    </style>
</head>

<body>
    <div class="container">
        <!------------------------------------------------------------------------------------------------>
        <!-- <div class="form-group">
            <label for="historySlider">Select Historical Map:</label>
            <input type="range" class="form-control-range" id="historySlider" min="0" max="10" step="1">
        </div> -->
        <!------------------------------------------------------------------------------------------------>
        <div class="row">
            <div class="col-8">
                <div id="map"></div>
            </div>
            <div class="col-4">
                <div>
                    <label>Map Type</label>
                    <select id="map_type" class="form-control" >
                        <option></option>
                    </select>
                    <br/>
                </div>
                <div id="controls">
                 
                    <div id="categories" class="mb-4">   <h5>Categories</h5></div>

                 
                    <div id="species">   <h5>Species</h5></div>
                </div>
                <button class="btn btn-primary " id="available_map">Get available map</button> 
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

<script>
    /******************************************************************************************/
    // Initialize the Leaflet map
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
        // loadGeoJSON('path/to/initial.geojson');
    });

    /************************************************************************************************/
    // Fetch categories and species data from the server
    var selectedSpecies = [];
    var speciesCategories = {};
var classIds=[]
    function loadCategories() {
        $.get('https://providence.listentothedeep.com/local/get_categories.php', function (data) {
            var categories = data.categories;
            categories.forEach(function (category) {
                $('#categories').append('<div class="category" data-category="' + category[0] + '">' +
                    '<img src="https://providence.listentothedeep.com' + category[1] + '" alt="' + category[0] + '" width="70px"> &nbsp;' + category[0] + '</div>');
            });

            // Droppable for categories
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
                    // Check if the category has reached the maximum limit of species
                    if ($category.find('.species').length >= 10) {
                        alert('Maximum species limit reached for this category.');
                        return;
                    }
                    // Append species to category
                    $species.appendTo($category).css({ top: 0, left: 0 });
                    // Add species to data structure
                    speciesCategories[classId] = speciesCategories[classId] || [];
                    speciesCategories[classId].push($category.data('category'));
                }
            });

            // Add click event to categories
            $('#categories').on('click', '.category', function () {
                    var category = $(this).data('category');
                    $('.category').css('border', '1px solid #ccc');
                    $(this).css('border','1px solid blue')
                    console.log('Selected Category:', category);
                    
                     classIds = [];
                    // Find all species within the selected category and collect their class_id
                    $(this).find('.species').each(function () {
                        var classId = $(this).data('class-id');
                        classIds.push(classId);
                    });

                    console.log('Class IDs in selected category:', classIds);

                    // Example action: Move selected species to this category
                    selectedSpecies.forEach(function (species) {
                        $('div[data-class-id="' + species + '"]').appendTo('div[data-category="' + category + '"]');
                    });

                    // Clear selected species
                    selectedSpecies = [];
                    $(".species").removeClass('selected');

                    // Now you have all class_ids of species in the selected category
                    // You can perform additional actions with the classIds array if needed
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

            // Make species draggable after they are added to the DOM
            $(".species").draggable({
                revert: true
            });

            $("#species").droppable({
                accept: ".species",
                drop: function (event, ui) {
                    var $species = ui.draggable;
                    $species.appendTo("#species").css({ top: 0, left: 0 });
                    var classId = $species.data('class-id');
                    // Remove species from its current category
                    for (var category in speciesCategories[classId]) {
                        speciesCategories[classId] = speciesCategories[classId].filter(function (cat) {
                            return cat !== category;
                        });
                    }
                }
            });

            // Add click event to species
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

            // Add double-click event to species to remove from category
            $('#species').on('dblclick', '.species', function () {
                var $species = $(this);
                $species.appendTo("#species").css({ top: 0, left: 0 });
                var classId = $species.data('class-id');
                // Remove species from its current category
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

    /************************************************************************************* */
    //get map type 

    $.ajax({
        url:'https://providence.listentothedeep.com/local/get_map_types.php',
        method:'get',
        success:function(response){
           // console.log(response)
            var mapTypes  = response.map_types
            //console.log(data)
           
            var selectOptions = "";

// Iterate over the keys of the mapTypes object
Object.keys(mapTypes).forEach(function(key) {
    selectOptions += "<option value='" + key + "'>" + key + "</option>";
});

// Append the options to the select element
$('#map_type').append(selectOptions);
        }
    })

    /************************************************************************************ */
    function getAvailableMaps(mapType, classId) {
    $.get('https://providence.listentothedeep.com/local/get_available_maps.php', { type: "abundance", class: "1141899521" }, function (data) {
        var availableMaps = data.available.maps;

        // Sort maps chronologically based on the 'created' field
        availableMaps.sort(function (a, b) {
            return new Date(a.created) - new Date(b.created);
        });

        // Display the available maps
        availableMaps.forEach(function (map) {
            console.log('Map Name:', map.name);
            console.log('Created:', map.created);
            console.log('URL:', map.url);
            console.log('-----------------------------');
            // You can modify this part to display the maps in your desired format/UI
        });
    }).fail(function () {
        console.error('Failed to fetch available maps.');
        // Handle error case here
    });
}


/************************************************************************************** */

$('#available_map').click(function(e){
e.preventDefault()
console.log(classIds)
getAvailableMaps("mapType", "classId")
})
</script>

</html>

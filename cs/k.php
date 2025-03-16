<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EV Charging Station Locator</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .map-container {
            height: 600px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .controls {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .radius-select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .locate-btn {
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .locate-btn:hover {
            background: #45a049;
            transform: translateY(-2px);
        }

        .station-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .station-card {
            background: white;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .station-card:hover {
            transform: translateY(-5px);
        }

        .station-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }

        .station-name {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }

        .station-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-active {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .station-info {
            color: #666;
            font-size: 14px;
            margin: 5px 0;
        }

        .station-distance {
            color: #2196F3;
            font-weight: 500;
            margin-top: 10px;
        }

        .loading {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: none;
        }

        .error {
            background: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Find Nearby Charging Stations</h1>
        
        <div class="controls">
            <select id="searchRadius" class="radius-select">
                <option value="5">5 km</option>
                <option value="10" selected>10 km</option>
                <option value="20">20 km</option>
                <option value="50">50 km</option>
            </select>
            
            <button onclick="findNearbyStations()" class="locate-btn">
                <i class="fas fa-location-arrow"></i>
                Find Nearby Stations
            </button>
        </div>

        <div id="error" class="error"></div>
        <div id="loading" class="loading">
            <i class="fas fa-spinner fa-spin"></i> Finding nearby stations...
        </div>

        <div id="map" class="map-container"></div>
        <div id="stationList" class="station-list"></div>
    </div>

    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script>
        let map;
        let userMarker;
        let radiusCircle;
        const markers = [];

        // Initialize map
        function initMap() {
            map = L.map('map').setView([10.8505, 76.2711], 7);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);
        }

        // Find nearby stations
        function findNearbyStations() {
            if (!navigator.geolocation) {
                showError("Geolocation is not supported by your browser");
                return;
            }

            showLoading();

            navigator.geolocation.getCurrentPosition(
                position => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    const radius = document.getElementById('searchRadius').value;

                    // Update map view
                    updateMapView(lat, lng, radius);

                    // Fetch nearby stations
                    fetch(`api.php?action=getNearby&lat=${lat}&lng=${lng}&radius=${radius}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showStations(data.stations);
                            } else {
                                throw new Error(data.error);
                            }
                        })
                        .catch(error => {
                            showError("Failed to fetch nearby stations: " + error.message);
                        })
                        .finally(() => {
                            hideLoading();
                        });
                },
                error => {
                    hideLoading();
                    handleGeolocationError(error);
                }
            );
        }

        // Update map view with user location and radius
        function updateMapView(lat, lng, radius) {
            // Clear existing markers
            markers.forEach(marker => map.removeLayer(marker));
            markers.length = 0;

            if (userMarker) map.removeLayer(userMarker);
            if (radiusCircle) map.removeLayer(radiusCircle);

            // Add user marker
            userMarker = L.marker([lat, lng], {
                icon: L.divIcon({
                    className: 'user-location-marker',
                    html: '<i class="fas fa-user-circle"></i>'
                })
            }).addTo(map);

            // Add radius circle
            radiusCircle = L.circle([lat, lng], {
                radius: radius * 1000,
                color: '#4CAF50',
                fillColor: '#4CAF50',
                fillOpacity: 0.1,
                weight: 1
            }).addTo(map);

            // Fit map to show the entire radius
            map.fitBounds(radiusCircle.getBounds());
        }

        // Show stations on map and in list
        function showStations(stations) {
            const stationList = document.getElementById('stationList');
            stationList.innerHTML = '';

            stations.forEach(station => {
                // Add marker to map
                const marker = L.marker([station.lat, station.lng])
                    .bindPopup(createPopupContent(station))
                    .addTo(map);
                markers.push(marker);

                // Add station card to list
                stationList.innerHTML += createStationCard(station);
            });
        }

        // Create popup content for map markers
        function createPopupContent(station) {
            return `
                <div class="marker-popup">
                    <div class="popup-header">
                        <h3>${station.name}</h3>
                        <span class="status-badge ${station.status}">${station.status}</span>
                    </div>
                    <div class="popup-content">
                        <div class="popup-info">
                            <i class="fas fa-map-marker-alt"></i> ${station.address}
                        </div>
                        <div class="popup-stats">
                            <div class="stat">
                                <i class="fas fa-plug"></i>
                                <span>${station.availableSlots}/${station.totalSlots} Available</span>
                            </div>
                            <div class="stat">
                                <i class="fas fa-rupee-sign"></i>
                                <span>${station.price}/kWh</span>
                            </div>
                            <div class="stat">
                                <i class="fas fa-road"></i>
                                <span>${station.distance} km away</span>
                            </div>
                        </div>
                        <a href="../station_details.php?id=${station.id}" class="popup-details-btn">
                            <span>View Details</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>`;
        }

        // Create station card HTML
        function createStationCard(station) {
            return `
                <div class="station-card">
                    <div class="station-header">
                        <h3 class="station-name">${station.name}</h3>
                        <span class="station-status status-${station.status}">${station.status}</span>
                    </div>
                    <div class="station-info">
                        <p><i class="fas fa-map-marker-alt"></i> ${station.address}</p>
                        <p><i class="fas fa-plug"></i> ${station.availableSlots}/${station.totalSlots} slots available</p>
                        <p><i class="fas fa-rupee-sign"></i> ${station.price}/kWh</p>
                    </div>
                    <div class="station-distance">
                        <i class="fas fa-road"></i> ${station.distance} km away
                    </div>
                </div>`;
        }

        // Error handling functions
        function handleGeolocationError(error) {
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    showError("Please allow location access to find nearby stations.");
                    break;
                case error.POSITION_UNAVAILABLE:
                    showError("Location information is unavailable.");
                    break;
                case error.TIMEOUT:
                    showError("Location request timed out.");
                    break;
                default:
                    showError("An unknown error occurred.");
                    break;
            }
        }

        function showError(message) {
            const error = document.getElementById('error');
            error.textContent = message;
            error.style.display = 'block';
        }

        function showLoading() {
            document.getElementById('loading').style.display = 'block';
            document.getElementById('error').style.display = 'none';
        }

        function hideLoading() {
            document.getElementById('loading').style.display = 'none';
        }

        // Initialize map when page loads
        window.onload = initMap;
    </script>
</body>
</html>

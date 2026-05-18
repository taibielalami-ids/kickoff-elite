<section class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Find Your Next Match</h1>
        <p class="text-light-emphasis mb-0">Search by day, hour, distance, price, and availability with live map view.</p>
    </div>
    <?php if (!empty($slotStart)): ?>
        <span class="badge text-bg-warning px-3 py-2">Slot: <?= e($slotStart) ?> (1 hour)</span>
    <?php endif; ?>
</section>

<div class="row g-4">
    <div class="col-lg-4">
        <section class="glass-panel p-4">
            <h2 class="h5 mb-3">Search Filters</h2>
            <form method="get" action="<?= route_path('/pitches') ?>" class="row g-3">
                <div class="col-12">
                    <label class="form-label">Day</label>
                    <input type="date" name="day" class="form-control" value="<?= e($filters['day'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Hour</label>
                    <input type="time" name="hour" class="form-control" value="<?= e($filters['hour'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Location</label>
                    <select name="location" class="form-select">
                        <option value="">All cities</option>
                        <?php foreach (($cityOptions ?? []) as $city): ?>
                            <option value="<?= e($city) ?>" <?= (($filters['location'] ?? '') === $city) ? 'selected' : '' ?>>
                                <?= e($city) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Max Distance (km)</label>
                    <input type="number" step="0.1" min="0" name="max_distance" class="form-control" value="<?= e($filters['max_distance'] ?? '') ?>" placeholder="5">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Availability</label>
                    <select name="availability" class="form-select">
                        <?php $availabilities = ['all', 'available', 'reserved']; ?>
                        <?php foreach ($availabilities as $availability): ?>
                            <option value="<?= e($availability) ?>" <?= ($filters['availability'] ?? 'all') === $availability ? 'selected' : '' ?>><?= e($availability) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <input type="hidden" id="user_lat" name="user_lat" value="<?= e($filters['user_lat'] ?? '') ?>">
                <input type="hidden" id="user_lng" name="user_lng" value="<?= e($filters['user_lng'] ?? '') ?>">

                <div class="col-12 d-grid gap-2">
                    <button class="btn btn-warning">Apply Filters</button>
                    <button type="button" class="btn btn-outline-light" id="detectLocationBtn">Use My Location</button>
                    <a class="btn btn-outline-secondary" href="<?= route_path('/pitches') ?>">Clear</a>
                </div>
            </form>
        </section>
    </div>

    <div class="col-lg-8">
        <section class="glass-panel p-3 p-md-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h5 mb-0">Map (Satellite + 3D)</h2>
                <span class="small text-light-emphasis"><?= count($pitches) ?> pitches found</span>
            </div>
            <div id="mapboxOptionsPanel" class="map-options-panel <?= trim($mapboxToken) !== '' ? '' : 'd-none' ?>">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label for="mapStyleSelect" class="form-label small mb-1">Style</label>
                        <select id="mapStyleSelect" class="form-select form-select-sm">
                            <option value="mapbox://styles/mapbox/standard" selected>Standard</option>
                            <option value="mapbox://styles/mapbox/streets-v12">Streets</option>
                            <option value="mapbox://styles/mapbox/navigation-day-v1">Navigation</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="routeProfileSelect" class="form-label small mb-1">Directions</label>
                        <select id="routeProfileSelect" class="form-select form-select-sm">
                            <option value="driving" selected>Driving</option>
                            <option value="walking">Walking</option>
                            <option value="cycling">Cycling</option>
                        </select>
                    </div>
                </div>
            </div>
            <div id="pitchMap"></div>
            <div id="routeInfoPanel" class="mt-3 small">
                <span class="text-light-emphasis">Select a pitch and allow location to see route distance and estimated travel time.</span>
            </div>
            <?php if (trim($mapboxToken) === ''): ?>
                <p class="small mt-3 mb-0 text-warning">
                    Running free fallback map (satellite/2D). Add `MAPBOX_TOKEN` in `.env` to unlock 3D terrain.
                </p>
            <?php endif; ?>
        </section>

        <section class="row g-3">
            <?php if (empty($pitches)): ?>
                <div class="col-12">
                    <div class="glass-panel p-4">
                        <p class="mb-0">No pitches match your filters. Try wider distance, different hour, or clear filters.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($pitches as $pitch): ?>
                    <div class="col-md-6">
                        <article class="glass-panel h-100 p-3">
                            <?php if (!empty($pitch['cover_photo'])): ?>
                                <img src="<?= e($pitch['cover_photo']) ?>" alt="<?= e($pitch['name']) ?>" class="img-fluid rounded mb-3 owner-pitch-cover">
                            <?php endif; ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h3 class="h5 mb-0"><?= e($pitch['name']) ?></h3>
                                <?php $badgeClass = ($pitch['slot_state'] ?? 'available') === 'available' ? 'text-bg-success' : 'text-bg-danger'; ?>
                                <span class="badge <?= e($badgeClass) ?>"><?= e($pitch['slot_state'] ?? $pitch['status']) ?></span>
                            </div>
                            <p class="small text-light-emphasis mb-2"><?= e($pitch['city']) ?> - <?= e($pitch['address']) ?></p>
                            <p class="small mb-1">Owner: <strong><?= e($pitch['owner_name']) ?></strong></p>
                            <p class="small mb-1">Hours: <?= e(substr((string) $pitch['open_time'], 0, 5)) ?> - <?= e(substr((string) $pitch['close_time'], 0, 5)) ?></p>
                            <p class="small mb-3">
                                Distance:
                                <span class="js-distance-label"
                                      data-lat="<?= e((string) $pitch['lat']) ?>"
                                      data-lng="<?= e((string) $pitch['lng']) ?>"><?= e($pitch['distance_label']) ?></span>
                            </p>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong class="text-warning"><?= e(number_format((float) $pitch['price_per_player'], 0)) ?> DH / player</strong>
                                <div class="d-flex gap-2">
                                    <a href="<?= route_path('/pitches/profile?id=' . (int) $pitch['id']) ?>" class="btn btn-sm btn-warning">Pitch Profile</a>
                                    <button
                                            type="button"
                                            class="btn btn-sm btn-outline-light map-focus-btn"
                                            data-pitch-name="<?= e($pitch['name']) ?>"
                                            data-lat="<?= e((string) $pitch['lat']) ?>"
                                            data-lng="<?= e((string) $pitch['lng']) ?>">
                                        Route on Map
                                    </button>
                                </div>
                            </div>
                            <?php if (!empty($filters['day']) && !empty($filters['hour'])): ?>
                                <form method="post" action="<?= route_path('/bookings/lock') ?>" class="d-grid">
                                    <?= Csrf::inputField() ?>
                                    <input type="hidden" name="pitch_id" value="<?= (int) $pitch['id'] ?>">
                                    <input type="hidden" name="day" value="<?= e($filters['day']) ?>">
                                    <input type="hidden" name="hour" value="<?= e($filters['hour']) ?>">
                                    <input type="hidden" name="redirect_query" value="<?= e($filtersQuery ?? '') ?>">
                                    <button class="btn btn-warning" <?= ($pitch['slot_state'] !== 'available') ? 'disabled' : '' ?>>
                                        <?= ($pitch['slot_state'] === 'available') ? 'Reserve This Slot' : 'Unavailable' ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <p class="small text-warning mb-0">Select day and hour to enable reservation.</p>
                            <?php endif; ?>
                        </article>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php if (trim($mapboxToken) !== ''): ?>
    <link href="https://api.mapbox.com/mapbox-gl-js/v3.15.0/mapbox-gl.css" rel="stylesheet">
    <script src="https://api.mapbox.com/mapbox-gl-js/v3.15.0/mapbox-gl.js"></script>
    <link rel="stylesheet" href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v5.0.0/mapbox-gl-geocoder.css" type="text/css">
    <script src="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v5.0.0/mapbox-gl-geocoder.min.js"></script>
<?php else: ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
          crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""></script>
<?php endif; ?>

<script>
(() => {
    const detectBtn = document.getElementById('detectLocationBtn');
    const latInput = document.getElementById('user_lat');
    const lngInput = document.getElementById('user_lng');
    const routeInfoPanel = document.getElementById('routeInfoPanel');
    const mapToken = <?= json_encode($mapboxToken) ?>;
    const mapData = <?= $mapDataJson ?: '[]' ?>;
    const defaultCenter = mapData.length > 0 ? [mapData[0].lng, mapData[0].lat] : [-6.7984600, 34.0479000];

    const parseNum = (value) => {
        const n = parseFloat(value);
        return Number.isFinite(n) ? n : null;
    };

    let userPosition = null;
    const initialLat = parseNum(latInput ? latInput.value : null);
    const initialLng = parseNum(lngInput ? lngInput.value : null);
    if (initialLat !== null && initialLng !== null) {
        userPosition = { lat: initialLat, lng: initialLng };
    }

    let mapboxMap = null;
    let mapboxUserMarker = null;
    let mapboxMarkers = [];
    let mapboxRouteFeature = {
        type: 'Feature',
        geometry: { type: 'LineString', coordinates: [] }
    };
    let activeRouteTarget = null;
    let leafletMap = null;
    let leafletUserMarker = null;
    let leafletRouteLayer = null;

    const mapStyleSelect = document.getElementById('mapStyleSelect');
    const routeProfileSelect = document.getElementById('routeProfileSelect');

    const mapState = {
        style: (mapStyleSelect && mapStyleSelect.value) || 'mapbox://styles/mapbox/standard',
        projection: 'mercator',
        routeProfile: (routeProfileSelect && routeProfileSelect.value) || 'driving',
        lightPreset: 'day',
        terrainOn: false,
        terrainExaggeration: 1.3,
        threeDOn: false,
        labelsOn: true,
        fogOn: false,
        trafficOn: false,
        pitch: 0,
        bearing: 0
    };

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

    const formatDistance = (km) => `${km.toFixed(1)} km`;
    const formatDuration = (seconds) => {
        const min = Math.round(seconds / 60);
        if (min < 60) return `${min} min`;
        const h = Math.floor(min / 60);
        const rem = min % 60;
        return `${h}h ${rem}m`;
    };

    const haversineKm = (lat1, lng1, lat2, lng2) => {
        const toRad = (d) => (d * Math.PI) / 180;
        const dLat = toRad(lat2 - lat1);
        const dLng = toRad(lng2 - lng1);
        const a =
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
            Math.sin(dLng / 2) * Math.sin(dLng / 2);
        return 6371 * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
    };

    const setRouteMessage = (html, isError = false) => {
        if (!routeInfoPanel) return;
        routeInfoPanel.classList.remove('text-warning', 'text-danger');
        if (isError) {
            routeInfoPanel.classList.add('text-danger');
        } else {
            routeInfoPanel.classList.add('text-warning');
        }
        routeInfoPanel.innerHTML = html;
    };

    const updateDistanceLabels = () => {
        document.querySelectorAll('.js-distance-label').forEach((el) => {
            const lat = parseNum(el.dataset.lat);
            const lng = parseNum(el.dataset.lng);
            if (!userPosition || lat === null || lng === null) return;
            const km = haversineKm(userPosition.lat, userPosition.lng, lat, lng);
            el.textContent = formatDistance(km);
        });
    };

    const syncHiddenLocationInputs = () => {
        if (!latInput || !lngInput || !userPosition) return;
        latInput.value = userPosition.lat.toFixed(7);
        lngInput.value = userPosition.lng.toFixed(7);
    };

    const ensureMapboxUserMarker = () => {
        if (!mapboxMap || !userPosition) return;
        const lngLat = [userPosition.lng, userPosition.lat];
        if (!mapboxUserMarker) {
            mapboxUserMarker = new mapboxgl.Marker({ color: '#3b82f6' }).setLngLat(lngLat).addTo(mapboxMap);
            return;
        }
        mapboxUserMarker.setLngLat(lngLat);
    };

    const mapboxStyleSupportsConfig = () =>
        typeof mapState.style === 'string' &&
        (mapState.style.includes('/standard') || mapState.style.includes('/standard-satellite'));

    const setStandardConfig = (property, value) => {
        if (!mapboxMap || !mapboxStyleSupportsConfig() || typeof mapboxMap.setConfigProperty !== 'function') return;
        try {
            mapboxMap.setConfigProperty('basemap', property, value);
        } catch (error) {
                        }
    };

    const applyLabelVisibilityFallback = () => {
        if (!mapboxMap) return;
        const layers = mapboxMap.getStyle() && Array.isArray(mapboxMap.getStyle().layers)
            ? mapboxMap.getStyle().layers
            : [];
        layers.forEach((layer) => {
            if (layer.type === 'symbol') {
                try {
                    mapboxMap.setLayoutProperty(layer.id, 'visibility', mapState.labelsOn ? 'visible' : 'none');
                } catch (error) {
                }
            }
        });
    };

    const applyMapboxRouteLayer = () => {
        if (!mapboxMap) return;
        if (!mapboxMap.getSource('route')) {
            mapboxMap.addSource('route', { type: 'geojson', data: mapboxRouteFeature });
        }
        if (!mapboxMap.getLayer('route-line')) {
            mapboxMap.addLayer({
                id: 'route-line',
                type: 'line',
                source: 'route',
                layout: { 'line-join': 'round', 'line-cap': 'round' },
                paint: { 'line-color': '#facc15', 'line-width': 5 }
            });
        }
    };

    const applyMapboxTerrain = () => {
        if (!mapboxMap) return;
        if (mapState.terrainOn) {
            if (!mapboxMap.getSource('mapbox-dem')) {
                mapboxMap.addSource('mapbox-dem', {
                    type: 'raster-dem',
                    url: 'mapbox://mapbox.terrain-rgb',
                    tileSize: 512,
                    maxzoom: 14
                });
            }
            mapboxMap.setTerrain({ source: 'mapbox-dem', exaggeration: mapState.terrainExaggeration });
            return;
        }
        mapboxMap.setTerrain(null);
    };

    const applyMapboxAtmosphere = () => {
        if (!mapboxMap) return;
        if (mapState.fogOn) {
            mapboxMap.setFog({
                range: [-0.5, 2],
                color: 'rgb(186, 210, 235)',
                'high-color': 'rgb(36, 92, 223)',
                'space-color': 'rgb(11, 11, 25)',
                'horizon-blend': 0.2
            });
            return;
        }
        mapboxMap.setFog(null);
    };

    const applyMapbox3dObjects = () => {
        if (!mapboxMap) return;
        setStandardConfig('show3dObjects', mapState.threeDOn);

        const targetId = 'kickoff-3d-buildings';
        const hasLayer = !!mapboxMap.getLayer(targetId);
        if (!mapState.threeDOn) {
            if (hasLayer) {
                mapboxMap.setLayoutProperty(targetId, 'visibility', 'none');
            }
            return;
        }

        if (hasLayer) {
            mapboxMap.setLayoutProperty(targetId, 'visibility', 'visible');
            return;
        }

        try {
            mapboxMap.addLayer({
                id: targetId,
                source: 'composite',
                'source-layer': 'building',
                filter: ['==', 'extrude', 'true'],
                type: 'fill-extrusion',
                minzoom: 14,
                paint: {
                    'fill-extrusion-color': '#94a3b8',
                    'fill-extrusion-height': ['get', 'height'],
                    'fill-extrusion-base': ['get', 'min_height'],
                    'fill-extrusion-opacity': 0.62
                }
            });
        } catch (error) {
                }
    };

    const applyMapboxLabels = () => {
        setStandardConfig('showPlaceLabels', mapState.labelsOn);
        setStandardConfig('showRoadLabels', mapState.labelsOn);
        setStandardConfig('showPointOfInterestLabels', mapState.labelsOn);
        setStandardConfig('showTransitLabels', mapState.labelsOn);
        applyLabelVisibilityFallback();
    };

    const applyMapboxTraffic = () => {
        setStandardConfig('showTraffic', mapState.trafficOn);
    };

    const applyMapboxLightPreset = () => {
        setStandardConfig('lightPreset', mapState.lightPreset);
    };

    const applyMapboxProjection = () => {
        if (!mapboxMap) return;
        try {
            mapboxMap.setProjection(mapState.projection);
        } catch (error) {
            }
    };

    const applyMapboxViewAngles = () => {
        if (!mapboxMap) return;
        mapboxMap.easeTo({
            pitch: mapState.pitch,
            bearing: mapState.bearing,
            duration: 400
        });
    };

    const addPitchMarkersToMapbox = () => {
        if (!mapboxMap) return;
        mapboxMarkers.forEach((marker) => marker.remove());
        mapboxMarkers = [];
        mapData.forEach((item) => {
            const color = item.status === 'available' ? '#22c55e' : '#ef4444';
            const marker = new mapboxgl.Marker({ color })
                .setLngLat([item.lng, item.lat])
                .setPopup(new mapboxgl.Popup({ offset: 20 }).setHTML(
                    `<strong>${escapeHtml(item.name)}</strong><br>${escapeHtml(item.city)}<br>${item.price} DH/player<br>${escapeHtml(item.status)}<br>${escapeHtml(item.distance_label)}`
                ))
                .addTo(mapboxMap);
            mapboxMarkers.push(marker);
        });
    };

    const applyAllMapboxOptions = () => {
        if (!mapboxMap) return;
        applyMapboxRouteLayer();
        applyMapboxTerrain();
        applyMapboxAtmosphere();
        applyMapbox3dObjects();
        applyMapboxLabels();
        applyMapboxTraffic();
        applyMapboxLightPreset();
        applyMapboxProjection();
        ensureMapboxUserMarker();
    };

    const ensureLeafletUserMarker = () => {
        if (!leafletMap || !userPosition || typeof L === 'undefined') return;
        const latLng = [userPosition.lat, userPosition.lng];
        if (!leafletUserMarker) {
            leafletUserMarker = L.marker(latLng).addTo(leafletMap).bindPopup('Your Location');
            return;
        }
        leafletUserMarker.setLatLng(latLng);
    };

    const onLocationReady = () => {
        syncHiddenLocationInputs();
        updateDistanceLabels();
        ensureMapboxUserMarker();
        ensureLeafletUserMarker();
        if (detectBtn) detectBtn.textContent = 'Location Ready';
    };

    const detectLocation = (showAlertOnFail) => {
        if (!navigator.geolocation) {
            if (showAlertOnFail) alert('Geolocation is not supported by your browser.');
            return;
        }
        if (detectBtn) detectBtn.textContent = 'Detecting...';
        navigator.geolocation.getCurrentPosition((position) => {
            userPosition = {
                lat: position.coords.latitude,
                lng: position.coords.longitude
            };
            onLocationReady();
        }, () => {
            if (detectBtn) detectBtn.textContent = 'Use My Location';
            if (showAlertOnFail) {
                alert('Could not get your location. Please allow location access.');
            }
        }, {
            enableHighAccuracy: true,
            timeout: 12000
        });
    };

    if (detectBtn) {
        detectBtn.addEventListener('click', () => detectLocation(true));
    }

    if (!userPosition) {
        detectLocation(false);
    } else {
        onLocationReady();
    }

    const buildDirectionsLink = (targetLat, targetLng) => {
        if (!userPosition) return '#';
        const origin = `${userPosition.lat},${userPosition.lng}`;
        const dest = `${targetLat},${targetLng}`;
        return `https://www.google.com/maps/dir/?api=1&origin=${encodeURIComponent(origin)}&destination=${encodeURIComponent(dest)}&travelmode=driving`;
    };

    const bindFocus = (focusFn) => {
        document.querySelectorAll('.map-focus-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                const lat = parseFloat(btn.dataset.lat || '0');
                const lng = parseFloat(btn.dataset.lng || '0');
                const name = btn.dataset.pitchName || 'Pitch';
                if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
                focusFn({ lat, lng, name });
            });
        });
    };

    if (mapToken && typeof mapboxgl !== 'undefined') {
        mapboxgl.accessToken = mapToken;
        mapboxMap = new mapboxgl.Map({
            container: 'pitchMap',
            style: mapState.style,
            center: defaultCenter,
            zoom: 11,
            pitch: mapState.pitch,
            bearing: mapState.bearing,
            antialias: true
        });

        mapboxMap.addControl(new mapboxgl.NavigationControl(), 'top-right');
        mapboxMap.addControl(new mapboxgl.FullscreenControl(), 'top-right');
        mapboxMap.addControl(new mapboxgl.ScaleControl({ maxWidth: 100, unit: 'metric' }), 'bottom-right');
        mapboxMap.addControl(new mapboxgl.GeolocateControl({
            positionOptions: { enableHighAccuracy: true },
            trackUserLocation: true
        }), 'top-right');
        if (typeof MapboxGeocoder !== 'undefined') {
            mapboxMap.addControl(new MapboxGeocoder({
                accessToken: mapToken,
                mapboxgl: mapboxgl,
                marker: false,
                placeholder: 'Search area or address',
                countries: 'ma'
            }), 'top-left');
        }

        mapboxMap.on('style.load', () => {
            applyAllMapboxOptions();
            addPitchMarkersToMapbox();
            applyMapboxViewAngles();
            if (activeRouteTarget) {
                drawMapboxRoute(activeRouteTarget);
            }
        });

        const bounds = new mapboxgl.LngLatBounds();
        mapData.forEach((item) => {
            bounds.extend([item.lng, item.lat]);
        });

        if (mapData.length > 1) {
            mapboxMap.fitBounds(bounds, { padding: 60, maxZoom: 14 });
        }

        const drawMapboxRoute = async (target) => {
            if (!userPosition) {
                setRouteMessage('Allow location access first, then click "Route on Map" to draw directions.', true);
                return;
            }
            activeRouteTarget = target;
            try {
                const profile = routeProfileSelect ? routeProfileSelect.value : 'driving';
                mapState.routeProfile = profile;
                const url = `https://api.mapbox.com/directions/v5/mapbox/${profile}/${userPosition.lng},${userPosition.lat};${target.lng},${target.lat}?alternatives=true&geometries=geojson&overview=full&steps=true&access_token=${encodeURIComponent(mapToken)}`;
                const response = await fetch(url);
                const data = await response.json();
                const route = data.routes && data.routes[0];
                if (!route || !route.geometry || !Array.isArray(route.geometry.coordinates)) {
                    setRouteMessage('No route found for this pitch.', true);
                    return;
                }

                mapboxRouteFeature = {
                    type: 'Feature',
                    geometry: {
                        type: 'LineString',
                        coordinates: route.geometry.coordinates
                    }
                };

                const routeSource = mapboxMap.getSource('route');
                if (routeSource) routeSource.setData(mapboxRouteFeature);

                const routeBounds = new mapboxgl.LngLatBounds();
                route.geometry.coordinates.forEach((coord) => routeBounds.extend(coord));
                mapboxMap.fitBounds(routeBounds, { padding: 70, maxZoom: 15 });

                const routeKm = route.distance / 1000;
                const directionsLink = buildDirectionsLink(target.lat, target.lng);
                setRouteMessage(
                    `Route to <strong>${escapeHtml(target.name)}</strong>: ${formatDistance(routeKm)}, about ${formatDuration(route.duration)}. ` +
                    `<a href="${directionsLink}" target="_blank" rel="noopener">Open external directions</a>`
                );
            } catch (error) {
                setRouteMessage('Could not load directions right now. Please try again.', true);
            }
        };

        if (mapStyleSelect) {
            mapStyleSelect.addEventListener('change', () => {
                mapState.style = mapStyleSelect.value;
                mapboxMap.setStyle(mapState.style, { diff: false });
            });
        }
        if (routeProfileSelect) {
            routeProfileSelect.addEventListener('change', () => {
                mapState.routeProfile = routeProfileSelect.value;
                if (activeRouteTarget) {
                    drawMapboxRoute(activeRouteTarget);
                }
            });
        }

        bindFocus((target) => {
            mapboxMap.flyTo({ center: [target.lng, target.lat], zoom: 15, pitch: 60, speed: 0.9 });
            drawMapboxRoute(target);
        });
        return;
    }

    if (typeof L !== 'undefined') {
        leafletMap = L.map('pitchMap').setView([defaultCenter[1], defaultCenter[0]], 11);

        const satelliteLayer = L.tileLayer(
            'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
            {
                attribution: '&copy; Esri'
            }
        );

        const streetLayer = L.tileLayer(
            'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            {
                attribution: '&copy; OpenStreetMap contributors'
            }
        );

        satelliteLayer.addTo(leafletMap);
        L.control.layers(
            { Satellite: satelliteLayer, Streets: streetLayer },
            {},
            { collapsed: true }
        ).addTo(leafletMap);

        const bounds = [];
        mapData.forEach((item) => {
            const marker = L.marker([item.lat, item.lng]).addTo(leafletMap);
            marker.bindPopup(
                `<strong>${item.name}</strong><br>${item.city}<br>${item.price} DH/player<br>${item.status}<br>${item.distance_label}`
            );
            bounds.push([item.lat, item.lng]);
        });

        if (bounds.length > 1) {
            leafletMap.fitBounds(bounds, { padding: [30, 30] });
        }

        ensureLeafletUserMarker();

        const drawLeafletRoute = async (target) => {
            if (!userPosition) {
                setRouteMessage('Allow location access first, then click "Route on Map" to draw directions.', true);
                return;
            }
            try {
                const url = `https://router.project-osrm.org/route/v1/driving/${userPosition.lng},${userPosition.lat};${target.lng},${target.lat}?overview=full&geometries=geojson`;
                const response = await fetch(url);
                const data = await response.json();
                const route = data.routes && data.routes[0];
                if (!route || !route.geometry || !Array.isArray(route.geometry.coordinates)) {
                    setRouteMessage('No route found for this pitch.', true);
                    return;
                }

                const latLngs = route.geometry.coordinates.map((coord) => [coord[1], coord[0]]);
                if (leafletRouteLayer) {
                    leafletMap.removeLayer(leafletRouteLayer);
                }
                leafletRouteLayer = L.polyline(latLngs, {
                    color: '#facc15',
                    weight: 5
                }).addTo(leafletMap);

                leafletMap.fitBounds(leafletRouteLayer.getBounds(), { padding: [35, 35], maxZoom: 15 });
                const routeKm = route.distance / 1000;
                const directionsLink = buildDirectionsLink(target.lat, target.lng);
                setRouteMessage(
                    `Route to <strong>${escapeHtml(target.name)}</strong>: ${formatDistance(routeKm)}, about ${formatDuration(route.duration)}. ` +
                    `<a href="${directionsLink}" target="_blank" rel="noopener">Open external directions</a>`
                );
            } catch (error) {
                setRouteMessage('Could not load directions right now. Please try again.', true);
            }
        };

        bindFocus((target) => {
            leafletMap.setView([target.lat, target.lng], 15, { animate: true });
            drawLeafletRoute(target);
        });
    }
})();
</script>

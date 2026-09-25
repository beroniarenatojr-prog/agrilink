/**
 * AgriLink live delivery map (Leaflet) with OSRM road routing.
 */
window.AgriTrackingMap = (function () {
    const ROUTE_COLOR = '#14b8a6';
    const ROUTE_OUTLINE = '#ffffff';
    const TRAIL_COLOR = '#0f766e';
    const ROUTE_REFRESH_METERS = 100;

    let map = null;
    let destMarker = null;
    let riderMarker = null;
    let trailLine = null;
    let routeLine = null;
    let routeOutline = null;
    let containerId = null;
    let viewerRole = 'buyer';

    let lastMapData = null;
    let lastRouteFrom = null;
    let lastRouteTo = null;
    let cachedEtaSeconds = null;
    let cachedDistanceMeters = null;
    let routeDebounceTimer = null;
    let routeInFlight = false;
    let pendingRoute = null;
    let routeRequestSeq = 0;

    function hasRoadRoute() {
        if (!routeLine) return false;
        const pts = routeLine.getLatLngs();
        return pts && pts.length > 4;
    }

    function ensureMap(lat, lng) {
        if (map) return map;
        const el = document.getElementById(containerId);
        if (!el || typeof L === 'undefined') return null;

        map = L.map(el, { scrollWheelZoom: false }).setView([lat, lng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        }).addTo(map);

        return map;
    }

    function destIcon() {
        return L.divIcon({
            className: 'tracking-marker tracking-marker--dest',
            html: '<span></span>',
            iconSize: [20, 20],
            iconAnchor: [10, 10],
        });
    }

    function riderIcon() {
        return L.divIcon({
            className: 'tracking-marker tracking-marker--rider tracking-marker--vehicle',
            html: '<span><i class="ti ti-truck-delivery"></i></span>',
            iconSize: [36, 36],
            iconAnchor: [18, 18],
        });
    }

    function haversineMeters(lat1, lng1, lat2, lng2) {
        const R = 6371000;
        const toRad = function (d) { return d * Math.PI / 180; };
        const dLat = toRad(lat2 - lat1);
        const dLng = toRad(lng2 - lng1);
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2)
            + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2))
            * Math.sin(dLng / 2) * Math.sin(dLng / 2);
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    function formatEta(seconds) {
        if (seconds == null || seconds < 0) return '';
        if (seconds < 60) return '< 1 min';
        const mins = Math.round(seconds / 60);
        if (mins < 60) return '~' + mins + ' min';
        const hrs = Math.floor(mins / 60);
        const rem = mins % 60;
        return '~' + hrs + 'h ' + rem + 'm';
    }

    function formatDistance(meters) {
        if (meters == null || meters < 0) return '';
        if (meters < 1000) return Math.round(meters) + ' m';
        return (meters / 1000).toFixed(1) + ' km';
    }

    function destLabel(data) {
        if (!data || !data.destination) return 'destination';
        const addr = data.destination.address || '';
        if (!addr) return 'destination';
        const parts = addr.split(',');
        const city = parts.length > 1 ? parts[parts.length - 2].trim() : parts[0].trim();
        return city || 'destination';
    }

    function updateStatusBar(data) {
        const bar = document.getElementById('trackingStatusBar');
        if (!bar) return;

        const liveDot = bar.querySelector('.tracking-live-dot');
        const msg = bar.querySelector('.tracking-status-msg');
        const time = bar.querySelector('.tracking-status-time');

        if (!data || data.status !== 'in_transit') {
            if (msg) msg.textContent = 'Delivery tracking is not active.';
            if (liveDot) liveDot.classList.remove('is-live');
            if (time) time.textContent = '';
            return;
        }

        const etaPart = cachedEtaSeconds != null ? formatEta(cachedEtaSeconds) : '';
        const distPart = cachedDistanceMeters != null ? formatDistance(cachedDistanceMeters) : '';
        const routePart = etaPart && distPart ? distPart + ' · ETA ' + etaPart : (etaPart ? 'ETA ' + etaPart : '');

        if (!data.rider) {
            if (viewerRole === 'logistics') {
                if (msg) msg.textContent = 'Waiting for your GPS fix…';
                if (liveDot) liveDot.classList.add('is-live');
                if (time) time.textContent = 'Allow location access if prompted.';
            } else {
                if (msg) msg.textContent = 'Waiting for rider location…';
                if (liveDot) liveDot.classList.remove('is-live');
                if (time) time.textContent = 'Keep the deliveries page open on the rider device.';
            }
            return;
        }

        const destName = destLabel(data);

        if (viewerRole === 'logistics') {
            if (data.rider.stale) {
                if (msg) msg.textContent = 'Location not updating — check GPS permission';
                if (liveDot) liveDot.classList.remove('is-live');
            } else {
                if (msg) msg.textContent = 'On the way to ' + destName + '…';
                if (liveDot) liveDot.classList.add('is-live');
            }
            const timeParts = [];
            if (routePart) timeParts.push(routePart);
            if (data.rider.updated_at) {
                timeParts.push('Last sent ' + new Date(data.rider.updated_at.replace(' ', 'T')).toLocaleTimeString());
            }
            if (time) time.textContent = timeParts.join(' · ');
            return;
        }

        if (data.rider.stale) {
            if (msg) msg.textContent = 'Rider location may be outdated.';
            if (liveDot) liveDot.classList.remove('is-live');
        } else {
            if (msg) msg.textContent = 'On the way to ' + destName + '…';
            if (liveDot) liveDot.classList.add('is-live');
        }

        const timeParts = [];
        if (routePart) timeParts.push(routePart);
        if (data.rider.updated_at) {
            timeParts.push('Updated ' + new Date(data.rider.updated_at.replace(' ', 'T')).toLocaleTimeString());
        }
        if (time) time.textContent = timeParts.join(' · ');
    }

    function setRoutePolylines(coords) {
        if (!map || !coords || coords.length < 2) return;

        const outlineOpts = {
            color: ROUTE_OUTLINE,
            weight: 9,
            opacity: 0.95,
            lineCap: 'round',
            lineJoin: 'round',
        };
        const routeOpts = {
            color: ROUTE_COLOR,
            weight: 6,
            opacity: 0.95,
            lineCap: 'round',
            lineJoin: 'round',
        };

        if (routeOutline) {
            routeOutline.setLatLngs(coords);
        } else {
            routeOutline = L.polyline(coords, outlineOpts).addTo(map);
        }

        if (routeLine) {
            routeLine.setLatLngs(coords);
        } else {
            routeLine = L.polyline(coords, routeOpts).addTo(map);
            routeLine.bringToFront();
        }

        if (riderMarker) riderMarker.bringToFront();
        if (destMarker) destMarker.bringToFront();
    }

    function clearRoute() {
        if (routeLine && map) {
            map.removeLayer(routeLine);
            routeLine = null;
        }
        if (routeOutline && map) {
            map.removeLayer(routeOutline);
            routeOutline = null;
        }
        cachedEtaSeconds = null;
        cachedDistanceMeters = null;
        lastRouteFrom = null;
        lastRouteTo = null;
    }

    function shouldRefreshRoute(rLat, rLng, dLat, dLng) {
        if (!lastRouteFrom || !lastRouteTo) return true;
        if (Math.abs(dLat - lastRouteTo.lat) > 0.00001 || Math.abs(dLng - lastRouteTo.lng) > 0.00001) {
            return true;
        }
        return haversineMeters(rLat, rLng, lastRouteFrom.lat, lastRouteFrom.lng) >= ROUTE_REFRESH_METERS;
    }

    function routeApiUrl() {
        return window.__AGRI_ROUTE_API__ || '/api/delivery-route.php';
    }

    function fetchOsrmRoute(rLat, rLng, dLat, dLng) {
        if (!map) return;

        if (routeInFlight) {
            pendingRoute = { rLat: rLat, rLng: rLng, dLat: dLat, dLng: dLng };
            return;
        }

        routeInFlight = true;
        const seq = ++routeRequestSeq;
        const params = new URLSearchParams({
            from_lat: String(rLat),
            from_lng: String(rLng),
            to_lat: String(dLat),
            to_lng: String(dLng),
        });

        fetch(routeApiUrl() + '?' + params.toString(), {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        })
            .then(function (r) {
                if (!r.ok) throw new Error('route_http_' + r.status);
                return r.json();
            })
            .then(function (json) {
                if (seq !== routeRequestSeq) return;
                if (!json.coordinates || json.coordinates.length < 2) {
                    throw new Error('route_empty');
                }

                cachedEtaSeconds = json.duration;
                cachedDistanceMeters = json.distance;
                lastRouteFrom = { lat: rLat, lng: rLng };
                lastRouteTo = { lat: dLat, lng: dLng };

                setRoutePolylines(json.coordinates);
                if (lastMapData) updateStatusBar(lastMapData);
                fitBounds();
            })
            .catch(function (err) {
                if (seq !== routeRequestSeq) return;
                console.warn('[AgriTrackingMap] Road route fetch failed:', err);
                if (!hasRoadRoute()) {
                    setRoutePolylines([[rLat, rLng], [dLat, dLng]]);
                }
            })
            .finally(function () {
                if (seq !== routeRequestSeq) return;
                routeInFlight = false;
                if (pendingRoute) {
                    const p = pendingRoute;
                    pendingRoute = null;
                    if (shouldRefreshRoute(p.rLat, p.rLng, p.dLat, p.dLng)) {
                        fetchOsrmRoute(p.rLat, p.rLng, p.dLat, p.dLng);
                    }
                }
            });
    }

    function scheduleRouteUpdate(rLat, rLng, dLat, dLng) {
        if (!shouldRefreshRoute(rLat, rLng, dLat, dLng) && hasRoadRoute()) return;

        if (routeDebounceTimer) clearTimeout(routeDebounceTimer);
        routeDebounceTimer = setTimeout(function () {
            routeDebounceTimer = null;
            if (!shouldRefreshRoute(rLat, rLng, dLat, dLng) && hasRoadRoute()) return;
            fetchOsrmRoute(rLat, rLng, dLat, dLng);
        }, 800);
    }

    function updateRoute(data) {
        if (!data || data.status !== 'in_transit' || !data.rider || !data.destination) {
            clearRoute();
            return;
        }

        scheduleRouteUpdate(
            data.rider.lat,
            data.rider.lng,
            data.destination.lat,
            data.destination.lng
        );
    }

    function fitBounds() {
        if (!map) return;
        const layers = [];
        if (destMarker) layers.push(destMarker);
        if (riderMarker) layers.push(riderMarker);
        if (routeLine) layers.push(routeLine);
        if (trailLine) layers.push(trailLine);

        if (layers.length === 0) return;
        if (layers.length === 1) {
            map.setView(layers[0].getLatLng(), 14);
            return;
        }

        const group = L.featureGroup(layers);
        map.fitBounds(group.getBounds().pad(0.15));
    }

    return {
        init: function (id, options) {
            containerId = id;
            const opts = options || {};
            viewerRole = opts.viewerRole || 'buyer';
            const lat = opts.lat || 14.5995;
            const lng = opts.lng || 120.9842;
            ensureMap(lat, lng);
            if (opts.data) {
                this.update(opts.data);
            }
        },

        updateRiderPreview: function (lat, lng) {
            if (!map) return;
            const pos = [lat, lng];
            if (!riderMarker) {
                riderMarker = L.marker(pos, { icon: riderIcon() }).addTo(map);
                riderMarker.bindPopup('Your location');
            } else {
                riderMarker.setLatLng(pos);
            }

            if (lastMapData && lastMapData.destination) {
                const dLat = lastMapData.destination.lat;
                const dLng = lastMapData.destination.lng;
                const preview = Object.assign({}, lastMapData, {
                    rider: { lat: lat, lng: lng, stale: false },
                });
                if (shouldRefreshRoute(lat, lng, dLat, dLng) || !hasRoadRoute()) {
                    scheduleRouteUpdate(lat, lng, dLat, dLng);
                }
                updateStatusBar(preview);
            }

            fitBounds();
        },

        update: function (data) {
            if (!data) return;

            lastMapData = data;
            updateStatusBar(data);

            let centerLat = 14.5995;
            let centerLng = 120.9842;

            if (data.destination) {
                centerLat = data.destination.lat;
                centerLng = data.destination.lng;
            } else if (data.rider) {
                centerLat = data.rider.lat;
                centerLng = data.rider.lng;
            }

            ensureMap(centerLat, centerLng);

            if (data.destination && map) {
                const pos = [data.destination.lat, data.destination.lng];
                const popup = data.destination.address || 'Delivery destination';
                if (!destMarker) {
                    destMarker = L.marker(pos, { icon: destIcon() }).addTo(map);
                    destMarker.bindPopup(popup);
                } else {
                    destMarker.setLatLng(pos);
                    destMarker.setPopupContent(popup);
                }
            }

            if (data.rider && map) {
                const pos = [data.rider.lat, data.rider.lng];
                const riderLabel = viewerRole === 'logistics' ? 'Your location' : 'Rider';
                if (!riderMarker) {
                    riderMarker = L.marker(pos, { icon: riderIcon() }).addTo(map);
                    riderMarker.bindPopup(riderLabel);
                } else {
                    riderMarker.setLatLng(pos);
                }
            }

            if (data.trail && data.trail.length > 1 && map) {
                const trailOpts = {
                    color: TRAIL_COLOR,
                    weight: 4,
                    opacity: 0.55,
                    lineCap: 'round',
                    lineJoin: 'round',
                };
                if (trailLine) {
                    trailLine.setLatLngs(data.trail);
                } else {
                    trailLine = L.polyline(data.trail, trailOpts).addTo(map);
                }
            } else if (trailLine && map) {
                map.removeLayer(trailLine);
                trailLine = null;
            }

            updateRoute(data);

            fitBounds();
            setTimeout(function () { if (map) map.invalidateSize(); }, 200);
        },
    };
})();

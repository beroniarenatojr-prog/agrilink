/**
 * Logistics rider GPS sharing (auto when in_transit orders are active).
 */
window.AgriLogisticsTracker = (function () {
    const watchers = {};
    let sendTimer = null;
    let statusTimer = null;
    let lastPositions = {};
    let lastPostAt = {};
    let config = {};

    function showBanner(orders, errorMsg) {
        let banner = document.getElementById('logisticsTrackingBanner');
        if (!orders.length && !errorMsg) {
            if (banner) banner.remove();
            return;
        }

        if (!banner) {
            banner = document.createElement('div');
            banner.id = 'logisticsTrackingBanner';
            banner.className = 'logistics-tracking-banner';
            document.body.appendChild(banner);
        }

        if (errorMsg) {
            banner.innerHTML =
                '<div class="logistics-tracking-banner__inner">' +
                '<span class="text-danger" style="font-size:0.875rem">' + errorMsg + '</span>' +
                '</div>';
            return;
        }

        const labels = orders.map(function (o) {
            return '<a href="' + config.orderViewBase + o.id + '">#' + o.id + '</a>';
        }).join(', ');

        banner.innerHTML =
            '<div class="logistics-tracking-banner__inner">' +
            '<span class="tracking-live-dot is-live"></span>' +
            '<span>Sharing live location for order ' + labels + '</span>' +
            '<span class="text-muted" style="font-size:0.8rem">Keep this tab open</span>' +
            '</div>';
    }

    function onPosition(orderId, pos) {
        lastPositions[orderId] = pos;

        if (typeof window.AgriTrackingMap !== 'undefined' && window.AgriTrackingMap.updateRiderPreview) {
            window.AgriTrackingMap.updateRiderPreview(pos.coords.latitude, pos.coords.longitude);
        }

        const now = Date.now();
        const last = lastPostAt[orderId] || 0;
        if (now - last >= 5000) {
            lastPostAt[orderId] = now;
            postPosition(orderId, pos);
        }
    }

    function postPosition(orderId, pos) {
        const body = new FormData();
        body.append('csrf_token', config.csrfToken);
        body.append('order_id', String(orderId));
        body.append('latitude', String(pos.coords.latitude));
        body.append('longitude', String(pos.coords.longitude));
        if (pos.coords.accuracy != null) body.append('accuracy', String(pos.coords.accuracy));
        if (pos.coords.heading != null) body.append('heading', String(pos.coords.heading));
        if (pos.coords.speed != null) body.append('speed', String(pos.coords.speed));

        fetch(config.updateUrl, {
            method: 'POST',
            body: body,
            credentials: 'same-origin',
        }).catch(function () {});
    }

    function sendAll() {
        Object.keys(lastPositions).forEach(function (orderId) {
            const pos = lastPositions[orderId];
            if (pos) onPosition(orderId, pos);
        });
    }

    function geoErrorMessage(err) {
        if (!err) return 'Location unavailable. Allow GPS access for this site.';
        if (err.code === 1) return 'Location blocked. Allow location permission in your browser.';
        if (err.code === 2) return 'Location unavailable. Check device GPS or network.';
        if (err.code === 3) return 'Location timed out. Try again or move near a window.';
        return 'Could not read your location.';
    }

    function startWatch(orderId) {
        if (watchers[orderId] || !navigator.geolocation) {
            if (!navigator.geolocation) {
                showBanner([], 'GPS is not supported in this browser.');
            }
            return;
        }

        navigator.geolocation.getCurrentPosition(
            function (pos) { onPosition(orderId, pos); },
            function (err) { showBanner([], geoErrorMessage(err)); },
            { enableHighAccuracy: true, maximumAge: 5000, timeout: 20000 }
        );

        watchers[orderId] = navigator.geolocation.watchPosition(
            function (pos) { onPosition(orderId, pos); },
            function (err) { showBanner([], geoErrorMessage(err)); },
            { enableHighAccuracy: true, maximumAge: 5000, timeout: 20000 }
        );
    }

    function stopWatch(orderId) {
        if (watchers[orderId]) {
            navigator.geolocation.clearWatch(watchers[orderId]);
            delete watchers[orderId];
        }
        delete lastPositions[orderId];
        delete lastPostAt[orderId];
    }

    function syncOrders(orders) {
        const activeIds = {};
        orders.forEach(function (o) {
            activeIds[String(o.id)] = true;
            startWatch(o.id);
        });

        Object.keys(watchers).forEach(function (id) {
            if (!activeIds[id]) stopWatch(id);
        });

        if (orders.length) {
            showBanner(orders);
        }

        if (orders.length && !sendTimer) {
            sendTimer = setInterval(sendAll, config.updateIntervalMs || 10000);
        }
        if (!orders.length && sendTimer) {
            clearInterval(sendTimer);
            sendTimer = null;
        }
    }

    function refreshActive() {
        if (!config.activeUrl) return;
        fetch(config.activeUrl, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (Array.isArray(data.orders)) syncOrders(data.orders);
            })
            .catch(function () {});
    }

    return {
        start: function (options) {
            config = options || {};
            if (Array.isArray(config.orders)) {
                syncOrders(config.orders);
            }
            refreshActive();
            if (!statusTimer) {
                statusTimer = setInterval(refreshActive, 30000);
            }
        },
    };
})();

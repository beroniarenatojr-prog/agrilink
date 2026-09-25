/**
 * Buyer / farmer live map polling.
 */
window.AgriBuyerTracking = (function () {
    let timer = null;

    function poll(orderId, apiUrl) {
        fetch(apiUrl + '?order_id=' + encodeURIComponent(orderId), {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.error) return;
                window.AgriTrackingMap.update(data);
                if (data.status !== 'in_transit' && timer) {
                    clearInterval(timer);
                    timer = null;
                }
            })
            .catch(function () {});
    }

    return {
        start: function (orderId, apiUrl, intervalMs) {
            poll(orderId, apiUrl);
            timer = setInterval(function () {
                poll(orderId, apiUrl);
            }, intervalMs || 5000);
        },
    };
})();

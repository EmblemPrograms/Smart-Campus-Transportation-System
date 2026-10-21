/**
 * Live Shuttle Tracking Map
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * Renders campus locations on an SVG map and refreshes active shuttle
 * positions via AJAX every TRACKING_REFRESH_INTERVAL (default 15s).
 */
(function (window, $) {
    'use strict';

    const SCTS = window.SCTS || (window.SCTS = {});

    const TrackingMap = {
        svg: null,
        locations: [],
        roads: [],
        shuttles: [],
        timer: null,
        width: 900,
        height: 480,
        padding: 40,

        /**
         * Initialise the tracking map.
         * @param {string} svgSelector SVG element selector
         * @param {object} options Optional {locations, roads}
         */
        init: function (svgSelector, options) {
            this.svg = document.querySelector(svgSelector);
            if (!this.svg) return;

            options = options || {};
            this.locations = options.locations || window.CAMPUS_LOCATIONS || [];
            this.roads = options.roads || window.CAMPUS_ROADS || [];

            this.drawBase();
            this.refresh();
            const interval = SCTS.trackingInterval || 15000;
            this.timer = setInterval(this.refresh.bind(this), interval);

            const self = this;
            $(document).on('click', '#refreshTrackingBtn', function () {
                self.refresh(true);
            });
        },

        /**
         * Project lat/lng into SVG coordinates.
         */
        project: function (lat, lng) {
            if (!this.locations.length) {
                return { x: this.width / 2, y: this.height / 2 };
            }
            let minLat = Infinity, maxLat = -Infinity, minLng = Infinity, maxLng = -Infinity;
            this.locations.forEach(function (l) {
                const la = parseFloat(l.latitude);
                const lo = parseFloat(l.longitude);
                if (la < minLat) minLat = la;
                if (la > maxLat) maxLat = la;
                if (lo < minLng) minLng = lo;
                if (lo > maxLng) maxLng = lo;
            });
            if (minLat === maxLat) { minLat -= 0.001; maxLat += 0.001; }
            if (minLng === maxLng) { minLng -= 0.001; maxLng += 0.001; }

            const pad = this.padding;
            const x = pad + ((parseFloat(lng) - minLng) / (maxLng - minLng)) * (this.width - pad * 2);
            const y = pad + ((maxLat - parseFloat(lat)) / (maxLat - minLat)) * (this.height - pad * 2);
            return { x: x, y: y };
        },

        /**
         * Draw static campus nodes and roads.
         */
        drawBase: function () {
            const svg = this.svg;
            svg.setAttribute('viewBox', '0 0 ' + this.width + ' ' + this.height);
            svg.innerHTML = '';

            const gRoads = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            gRoads.setAttribute('id', 'roadsLayer');
            const self = this;
            const locMap = {};
            this.locations.forEach(function (l) { locMap[l.id] = l; });

            this.roads.forEach(function (r) {
                const from = locMap[r.from_location_id];
                const to = locMap[r.to_location_id];
                if (!from || !to) return;
                const p1 = self.project(from.latitude, from.longitude);
                const p2 = self.project(to.latitude, to.longitude);
                const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                line.setAttribute('x1', p1.x);
                line.setAttribute('y1', p1.y);
                line.setAttribute('x2', p2.x);
                line.setAttribute('y2', p2.y);
                line.setAttribute('class', 'map-edge');
                line.setAttribute('data-from', r.from_location_id);
                line.setAttribute('data-to', r.to_location_id);
                gRoads.appendChild(line);
            });
            svg.appendChild(gRoads);

            const gNodes = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            gNodes.setAttribute('id', 'nodesLayer');
            this.locations.forEach(function (l) {
                const p = self.project(l.latitude, l.longitude);
                const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                circle.setAttribute('cx', p.x);
                circle.setAttribute('cy', p.y);
                circle.setAttribute('r', 8);
                circle.setAttribute('class', 'map-node');
                circle.setAttribute('data-id', l.id);
                gNodes.appendChild(circle);

                const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                text.setAttribute('x', p.x + 10);
                text.setAttribute('y', p.y - 8);
                text.setAttribute('class', 'map-label');
                text.textContent = l.name || l.code || ('#' + l.id);
                gNodes.appendChild(text);
            });
            svg.appendChild(gNodes);

            const gShuttles = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            gShuttles.setAttribute('id', 'shuttlesLayer');
            svg.appendChild(gShuttles);
        },

        /**
         * Fetch active trips and redraw shuttle markers.
         */
        refresh: function (showToast) {
            if (!SCTS.ajax || !SCTS.ajax.tracking) return;
            const self = this;
            SCTS.get(SCTS.ajax.tracking, { action: 'active' })
                .done(function (res) {
                    if (!res || !res.success) return;
                    self.shuttles = res.trips || [];
                    self.drawShuttles();
                    self.updateTripList(self.shuttles);
                    $('#trackingUpdatedAt').text(new Date().toLocaleTimeString());
                    if (showToast) SCTS.toast('Tracking data refreshed', 'success');
                })
                .fail(function () {
                    if (showToast) SCTS.toast('Failed to refresh tracking', 'error');
                });
        },

        /**
         * Draw / update shuttle markers on the map.
         */
        drawShuttles: function () {
            const layer = this.svg.querySelector('#shuttlesLayer');
            if (!layer) return;
            layer.innerHTML = '';
            const self = this;
            const locMap = {};
            this.locations.forEach(function (l) { locMap[l.id] = l; });

            this.shuttles.forEach(function (trip) {
                let lat = parseFloat(trip.latitude);
                let lng = parseFloat(trip.longitude);
                if ((!lat || !lng) && trip.current_location_id && locMap[trip.current_location_id]) {
                    lat = parseFloat(locMap[trip.current_location_id].latitude);
                    lng = parseFloat(locMap[trip.current_location_id].longitude);
                }
                if (!lat || !lng) return;

                const p = self.project(lat, lng);
                const marker = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                marker.setAttribute('cx', p.x);
                marker.setAttribute('cy', p.y);
                marker.setAttribute('r', 7);
                marker.setAttribute('class', 'shuttle-marker');
                marker.setAttribute('data-trip', trip.id);
                marker.innerHTML = '<title>' +
                    (trip.plate_number || 'Shuttle') + ' — ' +
                    (trip.driver_name || 'Driver') +
                    '</title>';
                layer.appendChild(marker);

                const label = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                label.setAttribute('x', p.x + 10);
                label.setAttribute('y', p.y + 14);
                label.setAttribute('class', 'map-label');
                label.setAttribute('fill', '#dc2626');
                label.textContent = trip.plate_number || ('Trip #' + trip.id);
                layer.appendChild(label);
            });
        },

        /**
         * Update the HTML trip list beside the map.
         */
        updateTripList: function (trips) {
            const list = $('#activeTripsList');
            if (!list.length) return;
            if (!trips.length) {
                list.html('<div class="text-muted small p-3">No active shuttles right now.</div>');
                return;
            }
            let html = '';
            trips.forEach(function (t) {
                html +=
                    '<div class="border-bottom p-3">' +
                    '<div class="fw-semibold">' + (t.plate_number || 'Vehicle') + '</div>' +
                    '<div class="small text-muted">' + (t.route_name || 'Route') + ' · ' + (t.driver_name || 'Driver') + '</div>' +
                    '<div class="small">' + (t.current_location_name || 'En route') + ' · ' + (t.status || '') + '</div>' +
                    '</div>';
            });
            list.html(html);
        },

        destroy: function () {
            if (this.timer) clearInterval(this.timer);
        }
    };

    SCTS.TrackingMap = TrackingMap;

    $(function () {
        if (document.getElementById('campusMapSvg')) {
            TrackingMap.init('#campusMapSvg');
        }
    });
})(window, jQuery);

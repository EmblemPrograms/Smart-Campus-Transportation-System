/**
 * Dijkstra Path Visualisation
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * Highlights shortest-path nodes and edges on the campus SVG map.
 */
(function (window, $) {
    'use strict';

    const SCTS = window.SCTS || (window.SCTS = {});

    const DijkstraMap = {
        svg: null,
        locations: [],
        roads: [],
        width: 900,
        height: 480,
        padding: 40,

        /**
         * Initialise Dijkstra map canvas.
         */
        init: function (svgSelector, options) {
            this.svg = document.querySelector(svgSelector);
            if (!this.svg) return;
            options = options || {};
            this.locations = options.locations || window.CAMPUS_LOCATIONS || [];
            this.roads = options.roads || window.CAMPUS_ROADS || [];
            this.drawBase();
            this.bindForm();
        },

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

        drawBase: function () {
            const svg = this.svg;
            svg.setAttribute('viewBox', '0 0 ' + this.width + ' ' + this.height);
            svg.innerHTML = '';
            const self = this;
            const locMap = {};
            this.locations.forEach(function (l) { locMap[l.id] = l; });

            const gRoads = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            gRoads.setAttribute('id', 'dijkstraRoads');
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
            gNodes.setAttribute('id', 'dijkstraNodes');
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
        },

        /**
         * Clear previous path highlighting.
         */
        clearPath: function () {
            if (!this.svg) return;
            this.svg.querySelectorAll('.map-edge').forEach(function (el) {
                el.classList.remove('path-edge');
            });
            this.svg.querySelectorAll('.map-node').forEach(function (el) {
                el.classList.remove('path-node', 'active');
            });
        },

        /**
         * Highlight a path of location IDs.
         * @param {number[]} pathIds Ordered location IDs
         */
        highlightPath: function (pathIds) {
            this.clearPath();
            if (!pathIds || pathIds.length === 0) return;

            const idSet = {};
            pathIds.forEach(function (id) { idSet[String(id)] = true; });

            this.svg.querySelectorAll('.map-node').forEach(function (el) {
                if (idSet[el.getAttribute('data-id')]) {
                    el.classList.add('path-node');
                }
            });

            for (let i = 0; i < pathIds.length - 1; i++) {
                const a = String(pathIds[i]);
                const b = String(pathIds[i + 1]);
                this.svg.querySelectorAll('.map-edge').forEach(function (el) {
                    const f = el.getAttribute('data-from');
                    const t = el.getAttribute('data-to');
                    if ((f === a && t === b) || (f === b && t === a)) {
                        el.classList.add('path-edge');
                    }
                });
            }
        },

        /**
         * Bind optimize form to AJAX Dijkstra endpoint.
         */
        bindForm: function () {
            const self = this;
            $(document).on('click', '#btnOptimizeDijkstra, #btnFindPath', function (e) {
                e.preventDefault();
                const startId = $('#start_location_id').val() || $('#dijkstra_start').val();
                const endId = $('#end_location_id').val() || $('#dijkstra_end').val();
                if (!startId || !endId) {
                    SCTS.toast('Select start and end locations', 'warning');
                    return;
                }
                SCTS.showSpinner();
                SCTS.post(SCTS.ajax.dijkstra, {
                    action: 'shortest_path',
                    start_id: startId,
                    end_id: endId
                })
                    .done(function (res) {
                        SCTS.hideSpinner();
                        if (!res || !res.success) {
                            SCTS.toast((res && res.message) || 'No path found', 'error');
                            self.clearPath();
                            return;
                        }
                        self.highlightPath(res.path || []);
                        $('#pathResult').removeClass('d-none');
                        $('#pathNames').text((res.path_names || []).join(' → '));
                        $('#pathDistance').text(res.distance_formatted || (res.distance + ' m'));
                        $('#pathHops').text(res.hops || 0);

                        // Populate hidden fields for saving route
                        if ($('#path_json').length) {
                            $('#path_json').val(JSON.stringify(res.path || []));
                        }
                        if ($('#total_distance').length) {
                            $('#total_distance').val(res.distance || 0);
                        }
                        if ($('#estimated_time').length && res.distance) {
                            $('#estimated_time').val(Math.max(1, Math.ceil(res.distance / 250)));
                        }
                        SCTS.toast('Shortest path computed', 'success');
                    })
                    .fail(function () {
                        SCTS.hideSpinner();
                        SCTS.toast('Dijkstra request failed', 'error');
                    });
            });
        }
    };

    SCTS.DijkstraMap = DijkstraMap;

    $(function () {
        if (document.getElementById('dijkstraMapSvg')) {
            DijkstraMap.init('#dijkstraMapSvg');
        }
    });
})(window, jQuery);

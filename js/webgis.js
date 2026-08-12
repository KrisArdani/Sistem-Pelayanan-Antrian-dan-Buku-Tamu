/**
 * SPST BPS Kota Tegal - WebGIS Interactive Engine (White Dashed Borders & Translucent Gray Regions)
 */

document.addEventListener('DOMContentLoaded', () => {
    let map = null;
    let geojsonLayer = null;
    let pieChart = null;
    let trendChart = null;
    let currentGeoJson = null;
    let currentApiData = null;

    // Translucent neutral slate/gray scale palette for pristine map transparency
    const grayPalette = ['#cbd5e1', '#94a3b8', '#64748b', '#475569'];

    // DOM Elements
    const mainCategorySelect = document.getElementById('mainCategorySelector');
    const dataCategorySelect = document.getElementById('dataCategorySelector');
    const areaSelect = document.getElementById('areaSelector');
    const yearSelect = document.getElementById('yearSelector');
    const startYearSelect = document.getElementById('startYearSelector');
    const endYearSelect = document.getElementById('endYearSelector');
    const autoSummaryEl = document.getElementById('autoSummaryContent');
    const statTotalValEl = document.getElementById('statTotalVal');
    const statTotalUnitEl = document.getElementById('statTotalUnit');

    // Initialize Leaflet Map
    function initMap() {
        if (map) return;
        
        // Center on Kota Tegal
        map = L.map('webgisMap', {
            center: [-6.868, 109.128],
            zoom: 13,
            zoomControl: true
        });

        // OpenStreetMap Base Tile Layer for high map clarity
        const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> | BPS Kota Tegal',
            maxZoom: 19
        });

        // CartoDB Positron Tile Layer (Light)
        const cartoLight = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; CARTO | BPS Kota Tegal',
            subdomains: 'abcd',
            maxZoom: 19
        });

        // Esri Satellite Layer
        const esriSat = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: '&copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS',
            maxZoom: 18
        });

        cartoLight.addTo(map);

        const baseMaps = {
            "Peta Terang (Default)": cartoLight,
            "OpenStreetMap (Jalan & Label)": osm,
            "Satelit (Foto Udara)": esriSat
        };

        L.control.layers(baseMaps, null, { position: 'topright' }).addTo(map);
    }

    // Get color for value in neutral gray scale
    function getColor(val, minVal, maxVal) {
        if (maxVal === minVal) return grayPalette[1];
        const norm = (val - minVal) / (maxVal - minVal);
        if (norm <= 0.25) return grayPalette[0];
        if (norm <= 0.50) return grayPalette[1];
        if (norm <= 0.75) return grayPalette[2];
        return grayPalette[3];
    }

    // Fetch GeoJSON and Render Map
    async function loadGeoJson() {
        if (!currentGeoJson) {
            try {
                const response = await fetch('js/geojson/kota_tegal_kecamatan.geojson');
                currentGeoJson = await response.json();
            } catch (err) {
                console.error('Gagal memuat GeoJSON Kota Tegal:', err);
                return;
            }
        }
        updateMapLayers();
    }

    // Update Map Layers (White Dashed Borders + Translucent Gray Regions)
    function updateMapLayers() {
        if (!map || !currentGeoJson || !currentApiData) return;

        if (geojsonLayer) {
            map.removeLayer(geojsonLayer);
        }

        const dataMap = currentApiData.current_data || {};
        const indName = currentApiData.active_indicator.name;
        const unit = currentApiData.active_indicator.unit;

        // Calculate Min and Max values for scale
        let values = Object.values(dataMap).map(d => d.value);
        let minVal = values.length ? Math.min(...values) : 0;
        let maxVal = values.length ? Math.max(...values) : 1;

        geojsonLayer = L.geoJSON(currentGeoJson, {
            style: (feature) => {
                const code = feature.properties.kode_kec;
                const item = dataMap[code];
                const val = item ? item.value : 0;
                const isSelectedArea = !areaSelect.value || areaSelect.value === code;

                return {
                    fillColor: getColor(val, minVal, maxVal),
                    weight: isSelectedArea ? 2.5 : 1.8,
                    opacity: 0.95,
                    color: '#ffffff', // Garis Putih Putus-putus
                    dashArray: '5,5',  // Dashed style
                    lineJoin: 'round',
                    lineCap: 'round',
                    fillOpacity: isSelectedArea ? 0.25 : 0.10
                };
            },
            onEachFeature: (feature, layer) => {
                const code = feature.properties.kode_kec;
                const name = feature.properties.nama_kec;
                const item = dataMap[code] || { formatted: '0', percentage: 0 };

                // Hover Tooltip
                layer.bindTooltip(`
                    <div class="px-2.5 py-1.5 text-xs font-sans bg-slate-900/90 text-white rounded-lg shadow-lg">
                        <div class="font-bold text-sky-300">${name}</div>
                        <div class="text-slate-200 font-semibold">${indName}: <b>${item.formatted} ${unit}</b> (${item.percentage}%)</div>
                    </div>
                `, { sticky: true, direction: 'top' });

                // Click Popup
                layer.bindPopup(`
                    <div class="p-3 font-sans min-w-[210px]">
                        <div class="text-xs uppercase font-extrabold text-sky-600 tracking-wider mb-1">Kecamatan Kota Tegal</div>
                        <h4 class="text-base font-extrabold text-slate-900 mb-2">${name}</h4>
                        <div class="space-y-1.5 text-xs text-slate-700 bg-slate-50 p-2.5 rounded-xl border border-slate-200">
                            <div class="flex justify-between"><span>Indikator:</span><b class="text-slate-900">${indName}</b></div>
                            <div class="flex justify-between"><span>Tahun:</span><b class="text-slate-900">${currentApiData.current_year}</b></div>
                            <div class="flex justify-between"><span>Nilai:</span><b class="text-sky-700 text-sm">${item.formatted} ${unit}</b></div>
                            <div class="flex justify-between"><span>Proporsi:</span><b class="text-emerald-700">${item.percentage}%</b></div>
                            <div class="flex justify-between pt-1 border-t border-slate-200"><span>Luas Wilayah:</span><b>${feature.properties.luas_km2} Km²</b></div>
                            <div class="flex justify-between"><span>Jumlah Kelurahan:</span><b>${feature.properties.jumlah_kelurahan}</b></div>
                        </div>
                    </div>
                `);

                // Mouseover highlight
                layer.on({
                    mouseover: (e) => {
                        const l = e.target;
                        l.setStyle({ weight: 3.5, color: '#00A3E0', dashArray: '', fillOpacity: 0.40 });
                        l.bringToFront();
                    },
                    mouseout: (e) => {
                        geojsonLayer.resetStyle(e.target);
                    },
                    click: (e) => {
                        areaSelect.value = code;
                        fetchDataAndUpdate();
                    }
                });
            }
        }).addTo(map);

        // Zoom fit if specific area selected
        if (areaSelect.value) {
            geojsonLayer.eachLayer((l) => {
                if (l.feature.properties.kode_kec === areaSelect.value) {
                    map.fitBounds(l.getBounds(), { padding: [30, 30] });
                }
            });
        }
    }

    // Render Pie Chart (Distribution)
    function renderPieChart(apiData) {
        const ctx = document.getElementById('pieChartCanvas').getContext('2d');
        const dataMap = apiData.current_data || {};
        
        const labels = Object.values(dataMap).map(d => d.name);
        const dataVals = Object.values(dataMap).map(d => d.percentage);
        const rawVals = Object.values(dataMap).map(d => d.formatted);
        const unit = apiData.active_indicator.unit;

        if (pieChart) pieChart.destroy();

        pieChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: dataVals,
                    backgroundColor: ['#0284c7', '#059669', '#d97706', '#9333ea'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: { family: 'Inter', size: 11, weight: '600' } }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const idx = context.dataIndex;
                                return `${context.label}: ${context.raw}% (${rawVals[idx]} ${unit})`;
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });
    }

    // Render Bar / Line Chart (Multi-Year Trend)
    function renderTrendChart(apiData) {
        const ctx = document.getElementById('trendChartCanvas').getContext('2d');
        const years = apiData.trend_years || [];
        const datasets = apiData.trend_datasets || [];
        const unit = apiData.active_indicator.unit;

        if (trendChart) trendChart.destroy();

        const chartDatasets = datasets.map((ds, idx) => ({
            label: ds.label,
            data: ds.data,
            borderColor: apiData.active_category.color || '#0284c7',
            backgroundColor: (apiData.active_category.color || '#0284c7') + '22',
            borderWidth: 3,
            tension: 0.3,
            fill: true,
            pointRadius: 4,
            pointBackgroundColor: '#ffffff',
            pointBorderWidth: 2
        }));

        trendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: years,
                datasets: chartDatasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: false,
                        grid: { color: '#f1f5f9' },
                        ticks: { font: { family: 'Inter', size: 11 } }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Inter', size: 11 } }
                    }
                },
                plugins: {
                    legend: { display: true, position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.raw.toLocaleString('id-ID')} ${unit}`;
                            }
                        }
                    }
                }
            }
        });
    }

    // Populate Dynamic Indicator Dropdown based on Main Category
    function updateIndicatorDropdown(apiData) {
        const categories = apiData.categories || [];
        const activeCat = apiData.active_category.code;
        const currentInd = apiData.active_indicator.code;

        const catObj = categories.find(c => c.code === activeCat);
        if (!catObj) return;

        dataCategorySelect.innerHTML = '';
        catObj.indicators.forEach(ind => {
            const opt = document.createElement('option');
            opt.value = ind.code;
            opt.textContent = `${ind.name} (${ind.unit})`;
            if (ind.code === currentInd) opt.selected = true;
            dataCategorySelect.appendChild(opt);
        });
    }

    // Main Data Fetcher
    async function fetchDataAndUpdate() {
        const cat = mainCategorySelect.value;
        const ind = dataCategorySelect.value;
        const area = areaSelect.value;
        const yr = yearSelect.value;
        const startYr = startYearSelect.value;
        const endYr = endYearSelect.value;

        const url = `api/webgis_data.php?category=${encodeURIComponent(cat)}&indicator=${encodeURIComponent(ind)}&area=${encodeURIComponent(area)}&year=${encodeURIComponent(yr)}&start_year=${encodeURIComponent(startYr)}&end_year=${encodeURIComponent(endYr)}`;

        try {
            const res = await fetch(url);
            const data = await res.json();

            if (data.status === 'success') {
                currentApiData = data;
                
                // Update UI Components
                updateIndicatorDropdown(data);
                if (statTotalValEl) statTotalValEl.textContent = data.total_city_formatted;
                if (statTotalUnitEl) statTotalUnitEl.textContent = data.active_indicator.unit;
                if (autoSummaryEl) autoSummaryEl.innerHTML = data.auto_summary;

                updateMapLayers();
                renderPieChart(data);
                renderTrendChart(data);
            }
        } catch (err) {
            console.error('Gagal mengambil data WebGIS dari API:', err);
        }
    }

    // Event Listeners
    mainCategorySelect.addEventListener('change', () => {
        dataCategorySelect.value = '';
        fetchDataAndUpdate();
    });

    dataCategorySelect.addEventListener('change', fetchDataAndUpdate);
    areaSelect.addEventListener('change', fetchDataAndUpdate);
    yearSelect.addEventListener('change', fetchDataAndUpdate);
    startYearSelect.addEventListener('change', fetchDataAndUpdate);
    endYearSelect.addEventListener('change', fetchDataAndUpdate);

    const btnResetFilter = document.getElementById('btnResetFilter');
    if (btnResetFilter) {
        btnResetFilter.addEventListener('click', () => {
            mainCategorySelect.value = 'penduduk';
            areaSelect.value = '';
            yearSelect.value = '2024';
            startYearSelect.value = '2020';
            endYearSelect.value = '2026';
            dataCategorySelect.value = '';
            fetchDataAndUpdate();
        });
    }

    // Initial Execution
    initMap();
    loadGeoJson();
    fetchDataAndUpdate();
});

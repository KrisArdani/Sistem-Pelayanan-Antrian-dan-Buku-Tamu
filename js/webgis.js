/**
 * SPST BPS Kota Tegal - WebGIS Interactive Engine (Dual Mode: Local Spatial & Live Web API BPS)
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

    // Dynamic UI Elements
    const mapCardTitleEl = document.getElementById('mapCardTitle');
    const mapSourceBadgeEl = document.getElementById('mapSourceBadge');
    const mapSourceTextEl = document.getElementById('mapSourceText');
    const distributionTitleEl = document.getElementById('distributionTitle');
    const distributionBadgeEl = document.getElementById('distributionBadge');
    const distributionDescEl = document.getElementById('distributionDesc');
    const distributionIconEl = document.getElementById('distributionIcon');
    const pieChartContainerEl = document.getElementById('pieChartContainer');
    const bpsComparisonContainerEl = document.getElementById('bpsComparisonContainer');
    const bpsValKotaEl = document.getElementById('bpsValKota');
    const bpsUnitKotaEl = document.getElementById('bpsUnitKota');
    const bpsValJatengEl = document.getElementById('bpsValJateng');
    const bpsUnitJatengEl = document.getElementById('bpsUnitJateng');
    const bpsJatengCardEl = document.getElementById('bpsJatengCard');

    // Initialize Leaflet Map
    function initMap() {
        if (map) return;
        
        // Center on Kota Tegal
        map = L.map('webgisMap', {
            center: [-6.868, 109.128],
            zoom: 13,
            zoomControl: true
        });

        // OpenStreetMap Base Tile Layer
        const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> | BPS Kota Tegal',
            maxZoom: 19
        });

        // CartoDB Positron Tile Layer (Light Default)
        const cartoLight = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; CARTO | BPS Kota Tegal',
            subdomains: 'abcd',
            maxZoom: 19
        });

        // Esri Satellite Layer
        const esriSat = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: '&copy; Esri &mdash; BPS Kota Tegal',
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

    // Update Map Layers (Adapts automatically between Local Spatial & Live BPS API mode)
    function updateMapLayers() {
        if (!map || !currentGeoJson || !currentApiData) return;

        if (geojsonLayer) {
            map.removeLayer(geojsonLayer);
        }

        const isBpsMode = currentApiData.data_source === 'bps_api';
        const indName = currentApiData.active_indicator.name;
        const unit = currentApiData.active_indicator.unit;
        const catColor = currentApiData.active_category.color || '#0284c7';

        if (isBpsMode) {
            // MODE BPS LIVE: Sorot seluruh wilayah Kota Tegal dengan warna tematik kategori
            const valKota = currentApiData.total_city_formatted;
            const comparison = currentApiData.comparison_data || {};
            const jatengVal = comparison['3399'] ? comparison['3399'].formatted : null;

            geojsonLayer = L.geoJSON(currentGeoJson, {
                style: (feature) => ({
                    fillColor: catColor,
                    weight: 2.5,
                    opacity: 0.9,
                    color: '#ffffff',
                    dashArray: '4,4',
                    fillOpacity: 0.22
                }),
                onEachFeature: (feature, layer) => {
                    const name = feature.properties.nama_kec;

                    let tooltipHtml = `
                        <div class="px-3 py-2 text-xs font-sans bg-slate-900/95 text-white rounded-xl shadow-xl border border-slate-700">
                            <div class="flex items-center gap-1 text-[10px] uppercase font-extrabold text-amber-400 mb-0.5">
                                <span class="material-icons text-xs">bolt</span> Data BPS Web API Live
                            </div>
                            <div class="font-bold text-white text-sm">Kota Tegal (${name})</div>
                            <div class="text-sky-300 font-semibold mt-1">${indName}: <b class="text-white text-sm">${valKota} ${unit}</b></div>
                            ${jatengVal ? `<div class="text-slate-300 text-[11px] mt-0.5">Jawa Tengah: <b>${jatengVal} ${unit}</b></div>` : ''}
                        </div>
                    `;
                    layer.bindTooltip(tooltipHtml, { sticky: true, direction: 'top' });

                    layer.bindPopup(`
                        <div class="p-3.5 font-sans min-w-[240px]">
                            <div class="flex items-center gap-1 text-[11px] uppercase font-extrabold text-amber-600 tracking-wider mb-1">
                                <span class="material-icons text-xs">verified</span> Rilis Resmi Web API BPS
                            </div>
                            <h4 class="text-base font-black text-slate-900 mb-2">Kota Tegal &bull; ${name}</h4>
                            <div class="space-y-2 text-xs text-slate-700 bg-sky-50/60 p-3 rounded-2xl border border-sky-200">
                                <div class="flex justify-between"><span>Indikator:</span><b class="text-slate-900">${indName}</b></div>
                                <div class="flex justify-between"><span>Tahun Data:</span><b class="text-slate-900">${currentApiData.current_year}</b></div>
                                <div class="flex justify-between items-center pt-1 border-t border-sky-200">
                                    <span>Capaian Kota Tegal:</span>
                                    <b class="text-sky-800 text-sm font-black">${valKota} ${unit}</b>
                                </div>
                                ${jatengVal ? `
                                <div class="flex justify-between items-center">
                                    <span>Rata-rata Jawa Tengah:</span>
                                    <b class="text-slate-600 font-bold">${jatengVal} ${unit}</b>
                                </div>` : ''}
                                <div class="pt-1.5 border-t border-sky-200 text-[11px] text-slate-500">
                                    Pembaruan terakhir BPS: <b>${currentApiData.bps_last_update || '-'}</b>
                                </div>
                            </div>
                        </div>
                    `);

                    layer.on({
                        mouseover: (e) => {
                            const l = e.target;
                            l.setStyle({ weight: 3.5, color: '#f59e0b', dashArray: '', fillOpacity: 0.40 });
                            l.bringToFront();
                        },
                        mouseout: (e) => {
                            geojsonLayer.resetStyle(e.target);
                        }
                    });
                }
            }).addTo(map);

            // Fit Kota Tegal
            map.setView([-6.868, 109.128], 13);

        } else {
            // MODE SPASIAL LOKAL: Choropleth per 4 Kecamatan
            const dataMap = currentApiData.current_data || {};
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
                        color: '#ffffff',
                        dashArray: '5,5',
                        lineJoin: 'round',
                        lineCap: 'round',
                        fillOpacity: isSelectedArea ? 0.25 : 0.10
                    };
                },
                onEachFeature: (feature, layer) => {
                    const code = feature.properties.kode_kec;
                    const name = feature.properties.nama_kec;
                    const item = dataMap[code] || { formatted: '0', percentage: 0 };

                    layer.bindTooltip(`
                        <div class="px-2.5 py-1.5 text-xs font-sans bg-slate-900/90 text-white rounded-lg shadow-lg">
                            <div class="font-bold text-sky-300">${name}</div>
                            <div class="text-slate-200 font-semibold">${indName}: <b>${item.formatted} ${unit}</b> (${item.percentage}%)</div>
                        </div>
                    `, { sticky: true, direction: 'top' });

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

            if (areaSelect.value) {
                geojsonLayer.eachLayer((l) => {
                    if (l.feature.properties.kode_kec === areaSelect.value) {
                        map.fitBounds(l.getBounds(), { padding: [30, 30] });
                    }
                });
            }
        }
    }

    // Render Pie Chart or Switch to BPS Comparison Widget
    function renderPieOrComparison(apiData) {
        const isBpsMode = apiData.data_source === 'bps_api';

        if (isBpsMode) {
            // Tampilkan Widget Komparasi BPS
            if (pieChartContainerEl) pieChartContainerEl.classList.add('hidden');
            if (bpsComparisonContainerEl) bpsComparisonContainerEl.classList.remove('hidden');

            if (distributionTitleEl) distributionTitleEl.textContent = 'Komparasi Capaian Wilayah';
            if (distributionBadgeEl) {
                distributionBadgeEl.textContent = 'BPS LIVE';
                distributionBadgeEl.className = 'text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 uppercase flex items-center gap-0.5';
            }
            if (distributionDescEl) distributionDescEl.textContent = 'Perbandingan data resmi Kota Tegal terhadap rata-rata Jawa Tengah.';
            if (distributionIconEl) distributionIconEl.textContent = 'compare_arrows';

            const comp = apiData.comparison_data || {};
            const unit = apiData.active_indicator.unit;

            if (bpsValKotaEl) bpsValKotaEl.textContent = comp['3376'] ? comp['3376'].formatted : '0';
            if (bpsUnitKotaEl) bpsUnitKotaEl.textContent = unit;

            if (comp['3399'] && comp['3399'].value > 0) {
                if (bpsJatengCardEl) bpsJatengCardEl.classList.remove('hidden');
                if (bpsValJatengEl) bpsValJatengEl.textContent = comp['3399'].formatted;
                if (bpsUnitJatengEl) bpsUnitJatengEl.textContent = unit;
            } else {
                if (bpsJatengCardEl) bpsJatengCardEl.classList.add('hidden');
            }

        } else {
            // Tampilkan Doughnut Pie Chart
            if (pieChartContainerEl) pieChartContainerEl.classList.remove('hidden');
            if (bpsComparisonContainerEl) bpsComparisonContainerEl.classList.add('hidden');

            if (distributionTitleEl) distributionTitleEl.textContent = 'Proporsi Data Wilayah';
            if (distributionBadgeEl) {
                distributionBadgeEl.textContent = 'KECAMATAN';
                distributionBadgeEl.className = 'text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 uppercase';
            }
            if (distributionDescEl) distributionDescEl.textContent = 'Proporsi distribusi data antar kecamatan pada tahun terpilih.';
            if (distributionIconEl) distributionIconEl.textContent = 'pie_chart';

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
    }

    // Render Bar / Line Chart (Multi-Year Trend)
    function renderTrendChart(apiData) {
        const ctx = document.getElementById('trendChartCanvas').getContext('2d');
        const years = apiData.trend_years || [];
        const datasets = apiData.trend_datasets || [];
        const unit = apiData.active_indicator.unit;
        const isBpsMode = apiData.data_source === 'bps_api';

        if (trendChart) trendChart.destroy();

        const chartDatasets = datasets.map((ds, idx) => {
            const color = ds.borderColor || (idx === 0 ? (apiData.active_category.color || '#0284c7') : '#94a3b8');
            return {
                label: ds.label,
                data: ds.data,
                borderColor: color,
                backgroundColor: color + '20',
                borderWidth: idx === 0 ? 3 : 2.2,
                borderDash: idx === 1 ? [5, 5] : [],
                tension: 0.3,
                fill: idx === 0,
                pointRadius: 4,
                pointBackgroundColor: '#ffffff',
                pointBorderWidth: 2
            };
        });

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
                    legend: { 
                        display: true, 
                        position: 'top',
                        labels: { font: { family: 'Inter', size: 11, weight: '600' } }
                    },
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
                const isBpsMode = data.data_source === 'bps_api';
                
                // Update UI Components
                updateIndicatorDropdown(data);
                if (statTotalValEl) statTotalValEl.textContent = data.total_city_formatted;
                if (statTotalUnitEl) statTotalUnitEl.textContent = data.active_indicator.unit;
                if (autoSummaryEl) autoSummaryEl.innerHTML = data.auto_summary;

                // Update Header Source Badge
                if (mapSourceBadgeEl && mapSourceTextEl) {
                    if (isBpsMode) {
                        mapSourceBadgeEl.className = 'text-[11px] font-extrabold px-3 py-1 rounded-lg bg-amber-50 text-amber-800 border border-amber-300 flex items-center gap-1 shadow-sm';
                        mapSourceTextEl.innerHTML = '<span class="material-icons text-xs text-amber-600">bolt</span> Web API BPS Live';
                        if (mapCardTitleEl) mapCardTitleEl.textContent = `Peta Cakupan Wilayah Kota Tegal &bull; ${data.active_category.name}`;
                    } else {
                        mapSourceBadgeEl.className = 'text-[11px] font-bold px-2.5 py-1 rounded-lg bg-sky-50 text-sky-700 border border-sky-200 flex items-center gap-1';
                        mapSourceTextEl.textContent = 'Data Spasial Kecamatan';
                        if (mapCardTitleEl) mapCardTitleEl.textContent = 'Peta Spasial Choropleth Kota Tegal';
                    }
                }

                // Handle Area Selector availability in BPS Mode
                if (areaSelect) {
                    if (isBpsMode) {
                        areaSelect.disabled = true;
                        areaSelect.title = 'Data indikator makro BPS berlaku agregat seluruh Kota Tegal';
                        areaSelect.classList.add('opacity-60', 'cursor-not-allowed');
                    } else {
                        areaSelect.disabled = false;
                        areaSelect.title = '';
                        areaSelect.classList.remove('opacity-60', 'cursor-not-allowed');
                    }
                }

                updateMapLayers();
                renderPieOrComparison(data);
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
            endYearSelect.value = '2024';
            dataCategorySelect.value = '';
            fetchDataAndUpdate();
        });
    }

    // Initial Execution
    initMap();
    loadGeoJson();
    fetchDataAndUpdate();
});

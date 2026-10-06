@extends('features.layout')

@section('title', 'Dashboard - AidFlow')
@section('page-title', 'Dashboard')

@section('content')
    <div class=" flex flex-col gap-4">
        <style>
            /* Glowing highlight styles for Leaflet SVG features */
            .glow-red {
                filter: drop-shadow(0 0 10px rgba(239, 68, 68, 0.95));
                transition: filter 150ms ease;
            }

            .glow-amber {
                filter: drop-shadow(0 0 10px rgba(180, 100, 20, 0.9));
                transition: filter 150ms ease;
            }

            .glow-green {
                filter: drop-shadow(0 0 10px rgba(40, 148, 50, 0.9));
                transition: filter 150ms ease;
            }

            .glow-hover {
                stroke-width: 3px;
            }
        </style>
        <div class="flex flex-row flex-wrap lg:flex-nowrap gap-2">
            <!-- Total Inventory -->
            <div class="bg-white rounded-[20px] border-[3px] border-[#8a1c1c] p-4 flex items-center shadow-sm w-full">
                <div class="shrink-0 mr-4">
                    <div
                        class="w-15 h-15 rounded-[18px] border-[3px] border-[#8a1c1c] bg-[#ff6b6b] flex items-center justify-center">
                        <i class="fas fa-warehouse text-2xl text-[#1f0000]"></i>
                    </div>
                </div>
                <div>
                    <p class="text-xs text-gray-700 mb-0.5 whitespace-nowrap">Total Inventory Items</p>
                    <p class="text-[28px] leading-none font-medium">{{ number_format($totalItems) }}</p>
                </div>
            </div>

            <!-- Standard relief packs -->
            <div class="bg-white rounded-[20px] border-[3px] border-[#8a1c1c] p-4 flex items-center shadow-sm w-full">
                <div class="shrink-0 mr-4">
                    <div
                        class="w-15 h-15 rounded-[18px] border-[3px] border-[#8a1c1c] bg-[#ff6b6b] flex items-center justify-center">
                        <i class="fas fa-shopping-bag text-2xl text-[#1f0000]"></i>
                    </div>
                </div>
                <div>
                    <p class="text-xs text-gray-700 mb-0.5 whitespace-nowrap">Standard relief packs</p>
                    <p class="text-[28px] leading-none font-medium">{{ number_format($standardReliefPacks) }}</p>
                </div>
            </div>

            <!-- Occupied Tents -->
            <div class="bg-white rounded-[20px] border-[3px] border-[#8a1c1c] p-4 flex items-center shadow-sm w-full">
                <div class="shrink-0 mr-4">
                    <div
                        class="w-15 h-15 rounded-[18px] border-[3px] border-[#8a1c1c] bg-[#ff6b6b] flex items-center justify-center">
                        <i class="fas fa-campground text-2xl text-[#1f0000]"></i>
                    </div>
                </div>
                <div>
                    <p class="text-xs text-gray-700 mb-0.5 whitespace-nowrap">Occupied Tents</p>
                    <p class="text-[28px] leading-none font-medium">{{ number_format($occupiedTentsCount) }}</p>
                </div>
            </div>

            <!-- Occupied Barangays -->
            <div class="bg-white rounded-[20px] border-[3px] border-[#8a1c1c] p-4 flex items-center shadow-sm w-full">
                <div class="shrink-0 mr-4">
                    <div
                        class="w-15 h-15 rounded-[18px] border-[3px] border-[#8a1c1c] bg-[#ff6b6b] flex items-center justify-center">
                        <i class="fas fa-university text-2xl text-[#1f0000]"></i>
                    </div>
                </div>
                <div>
                    <p class="text-xs text-gray-700 mb-0.5 whitespace-nowrap">Occupied Barangays</p>
                    <p class="text-[28px] leading-none font-medium">{{ number_format($barangayCount) }}</p>
                </div>
            </div>
        </div>

        <div class="flex flex-row gap-2 flex-wrap lg:flex-nowrap w-full min-h-[calc(90vh-120px)]">
            {{-- Inventory --}}
            <div class="bg-white rounded-[20px] border-[3px] border-[#8a1c1c] p-4 shadow-sm flex flex-col w-130">
                <h2 class="text-lg text-[#be3d3d] mb-3">Recent Inventory Transactions</h2>

                <table class="w-full mb-4 text-xs text-left border border-gray-300 rounded-lg overflow-hidden bg-white">
                    <caption class="sr-only">Standard relief pack threshold</caption>
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 font-semibold text-gray-700">Standard relief packs</th>
                            <th class="px-3 py-2 font-semibold text-gray-700 text-right">Threshold</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-t border-gray-300">
                            <td class="px-3 py-2 text-gray-700">
                                <div class="flex items-center gap-2">
                                    <div class="h-2.5 flex-1 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-[#d94b4b] rounded-full transition-all duration-500"
                                            style="width: {{ min(100, ($standardReliefPacks / $standardReliefPackThreshold) * 100) }}%"></div>
                                    </div>
                                    <span class="whitespace-nowrap font-semibold text-[#8a1c1c]">
                                        {{ number_format($standardReliefPacks) }} / {{ number_format($standardReliefPackThreshold) }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-3 py-2 text-right font-semibold text-gray-700">
                                {{ number_format($standardReliefPackThreshold) }} packs
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="border border-gray-400 rounded-[14px] overflow-hidden flex-1 mb-4 flex flex-col">
                    <div class="overflow-y-auto">
                        <table class="w-full text-xs text-left">
                            <thead>
                                <tr class="border-b border-gray-300">
                                    <th class="px-3 py-2 font-normal text-gray-800">Date</th>
                                    <th class="px-3 py-2 font-normal text-gray-800">Type</th>
                                    <th class="px-3 py-2 font-normal text-gray-800">Item</th>
                                    <th class="px-3 py-2 font-normal text-gray-800">Category</th>
                                    <th class="px-3 py-2 font-normal text-gray-800 text-right">Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($inventory->take(8) as $index => $item)
                                    @php
                                        $isOut = $index === 2 || $index === 6 || $index === 7;
                                        $typeStr = $isOut ? 'Stock-Out' : 'Stock-In';
                                        $typeColor = $isOut ? 'text-red-500' : 'text-green-500';
                                        $dateStr = !empty($item['received']) ?
                                            \Carbon\Carbon::parse($item['received'])->format('m/d/y') : date('m/d/y');
                                    @endphp
                                    <tr>
                                        <td class="px-3 py-1.5 text-gray-700 whitespace-nowrap">{{ $dateStr }}</td>
                                        <td class="px-3 py-1.5 {{ $typeColor }}">{{ $typeStr }}</td>
                                        <td class="px-3 py-1.5 text-gray-700">{{ $item['name'] ?? 'Item' }}</td>
                                        <td class="px-3 py-1.5 text-gray-700">{{ $item['category'] ?? 'Category' }}</td>
                                        <td class="px-3 py-1.5 text-gray-700 text-right">{{ $item['stock'] ?? 0 }}
                                            {{ $item['unit'] ?? '' }}
                                        </td>
                                    </tr>
                                @empty

                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="flex justify-end mt-auto pt-2">
                    <a href="{{ route('inventory.index') }}"
                        class="px-5 py-1 bg-[#faa8a8] text-[#1f0000] border border-[#d97c7c] rounded-full text-sm hover:bg-[#ffbaba] transition">View
                        All</a>
                </div>
            </div>

            <!-- Evacuation Overview -->
            <div class="bg-white rounded-[20px] border-[3px] border-[#8a1c1c] p-4 shadow-sm flex flex-col w-200">
                <h2 class="text-lg text-[#be3d3d] mb-3">Evacuation Overview</h2>

                <div class="flex flex-wrap items-center gap-3 mb-3 rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-700"
                     aria-label="Evacuation map legend">
                    <span class="flex items-center gap-1.5">
                        <span class="h-3 w-3 rounded-full bg-[#289432]" aria-hidden="true"></span>
                        Free
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="h-3 w-3 rounded-full bg-[#948628]" aria-hidden="true"></span>
                        Occupied
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="h-3 w-3 rounded-full bg-[#ef4444]" aria-hidden="true"></span>
                        Full
                    </span>
                </div>

                <!-- Map Area -->
                <div
                    class="border border-gray-400 mb-4 w-full h-55 sm:h-65 md:h-80 lg:h-100 xl:h-120 rounded-tr-[14px] rounded-tl-[14px] overflow-hidden bg-gray-50">

                    <div id="dashboard-map" class="w-full h-full"></div>
                </div>

                <!-- Recent Scanned Tents -->
                <div
                    class="border border-gray-400 rounded-br-[14px] rounded-bl-[14px] overflow-hidden flex-1 flex flex-col">
                    <div class="px-3 py-1.5">
                        <h3 class="text-[#8a1c1c] text-sm">Recent Scanned Tents</h3>
                    </div>
                    <div class="overflow-y-auto">
                        <table class="w-full text-xs text-left border-t border-gray-300">
                            <thead>
                                <tr class="border-b border-gray-300">
                                    <th class="px-3 py-1.5 font-normal text-gray-800">Date</th>
                                    <th class="px-3 py-1.5 font-normal text-gray-800">Tent ID</th>
                                    <th class="px-3 py-1.5 font-normal text-gray-800">Barangay</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentScannedTents->take(6) as $scan)
                                    <tr>
                                        <td class="px-3 py-1 text-gray-700">
                                            {{ !empty($scan['scannedAt']) ? \Carbon\Carbon::parse($scan['scannedAt'])->format('m/d/y') : date('m/d/y') }}
                                        </td>
                                        <td class="px-3 py-1 text-gray-700">{{ $scan['tentCode'] ?? 'T-001' }}</td>
                                        <td class="px-3 py-1 text-gray-700">
                                            {{ $scan['barangay'] ?? $scan['barangayName'] ?? 'Balong Bato' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="px-3 py-1 text-gray-700">06/15/26</td>
                                        <td class="px-3 py-1 text-gray-700">T-001</td>
                                        <td class="px-3 py-1 text-gray-700">Balong Bato</td>
                                    </tr>
                                    <tr>
                                        <td class="px-3 py-1 text-gray-700">06/15/26</td>
                                        <td class="px-3 py-1 text-gray-700">T-002</td>
                                        <td class="px-3 py-1 text-gray-700">Balong Bato</td>
                                    </tr>
                                    <tr>
                                        <td class="px-3 py-1 text-gray-700">06/15/26</td>
                                        <td class="px-3 py-1 text-gray-700">T-003</td>
                                        <td class="px-3 py-1 text-gray-700">Balong Bato</td>
                                    </tr>
                                    <tr>
                                        <td class="px-3 py-1 text-gray-700">06/15/26</td>
                                        <td class="px-3 py-1 text-gray-700">T-004</td>
                                        <td class="px-3 py-1 text-gray-700">Balong Bato</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Forecast -->
            <div class="bg-white rounded-[20px] border-[3px] border-[#8a1c1c] p-5 shadow-sm flex flex-col w-150">
                <h2 class="text-lg text-[#be3d3d] mb-5 flex items-center gap-2">
                    <i class="fas fa-chart-line"></i> Forecast
                </h2>

                <div class="mb-5">
                    <p class="text-gray-900 text-sm mb-1">Predicted Family Head Count</p>
                    <div class="flex items-center gap-6">
                        <span class="text-4xl font-bold text-[#cc2929]">{{ number_format($forecastFamilyHeads) }}</span>
                    </div>
                </div>

                <div class="mb-6 border-t border-gray-200 pt-5">
                    <p class="text-gray-900 text-sm mb-1">Estimated Family Food Packs Needed</p>
                    <div class="flex items-center gap-6">
                        <span class="text-4xl font-bold text-[#cc2929]">{{ number_format($forecastFfp) }}</span>
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-5 flex-1 flex flex-col">
                    <p class="text-gray-900 text-sm mb-4">Audit Logs</p>

                    <div class="space-y-3 text-xs text-gray-700 flex-1">
                        @forelse($auditLogs as $log)
                            @php
                                $timestamp = $log['timestamp'] ?? null;
                                $formattedTime = $timestamp ? \Carbon\Carbon::parse($timestamp)->format('M d • h:i A') : 'Unknown time';
                                $meta = $log['type'] === 'reliefPackScan'
                                    ? 'Pack #' . ($log['packNumber'] ?? '')
                                    : ($log['tentCode'] ?? 'N/A') . ($log['barangayName'] ? ' • ' . $log['barangayName'] : '');
                            @endphp

                            <div class="flex items-start gap-2 pb-2 border-b border-gray-200 last:border-b-0 last:pb-0">
                                <span class="mt-1 h-2.5 w-2.5 rounded-full {{ $log['color'] ?? 'bg-blue-500' }}"></span>
                                <div class="flex-1">
                                    <p class="font-semibold text-gray-800">{{ $log['label'] ?? 'System event' }}</p>
                                    @if($meta)
                                        <p class="text-[11px] text-gray-500">{{ $meta }}</p>
                                    @endif
                                    <p class="text-[11px] text-gray-500">{{ $formattedTime }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-gray-500">No audit logs available.</p>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>
    </div>
    @push('scripts')
        <script type="module">
            import { barangayList } from '/js/barangayList.js';

            const barangayMap = {};
            barangayList.forEach(b => {
                barangayMap[b.name] = {
                    code: b.code,
                    maxTents: Object.keys(b.tents).length
                };
            });

            const occupancy = @json($occupiedTents);
            let geoLayer = null;

            const getStyleForFeature = (feature) => {
                const info = barangayMap[feature.properties.name];
                if (!info) {
                    return { color: '#555', fillColor: '#999', fillOpacity: .4, weight: 1.5 };
                }


                let occupied = 0;
                for (const [id, data] of Object.entries(occupancy)) {

                    occupied += data.barangayCode === info.code ? 1 : 0;
                }
                const ratio = info.maxTents > 0 ? occupied / info.maxTents : 0;

                // Highlight any occupied barangay in amber and high utilization (80%+) in red.
                let fill = '#289432';
                let border = '#1C4D23';
                let className = 'glow-green';

                if (ratio > 0) {
                    fill = '#948628';
                    border = '#c2410c';
                    className = 'glow-amber';
                }
                if (ratio >= 0.8) {
                    fill = '#ef4444';
                    border = '#991b1b';
                    className = 'glow-red';
                }

                return {
                    color: border,
                    fillColor: fill,
                    fillOpacity: .7,
                    weight: 1.5,
                    className: className
                };
            };

            const updateLayerTooltips = () => {
                if (!geoLayer) return;

                geoLayer.eachLayer((layer) => {
                    const feature = layer.feature;
                    if (!feature?.properties?.name) return;

                    const info = barangayMap[feature.properties.name];
                    if (!info) return;

                    let occupied = 0;
                    for (const [id, data] of Object.entries(occupancy)) {
                        occupied += data.barangayCode === info.code ? 1 : 0;
                    }
                    const tooltipText = `${feature.properties.name} — ${occupied}/${info.maxTents} occupied`;

                    if (layer.getTooltip()) {
                        layer.setTooltipContent(tooltipText);
                    } else {
                        layer.bindTooltip(tooltipText);
                    }

                    layer.setStyle(getStyleForFeature(feature));
                });
            };

            document.addEventListener('DOMContentLoaded', () => {
                const map = L.map('dashboard-map', {
                    zoomControl: false,
                    dragging: false,
                    doubleClickZoom: false,
                    scrollWheelZoom: false,
                    attributionControl: false,
                    maxZoom: 16,
                    minZoom: 12
                });

                fetch('/geojson/sanjuan.geojson')
                    .then(r => r.json())
                    .then(data => {
                        const geoLayer = L.geoJSON(data, {
                            pointToLayer(feature, latlng) {
                                // Hide default point icons from GeoJSON points.
                                return L.circleMarker(latlng, {
                                    radius: 0,
                                    opacity: 0,
                                    fillOpacity: 0
                                });
                            },
                            style(feature) {
                                return getStyleForFeature(feature);
                            },
                            onEachFeature(feature, layer) {
                                const info = barangayMap[feature.properties.name];
                                if (!info) return;

                                let occupied = 0;
                                for (const [id, data] of Object.entries(occupancy)) {
                                    occupied += data.barangayCode === info.code ? 1 : 0;
                                }
                                layer.bindTooltip(`${feature.properties.name} — ${occupied}/${info.maxTents} occupied`);

                                // Add hover handlers to apply a stronger glow and bring feature to front
                                layer.on('mouseover', () => {
                                    const el = layer.getElement ? layer.getElement() : layer._path;
                                    if (el) el.classList.add('glow-hover');
                                    if (layer.bringToFront) layer.bringToFront();
                                });
                                layer.on('mouseout', () => {
                                    const el = layer.getElement ? layer.getElement() : layer._path;
                                    if (el) el.classList.remove('glow-hover');
                                    try { layer.setStyle(getStyleForFeature(feature)); } catch (e) { }
                                });
                            }
                        }).addTo(map);

                        updateLayerTooltips();
                        // Wait for the map container to finish laying out before measuring its bounds.
                        setTimeout(() => {
                            map.invalidateSize();
                            const bounds = geoLayer.getBounds();
                            if (bounds.isValid()) {
                                map.fitBounds(bounds, { padding: [16, 16], maxZoom: 16 });
                            }
                        }, 100);
                    });


                    // update layer tooltips when occupancy data changes in real-time via Firebase
                if (window.firebaseDatabase) {
                    const occupancyRef = ref(window.firebaseDatabase, 'occupiedTents');

                    onValue(occupancyRef, (snapshot) => {
                        const data = snapshot.val() ?? {};
                        const nextOccupancy = {};

                        Object.values(data).forEach((entry) => {
                            const barangayCode = entry?.barangayCode ?? entry?.barangayCode ?? null;
                            if (barangayCode) {
                                nextOccupancy[barangayCode] = (nextOccupancy[barangayCode] ?? 0) + 1;
                            }
                        });

                        Object.keys(occupancy).forEach((code) => delete occupancy[code]);
                        Object.assign(occupancy, nextOccupancy);
                        updateLayerTooltips();
                    });
                }
            });
        </script>
    @endpush

@endsection
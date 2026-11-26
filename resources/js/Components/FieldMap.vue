<template>
    <div class="field-map-container">
        <ol-map :loadTilesWhileAnimating="true" :loadTilesWhileInteracting="true" :style="{ height: height }">
            <ol-view ref="view" :center="center" :rotation="rotation" :zoom="zoom" :projection="projection" />

            <ol-tile-layer>
                <ol-source-xyz
                    url="https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}"
                    crossOrigin="anonymous"
                />
            </ol-tile-layer>

            <ol-tile-layer :visible="showLabels">
                <ol-source-osm :opacity="0.4" />
            </ol-tile-layer>

            <ol-vector-layer>
                <ol-source-vector :projection="projection" :features="highlightedFeatures">
                    <ol-interaction-modify v-if="modifyEnabled" :features="selectedFeatures" @modifyend="modifyend" />
                    <ol-interaction-draw v-if="drawEnabled" type="Polygon" :stopClick="true" @drawend="drawend" />
                    <ol-interaction-snap v-if="modifyEnabled || drawEnabled" />
                </ol-source-vector>
                <ol-style>
                    <ol-style-stroke color="#6576ff" :width="2" />
                    <ol-style-fill color="rgba(255, 255, 255, 0.2)" />
                </ol-style>
            </ol-vector-layer>

            <ol-interaction-select @select="featureSelected" :features="selectedFeatures">
                <ol-style>
                    <ol-style-stroke color="#6576ff" :width="3" />
                    <ol-style-fill color="rgba(101, 118, 255, 0.3)" />
                </ol-style>
            </ol-interaction-select>
        </ol-map>

        <div v-if="mode === 'create'" class="map-search">
            <div class="input-group">
                <input
                    type="search"
                    class="form-control"
                    placeholder="Search for a place..."
                    aria-label="Search for a place"
                    v-model="searchQuery"
                    @keydown.enter.prevent="searchLocation"
                    @keydown.esc="clearSearchResults"
                />
                <button
                    type="button"
                    class="btn btn-primary"
                    aria-label="Search"
                    :disabled="isSearching || !searchQuery.trim()"
                    @click="searchLocation"
                >
                    <span v-if="isSearching" class="spinner-border spinner-border-sm" role="status"></span>
                    <em v-else class="icon ni ni-search"></em>
                </button>
            </div>
            <ul v-if="searchResults.length > 0" class="list-group map-search-results">
                <li v-for="result in searchResults" :key="result.place_id" class="list-group-item p-0">
                    <button type="button" class="list-group-item-action map-search-result" @click="goToResult(result)">
                        {{ result.display_name }}
                    </button>
                </li>
            </ul>
            <div v-else-if="searchMessage" class="map-search-message">{{ searchMessage }}</div>
        </div>

        <div v-if="showControls" class="map-controls">
            <div class="control-group">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="show-labels" v-model="showLabels" />
                    <label class="form-check-label" for="show-labels">Show cities name</label>
                </div>
                <button v-if="mode === 'edit' || mode === 'create'" type="button" class="btn btn-primary ml-2" @click="toggleDraw">
                    <em class="icon ni ni-edit me-1"></em>
                    <span>{{ drawEnabled ? 'Draw active' : 'Draw polygon' }}</span>
                </button>
            </div>
        </div>

        <div v-if="areaInfo && calculatedArea" class="area-info">
            <div class="card">
                <div class="card-body">
                    <p class="mb-0">
                        Size (hectares):
                        <strong>{{ calculatedArea }} ha</strong>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { fromLonLat, toLonLat, transformExtent } from 'ol/proj';
import { Collection } from 'ol';
import Feature from 'ol/Feature';
import Polygon from 'ol/geom/Polygon';
import { Map, Layers, Sources, Interactions, Styles } from 'vue3-openlayers';
import 'vue3-openlayers/styles.css';
import { ConfirmModal } from '@/Helpers';

const { OlMap, OlView } = Map;
const { OlTileLayer, OlVectorLayer } = Layers;
const { OlSourceXyz, OlSourceOsm, OlSourceVector } = Sources;
const { OlInteractionModify, OlInteractionDraw, OlInteractionSnap, OlInteractionSelect } = Interactions;
const { OlStyle, OlStyleStroke, OlStyleFill } = Styles;

const props = defineProps({
    mode: {
        type: String,
        default: 'view',
        validator: (value) => ['create', 'edit', 'view'].includes(value),
    },
    boundaries: {
        type: Array,
        default: () => [],
    },
    showControls: {
        type: Boolean,
        default: true,
    },
    height: {
        type: String,
        default: '500px',
    },
    areaInfo: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['update:boundaries', 'area-calculated', 'draw-start', 'draw-end', 'modify-end']);

const center = ref(fromLonLat([19.0, 52.0]));
const zoom = ref(6);
const projection = ref('EPSG:3857');
const rotation = ref(0);
const view = ref(null);

const drawEnabled = ref(false);
const modifyEnabled = ref(false);
const showLabels = ref(true);
const highlightedFeatures = ref([]);
const selectedFeatures = ref(new Collection());
const calculatedArea = ref(null);

const searchQuery = ref('');
const searchResults = ref([]);
const searchMessage = ref(null);
const isSearching = ref(false);

const clearSearchResults = () => {
    searchResults.value = [];
    searchMessage.value = null;
};

const searchLocation = async () => {
    const query = searchQuery.value.trim();
    if (!query || isSearching.value) return;

    isSearching.value = true;
    clearSearchResults();

    try {
        const params = new URLSearchParams({ q: query, format: 'json', limit: '5' });
        const response = await fetch(`https://nominatim.openstreetmap.org/search?${params}`, {
            headers: { 'Accept-Language': navigator.language || 'en' },
        });

        if (!response.ok) {
            throw new Error(`Geocoding request failed with status ${response.status}`);
        }

        const results = await response.json();
        searchResults.value = results;

        if (results.length === 0) {
            searchMessage.value = 'No places found.';
        }
    } catch {
        searchMessage.value = 'Search is unavailable, try again later.';
    } finally {
        isSearching.value = false;
    }
};

const goToResult = (result) => {
    if (!view.value) return;

    const [south, north, west, east] = result.boundingbox.map(Number);
    const extent = transformExtent([west, south, east, north], 'EPSG:4326', projection.value);

    view.value.fit(extent, {
        maxZoom: 17,
        duration: 1000,
    });

    searchQuery.value = result.display_name;
    clearSearchResults();
};

const calculateArea = (feature) => {
    const areaInSquareMeters = Math.abs(feature.getGeometry().getArea());
    calculatedArea.value = parseFloat((areaInSquareMeters / 10000).toFixed(2));

    emit('area-calculated', calculatedArea.value);
};

const updateBoundaries = () => {
    if (highlightedFeatures.value.length === 0) return;

    const mercatorCoords = highlightedFeatures.value[0].getGeometry().getCoordinates()[0];

    emit(
        'update:boundaries',
        mercatorCoords.map((coord) => toLonLat(coord)),
    );
};

const loadFieldBoundaries = (boundaries) => {
    if (!boundaries || boundaries.length === 0) return;

    const polygon = new Polygon([boundaries.map((coord) => fromLonLat(coord))]);
    const feature = new Feature(polygon);

    highlightedFeatures.value = [feature];

    calculateArea(feature);

    if (view.value) {
        view.value.fit(polygon.getExtent(), {
            padding: [50, 50, 50, 50],
            duration: 1000,
        });
    }

    if (props.mode === 'edit') {
        modifyEnabled.value = true;
        selectedFeatures.value.clear();
        selectedFeatures.value.push(feature);
    }
};

const resetMap = () => {
    drawEnabled.value = false;
    modifyEnabled.value = false;
    highlightedFeatures.value = [];
    selectedFeatures.value.clear();
    calculatedArea.value = null;
};

const startDrawing = () => {
    resetMap();
    drawEnabled.value = true;
    emit('draw-start');
};

const toggleDraw = async () => {
    if (highlightedFeatures.value.length === 0) {
        startDrawing();
        return;
    }

    const result = await ConfirmModal.fire({
        icon: 'warning',
        title: 'Clear the current polygon?',
        text: 'The boundary you have drawn will be removed.',
        confirmButtonText: 'Yes, clear it',
    });

    if (result.isConfirmed) {
        startDrawing();
    }
};

const drawend = (event) => {
    drawEnabled.value = false;
    modifyEnabled.value = true;

    highlightedFeatures.value = [event.feature];
    selectedFeatures.value.clear();
    selectedFeatures.value.push(event.feature);

    calculateArea(event.feature);
    updateBoundaries();

    emit('draw-end', event.feature);
};

const modifyend = (event) => {
    event.features.forEach((feature) => calculateArea(feature));

    updateBoundaries();
    emit('modify-end', event.features);
};

const featureSelected = (event) => {
    modifyEnabled.value = event.selected?.length > 0;
    selectedFeatures.value = event.target.getFeatures();
};

onMounted(() => {
    if (props.boundaries && props.boundaries.length > 0) {
        setTimeout(() => loadFieldBoundaries(props.boundaries), 300);
    }

    if (props.mode === 'create') {
        setTimeout(() => {
            drawEnabled.value = true;
            emit('draw-start');
        }, 500);
    }
});

watch(
    () => props.boundaries,
    (newBoundaries) => {
        if (newBoundaries && newBoundaries.length > 0 && view.value) {
            loadFieldBoundaries(newBoundaries);
        }
    },
    { deep: true },
);

watch(
    () => props.mode,
    (newMode) => {
        if (newMode === 'edit') {
            modifyEnabled.value = true;
        } else if (newMode === 'view') {
            modifyEnabled.value = false;
            drawEnabled.value = false;
        } else if (newMode === 'create') {
            drawEnabled.value = true;
            emit('draw-start');
        }
    },
);
</script>

<style scoped>
.field-map-container {
    position: relative;
    width: 100%;
}

.map-controls {
    position: absolute;
    bottom: 10px;
    left: 10px;
    right: 10px;
    background-color: rgba(255, 255, 255, 0.8);
    border-radius: 4px;
    padding: 10px;
    z-index: 10;
}

.control-group {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.map-search {
    position: absolute;
    top: 10px;
    left: 50px;
    width: min(400px, calc(100% - 200px));
    min-width: 220px;
    z-index: 11;
}

.map-search-results {
    margin-top: 4px;
    max-height: 240px;
    overflow-y: auto;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
}

.map-search-result {
    display: block;
    width: 100%;
    padding: 0.5rem 1rem;
    border: 0;
    background: none;
    text-align: left;
    font-size: 0.875rem;
}

.map-search-message {
    margin-top: 4px;
    padding: 6px 12px;
    border-radius: 4px;
    background-color: rgba(255, 255, 255, 0.9);
    font-size: 0.875rem;
}

.area-info {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 10;
}

.area-info .card {
    background-color: rgba(255, 255, 255, 0.8);
    border: none;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}
</style>

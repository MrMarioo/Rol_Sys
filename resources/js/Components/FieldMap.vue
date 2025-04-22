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
                    <ol-interaction-draw v-if="drawEnabled" type="Polygon" :stopClick="true" @drawend="drawend" @drawstart="drawstart" />
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

<script>
import { ref, onMounted, onBeforeUnmount, watch, computed } from 'vue';
import { fromLonLat, toLonLat } from 'ol/proj';
import { Collection } from 'ol';
import Feature from 'ol/Feature';
import Polygon from 'ol/geom/Polygon';

export default {
    name: 'FieldMap',
    props: {
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
    },
    emits: ['update:boundaries', 'area-calculated', 'draw-start', 'draw-end', 'modify-end'],
    setup(props, { emit }) {
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
        const currentFeature = ref(null);
        const calculatedArea = ref(null);

        const loadFieldBoundaries = (boundaries) => {
            if (!boundaries || boundaries.length === 0) return;

            try {
                const mercatorCoords = boundaries.map((coord) => fromLonLat(coord));

                const polygon = new Polygon([mercatorCoords]);
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
            } catch (error) {
                console.error('Error loading field boundaries:', error);
            }
        };

        const toggleDraw = () => {
            if (highlightedFeatures.value.length > 0) {
                if (confirm('Do you wanna clear current polygon?')) {
                    resetMap();
                    drawEnabled.value = true;
                    emit('draw-start');
                }
            } else {
                resetMap();
                drawEnabled.value = true;
                emit('draw-start');
            }
        };

        const drawstart = (event) => {
            try {
                currentFeature.value = event.feature;
            } catch (error) {
                console.error('Error in drawstart:', error);
            }
        };

        const drawend = (event) => {
            try {
                drawEnabled.value = false;
                modifyEnabled.value = true;

                highlightedFeatures.value = [event.feature];
                selectedFeatures.value.clear();
                selectedFeatures.value.push(event.feature);

                calculateArea(event.feature);

                updateBoundaries();

                emit('draw-end', event.feature);
            } catch (error) {
                console.error('Error in drawend:', error);
            }
        };

        const modifyend = (event) => {
            try {
                event.features.forEach(function (feature) {
                    calculateArea(feature);
                });

                updateBoundaries();
                emit('modify-end', event.features);
            } catch (error) {
                console.error('Error in modifyend:', error);
            }
        };

        const calculateArea = (feature) => {
            try {
                const geometry = feature.getGeometry();
                const areaInSquareMeters = Math.abs(geometry.getArea());
                const areaInHectares = areaInSquareMeters / 10000;
                calculatedArea.value = parseFloat(areaInHectares.toFixed(2));

                emit('area-calculated', calculatedArea.value);
            } catch (error) {
                console.error('Error calculating field size:', error);
                calculatedArea.value = null;
            }
        };

        const updateBoundaries = () => {
            try {
                if (highlightedFeatures.value.length === 0) return;

                const feature = highlightedFeatures.value[0];
                const geometry = feature.getGeometry();

                const mercatorCoords = geometry.getCoordinates()[0];

                const wgs84Coords = mercatorCoords.map((coord) => toLonLat(coord));

                emit('update:boundaries', wgs84Coords);
            } catch (error) {
                console.error('Error updating boundaries:', error);
            }
        };

        const featureSelected = (event) => {
            try {
                modifyEnabled.value = false;

                if (event.selected && event.selected.length > 0) {
                    modifyEnabled.value = true;
                }

                selectedFeatures.value = event.target.getFeatures();
            } catch (error) {
                console.error('Error in feature selection:', error);
            }
        };

        const resetMap = () => {
            try {
                drawEnabled.value = false;
                modifyEnabled.value = false;
                highlightedFeatures.value = [];
                selectedFeatures.value.clear();
                currentFeature.value = null;
                calculatedArea.value = null;
            } catch (error) {
                console.error('Error resetting map:', error);
            }
        };

        onMounted(() => {
            if (props.boundaries && props.boundaries.length > 0) {
                setTimeout(() => {
                    loadFieldBoundaries(props.boundaries);
                }, 300);
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
                try {
                    if (newBoundaries && newBoundaries.length > 0 && view.value) {
                        loadFieldBoundaries(newBoundaries);
                    }
                } catch (error) {
                    console.error('Error in boundaries watcher:', error);
                }
            },
            { deep: true }
        );

        watch(
            () => props.mode,
            (newMode) => {
                try {
                    if (newMode === 'edit') {
                        modifyEnabled.value = true;
                    } else if (newMode === 'view') {
                        modifyEnabled.value = false;
                        drawEnabled.value = false;
                    } else if (newMode === 'create') {
                        drawEnabled.value = true;
                        emit('draw-start');
                    }
                } catch (error) {
                    console.error('Error in mode watcher:', error);
                }
            }
        );

        return {
            center,
            zoom,
            projection,
            rotation,
            view,
            drawEnabled,
            modifyEnabled,
            showLabels,
            highlightedFeatures,
            selectedFeatures,
            calculatedArea,
            toggleDraw,
            resetMap,
            drawstart,
            drawend,
            modifyend,
            featureSelected,
        };
    },
};
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

<script>
import { useForm } from '@inertiajs/vue3';
import { ref, inject, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { FieldStatus } from '@/Enum';
import { Collection } from 'ol';
import Feature from 'ol/Feature';
import Polygon from 'ol/geom/Polygon';
import OSM from 'ol/source/OSM';
import Swal from 'sweetalert2';
import { Select2ajax } from '@netblink/vue-components';
import TileLayer from 'ol/layer/Tile';
import XYZ from 'ol/source/XYZ';
import Map from 'ol/Map';
import View from 'ol/View';

export default {
    components: {
        Select2ajax,
    },
    props: {
        crops: {
            type: Array,
            default: () => [],
        },
        statuses: {
            type: Array,
            default: () => [],
        },
    },
    setup(props, { emit }) {
        const showModal = ref(false);
        const form = useForm({
            name: null,
            location: null,
            size: null,
            description: null,
            boundaries: null,
            status: FieldStatus.Active,
            crop_id: [],
        });

        // Prepare crop options for select
        const cropsOptions = props.crops.map((crop) => ({
            id: crop.id,
            text: crop.name,
        }));

        // OpenLayers Map variables
        const mapElement = ref(null);
        const drawEnabled = ref(false);
        const modifyEnabled = ref(false);
        const center = ref([-12000, 6700000]); // Default center (adjust as needed)
        const zoom = ref(6);
        const projection = ref('EPSG:3857');
        const highlightedFeatures = ref([]);
        const selectedFeatures = ref(new Collection());
        const rotation = ref(0);
        const view = ref(null);
        const currentFeature = ref(null);
        const isLoading = ref(false);
        const olMap = ref(null);

        // Map configuration
        const satelliteUrl = 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}';
        const labelsUrl = 'https://stamen-tiles.a.ssl.fastly.net/toner-labels/{z}/{x}/{y}.png';
        const showLabels = ref(true);

        const openModal = () => {
            showModal.value = true;
            setTimeout(() => {
                initMap();
            }, 100);
        };

        const closeModal = () => {
            showModal.value = false;
            form.reset();
            resetMap();

            if (olMap.value) {
                olMap.value.setTarget(null);
                olMap.value = null;
            }
        };

        const initMap = () => {
            if (olMap.value) return;

            const satelliteLayer = new TileLayer({
                source: new XYZ({
                    url: satelliteUrl,
                    crossOrigin: 'anonymous',
                }),
            });

            const osmLayer = new TileLayer({
                source: new OSM(),
                opacity: 0.4, // Semi-przezroczysta warstwa
                visible: showLabels.value,
            });

            const labelsLayer = new TileLayer({
                source: new XYZ({
                    url: labelsUrl,
                    crossOrigin: 'anonymous',
                }),
                visible: showLabels.value,
            });

            olMap.value = new Map({
                target: mapElement.value,
                layers: [satelliteLayer, osmLayer],
                view: new View({
                    center: center.value,
                    zoom: zoom.value,
                    projection: projection.value,
                }),
            });

            watch(showLabels, (newValue) => {
                labelsLayer.setVisible(newValue);
            });
        };

        const resetMap = () => {
            drawEnabled.value = false;
            modifyEnabled.value = false;
            highlightedFeatures.value = [];
            selectedFeatures.value.clear();
            currentFeature.value = null;
        };

        const saveField = () => {
            if (highlightedFeatures.value.length === 0) {
                Swal.fire({
                    title: 'No Field Drawn',
                    text: 'Please draw a field on the map before saving.',
                    icon: 'warning',
                    confirmButtonText: 'OK',
                });
                return;
            }

            if (!form.size || form.size <= 0) {
                try {
                    const feature = highlightedFeatures.value[0];
                    const geometry = feature.getGeometry();

                    const areaInSquareMeters = Math.abs(geometry.getArea());

                    const areaInHectares = areaInSquareMeters / 10000;
                    form.size = parseFloat(areaInHectares.toFixed(2));
                } catch (error) {
                    console.error('Error calculating field size:', error);

                    Swal.fire({
                        title: 'Field Size Required',
                        text: 'Please enter the field size in hectares.',
                        icon: 'warning',
                        confirmButtonText: 'OK',
                    });
                    return;
                }
            }

            isLoading.value = true;

            const feature = highlightedFeatures.value[0];
            const geometry = feature.getGeometry();
            form.boundaries = geometry.getCoordinates()[0];

            form.post(route('fields.store'), {
                preserveScroll: true,
                onSuccess: () => {
                    closeModal();

                    Swal.fire({
                        title: 'Field Created',
                        text: 'The field has been created successfully.',
                        icon: 'success',
                        confirmButtonText: 'OK',
                    });

                    emit('field-created');
                },
                onError: () => {
                    isLoading.value = false;
                },
                onFinish: () => {
                    isLoading.value = false;
                },
            });
        };

        const enableDraw = () => {
            if (highlightedFeatures.value.length > 0) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'Your current drawing will be cleared.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, clear it',
                    cancelButtonText: 'Cancel',
                }).then((result) => {
                    if (result.isConfirmed) {
                        resetMap();
                        drawEnabled.value = true;
                    }
                });
            } else {
                resetMap();
                drawEnabled.value = true;
            }
        };

        const drawstart = (event) => {
            currentFeature.value = event.feature;
        };

        const drawend = (event) => {
            drawEnabled.value = false;
            modifyEnabled.value = true;
            selectedFeatures.value.push(event.feature);
            highlightedFeatures.value = [event.feature];

            // Try to estimate field size
            try {
                const geometry = event.feature.getGeometry();
                const areaInSquareMeters = Math.abs(geometry.getArea());
                const areaInHectares = areaInSquareMeters / 10000;
                form.size = parseFloat(areaInHectares.toFixed(2));
            } catch (error) {
                console.error('Error calculating field size:', error);
            }
        };

        const modifyend = (event) => {
            event.features.forEach(function (feature) {
                try {
                    const geometry = feature.getGeometry();
                    const areaInSquareMeters = Math.abs(geometry.getArea());
                    const areaInHectares = areaInSquareMeters / 10000;
                    form.size = parseFloat(areaInHectares.toFixed(2));
                } catch (error) {
                    console.error('Error updating field size:', error);
                }
            });
        };

        const featureSelected = (event) => {
            modifyEnabled.value = false;
            if (event.selected?.length > 0) {
                modifyEnabled.value = true;
            }
            selectedFeatures.value = event.target.getFeatures();
        };

        return {
            showModal,
            form,
            openModal,
            closeModal,
            saveField,
            isLoading,
            mapElement,
            drawEnabled,
            modifyEnabled,
            center,
            zoom,
            projection,
            rotation,
            highlightedFeatures,
            selectedFeatures,
            view,
            enableDraw,
            drawstart,
            drawend,
            modifyend,
            featureSelected,
            satelliteUrl,
            cropsOptions,
            showLabels,
        };
    },
};
</script>

<template>
    <div class="modal fade" :class="{ 'show d-block': showModal }" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Field</h5>
                    <a href="#" class="close" @click.prevent="closeModal">
                        <em class="icon ni ni-cross"></em>
                    </a>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="saveField">
                        <div class="row g-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">Draw Field on Map</h5>
                                    </div>
                                    <div class="card-body p-0">
                                        <div style="height: 500px">
                                            <div ref="mapElement" style="width: 100%; height: 100%"></div>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-light p-3">
                                        <div class="row align-items-center">
                                            <div class="col-md-12 d-flex justify-content-between">
                                                <div class="form-check form-switch pt-1">
                                                    <input class="form-check-input" type="checkbox" id="show-labels" v-model="showLabels" />
                                                    <label class="form-check-label" for="show-labels">Show city names</label>
                                                </div>
                                                <button type="button" class="btn btn-primary" @click="enableDraw">
                                                    <em class="icon ni ni-edit me-1"></em>
                                                    <span>{{ drawEnabled ? 'Drawing Active' : 'Start Drawing' }}</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="field-name">
                                        Field Name
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="form-control-wrap">
                                        <input
                                            type="text"
                                            class="form-control"
                                            id="field-name"
                                            placeholder="Enter field name"
                                            v-model="form.name"
                                            required
                                        />
                                    </div>
                                    <div v-if="form.errors.name" class="form-note text-danger">{{ form.errors.name }}</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="field-location">Location</label>
                                    <div class="form-control-wrap">
                                        <input
                                            type="text"
                                            class="form-control"
                                            id="field-location"
                                            placeholder="Enter location (optional)"
                                            v-model="form.location"
                                        />
                                    </div>
                                    <div v-if="form.errors.location" class="form-note text-danger">{{ form.errors.location }}</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="field-size">
                                        Size (hectares)
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="form-control-wrap">
                                        <input
                                            type="number"
                                            class="form-control"
                                            id="field-size"
                                            placeholder="Enter field size"
                                            v-model="form.size"
                                            step="0.01"
                                            min="0.01"
                                            required
                                        />
                                    </div>
                                    <div v-if="form.errors.size" class="form-note text-danger">{{ form.errors.size }}</div>
                                    <div class="form-note">Size is calculated automatically based on the drawn area.</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="field-status">
                                        Status
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="form-control-wrap">
                                        <!-- Using regular select for status -->
                                        <select class="form-select" id="field-status" v-model="form.status" required>
                                            <option v-for="status in statuses" :key="status" :value="status">
                                                {{ status === 'active' ? 'Active' : status === 'inactive' ? 'Inactive' : status }}
                                            </option>
                                        </select>
                                    </div>
                                    <div v-if="form.errors.status" class="form-note text-danger">{{ form.errors.status }}</div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-group">
                                    <label class="form-label" for="field-crops">Crops</label>
                                    <div class="form-control-wrap">
                                        <Select2ajax id="field-crops" :form="form" field="crop_id" :url="route('crops.find')" />
                                    </div>
                                    <div v-if="form.errors.crop_id" class="form-note text-danger">{{ form.errors.crop_id }}</div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-group">
                                    <label class="form-label" for="field-description">Description</label>
                                    <div class="form-control-wrap">
                                        <textarea
                                            class="form-control"
                                            id="field-description"
                                            placeholder="Enter description (optional)"
                                            v-model="form.description"
                                            rows="3"
                                        ></textarea>
                                    </div>
                                    <div v-if="form.errors.description" class="form-note text-danger">{{ form.errors.description }}</div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" @click="closeModal">Cancel</button>
                    <button
                        type="button"
                        class="btn btn-primary"
                        @click="saveField"
                        :disabled="isLoading || !form.name || !form.size || highlightedFeatures.length === 0"
                    >
                        <div v-if="isLoading" class="spinner-border spinner-border-sm me-1" role="status"></div>
                        <em v-else class="icon ni ni-save me-1"></em>
                        <span>Save Field</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.modal {
    background-color: rgba(0, 0, 0, 0.4);
}
</style>

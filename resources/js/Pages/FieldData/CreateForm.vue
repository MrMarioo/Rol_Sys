<template>
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between g-3">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Add New Field Data</h3>
                <div class="nk-block-des text-soft">
                    <p>Add measurements or observations for a selected field.</p>
                </div>
            </div>
            <div class="nk-block-head-content">
                <div class="toggle-wrap nk-block-tools-toggle">
                    <div class="toggle-expand-content">
                        <ul class="nk-block-tools g-3">
                            <li>
                                <Link :href="route('field-data.index')" class="btn btn-outline-light">
                                    <em class="icon ni ni-arrow-left"></em>
                                    <span>Back</span>
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="nk-block">
        <div class="card card-bordered">
            <div class="card-inner">
                <form @submit.prevent="submitForm">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="form-label" for="field-select">Field *</label>
                                <div class="form-control-wrap">
                                    <select
                                        id="field-select"
                                        v-model="form.field_id"
                                        class="form-select"
                                        :class="{ error: form.errors.field_id }"
                                        required
                                    >
                                        <option value="">Select field</option>
                                        <option v-for="field in fields" :key="field.id" :value="field.id">
                                            {{ field.name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.field_id" class="form-error">{{ form.errors.field_id }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="form-label" for="collection-date">Collection Date *</label>
                                <div class="form-control-wrap">
                                    <input
                                        type="date"
                                        id="collection-date"
                                        v-model="form.collection_date"
                                        class="form-control"
                                        :class="{ error: form.errors.collection_date }"
                                        required
                                    />
                                    <div v-if="form.errors.collection_date" class="form-error">{{ form.errors.collection_date }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="form-label" for="data-type">Data Type *</label>
                                <div class="form-control-wrap">
                                    <select
                                        id="data-type"
                                        v-model="form.data_type"
                                        class="form-select"
                                        :class="{ error: form.errors.data_type }"
                                        required
                                    >
                                        <option value="">Select data type</option>
                                        <option value="soil_moisture">Soil Moisture</option>
                                        <option value="ndvi">Vegetation Index (NDVI)</option>
                                        <option value="temperature">Temperature</option>
                                        <option value="pest_detection">Pest Detection</option>
                                        <option value="weed_detection">Weed Detection</option>
                                        <option value="crop_health">Crop Health</option>
                                        <option value="other">Other</option>
                                    </select>
                                    <div v-if="form.errors.data_type" class="form-error">{{ form.errors.data_type }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="form-label">Location (optional)</label>
                                <div class="row g-2">
                                    <div class="col-sm-6">
                                        <div class="form-control-wrap">
                                            <input
                                                type="number"
                                                step="0.0000001"
                                                placeholder="Latitude"
                                                v-model="form.latitude"
                                                class="form-control"
                                                :class="{ error: form.errors.latitude }"
                                            />
                                            <div v-if="form.errors.latitude" class="form-error">{{ form.errors.latitude }}</div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-control-wrap">
                                            <input
                                                type="number"
                                                step="0.0000001"
                                                placeholder="Longitude"
                                                v-model="form.longitude"
                                                class="form-control"
                                                :class="{ error: form.errors.longitude }"
                                            />
                                            <div v-if="form.errors.longitude" class="form-error">{{ form.errors.longitude }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label">Measurement Data *</label>
                                <div class="form-control-wrap mb-3">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <span class="badge bg-primary">{{ dataFormatDescription }}</span>
                                        </div>
                                        <div>
                                            <button type="button" class="btn btn-sm btn-outline-primary" @click="generateSampleData">
                                                Generate Sample Data
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-control-wrap">
                                    <textarea
                                        v-model="dataInput"
                                        class="form-control code-textarea"
                                        :class="{ error: form.errors.data }"
                                        rows="10"
                                        placeholder="Enter data in JSON format"
                                        @input="validateJsonInput"
                                        required
                                    ></textarea>
                                    <div v-if="form.errors.data" class="form-error">{{ form.errors.data }}</div>
                                    <div v-if="jsonError" class="form-error mt-1">{{ jsonError }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label">Metadata (optional)</label>
                                <div class="form-control-wrap">
                                    <textarea
                                        v-model="metadataInput"
                                        class="form-control code-textarea"
                                        :class="{ error: form.errors.metadata }"
                                        rows="6"
                                        placeholder="Enter metadata in JSON format (optional)"
                                        @input="validateMetadataInput"
                                    ></textarea>
                                    <div v-if="form.errors.metadata" class="form-error">{{ form.errors.metadata }}</div>
                                    <div v-if="metadataError" class="form-error mt-1">{{ metadataError }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary" :disabled="form.processing || jsonError || metadataError">
                                    <span v-if="form.processing">Saving...</span>
                                    <span v-else>Save Data</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({
    layout: AppLayout,
});
// Props definition
const props = defineProps({
    fields: Array,
});

// Form state
const form = useForm({
    field_id: '',
    collection_date: new Date().toISOString().split('T')[0], // Today's date
    data_type: '',
    data: {},
    latitude: null,
    longitude: null,
    metadata: null,
});

// Component state
const dataInput = ref('');
const metadataInput = ref('');
const jsonError = ref('');
const metadataError = ref('');

// Data format description based on selected type
const dataFormatDescription = computed(() => {
    switch (form.data_type) {
        case 'soil_moisture':
            return 'Format: Time series with moisture readings';
        case 'ndvi':
            return 'Format: NDVI map or time series';
        case 'temperature':
            return 'Format: Time series with temperature readings';
        case 'pest_detection':
            return 'Format: Detected pests with locations';
        case 'weed_detection':
            return 'Format: Detected weeds with locations';
        case 'crop_health':
            return 'Format: Crop health index';
        default:
            return 'Format: JSON data';
    }
});

// JSON data validation
const validateJsonInput = () => {
    if (!dataInput.value.trim()) {
        jsonError.value = '';
        form.data = {};
        return;
    }

    try {
        form.data = JSON.parse(dataInput.value);
        jsonError.value = '';
    } catch (e) {
        jsonError.value = 'Invalid JSON format';
    }
};

// JSON metadata validation
const validateMetadataInput = () => {
    if (!metadataInput.value.trim()) {
        metadataError.value = '';
        form.metadata = null;
        return;
    }

    try {
        form.metadata = JSON.parse(metadataInput.value);
        metadataError.value = '';
    } catch (e) {
        metadataError.value = 'Invalid JSON format';
    }
};

// Generate sample data based on data type
const generateSampleData = () => {
    let sampleData = {};

    switch (form.data_type) {
        case 'soil_moisture':
            sampleData = {
                readings: [
                    { timestamp: new Date().toISOString(), value: 35.2, depth: 10 },
                    { timestamp: new Date(Date.now() - 3600000).toISOString(), value: 34.8, depth: 10 },
                    { timestamp: new Date(Date.now() - 7200000).toISOString(), value: 34.5, depth: 10 },
                ],
                unit: 'percent',
                average: 34.83,
            };
            break;
        case 'ndvi':
            sampleData = {
                grid: [
                    [0.65, 0.67, 0.71, 0.69],
                    [0.62, 0.63, 0.64, 0.66],
                    [0.6, 0.61, 0.63, 0.62],
                    [0.59, 0.58, 0.61, 0.6],
                ],
                min: 0.58,
                max: 0.71,
                average: 0.63,
            };
            break;
        case 'temperature':
            sampleData = {
                readings: [
                    { timestamp: new Date().toISOString(), temperature: 22.5 },
                    { timestamp: new Date(Date.now() - 3600000).toISOString(), temperature: 21.8 },
                    { timestamp: new Date(Date.now() - 7200000).toISOString(), temperature: 21.2 },
                ],
                unit: 'celsius',
                average: 21.83,
            };
            break;
        case 'pest_detection':
            sampleData = {
                detections: [
                    { type: 'aphid', count: 15, severity: 'medium', location: [51.123, 19.456] },
                    { type: 'beetle', count: 3, severity: 'low', location: [51.124, 19.457] },
                ],
                summary: {
                    total_count: 18,
                    highest_severity: 'medium',
                },
            };
            break;
        case 'weed_detection':
            sampleData = {
                detections: [
                    { type: 'thistle', coverage: 0.05, location: [51.123, 19.456] },
                    { type: 'nettle', coverage: 0.03, location: [51.124, 19.457] },
                ],
                total_coverage: 0.08,
            };
            break;
        case 'crop_health':
            sampleData = {
                health_index: 0.82,
                areas: [
                    { location: [51.123, 19.456], index: 0.78, issue: 'chlorosis' },
                    { location: [51.124, 19.457], index: 0.85, issue: null },
                ],
            };
            break;
        default:
            sampleData = { value: 'Sample data', timestamp: new Date().toISOString() };
            break;
    }

    dataInput.value = JSON.stringify(sampleData, null, 4);
    validateJsonInput();
};

// Watch for changes in data type
watch(
    () => form.data_type,
    () => {
        if (form.data_type && (!dataInput.value || jsonError.value)) {
            generateSampleData();
        }
    }
);

// Submit form handler
const submitForm = () => {
    validateJsonInput();
    validateMetadataInput();

    if (jsonError.value || metadataError.value) {
        return;
    }

    form.post(route('field-data.store'));
};
</script>

<style scoped>
.code-textarea {
    font-family: monospace;
    white-space: pre;
    tab-size: 4;
}

.form-error {
    color: #e85347;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.form-control.error,
.form-select.error {
    border-color: #e85347;
}
</style>

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
                <!-- Używamy zwykłego formularza HTML zamiast reactivity z Inertia -->
                <form @submit.prevent="submitForm">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="form-label" for="field-select">Field *</label>
                                <div class="form-control-wrap">
                                    <select
                                        id="field-select"
                                        v-model="fieldId"
                                        class="form-select"
                                        :class="{ error: errors.field_id }"
                                        required
                                    >
                                        <option value="">Select field</option>
                                        <option v-for="field in fields" :key="field.id" :value="field.id">
                                            {{ field.name }}
                                        </option>
                                    </select>
                                    <div v-if="errors.field_id" class="form-error">{{ errors.field_id }}</div>
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
                                        v-model="collectionDate"
                                        class="form-control"
                                        :class="{ error: errors.collection_date }"
                                        required
                                    />
                                    <div v-if="errors.collection_date" class="form-error">{{ errors.collection_date }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="form-label" for="data-type">Data Type *</label>
                                <div class="form-control-wrap">
                                    <select
                                        id="data-type"
                                        v-model="dataType"
                                        class="form-select"
                                        :class="{ error: errors.data_type }"
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
                                    <div v-if="errors.data_type" class="form-error">{{ errors.data_type }}</div>
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
                                                v-model="latitude"
                                                class="form-control"
                                                :class="{ error: errors.latitude }"
                                            />
                                            <div v-if="errors.latitude" class="form-error">{{ errors.latitude }}</div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-control-wrap">
                                            <input
                                                type="number"
                                                step="0.0000001"
                                                placeholder="Longitude"
                                                v-model="longitude"
                                                class="form-control"
                                                :class="{ error: errors.longitude }"
                                            />
                                            <div v-if="errors.longitude" class="form-error">{{ errors.longitude }}</div>
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
                                        :class="{ error: jsonError || errors.data_json }"
                                        rows="10"
                                        placeholder="Enter data in JSON format"
                                        required
                                    ></textarea>
                                    <div v-if="errors.data_json" class="form-error">{{ errors.data_json }}</div>
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
                                        :class="{ error: metadataError || errors.metadata_json }"
                                        rows="6"
                                        placeholder="Enter metadata in JSON format (optional)"
                                    ></textarea>
                                    <div v-if="errors.metadata_json" class="form-error">{{ errors.metadata_json }}</div>
                                    <div v-if="metadataError" class="form-error mt-1">{{ metadataError }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary" >
                                    <span v-if="processing">Saving...</span>
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
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    fields: Array,
    errors: Object
});

const fieldId = ref('');
const dataType = ref('');
const collectionDate = ref(new Date().toISOString().split('T')[0]); // Today's date
const latitude = ref(null);
const longitude = ref(null);
const dataInput = ref('');
const metadataInput = ref('');
const jsonError = ref('');
const metadataError = ref('');
const processing = ref(false);

const dataFormatDescription = computed(() => {
    switch (dataType.value) {
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

const generateSampleData = () => {
    if (!dataType.value) {
        return;
    }

    let sampleData = {};

    switch (dataType.value) {
        case 'soil_moisture':
            sampleData = {
                readings: [
                    { timestamp: new Date().toISOString(), value: 35.2, depth: 10 },
                    { timestamp: new Date(Date.now() - 3600000).toISOString(), value: 34.8, depth: 10 },
                    { timestamp: new Date(Date.now() - 7200000).toISOString(), value: 34.5, depth: 10 },
                ],
                unit: "percent",
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
                unit: "celsius",
                average: 21.83,
            };
            break;
        case 'pest_detection':
            sampleData = {
                detections: [
                    { type: "aphid", count: 15, severity: "medium", location: [51.123, 19.456] },
                    { type: "beetle", count: 3, severity: "low", location: [51.124, 19.457] },
                ],
                summary: {
                    total_count: 18,
                    highest_severity: "medium",
                },
            };
            break;
        case 'weed_detection':
            sampleData = {
                detections: [
                    { type: "thistle", coverage: 0.05, location: [51.123, 19.456] },
                    { type: "nettle", coverage: 0.03, location: [51.124, 19.457] },
                ],
                total_coverage: 0.08,
            };
            break;
        case 'crop_health':
            sampleData = {
                health_index: 0.82,
                areas: [
                    { location: [51.123, 19.456], index: 0.78, issue: "chlorosis" },
                    { location: [51.124, 19.457], index: 0.85, issue: null },
                ],
            };
            break;
        default:
            sampleData = { value: "Sample data", timestamp: new Date().toISOString() };
            break;
    }

    dataInput.value = JSON.stringify(sampleData, null, 4);
    jsonError.value = '';
};

const validateJson = (jsonString) => {
    if (!jsonString || !jsonString.trim()) {
        return { valid: false, error: 'JSON data is required' };
    }

    try {
        const parsed = JSON.parse(jsonString);
        return { valid: true, data: parsed };
    } catch (e) {
        return { valid: false, error: 'Invalid JSON format' };
    }
};

watch(
    () => dataType.value,
    (newValue) => {
        if (newValue && (!dataInput.value || jsonError.value)) {
            generateSampleData();
        }
    }
);

const submitForm = () => {
    jsonError.value = '';
    metadataError.value = '';

    const dataValidation = validateJson(dataInput.value);
    if (!dataValidation.valid) {
        jsonError.value = dataValidation.error;
        return;
    }

    let metadata = null;
    if (metadataInput.value.trim()) {
        const metadataValidation = validateJson(metadataInput.value);
        if (!metadataValidation.valid) {
            metadataError.value = metadataValidation.error;
            return;
        }
        metadata = metadataValidation.data;
    }

    processing.value = true;

    const formData = {
        field_id: fieldId.value,
        collection_date: collectionDate.value,
        data_type: dataType.value,
        latitude: latitude.value,
        longitude: longitude.value,
        data_json: dataInput.value,
        metadata_json: metadataInput.value || null
    };

    router.post(route('field-data.store'), formData, {
        onFinish: () => {
            processing.value = false;
        }
    });
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

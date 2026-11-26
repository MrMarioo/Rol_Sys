<template>
    <Head title="Add Field Data" />
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
                                        <option v-for="option in DATA_TYPE_OPTIONS" :key="option.value" :value="option.value">
                                            {{ option.label }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.data_type" class="form-error">{{ form.errors.data_type }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="form-label" for="latitude">Location (optional)</label>
                                <div class="row g-2">
                                    <div class="col-sm-6">
                                        <div class="form-control-wrap">
                                            <input
                                                id="latitude"
                                                type="number"
                                                step="0.0000001"
                                                placeholder="Latitude"
                                                aria-label="Latitude"
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
                                                id="longitude"
                                                type="number"
                                                step="0.0000001"
                                                placeholder="Longitude"
                                                aria-label="Longitude"
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
                                <label class="form-label" for="data-json">Measurement Data *</label>
                                <div class="form-control-wrap mb-3">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <span class="badge bg-primary">{{ dataFormatDescription(form.data_type) }}</span>
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
                                        id="data-json"
                                        v-model="form.data_json"
                                        class="form-control code-textarea"
                                        :class="{ error: jsonError || form.errors.data_json }"
                                        rows="10"
                                        placeholder="Enter data in JSON format"
                                        required
                                    ></textarea>
                                    <div v-if="form.errors.data_json" class="form-error">{{ form.errors.data_json }}</div>
                                    <div v-if="jsonError" class="form-error mt-1">{{ jsonError }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label" for="metadata-json">Metadata (optional)</label>
                                <div class="form-control-wrap">
                                    <textarea
                                        id="metadata-json"
                                        v-model="form.metadata_json"
                                        class="form-control code-textarea"
                                        :class="{ error: metadataError || form.errors.metadata_json }"
                                        rows="6"
                                        placeholder="Enter metadata in JSON format (optional)"
                                    ></textarea>
                                    <div v-if="form.errors.metadata_json" class="form-error">{{ form.errors.metadata_json }}</div>
                                    <div v-if="metadataError" class="form-error mt-1">{{ metadataError }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary" :disabled="form.processing">
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
import { ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { DATA_TYPE_OPTIONS, dataFormatDescription, parseJson, sampleDataJson } from '@/Components/FieldData/fieldDataTypes';

defineOptions({
    layout: AppLayout,
});

defineProps({
    fields: Array,
});

const form = useForm({
    field_id: '',
    collection_date: new Date().toISOString().split('T')[0],
    data_type: '',
    latitude: null,
    longitude: null,
    data_json: '',
    metadata_json: '',
});

const jsonError = ref('');
const metadataError = ref('');

const generateSampleData = () => {
    if (!form.data_type) {
        return;
    }

    form.data_json = sampleDataJson(form.data_type);
    jsonError.value = '';
};

watch(
    () => form.data_type,
    (type) => {
        if (type && (!form.data_json || jsonError.value)) {
            generateSampleData();
        }
    },
);

const validateJsonFields = () => {
    jsonError.value = '';
    metadataError.value = '';

    if (!form.data_json.trim()) {
        jsonError.value = 'JSON data is required';
    } else if (!parseJson(form.data_json).valid) {
        jsonError.value = 'Invalid JSON format';
    }

    if (form.metadata_json.trim() && !parseJson(form.metadata_json).valid) {
        metadataError.value = 'Invalid JSON format';
    }

    return !jsonError.value && !metadataError.value;
};

const submitForm = () => {
    if (!validateJsonFields()) {
        return;
    }

    form.transform((data) => ({
        ...data,
        metadata_json: data.metadata_json.trim() ? data.metadata_json : null,
    })).post(route('field-data.store'));
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

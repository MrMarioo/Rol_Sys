<template>
    <AppLayout title="Edit Field Data">
        <div class="nk-block-head nk-block-head-sm">
            <div class="nk-block-between g-3">
                <div class="nk-block-head-content">
                    <h3 class="nk-block-title page-title">Edit Field Data</h3>
                    <div class="nk-block-des text-soft">
                        <p>Edit measurements or observations for selected field.</p>
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
                                    <label class="form-label" for="metadata-json">Metadata (optional)</label>
                                    <div class="form-control-wrap">
                                        <textarea
                                            id="metadata-json"
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
                                    <button type="submit" class="btn btn-primary" :disabled="form.processing || !!jsonError || !!metadataError">
                                        <span v-if="form.processing">Saving...</span>
                                        <span v-else>Save Changes</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { DATA_TYPE_OPTIONS, dataFormatDescription, parseJson, sampleDataJson } from '@/Components/FieldData/fieldDataTypes';

const props = defineProps({
    fieldData: Object,
    fields: Array,
});

const form = useForm({
    field_id: props.fieldData.field_id,
    collection_date: props.fieldData.collection_date,
    data_type: props.fieldData.data_type,
    data: props.fieldData.data ?? {},
    latitude: props.fieldData.latitude,
    longitude: props.fieldData.longitude,
    metadata: props.fieldData.metadata,
});

const dataInput = ref(JSON.stringify(props.fieldData.data ?? {}, null, 4));
const metadataInput = ref(props.fieldData.metadata ? JSON.stringify(props.fieldData.metadata, null, 4) : '');
const jsonError = ref('');
const metadataError = ref('');

const validateJsonInput = () => {
    if (!dataInput.value.trim()) {
        jsonError.value = '';
        form.data = {};
        return;
    }

    const { valid, data } = parseJson(dataInput.value);
    jsonError.value = valid ? '' : 'Invalid JSON format';
    if (valid) {
        form.data = data;
    }
};

const validateMetadataInput = () => {
    if (!metadataInput.value.trim()) {
        metadataError.value = '';
        form.metadata = null;
        return;
    }

    const { valid, data } = parseJson(metadataInput.value);
    metadataError.value = valid ? '' : 'Invalid JSON format';
    if (valid) {
        form.metadata = data;
    }
};

const generateSampleData = () => {
    dataInput.value = sampleDataJson(form.data_type);
    validateJsonInput();
};

watch(
    () => form.data_type,
    (type) => {
        if (type && (!dataInput.value || jsonError.value)) {
            generateSampleData();
        }
    },
);

const submitForm = () => {
    validateJsonInput();
    validateMetadataInput();

    if (jsonError.value || metadataError.value) {
        return;
    }

    form.put(route('field-data.update', props.fieldData.id));
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

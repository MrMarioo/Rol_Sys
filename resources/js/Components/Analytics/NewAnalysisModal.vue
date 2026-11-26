<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import DialogModal from '@/Components/DialogModal.vue';
import InputError from '@/Components/InputError.vue';
import FieldStatusBadge from '@/Components/FieldStatusBadge.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    fields: {
        type: Array,
        default: () => [],
    },
    sensitivities: {
        type: Array,
        default: () => ['low', 'medium', 'high'],
    },
});

const emit = defineEmits(['close']);

const form = useForm({
    field_id: '',
    start_date: '',
    end_date: '',
    sensitivity: 'medium',
    include_growth_prediction: false,
    prediction_days: 7,
});

const sensitivityLabels = {
    low: 'Low (fewer alerts)',
    medium: 'Medium (balanced)',
    high: 'High (more detailed)',
};

const sensitivityLabel = (value) => sensitivityLabels[value] ?? value;

const selectedField = computed(() => props.fields.find((field) => field.id === form.field_id) ?? null);

watch(
    () => props.show,
    (visible) => {
        if (visible) {
            form.clearErrors();
        }
    },
);

watch(
    () => form.start_date,
    (startDate) => {
        if (startDate && form.end_date && form.end_date < startDate) {
            form.end_date = startDate;
        }
    },
);

const close = () => {
    if (form.processing) return;
    emit('close');
};

const submit = () => {
    form.post(route('analytics.analyze', form.field_id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit('close');
        },
    });
};
</script>

<template>
    <DialogModal :show="show" max-width="lg" :closeable="!form.processing" @close="close">
        <template #title>Run New Analysis</template>

        <template #content>
            <form id="new-analysis-form" @submit.prevent="submit">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="new-analysis-field">Select Field</label>
                            <div class="form-control-wrap">
                                <select id="new-analysis-field" v-model="form.field_id" class="form-select" required>
                                    <option value="">Select a field</option>
                                    <option v-for="field in fields" :key="field.id" :value="field.id">
                                        {{ field.name }}
                                    </option>
                                </select>
                            </div>
                            <InputError :message="form.errors.field_id" />
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="new-analysis-sensitivity">Analysis Sensitivity</label>
                            <div class="form-control-wrap">
                                <select id="new-analysis-sensitivity" v-model="form.sensitivity" class="form-select">
                                    <option v-for="sensitivity in sensitivities" :key="sensitivity" :value="sensitivity">
                                        {{ sensitivityLabel(sensitivity) }}
                                    </option>
                                </select>
                            </div>
                            <InputError :message="form.errors.sensitivity" />
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="new-analysis-start-date">Start Date</label>
                            <div class="form-control-wrap">
                                <input
                                    id="new-analysis-start-date"
                                    v-model="form.start_date"
                                    type="date"
                                    class="form-control"
                                />
                            </div>
                            <InputError :message="form.errors.start_date" />
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="new-analysis-end-date">End Date</label>
                            <div class="form-control-wrap">
                                <input
                                    id="new-analysis-end-date"
                                    v-model="form.end_date"
                                    type="date"
                                    class="form-control"
                                    :min="form.start_date || undefined"
                                />
                            </div>
                            <InputError :message="form.errors.end_date" />
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-check">
                            <input
                                id="new-analysis-include-prediction"
                                v-model="form.include_growth_prediction"
                                type="checkbox"
                                class="form-check-input"
                            />
                            <label class="form-check-label" for="new-analysis-include-prediction">
                                <strong>Include Growth Prediction</strong>
                            </label>
                        </div>
                        <small class="form-text text-muted">
                            Enable AI-powered growth prediction for the next several days (requires more processing time)
                        </small>
                        <InputError :message="form.errors.include_growth_prediction" />
                    </div>

                    <div v-if="form.include_growth_prediction" class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="new-analysis-prediction-days">Prediction Days</label>
                            <div class="form-control-wrap">
                                <input
                                    id="new-analysis-prediction-days"
                                    v-model.number="form.prediction_days"
                                    type="number"
                                    class="form-control"
                                    min="1"
                                    max="30"
                                    placeholder="7"
                                />
                            </div>
                            <small class="form-text text-muted">Number of days to predict future growth (1-30 days)</small>
                            <InputError :message="form.errors.prediction_days" />
                        </div>
                    </div>

                    <div v-if="selectedField" class="col-12">
                        <div class="alert alert-info">
                            <h6 class="alert-heading">Selected Field Information</h6>
                            <p class="mb-1"><strong>Name:</strong> {{ selectedField.name }}</p>
                            <p class="mb-1"><strong>Size:</strong> {{ selectedField.size }} hectares</p>
                            <p class="mb-0"><strong>Status:</strong> <FieldStatusBadge :status="selectedField.status" /></p>
                        </div>
                    </div>
                </div>

                <div v-if="form.processing" class="d-flex align-items-center mt-3">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                    <span>Running analysis… this can take up to a minute</span>
                </div>
            </form>
        </template>

        <template #footer>
            <button type="button" class="btn btn-secondary me-2" :disabled="form.processing" @click="close">
                Cancel
            </button>
            <button
                type="submit"
                form="new-analysis-form"
                class="btn btn-primary"
                :disabled="form.processing || !form.field_id"
            >
                <span v-if="form.processing" class="spinner-border spinner-border-sm me-1" role="status"></span>
                <em v-else class="icon ni ni-growth me-1"></em>
                <span>{{ form.include_growth_prediction ? 'Run Analysis + Prediction' : 'Run Analysis' }}</span>
            </button>
        </template>
    </DialogModal>
</template>

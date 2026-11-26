<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import DialogModal from '@/Components/DialogModal.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    fieldId: {
        type: Number,
        required: true,
    },
    sensitivities: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close']);

const form = useForm({
    start_date: '',
    end_date: '',
    sensitivity: 'medium',
    include_growth_prediction: false,
    prediction_days: 7,
});

watch(
    () => props.show,
    (visible) => {
        if (visible) {
            form.clearErrors();
        }
    },
);

const sensitivityLabels = {
    low: 'Low (fewer alerts)',
    medium: 'Medium (balanced)',
    high: 'High (more detailed)',
};

const sensitivityLabel = (value) => sensitivityLabels[value] ?? value;

const close = () => {
    if (form.processing) return;
    emit('close');
};

const submit = () => {
    form.post(route('analytics.analyze', props.fieldId), {
        preserveScroll: true,
    });
};
</script>

<template>
    <DialogModal :show="show" max-width="lg" :closeable="!form.processing" @close="close">
        <template #title>Run analysis</template>

        <template #content>
            <form id="run-analysis-form" @submit.prevent="submit">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="run-start-date">Start Date</label>
                            <input
                                id="run-start-date"
                                v-model="form.start_date"
                                type="date"
                                class="form-control"
                                required
                            />
                            <InputError :message="form.errors.start_date" />
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="run-end-date">End Date</label>
                            <input
                                id="run-end-date"
                                v-model="form.end_date"
                                type="date"
                                class="form-control"
                                :min="form.start_date || undefined"
                                required
                            />
                            <InputError :message="form.errors.end_date" />
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="run-sensitivity">Analysis Sensitivity</label>
                            <select id="run-sensitivity" v-model="form.sensitivity" class="form-select">
                                <option v-for="sensitivity in sensitivities" :key="sensitivity" :value="sensitivity">
                                    {{ sensitivityLabel(sensitivity) }}
                                </option>
                            </select>
                            <InputError :message="form.errors.sensitivity" />
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-check">
                            <input
                                id="run-include-prediction"
                                v-model="form.include_growth_prediction"
                                type="checkbox"
                                class="form-check-input"
                            />
                            <label class="form-check-label" for="run-include-prediction">
                                <strong>Include Growth Prediction</strong>
                            </label>
                        </div>
                        <small class="text-muted">
                            Enable AI-powered growth prediction for the next several days (requires more processing time)
                        </small>
                        <InputError :message="form.errors.include_growth_prediction" />
                    </div>

                    <div v-if="form.include_growth_prediction" class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="run-prediction-days">Prediction Days</label>
                            <input
                                id="run-prediction-days"
                                v-model.number="form.prediction_days"
                                type="number"
                                class="form-control"
                                min="1"
                                max="30"
                            />
                            <small class="text-muted">Number of days to predict future growth (1-30 days)</small>
                            <InputError :message="form.errors.prediction_days" />
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
            <button type="submit" form="run-analysis-form" class="btn btn-primary" :disabled="form.processing">
                Run Analysis
            </button>
        </template>
    </DialogModal>
</template>

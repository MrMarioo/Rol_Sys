<template>
    <div class="comparative-report">
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="alert alert-light">
                    <h6 class="mb-2">
                        Comparative Analysis
                        <span class="badge badge-dim bg-outline-primary ms-1">{{ comparisonLabel }}</span>
                    </h6>
                    <p class="mb-0">Period: {{ formatDate(content.period?.start) }} - {{ formatDate(content.period?.end) }}</p>
                </div>
            </div>
        </div>

        <div v-if="content.comparison_type === 'fields'" class="card">
            <div class="card-inner">
                <h5 class="card-title">Field Performance Comparison</h5>
                <p v-if="fields.length === 0" class="text-muted mb-0">No fields were included in this comparison.</p>
                <div v-else class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Field</th>
                                <th>Size (ha)</th>
                                <th>Data Collections</th>
                                <th>Avg NDVI</th>
                                <th>Avg Moisture</th>
                                <th>Issues</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="field in fields" :key="field.field_id">
                                <td class="fw-bold">{{ field.field_name }}</td>
                                <td>{{ formatNumber(field.field_size) }}</td>
                                <td>{{ field.data_collections ?? '—' }}</td>
                                <td>{{ formatNdvi(field.avg_ndvi) }}</td>
                                <td>{{ formatMoisture(field.avg_moisture) }}</td>
                                <td>{{ field.issues_detected ?? '—' }}</td>
                                <td><ReportHealthBadge :status="field.status" /></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div v-else-if="content.comparison_type === 'time_periods'" class="card">
            <div class="card-inner">
                <h5 class="card-title">Time Period Comparison</h5>
                <p class="text-soft">
                    Current: {{ formatDate(current.start) }} - {{ formatDate(current.end) }}
                    <span class="mx-1">|</span>
                    Previous: {{ formatDate(previous.start) }} - {{ formatDate(previous.end) }}
                </p>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Metric</th>
                                <th>Current</th>
                                <th>Previous</th>
                                <th>Change</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="metric in periodMetrics" :key="metric.key">
                                <td class="fw-bold">{{ metric.label }}</td>
                                <td>{{ metric.format(current[metric.key]) }}</td>
                                <td>{{ metric.format(previous[metric.key]) }}</td>
                                <td :class="changeClass(metric)">{{ formatChange(metric) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import ReportHealthBadge from '@/Components/Reports/ReportHealthBadge.vue';
import { formatDate, formatMoisture, formatNdvi, formatNumber, isNumber } from '@/format';

const props = defineProps({
    content: {
        type: Object,
        required: true,
    },
});

const COMPARISON_LABELS = {
    fields: 'Field-to-Field Comparison',
    time_periods: 'Time Period Comparison',
};

const formatCount = (value) => (isNumber(value) ? String(Math.round(Number(value))) : '—');

const signed = (value, text) => (value > 0 ? `+${text}` : text);

const periodMetrics = [
    {
        key: 'data_collections',
        label: 'Data Collections',
        format: formatCount,
        change: (diff) => signed(diff, String(Math.round(diff))),
        higherIsBetter: true,
    },
    {
        key: 'avg_ndvi',
        label: 'Average NDVI',
        format: formatNdvi,
        change: (diff) => signed(diff, diff.toFixed(2)),
        higherIsBetter: true,
    },
    {
        key: 'avg_moisture',
        label: 'Average Moisture',
        format: formatMoisture,
        change: (diff) => signed(diff, `${(diff * 100).toFixed(1)} pp`),
        higherIsBetter: null,
    },
    {
        key: 'issues_detected',
        label: 'Issues Detected',
        format: formatCount,
        change: (diff) => signed(diff, String(Math.round(diff))),
        higherIsBetter: false,
    },
];

const comparisonLabel = computed(() => COMPARISON_LABELS[props.content.comparison_type] ?? props.content.comparison_type);

const fields = computed(() => props.content.fields ?? []);

const current = computed(() => props.content.periods?.current ?? {});

const previous = computed(() => props.content.periods?.previous ?? {});

const difference = (metric) => {
    const currentValue = current.value[metric.key];
    const previousValue = previous.value[metric.key];

    if (!isNumber(currentValue) || !isNumber(previousValue)) {
        return null;
    }

    return Number(currentValue) - Number(previousValue);
};

const formatChange = (metric) => {
    const diff = difference(metric);

    return diff === null ? '—' : metric.change(diff);
};

const changeClass = (metric) => {
    const diff = difference(metric);

    if (diff === null || diff === 0 || metric.higherIsBetter === null) {
        return '';
    }

    return diff > 0 === metric.higherIsBetter ? 'text-success' : 'text-danger';
};
</script>

<style scoped>
.alert h6 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.badge {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    border-radius: 0.25rem;
}

.table th {
    font-weight: 600;
    color: #364a63;
    border-bottom: 2px solid #e5e9f2;
}

.table td {
    vertical-align: middle;
}
</style>

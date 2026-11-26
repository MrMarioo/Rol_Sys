<template>
    <div class="weekly-summary">
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="alert alert-light">
                    <h6 class="mb-2">{{ content.period.type }}</h6>
                    <p class="mb-0">
                        Period: {{ formatDate(content.period.start) }} - {{ formatDate(content.period.end) }}
                    </p>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card border-primary">
                    <div class="card-inner text-center">
                        <div class="nk-iv-wg2-title">
                            <em class="icon ni ni-map text-primary"></em>
                        </div>
                        <h4 class="title">{{ content.totals.total_fields }}</h4>
                        <p class="sub-text">Total Fields</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-success">
                    <div class="card-inner text-center">
                        <div class="nk-iv-wg2-title">
                            <em class="icon ni ni-grid-sq text-success"></em>
                        </div>
                        <h4 class="title">{{ formatNumber(content.totals.total_size) }} ha</h4>
                        <p class="sub-text">Total Area</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-info">
                    <div class="card-inner text-center">
                        <div class="nk-iv-wg2-title">
                            <em class="icon ni ni-growth text-info"></em>
                        </div>
                        <h4 class="title">{{ formatNdvi(content.totals.avg_ndvi) }}</h4>
                        <p class="sub-text">Average NDVI</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-warning">
                    <div class="card-inner text-center">
                        <div class="nk-iv-wg2-title">
                            <em class="icon ni ni-alert-circle text-warning"></em>
                        </div>
                        <h4 class="title">{{ content.totals.total_issues }}</h4>
                        <p class="sub-text">Issues Detected</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-inner">
                <h5 class="card-title">Field Details</h5>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Field Name</th>
                                <th>Size (ha)</th>
                                <th>Status</th>
                                <th>Avg NDVI</th>
                                <th>Avg Moisture</th>
                                <th>Data Collections</th>
                                <th>Issues</th>
                            </tr>
                        </thead>
                        <tbody>
                        <tr v-for="(field, fieldId) in content.summary" :key="fieldId">
                            <td class="fw-bold">{{ field.field_name }}</td>
                            <td>{{ formatNumber(field.field_size) }}</td>
                            <td>
                                <ReportHealthBadge :status="field.status" />
                            </td>
                            <td>{{ formatNdvi(field.avg_ndvi) }}</td>
                            <td>{{ formatMoisture(field.avg_moisture) }}</td>
                            <td>{{ field.data_collections }}</td>
                            <td>
                                <span v-if="field.issues_detected > 0" class="text-warning">
                                    <em class="icon ni ni-alert-circle"></em>
                                    {{ field.issues_detected }}
                                </span>
                                <span v-else class="text-success">
                                    <em class="icon ni ni-check"></em>
                                    None
                                </span>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mt-4" v-if="hasRecommendations">
            <div class="card-inner">
                <h5 class="card-title">Recommendations</h5>
                <div class="row g-3">
                    <div v-for="(field, fieldId) in content.summary" :key="fieldId" class="col-12">
                        <div v-if="field.recommendations?.length > 0" class="alert alert-info">
                            <h6 class="mb-2">{{ field.field_name }}</h6>
                            <ul class="mb-0">
                                <li v-for="recommendation in field.recommendations" :key="recommendation.action">
                                    <strong>{{ recommendation.action }}:</strong> {{ recommendation.description }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import ReportHealthBadge from '@/Components/Reports/ReportHealthBadge.vue';
import { formatDate, formatMoisture, formatNdvi, formatNumber } from '@/format';

const props = defineProps({
    content: {
        type: Object,
        required: true,
    },
});

const hasRecommendations = computed(() =>
    Object.values(props.content.summary ?? {}).some((field) => field.recommendations?.length > 0),
);
</script>

<style scoped>
.nk-iv-wg2-title .icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.card.border-primary {
    border-color: #6576ff !important;
}
.card.border-success {
    border-color: #1ee0ac !important;
}
.card.border-info {
    border-color: #1ee0ac !important;
}
.card.border-warning {
    border-color: #f4bd0e !important;
}
</style>

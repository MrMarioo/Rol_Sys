<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import FieldStatusBadge from '@/Components/FieldStatusBadge.vue';
import RecommendationList from '@/Components/Field/RecommendationList.vue';
import GrowthPredictionCard from '@/Components/Analytics/GrowthPredictionCard.vue';
import MetricCard from '@/Components/Analytics/MetricCard.vue';
import { formatDate, formatMoisture, formatNdvi, formatPercent } from '@/format';

defineOptions({ layout: AppLayout });

const props = defineProps({
    analytics: {
        type: Object,
        required: true,
    },
    field: {
        type: Object,
        required: true,
    },
});

const results = computed(() => props.analytics.results ?? null);
const parameters = computed(() => props.analytics.parameters ?? {});
const recommendations = computed(() => props.analytics.recommendations ?? []);
const anomalies = computed(() => results.value?.anomalies ?? []);

const hasGrowthPrediction = computed(() => Boolean(results.value?.growth_prediction && !results.value.growth_prediction.error));

const isComprehensive = computed(() => (props.analytics.analysis_type ?? '').includes('prediction'));

const moistureStatusClass = (status) =>
    ({ Optimal: 'text-success', Low: 'text-warning', High: 'text-danger' })[status] ?? '';

const healthStatusClass = (status) =>
    ({ Good: 'text-success', Moderate: 'text-info', Poor: 'text-warning', Critical: 'text-danger' })[status] ?? '';

const areaClass = (percent) => {
    if (percent < 10) return 'text-success';
    if (percent < 30) return 'text-warning';
    return 'text-danger';
};

const severityClass = (severity) => ({ high: 'bg-danger', medium: 'bg-warning', low: 'bg-info' })[severity] ?? 'bg-light text-dark';
</script>

<template>
    <Head title="Analysis Results" />

    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Analysis Results</h3>
                <div class="nk-block-des text-soft">
                    <p>{{ isComprehensive ? 'Comprehensive Analysis with Growth Prediction' : 'Standard Analysis' }}</p>
                </div>
            </div>
            <div class="nk-block-head-content">
                <div class="toggle-wrap nk-block-tools-toggle">
                    <Link :href="route('analytics.index')" class="btn btn-outline-primary me-2">
                        <em class="icon ni ni-arrow-left"></em>
                        <span>Back to Analytics</span>
                    </Link>
                    <a :href="route('analytics.download', analytics.id)" class="btn btn-primary">
                        <em class="icon ni ni-download"></em>
                        <span>Download Report</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="nk-block">
        <div class="card mb-4">
            <div class="card-inner border-bottom">
                <div class="card-title-group">
                    <div class="card-title">
                        <h5>
                            <em class="icon ni" :class="isComprehensive ? 'ni-growth' : 'ni-bar-chart'"></em>
                            Analysis Overview
                        </h5>
                    </div>
                    <div class="card-tools">
                        <span class="badge bg-primary me-2">{{ formatDate(analytics.analysis_date) }}</span>
                        <span class="badge" :class="isComprehensive ? 'bg-success' : 'bg-info'">
                            {{ isComprehensive ? 'With Prediction' : 'Standard' }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="card-inner">
                <div class="row g-4">
                    <div class="col-md-6">
                        <h6 class="overline-title">Field Information</h6>
                        <div class="profile-stats-group">
                            <div class="profile-stats-item">
                                <div class="profile-stats-count">{{ field.name }}</div>
                                <div class="profile-stats-label">Field Name</div>
                            </div>
                            <div class="profile-stats-item">
                                <div class="profile-stats-count">{{ field.size }} ha</div>
                                <div class="profile-stats-label">Size</div>
                            </div>
                        </div>
                        <p class="text-soft mt-2">
                            <strong>Location:</strong> {{ field.location || 'Not specified' }}<br />
                            <strong>Status:</strong>
                            <FieldStatusBadge :status="field.status" />
                        </p>
                    </div>

                    <div class="col-12">
                        <h6 class="overline-title">Analysis Parameters</h6>
                        <div class="row g-2">
                            <div class="col-auto">
                                <span class="badge badge-dim bg-outline-primary">
                                    Sensitivity: {{ parameters.sensitivity || 'medium' }}
                                </span>
                            </div>
                            <div class="col-auto">
                                <span class="badge badge-dim bg-outline-secondary">
                                    Data Points: {{ parameters.data_count ?? 'N/A' }}
                                </span>
                            </div>
                            <div v-if="parameters.include_prediction" class="col-auto">
                                <span class="badge badge-dim bg-outline-success">
                                    Prediction Days: {{ parameters.prediction_days || 7 }}
                                </span>
                            </div>
                            <div v-if="parameters.date_range?.from" class="col-auto">
                                <span class="badge badge-dim bg-outline-info">
                                    Range: {{ parameters.date_range.from }} to {{ parameters.date_range.to }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="results" class="card mb-4">
            <div v-if="hasGrowthPrediction" class="card-inner border-bottom">
                <GrowthPredictionCard :prediction="results.growth_prediction" />
            </div>

            <div v-if="results.soil_moisture" class="card-inner border-bottom">
                <h6 class="overline-title">💧 Soil Moisture Analysis</h6>
                <div class="row g-3">
                    <div class="col-sm-6 col-md-4">
                        <MetricCard label="Average Moisture" :value="formatMoisture(results.soil_moisture.avg_moisture)" />
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <MetricCard
                            label="Moisture Status"
                            :value="results.soil_moisture.moisture_status ?? '—'"
                            :value-class="moistureStatusClass(results.soil_moisture.moisture_status)"
                        />
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <MetricCard
                            label="Dry Area"
                            :value="formatPercent(results.soil_moisture.dry_area_percent)"
                            :value-class="areaClass(results.soil_moisture.dry_area_percent)"
                        />
                    </div>
                </div>
                <div v-if="results.soil_moisture.recommendation" class="alert alert-info mt-3 mb-0">
                    <strong>Moisture Recommendation:</strong> {{ results.soil_moisture.recommendation }}
                </div>
            </div>

            <div v-if="results.vegetation_health" class="card-inner border-bottom">
                <h6 class="overline-title">🌿 Vegetation Health Analysis</h6>
                <div class="row g-3">
                    <div class="col-sm-6 col-md-4">
                        <MetricCard label="Average NDVI" :value="formatNdvi(results.vegetation_health.avg_ndvi)" />
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <MetricCard
                            label="Health Status"
                            :value="results.vegetation_health.health_status ?? '—'"
                            :value-class="healthStatusClass(results.vegetation_health.health_status)"
                        />
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <MetricCard
                            label="Problem Areas"
                            :value="formatPercent(results.vegetation_health.problem_area_percent)"
                            :value-class="areaClass(results.vegetation_health.problem_area_percent)"
                        />
                    </div>
                </div>
                <div v-if="results.vegetation_health.recommendation" class="alert alert-info mt-3 mb-0">
                    <strong>Health Recommendation:</strong> {{ results.vegetation_health.recommendation }}
                </div>
            </div>

            <div v-if="anomalies.length" class="card-inner border-bottom">
                <h6 class="overline-title">🚨 Detected Anomalies</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Value</th>
                                <th>Expected</th>
                                <th>Severity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(anomaly, index) in anomalies" :key="index">
                                <td>{{ anomaly.date }}</td>
                                <td><span class="badge badge-dim bg-outline-secondary">{{ anomaly.type }}</span></td>
                                <td>{{ anomaly.description }}</td>
                                <td><strong>{{ anomaly.value ?? 'N/A' }}</strong></td>
                                <td>{{ anomaly.expected ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge" :class="severityClass(anomaly.severity)">{{ anomaly.severity }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="recommendations.length" class="card-inner">
                <h6 class="overline-title">💡 AI Recommendations</h6>
                <RecommendationList :items="recommendations" detailed />
            </div>
        </div>

        <div class="d-flex flex-wrap gap-3 justify-content-between mt-4">
            <div>
                <Link :href="route('analytics.index')" class="btn btn-outline-primary">
                    <em class="icon ni ni-arrow-left"></em>
                    <span>Back to Analytics</span>
                </Link>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a :href="route('analytics.download', analytics.id)" class="btn btn-primary">
                    <em class="icon ni ni-download"></em>
                    <span>Download Report</span>
                </a>
                <Link :href="route('fields.show', field.id)" class="btn btn-outline-secondary">
                    <em class="icon ni ni-map"></em>
                    <span>View Field</span>
                </Link>
                <Link v-if="hasGrowthPrediction" :href="route('analytics.index')" class="btn btn-success">
                    <em class="icon ni ni-growth"></em>
                    <span>Run New Prediction</span>
                </Link>
            </div>
        </div>
    </div>
</template>

<style scoped>
.overline-title {
    font-size: 11px;
    line-height: 1.2;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.2em;
    color: #8094ae;
    margin-bottom: 1rem;
}

.profile-stats-group {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.profile-stats-item {
    text-align: center;
}

.profile-stats-count {
    font-size: 1.25rem;
    font-weight: 700;
    color: #364a63;
    line-height: 1.2;
}

.profile-stats-label {
    font-size: 0.75rem;
    color: #8094ae;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-top: 0.25rem;
}
</style>

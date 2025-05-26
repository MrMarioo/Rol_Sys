<script>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

export default {
    layout: AppLayout,
    components: {
        Head,
        Link
    },
    props: {
        analytics: Object,
        field: Object,
    },
    setup(props) {
        // Format recommendation type to appropriate badge color
        const getRecommendationClass = (type) => {
            switch (type) {
                case 'urgent': return 'badge-danger';
                case 'high': return 'badge-warning';
                case 'medium': return 'badge-info';
                default: return 'badge-success';
            }
        };

        return {
            getRecommendationClass
        };
    }
};
</script>

<template>
    <Head title="Analysis Results" />

    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Analysis Results</h3>
                <div class="nk-block-des text-soft">
                    <p>Detailed AI analysis of field data</p>
                </div>
            </div>
            <div class="nk-block-head-content">
                <div class="toggle-wrap nk-block-tools-toggle">
                    <Link :href="`/analytics`" class="btn btn-outline-primary">
                        <em class="icon ni ni-arrow-left"></em>
                        <span>Back to Analytics</span>
                    </Link>
                </div>
            </div>
        </div>
    </div>

    <div class="nk-block">
        <!-- Field Information -->
        <div class="card mb-4">
            <div class="card-inner border-bottom">
                <div class="card-title-group">
                    <div class="card-title">
                        <h5>Field Information</h5>
                    </div>
                    <div class="card-tools">
                        <span class="badge badge-primary">Analysis Date: {{ new Date(analytics.analysis_date).toLocaleDateString() }}</span>
                    </div>
                </div>
            </div>
            <div class="card-inner">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Field Name:</strong> {{ field.name }}</p>
                        <p><strong>Size:</strong> {{ field.size }} ha</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Location:</strong> {{ field.location || 'Not specified' }}</p>
                        <p><strong>Status:</strong> {{ field.status }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Analysis Results -->
        <div v-if="analytics.results" class="card mb-4">
            <!-- Soil Moisture Analysis -->
            <div v-if="analytics.results.soil_moisture" class="card-inner border-bottom">
                <h6 class="overline-title">Soil Moisture Analysis</h6>
                <div class="row g-3">
                    <div class="col-sm-6 col-md-4">
                        <div class="card">
                            <div class="card-inner">
                                <div class="card-title-group align-start mb-2">
                                    <div class="card-title">
                                        <h6 class="subtitle">Average Moisture</h6>
                                    </div>
                                </div>
                                <div class="align-end flex-sm-wrap g-4 flex-md-nowrap">
                                    <div class="nk-sale-data">
                                        <span class="amount">{{ analytics.results.soil_moisture.avg_moisture.toFixed(2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <div class="card">
                            <div class="card-inner">
                                <div class="card-title-group align-start mb-2">
                                    <div class="card-title">
                                        <h6 class="subtitle">Moisture Status</h6>
                                    </div>
                                </div>
                                <div class="align-end flex-sm-wrap g-4 flex-md-nowrap">
                                    <div class="nk-sale-data">
                                        <span
                                            class="amount"
                                            :class="{
                                                'text-success': analytics.results.soil_moisture.moisture_status === 'Optimal',
                                                'text-warning': analytics.results.soil_moisture.moisture_status === 'Low',
                                                'text-danger': analytics.results.soil_moisture.moisture_status === 'High'
                                            }"
                                        >
                                            {{ analytics.results.soil_moisture.moisture_status }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <div class="card">
                            <div class="card-inner">
                                <div class="card-title-group align-start mb-2">
                                    <div class="card-title">
                                        <h6 class="subtitle">Dry Area</h6>
                                    </div>
                                </div>
                                <div class="align-end flex-sm-wrap g-4 flex-md-nowrap">
                                    <div class="nk-sale-data">
                                        <span
                                            class="amount"
                                            :class="{
                                                'text-success': analytics.results.soil_moisture.dry_area_percent < 10,
                                                'text-warning': analytics.results.soil_moisture.dry_area_percent >= 10 && analytics.results.soil_moisture.dry_area_percent < 30,
                                                'text-danger': analytics.results.soil_moisture.dry_area_percent >= 30
                                            }"
                                        >
                                            {{ analytics.results.soil_moisture.dry_area_percent.toFixed(1) }}%
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vegetation Health Analysis -->
            <div v-if="analytics.results.vegetation_health" class="card-inner border-bottom">
                <h6 class="overline-title">Vegetation Health Analysis</h6>
                <div class="row g-3">
                    <div class="col-sm-6 col-md-4">
                        <div class="card">
                            <div class="card-inner">
                                <div class="card-title-group align-start mb-2">
                                    <div class="card-title">
                                        <h6 class="subtitle">Average NDVI</h6>
                                    </div>
                                </div>
                                <div class="align-end flex-sm-wrap g-4 flex-md-nowrap">
                                    <div class="nk-sale-data">
                                        <span class="amount">{{ analytics.results.vegetation_health.avg_ndvi.toFixed(2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <div class="card">
                            <div class="card-inner">
                                <div class="card-title-group align-start mb-2">
                                    <div class="card-title">
                                        <h6 class="subtitle">Health Status</h6>
                                    </div>
                                </div>
                                <div class="align-end flex-sm-wrap g-4 flex-md-nowrap">
                                    <div class="nk-sale-data">
                                        <span
                                            class="amount"
                                            :class="{
                                                'text-success': analytics.results.vegetation_health.health_status === 'Good',
                                                'text-info': analytics.results.vegetation_health.health_status === 'Moderate',
                                                'text-warning': analytics.results.vegetation_health.health_status === 'Poor',
                                                'text-danger': analytics.results.vegetation_health.health_status === 'Critical'
                                            }"
                                        >
                                            {{ analytics.results.vegetation_health.health_status }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <div class="card">
                            <div class="card-inner">
                                <div class="card-title-group align-start mb-2">
                                    <div class="card-title">
                                        <h6 class="subtitle">Problem Areas</h6>
                                    </div>
                                </div>
                                <div class="align-end flex-sm-wrap g-4 flex-md-nowrap">
                                    <div class="nk-sale-data">
                                        <span
                                            class="amount"
                                            :class="{
                                                'text-success': analytics.results.vegetation_health.problem_area_percent < 10,
                                                'text-warning': analytics.results.vegetation_health.problem_area_percent >= 10 && analytics.results.vegetation_health.problem_area_percent < 30,
                                                'text-danger': analytics.results.vegetation_health.problem_area_percent >= 30
                                            }"
                                        >
                                            {{ analytics.results.vegetation_health.problem_area_percent.toFixed(1) }}%
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Anomalies -->
            <div v-if="analytics.results.anomalies && analytics.results.anomalies.length > 0" class="card-inner border-bottom">
                <h6 class="overline-title">Detected Anomalies</h6>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Severity</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="(anomaly, index) in analytics.results.anomalies" :key="index">
                            <td>{{ anomaly.date }}</td>
                            <td>{{ anomaly.type }}</td>
                            <td>{{ anomaly.description }}</td>
                            <td>
                                    <span
                                        class="badge"
                                        :class="{
                                            'badge-danger': anomaly.severity === 'high',
                                            'badge-warning': anomaly.severity === 'medium',
                                            'badge-info': anomaly.severity === 'low'
                                        }"
                                    >
                                        {{ anomaly.severity }}
                                    </span>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recommendations -->
            <div v-if="analytics.recommendations && analytics.recommendations.length > 0" class="card-inner">
                <h6 class="overline-title">Recommendations</h6>
                <div class="gap g-4">
                    <div v-for="(recommendation, index) in analytics.recommendations" :key="index" class="card">
                        <div class="card-inner">
                            <div class="card-title-group">
                                <div class="card-title">
                                    <h6>
                                        <span class="badge me-2" :class="getRecommendationClass(recommendation.type)">
                                            {{ recommendation.type }}
                                        </span>
                                        {{ recommendation.action }}
                                    </h6>
                                </div>
                            </div>
                            <p>{{ recommendation.description }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="gap g-3">
            <Link :href="`/analytics`" class="btn btn-outline-primary">
                <em class="icon ni ni-arrow-left"></em>
                <span>Back to Analytics</span>
            </Link>
            <Link :href="`/analytics/${analytics.id}/download`" class="btn btn-primary">
                <em class="icon ni ni-download"></em>
                <span>Download Report</span>
            </Link>
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

.subtitle {
    color: #526484;
    font-size: 12px;
}

.amount {
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1.2;
    color: #364a63;
}

.badge {
    display: inline-block;
    padding: 0.25em 0.5em;
    font-size: 0.75em;
    font-weight: 700;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 0.25rem;
}

.badge-success {
    color: #fff;
    background-color: #1ee0ac;
}

.badge-info {
    color: #fff;
    background-color: #09c2de;
}

.badge-warning {
    color: #fff;
    background-color: #f4bd0e;
}

.badge-danger {
    color: #fff;
    background-color: #e85347;
}

.text-success { color: #1ee0ac; }
.text-info { color: #09c2de; }
.text-warning { color: #f4bd0e; }
.text-danger { color: #e85347; }
</style>

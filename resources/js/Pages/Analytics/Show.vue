<script>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';

export default {
    layout: AppLayout,
    components: {
        Head,
        Link
    },
    props: {
        analytics: Object,
        field: Object,
        activeCrop: Object,
    },
    setup(props) {
        // Format recommendation type to appropriate badge color
        const getRecommendationClass = (type) => {
            switch (type) {
                case 'urgent': return 'badge-danger';
                case 'high': return 'badge-warning';
                case 'medium': return 'badge-info';
                case 'scheduled': return 'badge-primary';
                case 'predictive': return 'badge-info';
                case 'positive': return 'badge-success';
                default: return 'badge-success';
            }
        };

        // NOWE - Format priority badge
        const getPriorityClass = (priority) => {
            switch (priority) {
                case 'high': return 'badge-danger';
                case 'medium': return 'badge-warning';
                case 'low': return 'badge-success';
                default: return 'badge-secondary';
            }
        };

        // NOWE - Format confidence percentage
        const formatConfidence = (confidence) => {
            return Math.round(confidence * 100);
        };

        // NOWE - Check if analysis includes growth prediction
        const hasGrowthPrediction = computed(() => {
            return props.analytics?.results?.growth_prediction &&
                !props.analytics.results.growth_prediction.error;
        });

        // NOWE - Check analysis type
        const analysisType = computed(() => {
            const type = props.analytics?.analysis_type || 'standard';
            return {
                isComprehensive: type.includes('prediction'),
                displayName: type.includes('prediction') ? 'Comprehensive Analysis with Growth Prediction' : 'Standard Analysis',
                icon: type.includes('prediction') ? 'ni-growth' : 'ni-bar-chart'
            };
        });

        // NOWE - Get crop information
        const cropInfo = computed(() => {
            if (!props.activeCrop) return null;

            return {
                name: props.activeCrop.name,
                plantingDate: props.activeCrop.pivot?.planting_date,
                expectedHarvest: props.activeCrop.pivot?.expected_harvest_date,
                status: props.activeCrop.pivot?.status
            };
        });

        return {
            getRecommendationClass,
            getPriorityClass,
            formatConfidence,
            hasGrowthPrediction,
            analysisType,
            cropInfo
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
                    <p>{{ analysisType.displayName }}</p>
                </div>
            </div>
            <div class="nk-block-head-content">
                <div class="toggle-wrap nk-block-tools-toggle">
                    <Link :href="`/analytics`" class="btn btn-outline-primary me-2">
                        <em class="icon ni ni-arrow-left"></em>
                        <span>Back to Analytics</span>
                    </Link>
                    <Link :href="`/analytics/${analytics.id}/download`" class="btn btn-primary">
                        <em class="icon ni ni-download"></em>
                        <span>Download Report</span>
                    </Link>
                </div>
            </div>
        </div>
    </div>

    <div class="nk-block">
        <!-- ULEPSZONA Field & Analysis Information -->
        <div class="card mb-4">
            <div class="card-inner border-bottom">
                <div class="card-title-group">
                    <div class="card-title">
                        <h5>
                            <em class="icon ni" :class="analysisType.icon"></em>
                            Analysis Overview
                        </h5>
                    </div>
                    <div class="card-tools">
                        <span class="badge badge-primary me-2">
                            {{ new Date(analytics.analysis_date).toLocaleDateString() }}
                        </span>
                        <span class="badge" :class="analysisType.isComprehensive ? 'badge-success' : 'badge-info'">
                            {{ analysisType.isComprehensive ? 'With Prediction' : 'Standard' }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="card-inner">
                <div class="row g-4">
                    <!-- Field Information -->
                    <div class="col-md-6">
                        <h6 class="overline-title">Field Information</h6>
                        <div class="profile-stats">
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
                        </div>
                        <p class="text-soft mt-2">
                            <strong>Location:</strong> {{ field.location || 'Not specified' }}<br>
                            <strong>Status:</strong>
                            <span class="badge badge-sm" :class="field.status === 'active' ? 'badge-success' : 'badge-warning'">
                                {{ field.status }}
                            </span>
                        </p>
                    </div>

                    <!-- NOWE - Crop Information -->
                    <div v-if="cropInfo" class="col-md-6">
                        <h6 class="overline-title">Current Crop</h6>
                        <div class="profile-stats">
                            <div class="profile-stats-group">
                                <div class="profile-stats-item">
                                    <div class="profile-stats-count">{{ cropInfo.name }}</div>
                                    <div class="profile-stats-label">Crop Type</div>
                                </div>
                                <div v-if="cropInfo.plantingDate" class="profile-stats-item">
                                    <div class="profile-stats-count">{{ new Date(cropInfo.plantingDate).toLocaleDateString() }}</div>
                                    <div class="profile-stats-label">Planted</div>
                                </div>
                            </div>
                        </div>
                        <p v-if="cropInfo.expectedHarvest" class="text-soft mt-2">
                            <strong>Expected Harvest:</strong> {{ new Date(cropInfo.expectedHarvest).toLocaleDateString() }}
                        </p>
                    </div>

                    <!-- Analysis Parameters -->
                    <div class="col-12">
                        <h6 class="overline-title">Analysis Parameters</h6>
                        <div class="row g-2">
                            <div class="col-auto">
                                <span class="badge badge-outline-primary">
                                    Sensitivity: {{ analytics.parameters?.sensitivity || 'medium' }}
                                </span>
                            </div>
                            <div class="col-auto">
                                <span class="badge badge-outline-secondary">
                                    Data Points: {{ analytics.parameters?.data_count || 'N/A' }}
                                </span>
                            </div>
                            <div v-if="analytics.parameters?.include_prediction" class="col-auto">
                                <span class="badge badge-outline-success">
                                    Prediction Days: {{ analytics.parameters?.prediction_days || 7 }}
                                </span>
                            </div>
                            <div v-if="analytics.parameters?.date_range?.from" class="col-auto">
                                <span class="badge badge-outline-info">
                                    Range: {{ analytics.parameters.date_range.from }} to {{ analytics.parameters.date_range.to }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Analysis Results -->
        <div v-if="analytics.results" class="card mb-4">
            <!-- NOWA SEKCJA - Growth Prediction Results (jeśli dostępne) -->
            <div v-if="hasGrowthPrediction" class="card-inner border-bottom">
                <h6 class="overline-title">🌱 Growth Prediction Results</h6>

                <!-- Current Status Summary -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card card-bordered">
                            <div class="card-inner">
                                <div class="card-title-group align-start mb-2">
                                    <div class="card-title">
                                        <h6 class="subtitle">Current Biomass</h6>
                                    </div>
                                </div>
                                <div class="nk-sale-data">
                                    <span class="amount">{{ Math.round(analytics.results.growth_prediction.current_biomass) }}</span>
                                    <span class="subtitle">kg/ha</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-bordered">
                            <div class="card-inner">
                                <div class="card-title-group align-start mb-2">
                                    <div class="card-title">
                                        <h6 class="subtitle">Growth Stage</h6>
                                    </div>
                                </div>
                                <div class="nk-sale-data">
                                    <span class="amount">{{ analytics.results.growth_prediction.current_status.growth_stage }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-bordered">
                            <div class="card-inner">
                                <div class="card-title-group align-start mb-2">
                                    <div class="card-title">
                                        <h6 class="subtitle">Health Score</h6>
                                    </div>
                                </div>
                                <div class="nk-sale-data">
                                    <span class="amount" :class="{
                                        'text-success': analytics.results.growth_prediction.current_status.health_score >= 75,
                                        'text-warning': analytics.results.growth_prediction.current_status.health_score >= 50 && analytics.results.growth_prediction.current_status.health_score < 75,
                                        'text-danger': analytics.results.growth_prediction.current_status.health_score < 50
                                    }">{{ analytics.results.growth_prediction.current_status.health_score }}%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-bordered">
                            <div class="card-inner">
                                <div class="card-title-group align-start mb-2">
                                    <div class="card-title">
                                        <h6 class="subtitle">Days Since Planting</h6>
                                    </div>
                                </div>
                                <div class="nk-sale-data">
                                    <span class="amount">{{ analytics.results.growth_prediction.current_status.days_since_planting }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Prediction Summary -->
                <div v-if="analytics.results.growth_prediction.summary" class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card card-bordered">
                            <div class="card-inner text-center">
                                <div class="card-title-group align-start mb-2">
                                    <div class="card-title">
                                        <h6 class="subtitle">Predicted Total Growth</h6>
                                    </div>
                                </div>
                                <div class="nk-sale-data">
                                    <span class="amount text-success">+{{ analytics.results.growth_prediction.summary.total_predicted_growth }}</span>
                                    <span class="subtitle">kg/ha over {{ analytics.results.growth_prediction.predictions.length }} days</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-bordered">
                            <div class="card-inner text-center">
                                <div class="card-title-group align-start mb-2">
                                    <div class="card-title">
                                        <h6 class="subtitle">Average Daily Growth</h6>
                                    </div>
                                </div>
                                <div class="nk-sale-data">
                                    <span class="amount">{{ analytics.results.growth_prediction.summary.average_daily_growth }}</span>
                                    <span class="subtitle">kg/ha/day</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-bordered">
                            <div class="card-inner text-center">
                                <div class="card-title-group align-start mb-2">
                                    <div class="card-title">
                                        <h6 class="subtitle">Average Confidence</h6>
                                    </div>
                                </div>
                                <div class="nk-sale-data">
                                    <span class="amount">{{ formatConfidence(analytics.results.growth_prediction.summary.confidence_range.avg) }}%</span>
                                    <span class="subtitle">prediction accuracy</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Current Status Issues/Warnings -->
                <div v-if="analytics.results.growth_prediction.current_status.issues?.length > 0 || analytics.results.growth_prediction.current_status.warnings?.length > 0" class="mb-4">
                    <h6 class="subtitle mb-3">⚠️ Current Status Alerts</h6>
                    <div class="row g-2">
                        <div v-for="issue in analytics.results.growth_prediction.current_status.issues" :key="issue" class="col-12">
                            <div class="alert alert-danger alert-dismissible">
                                <div class="alert-cta">
                                    <em class="icon ni ni-alert-circle"></em>
                                </div>
                                <div class="alert-text">
                                    <strong>Critical Issue:</strong> {{ issue }}
                                </div>
                            </div>
                        </div>
                        <div v-for="warning in analytics.results.growth_prediction.current_status.warnings" :key="warning" class="col-12">
                            <div class="alert alert-warning alert-dismissible">
                                <div class="alert-cta">
                                    <em class="icon ni ni-alert-triangle"></em>
                                </div>
                                <div class="alert-text">
                                    <strong>Warning:</strong> {{ warning }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Daily Predictions Table -->
                <div v-if="analytics.results.growth_prediction.predictions" class="table-responsive">
                    <h6 class="subtitle mb-3">📈 Daily Growth Predictions</h6>
                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                        <tr>
                            <th>Day</th>
                            <th>Date</th>
                            <th>Predicted Biomass</th>
                            <th>Daily Growth</th>
                            <th>Growth Stage</th>
                            <th>Est. NDVI</th>
                            <th>Est. Moisture</th>
                            <th>Confidence</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="prediction in analytics.results.growth_prediction.predictions" :key="prediction.day">
                            <td><strong>+{{ prediction.day }}</strong></td>
                            <td>{{ new Date(prediction.date).toLocaleDateString() }}</td>
                            <td><strong>{{ Math.round(prediction.predicted_biomass) }} kg/ha</strong></td>
                            <td class="text-success">+{{ Math.round(prediction.growth_rate) }} kg/ha</td>
                            <td>
                                <span class="badge badge-outline-primary">{{ prediction.growth_stage }}</span>
                            </td>
                            <td v-if="prediction.estimated_ndvi">{{ prediction.estimated_ndvi.toFixed(2) }}</td>
                            <td v-else>-</td>
                            <td v-if="prediction.estimated_moisture">{{ prediction.estimated_moisture.toFixed(2) }}</td>
                            <td v-else>-</td>
                            <td>
                                    <span class="badge" :class="{
                                        'badge-success': prediction.confidence > 0.8,
                                        'badge-warning': prediction.confidence >= 0.6 && prediction.confidence <= 0.8,
                                        'badge-danger': prediction.confidence < 0.6
                                    }">
                                        {{ formatConfidence(prediction.confidence) }}%
                                    </span>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Key Metrics Summary -->
                <div v-if="analytics.results.growth_prediction.current_status.key_metrics" class="mt-4">
                    <h6 class="subtitle mb-3">📊 Key Metrics Used in Prediction</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="card card-bordered">
                                <div class="card-inner text-center">
                                    <h6 class="subtitle">Current NDVI</h6>
                                    <span class="amount">{{ analytics.results.growth_prediction.current_status.key_metrics.ndvi }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card card-bordered">
                                <div class="card-inner text-center">
                                    <h6 class="subtitle">Soil Moisture</h6>
                                    <span class="amount">{{ analytics.results.growth_prediction.current_status.key_metrics.soil_moisture }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card card-bordered">
                                <div class="card-inner text-center">
                                    <h6 class="subtitle">Temperature</h6>
                                    <span class="amount">{{ analytics.results.growth_prediction.current_status.key_metrics.temperature }}°C</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Model Information -->
                <div v-if="analytics.results.growth_prediction.model_info" class="mt-4">
                    <h6 class="subtitle mb-3">🤖 AI Model Information</h6>
                    <div class="alert alert-info">
                        <div class="alert-text">
                            <strong>Model:</strong> {{ analytics.results.growth_prediction.model_info.model_type }} |
                            <strong>Features:</strong> {{ analytics.results.growth_prediction.model_info.features_used }} parameters |
                            <strong>Version:</strong> {{ analytics.results.growth_prediction.model_info.version || 'N/A' }}
                            <div class="text-soft mt-1">
                                Last trained: {{ analytics.results.growth_prediction.model_info.last_updated || 'Unknown' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Soil Moisture Analysis -->
            <div v-if="analytics.results.soil_moisture" class="card-inner border-bottom">
                <h6 class="overline-title">💧 Soil Moisture Analysis</h6>
                <div class="row g-3">
                    <div class="col-sm-6 col-md-4">
                        <div class="card card-bordered">
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
                        <div class="card card-bordered">
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
                        <div class="card card-bordered">
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
                <!-- NOWE - dodatkowe informacje -->
                <div v-if="analytics.results.soil_moisture.recommendation" class="mt-3">
                    <div class="alert alert-info">
                        <div class="alert-text">
                            <strong>Moisture Recommendation:</strong> {{ analytics.results.soil_moisture.recommendation }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vegetation Health Analysis -->
            <div v-if="analytics.results.vegetation_health" class="card-inner border-bottom">
                <h6 class="overline-title">🌿 Vegetation Health Analysis</h6>
                <div class="row g-3">
                    <div class="col-sm-6 col-md-4">
                        <div class="card card-bordered">
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
                        <div class="card card-bordered">
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
                        <div class="card card-bordered">
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
                <!-- NOWE - dodatkowe informacje -->
                <div v-if="analytics.results.vegetation_health.recommendation" class="mt-3">
                    <div class="alert alert-info">
                        <div class="alert-text">
                            <strong>Health Recommendation:</strong> {{ analytics.results.vegetation_health.recommendation }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Anomalies -->
            <div v-if="analytics.results.anomalies && analytics.results.anomalies.length > 0" class="card-inner border-bottom">
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
                        <tr v-for="(anomaly, index) in analytics.results.anomalies" :key="index">
                            <td>{{ anomaly.date }}</td>
                            <td><span class="badge badge-outline-secondary">{{ anomaly.type }}</span></td>
                            <td>{{ anomaly.description }}</td>
                            <td><strong>{{ anomaly.value || 'N/A' }}</strong></td>
                            <td>{{ anomaly.expected || 'N/A' }}</td>
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

            <!-- ULEPSZONE Recommendations -->
            <div v-if="analytics.recommendations && analytics.recommendations.length > 0" class="card-inner">
                <h6 class="overline-title">💡 AI Recommendations</h6>
                <div class="gap g-4">
                    <div v-for="(recommendation, index) in analytics.recommendations" :key="index" class="card card-bordered">
                        <div class="card-inner">
                            <div class="card-title-group">
                                <div class="card-title">
                                    <h6>
                                        <!-- Typ rekomendacji -->
                                        <span class="badge me-2" :class="getRecommendationClass(recommendation.type)">
                                            {{ recommendation.type }}
                                        </span>
                                        <!-- Priorytet (jeśli dostępny) -->
                                        <span v-if="recommendation.priority" class="badge me-2" :class="getPriorityClass(recommendation.priority)">
                                            {{ recommendation.priority }} priority
                                        </span>
                                        <!-- Kategoria (jeśli dostępna) -->
                                        <span v-if="recommendation.category" class="badge badge-light me-2">
                                            {{ recommendation.category }}
                                        </span>
                                        {{ recommendation.action }}
                                    </h6>
                                </div>
                                <!-- Urgency i impact (jeśli dostępne) -->
                                <div v-if="recommendation.urgency_days || recommendation.estimated_impact" class="card-tools">
                                    <span v-if="recommendation.urgency_days" class="badge badge-outline-primary">
                                        {{ recommendation.urgency_days }} {{ recommendation.urgency_days === 1 ? 'day' : 'days' }}
                                    </span>
                                    <span v-if="recommendation.estimated_impact" class="badge badge-outline-secondary ms-1">
                                        {{ recommendation.estimated_impact }} impact
                                    </span>
                                </div>
                            </div>
                            <p class="mb-2">{{ recommendation.description }}</p>
                            <!-- Źródło rekomendacji (jeśli dostępne) -->
                            <div v-if="recommendation.source" class="text-soft">
                                <small>
                                    <em class="icon ni ni-info"></em>
                                    Source: {{ recommendation.source }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex gap-3 justify-content-between mt-20">
            <div>
                <Link :href="`/analytics`" class="btn btn-outline-primary">
                    <em class="icon ni ni-arrow-left"></em>
                    <span>Back to Analytics</span>
                </Link>
            </div>
            <div class="d-flex gap-2">
                <Link :href="`/analytics/${analytics.id}/download`" class="btn btn-primary">
                    <em class="icon ni ni-download"></em>
                    <span>Download Report</span>
                </Link>
                <Link :href="`/fields/${field.id}`" class="btn btn-outline-secondary">
                    <em class="icon ni ni-map"></em>
                    <span>View Field</span>
                </Link>
                <Link v-if="hasGrowthPrediction" href="/analytics" class="btn btn-success">
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

.profile-stats {
    margin: 0;
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

.badge-sm {
    padding: 0.15em 0.3em;
    font-size: 0.65em;
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

.badge-primary {
    color: #fff;
    background-color: #6576ff;
}

.badge-secondary {
    color: #fff;
    background-color: #6c757d;
}

.badge-light {
    color: #495057;
    background-color: #f8f9fa;
}

.badge-outline-primary {
    color: #6576ff;
    background-color: transparent;
    border: 1px solid #6576ff;
}

.badge-outline-secondary {
    color: #6c757d;
    background-color: transparent;
    border: 1px solid #6c757d;
}

.text-success { color: #1ee0ac; }
.text-info { color: #09c2de; }
.text-warning { color: #f4bd0e; }
.text-danger { color: #e85347; }

.card-bordered {
    border: 1px solid #e5e9f2;
}

.alert {
    padding: 0.75rem 1rem;
    margin-bottom: 1rem;
    border: 1px solid transparent;
    border-radius: 0.375rem;
}

.alert-info {
    color: #055160;
    background-color: #cff4fc;
    border-color: #9eeaf9;
}

.alert-success {
    color: #0f5132;
    background-color: #d1edcc;
    border-color: #badbcc;
}

.alert-warning {
    color: #664d03;
    background-color: #fff3cd;
    border-color: #ffecb5;
}

.alert-danger {
    color: #842029;
    background-color: #f8d7da;
    border-color: #f5c2c7;
}

.alert-cta {
    display: inline-block;
    margin-right: 0.5rem;
}

.alert-text {
    display: inline-block;
    vertical-align: middle;
}

.table-light {
    background-color: #f8f9fa;
}

.gap {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.d-flex {
    display: flex;
}

.gap-2 {
    gap: 0.5rem;
}

.gap-3 {
    gap: 1rem;
}

.justify-content-between {
    justify-content: space-between;
}
</style>

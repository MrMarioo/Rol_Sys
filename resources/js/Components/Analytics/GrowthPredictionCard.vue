<script setup>
import { computed } from 'vue';
import MetricCard from '@/Components/Analytics/MetricCard.vue';
import { formatConfidence, formatDate, formatMoisture, formatNdvi, isNumber } from '@/format';

const props = defineProps({
    prediction: {
        type: Object,
        required: true,
    },
});

const status = computed(() => props.prediction.current_status ?? {});
const summary = computed(() => props.prediction.summary ?? {});
const predictions = computed(() => props.prediction.predictions ?? []);
const issues = computed(() => status.value.issues ?? []);
const warnings = computed(() => status.value.warnings ?? []);

const round = (value) => (isNumber(value) ? Math.round(Number(value)) : '—');

const healthScoreClass = (score) => {
    if (score >= 75) return 'text-success';
    if (score >= 50) return 'text-warning';
    return 'text-danger';
};

const confidenceClass = (confidence) => {
    if (confidence > 0.8) return 'bg-success';
    if (confidence >= 0.6) return 'bg-warning';
    return 'bg-danger';
};
</script>

<template>
    <div>
        <h6 class="overline-title">🌱 Growth Prediction Results</h6>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <MetricCard label="Current Biomass" :value="round(prediction.current_biomass)" unit="kg/ha" />
            </div>
            <div class="col-md-3">
                <MetricCard label="Growth Stage" :value="status.growth_stage" />
            </div>
            <div class="col-md-3">
                <MetricCard
                    label="Health Score"
                    :value="`${status.health_score}%`"
                    :value-class="healthScoreClass(status.health_score)"
                />
            </div>
            <div class="col-md-3">
                <MetricCard label="Days Since Planting" :value="status.days_since_planting" />
            </div>
        </div>

        <div v-if="summary.confidence_range" class="row g-3 mb-4">
            <div class="col-md-4">
                <MetricCard
                    label="Predicted Total Growth"
                    :value="`+${summary.total_predicted_growth}`"
                    :unit="`kg/ha over ${predictions.length} days`"
                    value-class="text-success"
                    centered
                />
            </div>
            <div class="col-md-4">
                <MetricCard
                    label="Average Daily Growth"
                    :value="summary.average_daily_growth"
                    unit="kg/ha/day"
                    centered
                />
            </div>
            <div class="col-md-4">
                <MetricCard
                    label="Average Confidence"
                    :value="formatConfidence(summary.confidence_range.avg)"
                    unit="prediction accuracy"
                    centered
                />
            </div>
        </div>

        <div v-if="issues.length || warnings.length" class="mb-4">
            <h6 class="subtitle mb-3">⚠️ Current Status Alerts</h6>
            <div class="row g-2">
                <div v-for="issue in issues" :key="issue" class="col-12">
                    <div class="alert alert-danger">
                        <em class="icon ni ni-alert-circle me-2"></em>
                        <strong>Critical Issue:</strong> {{ issue }}
                    </div>
                </div>
                <div v-for="warning in warnings" :key="warning" class="col-12">
                    <div class="alert alert-warning">
                        <em class="icon ni ni-alert-triangle me-2"></em>
                        <strong>Warning:</strong> {{ warning }}
                    </div>
                </div>
            </div>
        </div>

        <div v-if="predictions.length" class="table-responsive">
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
                    <tr v-for="item in predictions" :key="item.day">
                        <td><strong>+{{ item.day }}</strong></td>
                        <td>{{ formatDate(item.date) }}</td>
                        <td><strong>{{ round(item.predicted_biomass) }} kg/ha</strong></td>
                        <td class="text-success">+{{ round(item.growth_rate) }} kg/ha</td>
                        <td>
                            <span class="badge badge-dim bg-outline-primary">{{ item.growth_stage }}</span>
                        </td>
                        <td>{{ formatNdvi(item.estimated_ndvi) }}</td>
                        <td>{{ formatMoisture(item.estimated_moisture) }}</td>
                        <td>
                            <span class="badge" :class="confidenceClass(item.confidence)">
                                {{ formatConfidence(item.confidence) }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="status.key_metrics" class="mt-4">
            <h6 class="subtitle mb-3">📊 Key Metrics Used in Prediction</h6>
            <div class="row g-3">
                <div class="col-md-4">
                    <MetricCard label="Current NDVI" :value="formatNdvi(status.key_metrics.ndvi)" centered />
                </div>
                <div class="col-md-4">
                    <MetricCard label="Soil Moisture" :value="formatMoisture(status.key_metrics.soil_moisture)" centered />
                </div>
                <div class="col-md-4">
                    <MetricCard label="Temperature" :value="`${status.key_metrics.temperature}°C`" centered />
                </div>
            </div>
        </div>

        <div v-if="prediction.model_info" class="mt-4">
            <h6 class="subtitle mb-3">🤖 AI Model Information</h6>
            <div class="alert alert-info">
                <strong>Model:</strong> {{ prediction.model_info.model_type }} |
                <strong>Features:</strong> {{ prediction.model_info.features_used }} parameters |
                <strong>Version:</strong> {{ prediction.model_info.version || 'N/A' }}
                <div class="text-soft mt-1">
                    Last trained: {{ prediction.model_info.training_date || prediction.model_info.last_updated || 'Unknown' }}
                </div>
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
</style>

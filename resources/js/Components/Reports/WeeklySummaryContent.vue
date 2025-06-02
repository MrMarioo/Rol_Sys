<template>
    <div class="weekly-summary">
        <!-- Period Info -->
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

        <!-- Summary Totals -->
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
                        <h4 class="title">{{ content.totals.total_size }} ha</h4>
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
                        <h4 class="title">{{ content.totals.avg_ndvi }}</h4>
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

        <!-- Field Details -->
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
                            <td>{{ field.field_size }}</td>
                            <td>
                                    <span :class="getStatusBadgeClass(field.status)">
                                        {{ getStatusText(field.status) }}
                                    </span>
                            </td>
                            <td>{{ field.avg_ndvi || 'N/A' }}</td>
                            <td>{{ field.avg_moisture || 'N/A' }}</td>
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

        <!-- Recommendations -->
        <div class="card mt-4" v-if="hasRecommendations">
            <div class="card-inner">
                <h5 class="card-title">Recommendations</h5>
                <div class="row g-3">
                    <div v-for="(field, fieldId) in content.summary" :key="fieldId" class="col-12">
                        <div v-if="field.recommendations && field.recommendations.length > 0" class="alert alert-info">
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

<script>
import { computed } from 'vue';

export default {
    props: {
        content: {
            type: Object,
            required: true
        }
    },
    setup(props) {
        const hasRecommendations = computed(() => {
            return Object.values(props.content.summary).some(field =>
                field.recommendations && field.recommendations.length > 0
            );
        });

        const formatDate = (dateString) => {
            return new Date(dateString).toLocaleDateString();
        };

        const getStatusBadgeClass = (status) => {
            const classes = {
                'excellent': 'badge badge-success',
                'good': 'badge badge-outline-success',
                'fair': 'badge badge-warning',
                'poor': 'badge badge-danger',
                'no_data': 'badge badge-secondary'
            };
            return classes[status] || 'badge badge-secondary';
        };

        const getStatusText = (status) => {
            const texts = {
                'excellent': 'Excellent',
                'good': 'Good',
                'fair': 'Fair',
                'poor': 'Poor',
                'no_data': 'No Data'
            };
            return texts[status] || status;
        };

        return {
            hasRecommendations,
            formatDate,
            getStatusBadgeClass,
            getStatusText
        };
    }
};
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

.badge {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    border-radius: 0.25rem;
}

.badge-success {
    background-color: #1ee0ac;
    color: white;
}
.badge-outline-success {
    border: 1px solid #1ee0ac;
    color: #1ee0ac;
    background: transparent;
}
.badge-warning {
    background-color: #f4bd0e;
    color: white;
}
.badge-danger {
    background-color: #e85347;
    color: white;
}
.badge-secondary {
    background-color: #526484;
    color: white;
}
</style>

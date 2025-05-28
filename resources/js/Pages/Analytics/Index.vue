<script>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';

import Swal from 'sweetalert2';


export default {
    layout: AppLayout,
    components: {
        Head,
        Link,
        Pagination
    },
    props: {
        fields: Array,
        analytics: Object,
        results: Object,
        sensitivities: {
            type: Array,
            default: () => ['low', 'medium', 'high']
        },
        processing: Boolean
    },
    setup(props) {
        // Analysis form
        const form = useForm({
            field_id: '',
            start_date: '',
            end_date: '',
            sensitivity: 'medium'
        });

        const isAnalyzing = ref(false);
        const analysisComplete = ref(false);
        const endDateMin = ref('');
        const showModal = ref(false);

        // Update end date minimum when start date changes
        watch(() => form.start_date, (newStartDate) => {
            if (newStartDate) {
                endDateMin.value = newStartDate;
                // If end date is now before start date, update it
                if (form.end_date && form.end_date < newStartDate) {
                    form.end_date = newStartDate;
                }
            }
        });

        // Selected field details
        const selectedField = computed(() => {
            if (!form.field_id || !props.fields) return null;
            return props.fields.find(field => field.id === form.field_id);
        });

        // Run field analysis
        const runAnalysis = () => {
            isAnalyzing.value = true;

            form.post(route('analytics.analyze', form.field_id), {
                onSuccess: () => {
                    isAnalyzing.value = false;
                    analysisComplete.value = true;

                    // Close modal properly using DOM API
                    const modalElement = document.getElementById('newAnalysis');
                    if (modalElement) {
                        // Remove any existing backdrops
                        document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
                            backdrop.remove();
                        });

                        // Hide the modal
                        modalElement.style.display = 'none';
                        modalElement.setAttribute('aria-hidden', 'true');
                        modalElement.removeAttribute('aria-modal');
                        modalElement.removeAttribute('role');

                        // Remove modal open class from body
                        document.body.classList.remove('modal-open');
                        document.body.style.overflow = '';
                        document.body.style.paddingRight = '';
                    }

                    // Reload page to show updated analytics list
                    router.reload();
                },
                onError: () => {
                    isAnalyzing.value = false;
                }
            });
        };

        // Delete analysis
        const deleteAnalysis = (id) => {
            if (confirm('Are you sure you want to delete this analysis?')) {
                router.delete(route('analytics.destroy', id), {
                    preserveScroll: true,
                    onSuccess: () => {
                        // Optionally show success message
                    }
                });
            }
        };

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
            form,
            isAnalyzing,
            analysisComplete,
            endDateMin,
            selectedField,
            runAnalysis,
            deleteAnalysis,
            getRecommendationClass
        };
    }
};
</script>

<template>
    <Head title="Field Analytics" />

    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Field Analytics</h3>
                <div class="nk-block-des text-soft">
                    <p>AI analysis of field data for precision agriculture insights</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics List -->
    <div class="nk-block">
        <div class="card card-stretch">
            <div class="card-inner-group">
                <div class="card-inner">
                    <div class="card-title-group">
                        <div class="card-title">
                            <h5 class="title">Recent Analytics</h5>
                        </div>
                        <div class="card-tools me-n1">
                            <a href="#" class="btn btn-icon btn-trigger" data-bs-toggle="modal" data-bs-target="#newAnalysis">
                                <em class="icon ni ni-plus"></em>
                            </a>
                        </div>
                    </div>
                </div>

                <div v-if="analytics && analytics.data && analytics.data.length === 0" class="card-inner p-5 text-center">
                    <div class="nk-data-empty">
                        <div class="nk-data-empty-icon">
                            <em class="icon ni ni-growth"></em>
                        </div>
                        <h5 class="nk-data-empty-title">No analytics found</h5>
                        <p class="nk-data-empty-note">Start by running an analysis on one of your fields.</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newAnalysis">
                            <em class="icon ni ni-plus"></em>
                            <span>Run New Analysis</span>
                        </button>
                    </div>
                </div>

                <div v-else-if="analytics && analytics.data" class="card-inner p-0">
                    <div class="nk-tb-list">
                        <div class="nk-tb-item nk-tb-head">
                            <div class="nk-tb-col"><span>#</span></div>
                            <div class="nk-tb-col tb-col-md"><span>Field</span></div>
                            <div class="nk-tb-col tb-col-md"><span>Analysis Date</span></div>
                            <div class="nk-tb-col tb-col-md"><span>Type</span></div>
                            <div class="nk-tb-col tb-col-md"><span>Health Status</span></div>
                            <div class="nk-tb-col tb-col-md"><span>Moisture Status</span></div>
                            <div class="nk-tb-col nk-tb-col-tools"></div>
                        </div>

                        <div v-for="(analytic, index) in analytics.data" :key="analytic.id" class="nk-tb-item">
                            <div class="nk-tb-col">
                                <span class="tb-lead">{{ analytics.from + index }}</span>
                            </div>
                            <div class="nk-tb-col tb-col-md">
                                <span class="tb-lead">
                                    <Link :href="route('fields.show', analytic.field_id)" class="text-primary">
                                        {{ analytic.field ? analytic.field.name : `Field #${analytic.field_id}` }}
                                    </Link>
                                </span>
                            </div>
                            <div class="nk-tb-col tb-col-md">
                                <span class="tb-lead">{{ new Date(analytic.analysis_date).toLocaleDateString() }}</span>
                            </div>
                            <div class="nk-tb-col tb-col-md">
                                <span class="tb-lead">{{ analytic.analysis_type }}</span>
                            </div>
                            <div class="nk-tb-col tb-col-md">
                                <span v-if="analytic.results && analytic.results.vegetation_health"
                                      class="badge"
                                      :class="{
                                        'badge-success': analytic.results.vegetation_health.health_status === 'Good',
                                        'badge-info': analytic.results.vegetation_health.health_status === 'Moderate',
                                        'badge-warning': analytic.results.vegetation_health.health_status === 'Poor',
                                        'badge-danger': analytic.results.vegetation_health.health_status === 'Critical'
                                      }">
                                    {{ analytic.results.vegetation_health.health_status }}
                                </span>
                                <span v-else class="tb-lead text-soft">N/A</span>
                            </div>
                            <div class="nk-tb-col tb-col-md">
                                <span v-if="analytic.results && analytic.results.soil_moisture"
                                      class="badge"
                                      :class="{
                                        'badge-success': analytic.results.soil_moisture.moisture_status === 'Optimal',
                                        'badge-warning': analytic.results.soil_moisture.moisture_status === 'Low',
                                        'badge-danger': analytic.results.soil_moisture.moisture_status === 'High'
                                      }">
                                    {{ analytic.results.soil_moisture.moisture_status }}
                                </span>
                                <span v-else class="tb-lead text-soft">N/A</span>
                            </div>
                            <div class="nk-tb-col nk-tb-col-tools">
                                <ul class="nk-tb-actions gx-1">
                                    <li>
                                        <div class="drodown">
                                            <a href="#" class="dropdown-toggle btn btn-icon btn-trigger" data-bs-toggle="dropdown">
                                                <em class="icon ni ni-more-h"></em>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <ul class="link-list-opt no-bdr">
                                                    <li>
                                                        <Link :href="`/analytics/${analytic.id}`">
                                                            <em class="icon ni ni-eye"></em>
                                                            <span>View Results</span>
                                                        </Link>
                                                    </li>
                                                    <li>
                                                        <Link :href="`/analytics/${analytic.id}/download`">
                                                            <em class="icon ni ni-download"></em>
                                                            <span>Download Report</span>
                                                        </Link>
                                                    </li>
                                                    <li class="divider"></li>
                                                    <li>
                                                        <a href="#" @click.prevent="deleteAnalysis(analytic.id)">
                                                            <em class="icon ni ni-trash"></em>
                                                            <span>Delete</span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="analytics && analytics.links" class="card-inner pb-3 pt-3">
                    <div class="d-flex justify-content-center">
                        <Pagination :links="analytics.links" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Analysis Modal -->
    <div class="modal fade" id="newAnalysis" tabindex="-1" aria-labelledby="newAnalysisLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newAnalysisLabel">Run New Analysis</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="runAnalysis">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="field-select">Select Field</label>
                                    <div class="form-control-wrap">
                                        <select
                                            id="field-select"
                                            v-model="form.field_id"
                                            class="form-select"
                                            required
                                        >
                                            <option value="">Select a field</option>
                                            <option
                                                v-for="field in fields"
                                                :key="field.id"
                                                :value="field.id"
                                            >
                                                {{ field.name }}
                                            </option>
                                        </select>
                                    </div>
                                    <div v-if="form.errors.field_id" class="form-note text-danger">{{ form.errors.field_id }}</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="sensitivity">Analysis Sensitivity</label>
                                    <div class="form-control-wrap">
                                        <select
                                            id="sensitivity"
                                            v-model="form.sensitivity"
                                            class="form-select"
                                        >
                                            <option value="low">Low (fewer alerts)</option>
                                            <option value="medium">Medium (balanced)</option>
                                            <option value="high">High (more detailed)</option>
                                        </select>
                                    </div>
                                    <div v-if="form.errors.sensitivity" class="form-note text-danger">{{ form.errors.sensitivity }}</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="start-date">Start Date</label>
                                    <div class="form-control-wrap">
                                        <input
                                            type="date"
                                            id="start-date"
                                            v-model="form.start_date"
                                            class="form-control"
                                        />
                                    </div>
                                    <div v-if="form.errors.start_date" class="form-note text-danger">{{ form.errors.start_date }}</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="end-date">End Date</label>
                                    <div class="form-control-wrap">
                                        <input
                                            type="date"
                                            id="end-date"
                                            v-model="form.end_date"
                                            class="form-control"
                                            :min="endDateMin"
                                        />
                                    </div>
                                    <div v-if="form.errors.end_date" class="form-note text-danger">{{ form.errors.end_date }}</div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button
                        type="button"
                        class="btn btn-primary"
                        @click="runAnalysis"
                        :disabled="isAnalyzing || !form.field_id"
                    >
                        <div v-if="isAnalyzing" class="spinner-border spinner-border-sm me-1" role="status"></div>
                        <em v-else class="icon ni ni-growth me-1"></em>
                        <span>Run Analysis</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Analysis Results (shown when results are available) -->
    <div v-if="results" class="card mt-4">
        <div class="card-header">
            <h5 class="card-title">Analysis Results</h5>
            <span class="subtitle">{{ results.analysis_date }}</span>
        </div>

        <!-- Soil Moisture Analysis -->
        <div v-if="results.soil_moisture" class="card-inner border-bottom">
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
                                    <span class="amount">{{ results.soil_moisture.avg_moisture.toFixed(2) }}</span>
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
                                                'text-success': results.soil_moisture.moisture_status === 'Optimal',
                                                'text-warning': results.soil_moisture.moisture_status === 'Low',
                                                'text-danger': results.soil_moisture.moisture_status === 'High'
                                            }"
                                        >
                                            {{ results.soil_moisture.moisture_status }}
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
                                                'text-success': results.soil_moisture.dry_area_percent < 10,
                                                'text-warning': results.soil_moisture.dry_area_percent >= 10 && results.soil_moisture.dry_area_percent < 30,
                                                'text-danger': results.soil_moisture.dry_area_percent >= 30
                                            }"
                                        >
                                            {{ results.soil_moisture.dry_area_percent.toFixed(1) }}%
                                        </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vegetation Health Analysis -->
        <div v-if="results.vegetation_health" class="card-inner border-bottom">
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
                                    <span class="amount">{{ results.vegetation_health.avg_ndvi.toFixed(2) }}</span>
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
                                                'text-success': results.vegetation_health.health_status === 'Good',
                                                'text-info': results.vegetation_health.health_status === 'Moderate',
                                                'text-warning': results.vegetation_health.health_status === 'Poor',
                                                'text-danger': results.vegetation_health.health_status === 'Critical'
                                            }"
                                        >
                                            {{ results.vegetation_health.health_status }}
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
                                                'text-success': results.vegetation_health.problem_area_percent < 10,
                                                'text-warning': results.vegetation_health.problem_area_percent >= 10 && results.vegetation_health.problem_area_percent < 30,
                                                'text-danger': results.vegetation_health.problem_area_percent >= 30
                                            }"
                                        >
                                            {{ results.vegetation_health.problem_area_percent.toFixed(1) }}%
                                        </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Anomalies -->
        <div v-if="results.anomalies && results.anomalies.length > 0" class="card-inner border-bottom">
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
                    <tr v-for="(anomaly, index) in results.anomalies" :key="index">
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
        <div v-if="results.recommendations && results.recommendations.length > 0" class="card-inner">
            <h6 class="overline-title">Recommendations</h6>
            <div class="gap g-4">
                <div v-for="(recommendation, index) in results.recommendations" :key="index" class="card">
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

    <!-- Empty state when no results available -->
    <div v-else-if="analysisComplete && !results" class="card mt-4">
        <div class="card-inner p-5 text-center">
            <div class="nk-data-empty">
                <div class="nk-data-empty-icon">
                    <em class="icon ni ni-growth"></em>
                </div>
                <h5 class="nk-data-empty-title">No analysis results</h5>
                <p class="nk-data-empty-note">There was not enough data to generate an analysis. Try selecting a different date range or field.</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.card {
    margin-bottom: 1.5rem;
}

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

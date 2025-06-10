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
        // Analysis form - DODANE NOWE POLA
        const form = useForm({
            field_id: '',
            start_date: '',
            end_date: '',
            sensitivity: 'medium',
            include_growth_prediction: false,  // NOWE
            prediction_days: 7                 // NOWE
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
                case 'scheduled': return 'badge-primary';
                case 'predictive': return 'badge-info';
                case 'positive': return 'badge-success';
                default: return 'badge-success';
            }
        };

        // NOWA FUNKCJA - Format priority badge
        const getPriorityClass = (priority) => {
            switch (priority) {
                case 'high': return 'badge-danger';
                case 'medium': return 'badge-warning';
                case 'low': return 'badge-success';
                default: return 'badge-secondary';
            }
        };

        // NOWA FUNKCJA - Format confidence percentage
        const formatConfidence = (confidence) => {
            return Math.round(confidence * 100);
        };

        return {
            form,
            isAnalyzing,
            analysisComplete,
            endDateMin,
            selectedField,
            runAnalysis,
            deleteAnalysis,
            getRecommendationClass,
            getPriorityClass,        // NOWE
            formatConfidence         // NOWE
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
                                <span class="tb-lead">
                                    {{ analytic.analysis_type }}
                                    <!-- NOWY WSKAŹNIK czy zawiera predykcję -->
                                    <span v-if="analytic.analysis_type.includes('prediction')" class="badge badge-info badge-sm ms-1">
                                        Prediction
                                    </span>
                                </span>
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

                            <!-- NOWA SEKCJA - Growth Prediction Options -->
                            <div class="col-12">
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input
                                            type="checkbox"
                                            class="custom-control-input"
                                            id="include-prediction"
                                            v-model="form.include_growth_prediction"
                                        >
                                        <label class="custom-control-label" for="include-prediction">
                                            <strong>Include Growth Prediction</strong>
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">
                                        Enable AI-powered growth prediction for the next several days (requires more processing time)
                                    </small>
                                    <div v-if="form.errors.include_growth_prediction" class="form-note text-danger">{{ form.errors.include_growth_prediction }}</div>
                                </div>
                            </div>

                            <!-- POLE PREDICTION DAYS (pokazywane tylko gdy checkbox zaznaczony) -->
                            <div v-if="form.include_growth_prediction" class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="prediction-days">Prediction Days</label>
                                    <div class="form-control-wrap">
                                        <input
                                            type="number"
                                            id="prediction-days"
                                            v-model.number="form.prediction_days"
                                            class="form-control"
                                            min="1"
                                            max="30"
                                            placeholder="7"
                                        />
                                    </div>
                                    <small class="form-text text-muted">Number of days to predict future growth (1-30 days)</small>
                                    <div v-if="form.errors.prediction_days" class="form-note text-danger">{{ form.errors.prediction_days }}</div>
                                </div>
                            </div>

                            <!-- INFORMACJA O WYBRANYM POLU -->
                            <div v-if="selectedField" class="col-12">
                                <div class="alert alert-info">
                                    <h6 class="alert-heading">Selected Field Information</h6>
                                    <p class="mb-1"><strong>Name:</strong> {{ selectedField.name }}</p>
                                    <p class="mb-1"><strong>Size:</strong> {{ selectedField.size }} hectares</p>
                                    <p class="mb-0"><strong>Status:</strong> {{ selectedField.status }}</p>
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
                        <span>{{ form.include_growth_prediction ? 'Run Analysis + Prediction' : 'Run Analysis' }}</span>
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

        <!-- NOWA SEKCJA - Growth Prediction Results -->
        <div v-if="results.growth_prediction && !results.growth_prediction.error" class="card-inner border-bottom">
            <h6 class="overline-title">🌱 Growth Prediction Results</h6>

            <!-- Current Status -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-inner">
                            <div class="card-title-group align-start mb-2">
                                <div class="card-title">
                                    <h6 class="subtitle">Current Biomass</h6>
                                </div>
                            </div>
                            <div class="align-end flex-sm-wrap g-4 flex-md-nowrap">
                                <div class="nk-sale-data">
                                    <span class="amount">{{ Math.round(results.growth_prediction.current_biomass) }}</span>
                                    <span class="subtitle">kg/ha</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-inner">
                            <div class="card-title-group align-start mb-2">
                                <div class="card-title">
                                    <h6 class="subtitle">Growth Stage</h6>
                                </div>
                            </div>
                            <div class="align-end flex-sm-wrap g-4 flex-md-nowrap">
                                <div class="nk-sale-data">
                                    <span class="amount">{{ results.growth_prediction.current_status.growth_stage }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-inner">
                            <div class="card-title-group align-start mb-2">
                                <div class="card-title">
                                    <h6 class="subtitle">Health Score</h6>
                                </div>
                            </div>
                            <div class="align-end flex-sm-wrap g-4 flex-md-nowrap">
                                <div class="nk-sale-data">
                                    <span class="amount" :class="{
                                        'text-success': results.growth_prediction.current_status.health_score >= 75,
                                        'text-warning': results.growth_prediction.current_status.health_score >= 50 && results.growth_prediction.current_status.health_score < 75,
                                        'text-danger': results.growth_prediction.current_status.health_score < 50
                                    }">{{ results.growth_prediction.current_status.health_score }}%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-inner">
                            <div class="card-title-group align-start mb-2">
                                <div class="card-title">
                                    <h6 class="subtitle">Days Since Planting</h6>
                                </div>
                            </div>
                            <div class="align-end flex-sm-wrap g-4 flex-md-nowrap">
                                <div class="nk-sale-data">
                                    <span class="amount">{{ results.growth_prediction.current_status.days_since_planting }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Prediction Summary -->
            <div v-if="results.growth_prediction.summary" class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-inner">
                            <div class="card-title-group align-start mb-2">
                                <div class="card-title">
                                    <h6 class="subtitle">Predicted Growth</h6>
                                </div>
                            </div>
                            <div class="align-end flex-sm-wrap g-4 flex-md-nowrap">
                                <div class="nk-sale-data">
                                    <span class="amount text-success">+{{ results.growth_prediction.summary.total_predicted_growth }}</span>
                                    <span class="subtitle">kg/ha</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-inner">
                            <div class="card-title-group align-start mb-2">
                                <div class="card-title">
                                    <h6 class="subtitle">Daily Growth Rate</h6>
                                </div>
                            </div>
                            <div class="align-end flex-sm-wrap g-4 flex-md-nowrap">
                                <div class="nk-sale-data">
                                    <span class="amount">{{ results.growth_prediction.summary.average_daily_growth }}</span>
                                    <span class="subtitle">kg/ha/day</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-inner">
                            <div class="card-title-group align-start mb-2">
                                <div class="card-title">
                                    <h6 class="subtitle">Avg Confidence</h6>
                                </div>
                            </div>
                            <div class="align-end flex-sm-wrap g-4 flex-md-nowrap">
                                <div class="nk-sale-data">
                                    <span class="amount">{{ formatConfidence(results.growth_prediction.summary.confidence_range.avg) }}%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daily Predictions Table -->
            <div v-if="results.growth_prediction.predictions" class="table-responsive">
                <h6 class="subtitle mb-3">📈 Daily Growth Predictions</h6>
                <table class="table table-bordered">
                    <thead>
                    <tr>
                        <th>Day</th>
                        <th>Date</th>
                        <th>Predicted Biomass</th>
                        <th>Growth Rate</th>
                        <th>Growth Stage</th>
                        <th>Confidence</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="prediction in results.growth_prediction.predictions" :key="prediction.day">
                        <td><strong>+{{ prediction.day }}</strong></td>
                        <td>{{ new Date(prediction.date).toLocaleDateString() }}</td>
                        <td>{{ Math.round(prediction.predicted_biomass) }} kg/ha</td>
                        <td class="text-success">+{{ Math.round(prediction.growth_rate) }} kg/ha</td>
                        <td>{{ prediction.growth_stage }}</td>
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

            <!-- Current Status Issues/Warnings -->
            <div v-if="results.growth_prediction.current_status.issues.length > 0 || results.growth_prediction.current_status.warnings.length > 0" class="mt-4">
                <h6 class="subtitle mb-3">⚠️ Current Status Alerts</h6>
                <div class="row g-2">
                    <div v-for="issue in results.growth_prediction.current_status.issues" :key="issue" class="col-12">
                        <div class="alert alert-danger mb-2">
                            <strong>Issue:</strong> {{ issue }}
                        </div>
                    </div>
                    <div v-for="warning in results.growth_prediction.current_status.warnings" :key="warning" class="col-12">
                        <div class="alert alert-warning mb-2">
                            <strong>Warning:</strong> {{ warning }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- BŁĄD PREDYKCJI (jeśli wystąpi) -->
        <div v-else-if="results.growth_prediction && results.growth_prediction.error" class="card-inner border-bottom">
            <h6 class="overline-title">🌱 Growth Prediction</h6>
            <div class="alert alert-danger">
                <h6 class="alert-heading">Prediction Error</h6>
                <p class="mb-0">{{ results.growth_prediction.error }}</p>
            </div>
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

        <!-- ZAKTUALIZOWANE Recommendations - z nowymi polami -->
        <div v-if="results.recommendations && results.recommendations.length > 0" class="card-inner">
            <h6 class="overline-title">💡 Recommendations</h6>
            <div class="gap g-4">
                <div v-for="(recommendation, index) in results.recommendations" :key="index" class="card">
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
                                        {{ recommendation.priority }}
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
                                    {{ recommendation.urgency_days }} days
                                </span>
                                <span v-if="recommendation.estimated_impact" class="badge badge-outline-secondary ms-1">
                                    {{ recommendation.estimated_impact }} impact
                                </span>
                            </div>
                        </div>
                        <p class="mb-1">{{ recommendation.description }}</p>
                        <!-- Źródło rekomendacji (jeśli dostępne) -->
                        <small v-if="recommendation.source" class="text-muted">
                            Source: {{ recommendation.source }}
                        </small>
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

/* Custom checkbox styling */
.custom-control-input {
    position: absolute;
    left: 0;
    z-index: -1;
    width: 1rem;
    height: 1.25rem;
    opacity: 0;
}

.custom-control-input:checked ~ .custom-control-label::before {
    color: #fff;
    border-color: #6576ff;
    background-color: #6576ff;
}

.custom-control-label {
    position: relative;
    margin-bottom: 0;
    vertical-align: top;
}

.custom-control-label::before {
    position: absolute;
    top: 0.25rem;
    left: -1.5rem;
    display: block;
    width: 1rem;
    height: 1rem;
    pointer-events: none;
    content: "";
    background-color: #fff;
    border: 1px solid #adb5bd;
    border-radius: 0.25rem;
}

.custom-control-label::after {
    position: absolute;
    top: 0.25rem;
    left: -1.5rem;
    display: block;
    width: 1rem;
    height: 1rem;
    content: "";
    background: no-repeat 50%/50% 50%;
}

.custom-control-input:checked ~ .custom-control-label::after {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8' viewBox='0 0 8 8'%3e%3cpath fill='%23fff' d='m6.564.75-3.59 3.612-1.538-1.55L0 4.26l2.974 2.99L8 2.193z'/%3e%3c/svg%3e");
}
</style>

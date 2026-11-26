<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import NewAnalysisModal from '@/Components/Analytics/NewAnalysisModal.vue';
import { ConfirmModal } from '@/Helpers';
import { formatDate, statusClass } from '@/format';

defineOptions({ layout: AppLayout });

defineProps({
    fields: {
        type: Array,
        default: () => [],
    },
    analytics: Object,
    sensitivities: {
        type: Array,
        default: () => ['low', 'medium', 'high'],
    },
});

const showNewAnalysis = ref(false);

const deleteAnalysis = async (id) => {
    const { isConfirmed } = await ConfirmModal.fire({
        icon: 'warning',
        title: 'Delete Analysis',
        text: 'Are you sure you want to delete this analysis?',
        confirmButtonText: 'Yes, delete',
    });

    if (isConfirmed) {
        router.delete(route('analytics.destroy', id), { preserveScroll: true });
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

    <div class="nk-block">
        <div class="card card-stretch">
            <div class="card-inner-group">
                <div class="card-inner">
                    <div class="card-title-group">
                        <div class="card-title">
                            <h5 class="title">Recent Analytics</h5>
                        </div>
                        <div class="card-tools me-n1">
                            <button
                                type="button"
                                class="btn btn-icon btn-trigger"
                                aria-label="Run new analysis"
                                @click="showNewAnalysis = true"
                            >
                                <em class="icon ni ni-plus"></em>
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="analytics?.data?.length === 0" class="card-inner p-5 text-center">
                    <div class="nk-data-empty">
                        <div class="nk-data-empty-icon">
                            <em class="icon ni ni-growth"></em>
                        </div>
                        <h5 class="nk-data-empty-title">No analytics found</h5>
                        <p class="nk-data-empty-note">Start by running an analysis on one of your fields.</p>
                        <button type="button" class="btn btn-primary" @click="showNewAnalysis = true">
                            <em class="icon ni ni-plus"></em>
                            <span>Run New Analysis</span>
                        </button>
                    </div>
                </div>

                <div v-else-if="analytics?.data" class="card-inner p-0">
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
                                <span class="tb-lead">{{ formatDate(analytic.analysis_date) }}</span>
                            </div>
                            <div class="nk-tb-col tb-col-md">
                                <span class="tb-lead">
                                    {{ analytic.analysis_type }}
                                    <span v-if="analytic.analysis_type?.includes('prediction')" class="badge badge-sm bg-info ms-1">
                                        Prediction
                                    </span>
                                </span>
                            </div>
                            <div class="nk-tb-col tb-col-md">
                                <span
                                    v-if="analytic.results?.vegetation_health?.health_status"
                                    class="badge"
                                    :class="statusClass(analytic.results.vegetation_health.health_status)"
                                >
                                    {{ analytic.results.vegetation_health.health_status }}
                                </span>
                                <span v-else class="tb-lead text-soft">N/A</span>
                            </div>
                            <div class="nk-tb-col tb-col-md">
                                <span
                                    v-if="analytic.results?.soil_moisture?.moisture_status"
                                    class="badge"
                                    :class="statusClass(analytic.results.soil_moisture.moisture_status)"
                                >
                                    {{ analytic.results.soil_moisture.moisture_status }}
                                </span>
                                <span v-else class="tb-lead text-soft">N/A</span>
                            </div>
                            <div class="nk-tb-col nk-tb-col-tools">
                                <ul class="nk-tb-actions gx-1">
                                    <li>
                                        <div class="dropdown">
                                            <button
                                                type="button"
                                                class="dropdown-toggle btn btn-icon btn-trigger"
                                                data-bs-toggle="dropdown"
                                                aria-label="Actions"
                                            >
                                                <em class="icon ni ni-more-h"></em>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <ul class="link-list-opt no-bdr">
                                                    <li>
                                                        <Link :href="route('analytics.show', analytic.id)">
                                                            <em class="icon ni ni-eye"></em>
                                                            <span>View Results</span>
                                                        </Link>
                                                    </li>
                                                    <li>
                                                        <a :href="route('analytics.download', analytic.id)">
                                                            <em class="icon ni ni-download"></em>
                                                            <span>Download Report</span>
                                                        </a>
                                                    </li>
                                                    <li class="divider"></li>
                                                    <li>
                                                        <button type="button" @click="deleteAnalysis(analytic.id)">
                                                            <em class="icon ni ni-trash"></em>
                                                            <span>Delete</span>
                                                        </button>
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

                <div v-if="analytics?.links" class="card-inner pb-3 pt-3">
                    <div class="d-flex justify-content-center">
                        <Pagination :links="analytics.links" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <NewAnalysisModal
        :show="showNewAnalysis"
        :fields="fields"
        :sensitivities="sensitivities"
        @close="showNewAnalysis = false"
    />
</template>

<style scoped>
.badge-sm {
    padding: 0.15em 0.3em;
    font-size: 0.65em;
}
</style>

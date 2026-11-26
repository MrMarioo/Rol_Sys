<template>
    <Head :title="field.name" />
    <div>
        <div class="nk-block-head nk-block-head-sm">
            <div class="nk-block-between">
                <div class="nk-block-head-content">
                    <h3 class="nk-block-title page-title">Field Details</h3>
                    <div class="nk-block-des text-soft">
                        <Link :href="route('fields.index')" class="btn btn-outline-primary btn-sm">
                            <em class="icon ni ni-arrow-left me-1"></em>
                            Back to Fields
                        </Link>
                        <Link :href="route('fields.edit', field.id)" class="btn btn-primary btn-sm ms-1">
                            <em class="icon ni ni-edit me-1"></em>
                            Edit
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs mt-n3" role="tablist">
            <li v-for="tab in tabs" :key="tab.key" class="nav-item">
                <button
                    type="button"
                    class="nav-link"
                    role="tab"
                    :class="{ active: activeTab === tab.key }"
                    :aria-selected="activeTab === tab.key"
                    @click="selectTab(tab.key)"
                >
                    {{ tab.label }}
                </button>
            </li>
        </ul>

        <div class="nk-block">
            <div v-if="activeTab === 'overview'" class="row g-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Field Location</h5>
                        </div>
                        <div class="card-body p-0">
                            <FieldMap mode="view" height="500px" :boundaries="field.boundaries" :showControls="false" />
                        </div>
                    </div>
                </div>

                <div v-if="latestReadings.length" class="col-12">
                    <LatestReadingTiles :readings="latestReadings" />
                </div>

                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">What to do now</h5>
                            <p v-if="latestAnalysis" class="text-soft mb-0">From analysis {{ latestAnalysis.date }}</p>
                        </div>
                        <div class="card-body">
                            <RecommendationList v-if="topRecommendations.length" :items="topRecommendations" compact />
                            <p v-else-if="latestAnalysis" class="text-soft mb-0">No recommendations in the latest analysis.</p>
                            <div v-else class="text-center p-3">
                                <em class="icon ni ni-growth text-soft empty-icon"></em>
                                <h6 class="mt-3">No AI analysis yet</h6>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-3" @click="selectTab('analytics')">
                                See all analyses
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="!recentReadings.length" class="col-12">
                    <div class="card">
                        <div class="card-body text-center p-5">
                            <em class="icon ni ni-growth text-soft empty-icon"></em>
                            <h6 class="mt-3">No data collected for this field yet</h6>
                            <p class="text-soft">Add the first measurement to see readings and trends here.</p>
                            <Link :href="route('field-data.create')" class="btn btn-primary">
                                <em class="icon ni ni-plus me-1"></em>
                                <span>Add data</span>
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="form-group">
                                <div class="form-label">Field Name</div>
                                <div class="form-control-wrap">
                                    <div class="form-control-plaintext">{{ field.name }}</div>
                                </div>
                            </div>

                            <div class="form-group mt-3">
                                <div class="form-label">Location</div>
                                <div class="form-control-wrap">
                                    <div class="form-control-plaintext">{{ field.location || 'Not specified' }}</div>
                                </div>
                            </div>

                            <div class="form-group mt-3">
                                <div class="form-label">Size (hectares)</div>
                                <div class="form-control-wrap">
                                    <div class="form-control-plaintext">{{ field.size }} ha</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="form-group">
                                <div class="form-label">Status</div>
                                <div class="form-control-wrap">
                                    <div class="form-control-plaintext">
                                        <FieldStatusBadge :status="field.status" />
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mt-3">
                                <div class="form-label">Description</div>
                                <div class="form-control-wrap">
                                    <div class="form-control-plaintext">{{ field.description || 'No description provided' }}</div>
                                </div>
                            </div>

                            <div class="form-group mt-3" v-if="field.created_at">
                                <div class="form-label">Created At</div>
                                <div class="form-control-wrap">
                                    <div class="form-control-plaintext">{{ formatDateTime(field.created_at) }}</div>
                                </div>
                            </div>

                            <div class="form-group mt-3" v-if="field.updated_at">
                                <div class="form-label">Last Updated</div>
                                <div class="form-control-wrap">
                                    <div class="form-control-plaintext">{{ formatDateTime(field.updated_at) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'data'" class="row g-4">
                <template v-if="recentReadings.length">
                    <div v-if="Object.keys(trendSeries).length" class="col-12">
                        <FieldTrendChart :series="trendSeries" />
                    </div>

                    <div class="col-12">
                        <RecentReadingsTable :readings="recentReadings" :field-id="field.id" />
                    </div>
                </template>

                <div v-else class="col-12">
                    <div class="card">
                        <div class="card-body text-center p-5">
                            <em class="icon ni ni-growth text-soft empty-icon"></em>
                            <h6 class="mt-3">No data collected for this field yet</h6>
                            <p class="text-soft">Add the first measurement to see readings and trends here.</p>
                            <Link :href="route('field-data.create')" class="btn btn-primary">
                                <em class="icon ni ni-plus me-1"></em>
                                <span>Add data</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'analytics'" class="row g-4">
                <div class="col-12 d-flex justify-content-end">
                    <button type="button" class="btn btn-primary" @click="showRunModal = true">
                        <em class="icon ni ni-play me-1"></em>
                        <span>Run analysis</span>
                    </button>
                </div>

                <template v-if="latestAnalysis">
                    <div class="col-12">
                        <LatestAnalysisCard :analysis="latestAnalysis" />
                    </div>

                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Recommendations</h5>
                            </div>
                            <div class="card-body">
                                <RecommendationList v-if="latestAnalysis.recommendations.length" :items="latestAnalysis.recommendations" />
                                <p v-else class="text-soft mb-0">No recommendations in the latest analysis.</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="analysisHistory.length >= 2" class="col-12">
                        <AnalysisHistoryChart :history="analysisHistory" />
                    </div>

                    <div class="col-12">
                        <AnalysisHistoryTable :history="analysisHistory" />
                    </div>
                </template>

                <div v-else class="col-12">
                    <div class="card">
                        <div class="card-body text-center p-5">
                            <em class="icon ni ni-growth text-soft empty-icon"></em>
                            <h6 class="mt-3">No analyses for this field yet</h6>
                            <button type="button" class="btn btn-primary mt-2" @click="showRunModal = true">
                                <em class="icon ni ni-play me-1"></em>
                                <span>Run analysis</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <RunAnalysisModal
            :show="showRunModal"
            :field-id="field.id"
            :sensitivities="sensitivities"
            @close="showRunModal = false"
        />
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import FieldMap from '@/Components/FieldMap.vue';
import FieldStatusBadge from '@/Components/FieldStatusBadge.vue';
import FieldTrendChart from '@/Components/Field/FieldTrendChart.vue';
import LatestReadingTiles from '@/Components/Field/LatestReadingTiles.vue';
import RecentReadingsTable from '@/Components/Field/RecentReadingsTable.vue';
import AnalysisHistoryChart from '@/Components/Field/AnalysisHistoryChart.vue';
import AnalysisHistoryTable from '@/Components/Field/AnalysisHistoryTable.vue';
import LatestAnalysisCard from '@/Components/Field/LatestAnalysisCard.vue';
import RecommendationList from '@/Components/Field/RecommendationList.vue';
import RunAnalysisModal from '@/Components/Field/RunAnalysisModal.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatDateTime } from '@/format';

defineOptions({ layout: AppLayout });

defineProps({
    field: {
        type: Object,
        required: true,
    },
    latestReadings: {
        type: Array,
        default: () => [],
    },
    trendSeries: {
        type: [Object, Array],
        default: () => ({}),
    },
    recentReadings: {
        type: Array,
        default: () => [],
    },
    latestAnalysis: {
        type: Object,
        default: null,
    },
    topRecommendations: {
        type: Array,
        default: () => [],
    },
    analysisHistory: {
        type: Array,
        default: () => [],
    },
    sensitivities: {
        type: Array,
        default: () => [],
    },
});

const tabs = [
    { key: 'overview', label: 'Overview' },
    { key: 'data', label: 'Data' },
    { key: 'analytics', label: 'Analytics' },
];

const initialTab = new URLSearchParams(window.location.search).get('tab');
const activeTab = ref(tabs.some((tab) => tab.key === initialTab) ? initialTab : 'overview');

const showRunModal = ref(false);

const selectTab = (tab) => {
    activeTab.value = tab;
    const url = new URL(window.location.href);
    url.searchParams.set('tab', tab);
    router.replace({ url: url.toString(), preserveState: true, preserveScroll: true });
};
</script>

<style scoped>
.empty-icon {
    font-size: 2.5rem;
}

.form-control-plaintext {
    padding: 0.375rem 0;
    font-size: 0.875rem;
    line-height: 1.5;
}
</style>

<template>
    <div>
        <div class="nk-block-head nk-block-head-sm">
            <div class="nk-block-between">
                <div class="nk-block-head-content">
                    <h3 class="nk-block-title page-title">Field Details</h3>
                    <div class="nk-block-des text-soft">
                        <Link href="/fields" class="btn btn-outline-primary btn-sm">
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

        <ul class="nav nav-tabs mt-n3">
            <li v-for="tab in tabs" :key="tab.key" class="nav-item">
                <a href="#" class="nav-link" :class="{ active: activeTab === tab.key }" @click.prevent="selectTab(tab.key)">
                    {{ tab.label }}
                </a>
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
                                <label class="form-label">Field Name</label>
                                <div class="form-control-wrap">
                                    <div class="form-control-plaintext">{{ field.name }}</div>
                                </div>
                            </div>

                            <div class="form-group mt-3">
                                <label class="form-label">Location</label>
                                <div class="form-control-wrap">
                                    <div class="form-control-plaintext">{{ field.location || 'Not specified' }}</div>
                                </div>
                            </div>

                            <div class="form-group mt-3">
                                <label class="form-label">Size (hectares)</label>
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
                                <label class="form-label">Status</label>
                                <div class="form-control-wrap">
                                    <div class="form-control-plaintext">
                                        <span :class="getStatusBadgeClass(field.status)">
                                            {{ formatStatus(field.status) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mt-3">
                                <label class="form-label">Description</label>
                                <div class="form-control-wrap">
                                    <div class="form-control-plaintext">{{ field.description || 'No description provided' }}</div>
                                </div>
                            </div>

                            <div class="form-group mt-3" v-if="field.created_at">
                                <label class="form-label">Created At</label>
                                <div class="form-control-wrap">
                                    <div class="form-control-plaintext">{{ formatDate(field.created_at) }}</div>
                                </div>
                            </div>

                            <div class="form-group mt-3" v-if="field.updated_at">
                                <label class="form-label">Last Updated</label>
                                <div class="form-control-wrap">
                                    <div class="form-control-plaintext">{{ formatDate(field.updated_at) }}</div>
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

<script>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import FieldMap from '@/Components/FieldMap.vue';
import FieldTrendChart from '@/Components/Field/FieldTrendChart.vue';
import LatestReadingTiles from '@/Components/Field/LatestReadingTiles.vue';
import RecentReadingsTable from '@/Components/Field/RecentReadingsTable.vue';
import AnalysisHistoryChart from '@/Components/Field/AnalysisHistoryChart.vue';
import AnalysisHistoryTable from '@/Components/Field/AnalysisHistoryTable.vue';
import LatestAnalysisCard from '@/Components/Field/LatestAnalysisCard.vue';
import RecommendationList from '@/Components/Field/RecommendationList.vue';
import RunAnalysisModal from '@/Components/Field/RunAnalysisModal.vue';
import AppLayout from '@/Layouts/AppLayout.vue';

export default {
    layout: AppLayout,
    components: {
        AnalysisHistoryChart,
        AnalysisHistoryTable,
        LatestAnalysisCard,
        FieldMap,
        FieldTrendChart,
        LatestReadingTiles,
        Link,
        RecentReadingsTable,
        RecommendationList,
        RunAnalysisModal,
    },
    props: {
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
    },
    setup(props) {
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

        const formatStatus = (status) => {
            if (status === 'active') return 'Active';
            if (status === 'inactive') return 'Inactive';
            return status.charAt(0).toUpperCase() + status.slice(1);
        };

        const getStatusBadgeClass = (status) => {
            if (status === 'active') return 'badge badge-success';
            if (status === 'inactive') return 'badge badge-warning';
            return 'badge badge-primary';
        };

        const formatDate = (dateString) => {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleString();
        };

        return {
            tabs,
            activeTab,
            showRunModal,
            selectTab,
            formatStatus,
            getStatusBadgeClass,
            formatDate,
        };
    },
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

.badge-warning {
    color: #fff;
    background-color: #f4bd0e;
}

.badge-primary {
    color: #fff;
    background-color: #6576ff;
}
</style>

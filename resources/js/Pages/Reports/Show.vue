<template>
    <div>
        <Head :title="report.title" />

        <div class="nk-block-head nk-block-head-sm">
            <div class="nk-block-between">
                <div class="nk-block-head-content">
                    <h3 class="nk-block-title page-title">{{ report.title }}</h3>
                    <div class="nk-block-des text-soft">
                        <Link href="/reports" class="btn btn-outline-primary btn-sm">
                            <em class="icon ni ni-arrow-left me-1"></em>
                            Back to Reports
                        </Link>
                    </div>
                </div>
                <div class="nk-block-head-content">
                    <div class="d-flex gap-2">
                        <button
                            v-if="report.status === 'draft'"
                            @click="generateReport"
                            class="btn btn-outline-primary"
                            :disabled="isGenerating"
                        >
                            <div v-if="isGenerating" class="spinner-border spinner-border-sm me-1" role="status"></div>
                            <em v-else class="icon ni ni-loader me-1"></em>
                            <span>Generate</span>
                        </button>
                        <button
                            v-if="report.status === 'generated'"
                            @click="downloadReport"
                            class="btn btn-outline-success"
                        >
                            <em class="icon ni ni-download me-1"></em>
                            <span>Download PDF</span>
                        </button>
                        <Link
                            v-if="report.status === 'draft'"
                            :href="route('reports.edit', report.id)"
                            class="btn btn-primary"
                        >
                            <em class="icon ni ni-edit me-1"></em>
                            <span>Edit</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Info -->
        <div class="nk-block">
            <div class="row g-4">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-inner">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label class="form-label">Report Type</label>
                                        <div class="form-control-plaintext">
                                            <span class="badge badge-outline-info">{{ formatReportType(report.type) }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label class="form-label">Status</label>
                                        <div class="form-control-plaintext">
                                            <ReportStatusBadge :status="report.status" />
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12" v-if="report.description">
                                    <div class="form-group">
                                        <label class="form-label">Description</label>
                                        <div class="form-control-plaintext">{{ report.description }}</div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label class="form-label">Created</label>
                                        <div class="form-control-plaintext">{{ formatDate(report.created_at) }}</div>
                                    </div>
                                </div>
                                <div class="col-sm-6" v-if="report.updated_at !== report.created_at">
                                    <div class="form-group">
                                        <label class="form-label">Last Updated</label>
                                        <div class="form-control-plaintext">{{ formatDate(report.updated_at) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-inner">
                            <h6 class="card-title">Report Parameters</h6>
                            <ul class="list-group list-group-flush">
                                <li v-for="(value, key) in report.parameters" :key="key" class="list-group-item px-0">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-soft">{{ formatParameterKey(key) }}:</span>
                                        <span>{{ formatParameterValue(key, value) }}</span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Content -->
        <div class="nk-block" v-if="report.status === 'generated' && report.content">
            <div class="card">
                <div class="card-inner">
                    <h5 class="card-title">Report Content</h5>

                    <!-- Weekly/Monthly Summary -->
                    <div v-if="isWeeklySummary || isMonthlySummary">
                        <WeeklySummaryContent :content="report.content" />
                    </div>

                    <!-- Problem Report -->
                    <div v-else-if="isProblemReport">
                        <ProblemReportContent :content="report.content" />
                    </div>

                    <!-- Crop Status Report -->
                    <div v-else-if="isCropStatus">
                        <CropStatusContent :content="report.content" />
                    </div>

                    <!-- Comparative Report -->
                    <div v-else-if="isComparative">
                        <ComparativeContent :content="report.content" />
                    </div>

                    <!-- Raw JSON for unknown types -->
                    <div v-else>
                        <pre class="bg-light p-3">{{ JSON.stringify(report.content, null, 2) }}</pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- Draft Message -->
        <div class="nk-block" v-else-if="report.status === 'draft'">
            <div class="card">
                <div class="card-inner text-center py-5">
                    <div class="nk-data-empty">
                        <div class="nk-data-empty-icon">
                            <em class="icon ni ni-file-text"></em>
                        </div>
                        <h5 class="nk-data-empty-title">Report Not Generated</h5>
                        <p class="nk-data-empty-note">This report is still in draft mode. Click "Generate" to create the content.</p>
                        <button @click="generateReport" class="btn btn-primary" :disabled="isGenerating">
                            <div v-if="isGenerating" class="spinner-border spinner-border-sm me-1" role="status"></div>
                            <em v-else class="icon ni ni-loader me-1"></em>
                            <span>Generate Report</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ReportStatusBadge from '@/Components/ReportStatusBadge.vue';
import WeeklySummaryContent from '@/Components/Reports/WeeklySummaryContent.vue';
import ProblemReportContent from '@/Components/Reports/ProblemReportContent.vue';
import CropStatusContent from '@/Components/Reports/CropStatusContent.vue';
import ComparativeContent from '@/Components/Reports/ComparativeContent.vue';

export default {
    layout: AppLayout,
    components: {
        Head,
        Link,
        ReportStatusBadge,
        WeeklySummaryContent,
        ProblemReportContent,
        CropStatusContent,
        ComparativeContent,
    },
    props: {
        report: Object,
    },
    setup(props) {
        const isGenerating = ref(false);

        const isWeeklySummary = computed(() => props.report.type === 'weekly_summary');
        const isMonthlySummary = computed(() => props.report.type === 'monthly_summary');
        const isProblemReport = computed(() => props.report.type === 'problem_report');
        const isCropStatus = computed(() => props.report.type === 'crop_status');
        const isComparative = computed(() => props.report.type === 'comparative');

        const formatReportType = (type) => {
            const types = {
                'weekly_summary': 'Weekly Summary',
                'monthly_summary': 'Monthly Summary',
                'problem_report': 'Problem Report',
                'crop_status': 'Crop Status',
                'comparative': 'Comparative Analysis'
            };
            return types[type] || type;
        };

        const formatDate = (dateString) => {
            return new Date(dateString).toLocaleString();
        };

        const formatParameterKey = (key) => {
            return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        };

        const formatParameterValue = (key, value) => {
            if (key.includes('date')) {
                return new Date(value).toLocaleDateString();
            }
            if (Array.isArray(value)) {
                return value.length + ' items';
            }
            return value;
        };

        const generateReport = () => {
            isGenerating.value = true;
            router.visit(route('reports.generate', props.report.id), {
                method: 'post',
                preserveScroll: true,
                onFinish: () => {
                    isGenerating.value = false;
                },
            });
        };

        const downloadReport = () => {
            window.open(route('reports.download', props.report.id), '_blank');
        };

        return {
            isGenerating,
            isWeeklySummary,
            isMonthlySummary,
            isProblemReport,
            isCropStatus,
            isComparative,
            formatReportType,
            formatDate,
            formatParameterKey,
            formatParameterValue,
            generateReport,
            downloadReport,
        };
    },
};
</script>

<style scoped>
.form-control-plaintext {
    padding: 0.375rem 0;
    font-size: 0.875rem;
    line-height: 1.5;
}

.list-group-item {
    border: none;
    padding: 0.5rem 0;
}

.nk-data-empty-icon .icon {
    font-size: 3rem;
    color: #c4c4c4;
}
</style>

<template>
    <div>
        <Head title="Reports Management" />

        <div class="nk-block-head nk-block-head-sm">
            <div class="nk-block-between">
                <div class="nk-block-head-content">
                    <h3 class="nk-block-title page-title">Reports Management</h3>
                    <div class="nk-block-des text-soft">
                        <p>You have total {{ reports.total }} reports</p>
                    </div>
                </div>
                <div class="nk-block-head-content">
                    <div class="toggle-wrap nk-block-tools-toggle">
                        <Link :href="route('reports.create')" class="btn btn-primary">
                            <em class="icon ni ni-plus"></em>
                            <span>Create Report</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="nk-block">
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-inner">
                            <h5 class="card-title">Quick Templates</h5>
                            <div class="row g-3">
                                <div v-for="(template, key) in templates" :key="key" class="col-md-4 col-lg-3">
                                    <Link
                                        :href="route('reports.create', { template: key })"
                                        class="card card-bordered template-card h-100"
                                    >
                                        <div class="card-inner text-center">
                                            <div class="nk-iv-wg2-title">
                                                <em class="icon ni" :class="template.icon"></em>
                                            </div>
                                            <h6 class="title">{{ template.name }}</h6>
                                            <p class="sub-text">{{ template.description }}</p>
                                        </div>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-stretch">
                <div class="card-inner-group">
                    <div v-if="reports.data.length === 0" class="card-inner p-5 text-center">
                        <div class="nk-data-empty">
                            <div class="nk-data-empty-icon">
                                <em class="icon ni ni-files"></em>
                            </div>
                            <h5 class="nk-data-empty-title">No reports found</h5>
                            <p class="nk-data-empty-note">Create your first report using one of the templates above.</p>
                        </div>
                    </div>

                    <div v-else class="card-inner p-0">
                        <div class="nk-tb-list">
                            <div class="nk-tb-item nk-tb-head">
                                <div class="nk-tb-col"><span>Title</span></div>
                                <div class="nk-tb-col tb-col-md"><span>Type</span></div>
                                <div class="nk-tb-col tb-col-md"><span>Status</span></div>
                                <div class="nk-tb-col tb-col-md"><span>Created</span></div>
                                <div class="nk-tb-col nk-tb-col-tools"></div>
                            </div>

                            <div v-for="report in reports.data" :key="report.id" class="nk-tb-item">
                                <div class="nk-tb-col">
                                    <span class="tb-lead">
                                        <Link :href="route('reports.show', report.id)" class="text-primary">
                                            {{ report.title }}
                                        </Link>
                                    </span>
                                    <span v-if="report.description" class="tb-sub">{{ report.description }}</span>
                                </div>
                                <div class="nk-tb-col tb-col-md">
                                    <span class="badge badge-dim bg-outline-info">{{ templates[report.type]?.name ?? formatLabel(report.type) }}</span>
                                </div>
                                <div class="nk-tb-col tb-col-md">
                                    <ReportStatusBadge :status="report.status" />
                                </div>
                                <div class="nk-tb-col tb-col-md">
                                    <span class="tb-lead">{{ formatDate(report.created_at) }}</span>
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
                                                            <Link :href="route('reports.show', report.id)">
                                                                <em class="icon ni ni-eye"></em>
                                                                <span>View</span>
                                                            </Link>
                                                        </li>
                                                        <li v-if="report.status === 'draft'">
                                                            <Link :href="route('reports.edit', report.id)">
                                                                <em class="icon ni ni-edit"></em>
                                                                <span>Edit</span>
                                                            </Link>
                                                        </li>
                                                        <li v-if="report.status === 'draft'">
                                                            <Link :href="route('reports.generate', report.id)" method="post" as="button" preserve-scroll>
                                                                <em class="icon ni ni-loader"></em>
                                                                <span>Generate</span>
                                                            </Link>
                                                        </li>
                                                        <li v-if="report.status === 'generated'">
                                                            <a :href="route('reports.download', report.id)" target="_blank" rel="noopener">
                                                                <em class="icon ni ni-download"></em>
                                                                <span>Download</span>
                                                            </a>
                                                        </li>
                                                        <li class="divider"></li>
                                                        <li>
                                                            <button type="button" @click="deleteReport(report.id)">
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

                    <div class="card-inner pb-3 pt-3">
                        <div class="d-flex justify-content-center">
                            <Pagination :links="reports.links" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ReportStatusBadge from '@/Components/ReportStatusBadge.vue';
import Pagination from '@/Components/Pagination.vue';
import { ConfirmModal } from '@/Helpers';
import { formatDate, formatLabel } from '@/format';

defineOptions({ layout: AppLayout });

defineProps({
    reports: Object,
    templates: Object,
});

const deleteReport = async (reportId) => {
    const { isConfirmed } = await ConfirmModal.fire({
        icon: 'warning',
        title: 'Delete Report',
        text: 'Are you sure you want to delete this report?',
        confirmButtonText: 'Yes, delete',
    });

    if (isConfirmed) {
        router.delete(route('reports.destroy', reportId), { preserveScroll: true });
    }
};
</script>

<style scoped>
.template-card {
    transition: all 0.3s ease;
    text-decoration: none;
}
.template-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(116, 125, 136, 0.15);
}
.nk-iv-wg2-title .icon {
    font-size: 2rem;
    color: #6576ff;
    margin-bottom: 0.5rem;
}
</style>

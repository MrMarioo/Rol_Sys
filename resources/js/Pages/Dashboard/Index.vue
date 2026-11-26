<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import FieldStatusBadge from '@/Components/FieldStatusBadge.vue';
import { formatDataType, formatDate, formatLabel, formatLocation, isNumber } from '@/format';

defineOptions({ layout: AppLayout });

const props = defineProps({
    stats: Object,
    fields: Array,
    crops: Array,
    fieldData: Array,
    analytics: Array,
});

const activeFieldsCount = computed(() => (props.fields ?? []).filter((field) => field.status === 'active').length);

const fieldName = (fieldId) => props.fields?.find((field) => field.id === fieldId)?.name ?? 'Unknown field';

const recommendationSummary = (recommendations) => {
    const count = Array.isArray(recommendations) ? recommendations.length : 0;
    return count === 1 ? '1 recommendation' : `${count} recommendations`;
};
</script>

<template>
    <Head title="Dashboard" />

    <div>
        <div class="container-fluid mb-4">
            <div class="row">
                <div class="col-12">
                    <h2 class="fw-normal">Precision Agriculture Dashboard</h2>
                </div>
            </div>
        </div>

        <div>
            <div class="container-fluid mb-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card h-100 border">
                            <div class="card-body">
                                <h6 class="card-subtitle text-muted mb-2">Fields</h6>
                                <h2 class="card-title mb-3">{{ stats?.totalFields || 0 }}</h2>
                                <div class="small text-muted">Active Fields: {{ activeFieldsCount }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card h-100 border">
                            <div class="card-body">
                                <h6 class="card-subtitle text-muted mb-2">Active Crops</h6>
                                <h2 class="card-title mb-3">{{ stats?.activeCrops || 0 }}</h2>
                                <div class="small text-muted">Types: {{ crops?.length || 0 }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card h-100 border">
                            <div class="card-body">
                                <h6 class="card-subtitle text-muted mb-2">Alerts</h6>
                                <h2 class="card-title text-danger mb-3">{{ stats?.pendingAlerts || 0 }}</h2>
                                <div class="small text-muted">Unread: {{ $page.props.notifications?.unread_count || 0 }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card h-100 border">
                            <div class="card-body">
                                <h6 class="card-subtitle text-muted mb-2">Recent Data</h6>
                                <h2 class="card-title mb-3">{{ stats?.recentDataCollections || 0 }}</h2>
                                <div class="small text-muted">This Week: {{ fieldData?.length || 0 }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="container-fluid mb-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card h-100 border">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex mb-3">
                                    <div class="flex-shrink-0">
                                        <em class="icon ni ni-map fs-1 text-primary me-3"></em>
                                    </div>
                                    <div>
                                        <h5 class="card-title">Field Management</h5>
                                        <p class="card-text">View and manage your fields, analyze soil conditions, and plan data collection.</p>
                                    </div>
                                </div>
                                <div class="border-top mt-auto pt-3">
                                    <Link :href="route('fields.index')" class="d-flex justify-content-between align-items-center text-decoration-none text-primary">
                                        <span>Manage Fields</span>
                                        <em class="icon ni ni-chevron-right"></em>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card h-100 border">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex mb-3">
                                    <div class="flex-shrink-0">
                                        <em class="icon ni ni-bar-chart fs-1 text-primary me-3"></em>
                                    </div>
                                    <div>
                                        <h5 class="card-title">Data Analysis</h5>
                                        <p class="card-text">View collected data, generate reports, and get crop optimization recommendations.</p>
                                    </div>
                                </div>
                                <div class="border-top mt-auto pt-3">
                                    <Link :href="route('analytics.index')" class="d-flex justify-content-between align-items-center text-decoration-none text-primary">
                                        <span>View Analytics</span>
                                        <em class="icon ni ni-chevron-right"></em>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="container-fluid mb-4">
                <div class="card border">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Recent Analytics</h6>
                        <Link :href="route('analytics.index')" class="small text-decoration-none">
                            See All
                            <em class="icon ni ni-chevron-right"></em>
                        </Link>
                    </div>

                    <div class="card-body text-center" v-if="!analytics || analytics.length === 0">
                        <p class="text-muted mb-0">No recent analytics to display</p>
                    </div>

                    <div class="table-responsive" v-else>
                        <table class="table-hover table">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Field</th>
                                    <th scope="col">Analysis Type</th>
                                    <th scope="col">Date</th>
                                    <th scope="col">Recommendations</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(analysis, index) in analytics.slice(0, 5)" :key="analysis.id">
                                    <td>{{ index + 1 }}</td>
                                    <td>{{ fieldName(analysis.field_id) }}</td>
                                    <td>{{ formatLabel(analysis.analysis_type) }}</td>
                                    <td>{{ formatDate(analysis.analysis_date) }}</td>
                                    <td>
                                        <span v-if="analysis.recommendations?.length">
                                            {{ recommendationSummary(analysis.recommendations) }}
                                        </span>
                                        <span v-else class="text-muted">No recommendations</span>
                                    </td>
                                    <td>
                                        <Link :href="route('analytics.show', analysis.id)" class="btn btn-sm btn-outline-primary">View Details</Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="container-fluid mb-4">
                <div class="card border">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Your Fields</h6>
                        <Link :href="route('fields.index')" class="small text-decoration-none">
                            See All
                            <em class="icon ni ni-chevron-right"></em>
                        </Link>
                    </div>

                    <div class="card-body text-center" v-if="!fields || fields.length === 0">
                        <p class="text-muted mb-0">No fields to display</p>
                    </div>

                    <div class="table-responsive" v-else>
                        <table class="table-hover table">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Location</th>
                                    <th scope="col">Size (ha)</th>
                                    <th scope="col">Current Crop</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(field, index) in fields.slice(0, 5)" :key="field.id">
                                    <td>{{ index + 1 }}</td>
                                    <td>{{ field.name }}</td>
                                    <td>{{ field.location }}</td>
                                    <td>{{ field.size }}</td>
                                    <td>
                                        <span v-if="field.current_crop">{{ field.current_crop.name }}</span>
                                        <span v-else class="text-muted">No crop</span>
                                    </td>
                                    <td>
                                        <FieldStatusBadge :status="field.status" />
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button
                                                class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                type="button"
                                                :id="`field-actions-${field.id}`"
                                                data-bs-toggle="dropdown"
                                                aria-expanded="false"
                                            >
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu" :aria-labelledby="`field-actions-${field.id}`">
                                                <li>
                                                    <Link class="dropdown-item" :href="route('fields.show', field.id)">
                                                        <em class="icon ni ni-eye me-2"></em>
                                                        View Details
                                                    </Link>
                                                </li>
                                                <li>
                                                    <Link class="dropdown-item" :href="route('fields.data', field.id)">
                                                        <em class="icon ni ni-download-cloud me-2"></em>
                                                        Get Data
                                                    </Link>
                                                </li>
                                                <li>
                                                    <Link class="dropdown-item" :href="route('fields.edit', field.id)">
                                                        <em class="icon ni ni-edit me-2"></em>
                                                        Edit
                                                    </Link>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="container-fluid mb-4">
                <div class="card border">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Recent Data Collections</h6>
                        <Link :href="route('field-data.index')" class="small text-decoration-none">
                            See All
                            <em class="icon ni ni-chevron-right"></em>
                        </Link>
                    </div>

                    <div class="card-body text-center" v-if="!fieldData || fieldData.length === 0">
                        <p class="text-muted mb-0">No recent data collections to display</p>
                    </div>

                    <div class="table-responsive" v-else>
                        <table class="table-hover table">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Field</th>
                                    <th scope="col">Data Type</th>
                                    <th scope="col">Collection Date</th>
                                    <th scope="col">Location</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(data, index) in fieldData.slice(0, 5)" :key="data.id">
                                    <td>{{ index + 1 }}</td>
                                    <td>{{ fieldName(data.field_id) }}</td>
                                    <td>{{ formatDataType(data.data_type) }}</td>
                                    <td>{{ formatDate(data.collection_date) }}</td>
                                    <td>
                                        <span v-if="isNumber(data.latitude) && isNumber(data.longitude)">
                                            {{ formatLocation(data.latitude, data.longitude) }}
                                        </span>
                                        <span v-else class="text-muted">Not specified</span>
                                    </td>
                                    <td>
                                        <Link :href="route('field-data.show', data.id)" class="btn btn-sm btn-outline-primary">View Data</Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="container-fluid">
                <div class="card border">
                    <div class="card-body py-4 text-center">
                        <div class="mb-3">
                            <em class="icon ni ni-headphone fs-1 text-primary"></em>
                        </div>
                        <h5>Need assistance with your precision agriculture system?</h5>
                        <p class="text-muted">
                            Our support team is ready to help you with field management, data analysis, and crop optimization to maximize your farming
                            efficiency.
                        </p>
                        <a href="mailto:support@precisionag.com" class="btn btn-outline-primary">Contact Support</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

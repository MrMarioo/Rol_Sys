<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    stats: Object,
    fields: Array,
    crops: Array,
    fieldData: Array,
    analytics: Array,
    notifications: Array,
});

defineOptions({
    layout: AppLayout,
});

const loading = ref(false);
</script>

<template>
    <Head title="Dashboard" />

    <div>
        <!-- Header Section -->
        <div class="container-fluid mb-4">
            <div class="row">
                <div class="col-12">
                    <h2 class="fw-normal">Precision Agriculture Dashboard</h2>
                </div>
            </div>
        </div>

        <!-- Loading Indicator -->
        <div v-if="loading" class="container-fluid mb-4">
            <div class="card">
                <div class="card-body d-flex justify-content-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>

        <div v-else>
            <!-- Stats Cards Section -->
            <div class="container-fluid mb-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card h-100 border">
                            <div class="card-body">
                                <h6 class="card-subtitle text-muted mb-2">Fields</h6>
                                <h2 class="card-title mb-3">{{ stats?.totalFields || 0 }}</h2>
                                <div class="small text-muted">Active Fields: {{ fields?.filter((f) => f.status === 'active').length || 0 }}</div>
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
                                <div class="small text-muted">Unread: {{ notifications?.length || 0 }}</div>
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

            <!-- Quick Access Cards -->
            <div class="container-fluid mb-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card h-100 border">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex mb-3">
                                    <div class="flex-shrink-0">
                                        <i class="bi bi-map fs-1 text-primary me-3"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title">Field Management</h5>
                                        <p class="card-text">View and manage your fields, analyze soil conditions, and plan data collection.</p>
                                    </div>
                                </div>
                                <div class="border-top mt-auto pt-3">
                                    <a href="/fields" class="d-flex justify-content-between align-items-center text-decoration-none text-primary">
                                        <span>Manage Fields</span>
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card h-100 border">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex mb-3">
                                    <div class="flex-shrink-0">
                                        <i class="bi bi-gear fs-1 text-primary me-3"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title">Data Analysis</h5>
                                        <p class="card-text">View collected data, generate reports, and get crop optimization recommendations.</p>
                                    </div>
                                </div>
                                <div class="border-top mt-auto pt-3">
                                    <a href="/analytics" class="d-flex justify-content-between align-items-center text-decoration-none text-primary">
                                        <span>View Analytics</span>
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Analytics Section -->
            <div class="container-fluid mb-4">
                <div class="card border">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Recent Analytics</h6>
                        <a href="/analytics" class="small text-decoration-none">
                            See All
                            <i class="bi bi-chevron-right"></i>
                        </a>
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
                                    <td>{{ fields.find((f) => f.id === analysis.field_id)?.name || 'Unknown field' }}</td>
                                    <td>{{ analysis.analysis_type }}</td>
                                    <td>{{ analysis.analysis_date }}</td>
                                    <td>
                                        <span v-if="analysis.recommendations" class="text-truncate d-inline-block" style="max-width: 200px">
                                            {{ analysis.recommendations }}
                                        </span>
                                        <span v-else class="text-muted">No recommendations</span>
                                    </td>
                                    <td>
                                        <a :href="`/analytics/${analysis.id}`" class="btn btn-sm btn-outline-primary">View Details</a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Fields Section -->
            <div class="container-fluid mb-4">
                <div class="card border">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Your Fields</h6>
                        <a href="/fields" class="small text-decoration-none">
                            See All
                            <i class="bi bi-chevron-right"></i>
                        </a>
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
                                        <span v-if="field.status === 'active'" class="badge bg-success">Active</span>
                                        <span v-else-if="field.status === 'inactive'" class="badge bg-secondary">Inactive</span>
                                        <span v-else-if="field.status === 'maintenance'" class="badge bg-warning text-dark">Maintenance</span>
                                        <span v-else class="badge bg-secondary">{{ field.status }}</span>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button
                                                class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                type="button"
                                                id="dropdownField"
                                                data-bs-toggle="dropdown"
                                                aria-expanded="false"
                                            >
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="dropdownField">
                                                <li>
                                                    <a class="dropdown-item" :href="`/fields/${field.id}`">
                                                        <i class="bi bi-eye me-2"></i>
                                                        View Details
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" :href="`/fields/${field.id}/data`">
                                                        <i class="bi bi-cloud-download me-2"></i>
                                                        Get Data
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" :href="`/fields/${field.id}/edit`">
                                                        <i class="bi bi-pencil me-2"></i>
                                                        Edit
                                                    </a>
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

            <!-- Recent Field Data -->
            <div class="container-fluid mb-4">
                <div class="card border">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Recent Data Collections</h6>
                        <a href="/field-data" class="small text-decoration-none">
                            See All
                            <i class="bi bi-chevron-right"></i>
                        </a>
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
                                    <td>{{ fields.find((f) => f.id === data.field_id)?.name || 'Unknown field' }}</td>
                                    <td>{{ data.data_type }}</td>
                                    <td>{{ data.collection_date }}</td>
                                    <td>
                                        <span v-if="data.latitude && data.longitude">
                                            {{ parseFloat(data.latitude).toFixed(5) }}, {{ parseFloat(data.longitude).toFixed(5) }}
                                        </span>
                                        <span v-else class="text-muted">Not specified</span>
                                    </td>
                                    <td>
                                        <a :href="`/field-data/${data.id}`" class="btn btn-sm btn-outline-primary">View Data</a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Support Section -->
            <div class="container-fluid">
                <div class="card border">
                    <div class="card-body py-4 text-center">
                        <div class="mb-3">
                            <i class="bi bi-headset fs-1 text-primary"></i>
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

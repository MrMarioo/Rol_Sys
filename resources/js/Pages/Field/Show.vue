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
                    </div>
                </div>
            </div>
        </div>

        <div class="nk-block">
            <div class="row g-4">
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

                <div class="col-12 mt-4">
                    <div class="d-flex justify-content-end gap-2">
                        <Link :href="route('fields.index')" class="btn btn-outline-secondary p-3">Back to List</Link>
                        <Link :href="route('fields.edit', field.id)" class="btn btn-primary p-3">
                            <em class="icon ni ni-edit me-1"></em>
                            <span>Edit Field</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import FieldMap from '@/Components/FieldMap.vue';
import AppLayout from '@/Layouts/AppLayout.vue';

export default {
    layout: AppLayout,
    components: {
        FieldMap,
        Link,
    },
    props: {
        field: {
            type: Object,
            required: true,
        },
    },
    setup(props) {
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
            formatStatus,
            getStatusBadgeClass,
            formatDate,
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

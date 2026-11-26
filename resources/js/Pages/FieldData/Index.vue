<template>
    <Head title="Field Data" />
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Field Data</h3>
                <div class="nk-block-des text-soft">
                    <p>Browse and manage data from monitored fields.</p>
                </div>
            </div>
            <div class="nk-block-head-content">
                <div class="toggle-wrap nk-block-tools-toggle">
                    <Link :href="route('field-data.create')" class="btn btn-primary">
                        <em class="icon ni ni-plus"></em>
                        <span>Add Data</span>
                    </Link>
                </div>
            </div>
        </div>
    </div>
    <div class="nk-block">
        <div class="card card-bordered">
            <div class="card-inner">
                <div class="row g-3">
                    <div class="col-md-12 mb-2">
                        <div class="form-group">
                            <div class="form-control-wrap">
                                <div class="form-icon form-icon-left">
                                    <em class="icon ni ni-search"></em>
                                </div>
                                <input
                                    type="text"
                                    class="form-control form-control-lg"
                                    v-model="filters.search"
                                    placeholder="Search data..."
                                    aria-label="Search data"
                                    @keyup.enter="applyFilters"
                                />
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label" for="field-filter">Field</label>
                            <select id="field-filter" v-model="filters.field_id" @change="applyFilters" class="form-select">
                                <option value="">All fields</option>
                                <option v-for="field in fields" :key="field.id" :value="field.id">
                                    {{ field.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label" for="data-type-filter">Data Type</label>
                            <select id="data-type-filter" v-model="filters.data_type" @change="applyFilters" class="form-select">
                                <option value="">All types</option>
                                <option v-for="type in dataTypes" :key="type" :value="type">
                                    {{ formatDataType(type) }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label" for="date-from">Date From</label>
                            <input type="date" v-model="filters.date_from" @change="applyFilters" class="form-control" id="date-from" />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label" for="date-to">Date To</label>
                            <input type="date" v-model="filters.date_to" @change="applyFilters" class="form-control" id="date-to" />
                        </div>
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-12">
                        <button type="button" class="btn btn-primary" @click="applyFilters">Filter</button>
                        <button type="button" class="btn btn-outline-primary ms-2" @click="resetFilters">Reset</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="nk-block">
        <div class="card card-bordered card-stretch">
            <div class="card-inner-group">
                <div class="card-inner p-0">
                    <div class="nk-tb-list nk-tb-ulist">
                        <div class="nk-tb-item nk-tb-head">
                            <div class="nk-tb-col"><span class="sub-text">Field</span></div>
                            <div class="nk-tb-col"><span class="sub-text">Collection Date</span></div>
                            <div class="nk-tb-col"><span class="sub-text">Data Type</span></div>
                            <div class="nk-tb-col"><span class="sub-text">Location</span></div>
                            <div class="nk-tb-col nk-tb-col-tools text-end">Actions</div>
                        </div>

                        <div v-if="fieldData.data.length === 0" class="nk-tb-item">
                            <div class="nk-tb-col">
                                <div class="alert alert-info">No data matching the filter criteria.</div>
                            </div>
                        </div>

                        <div v-for="data in fieldData.data" :key="data.id" class="nk-tb-item">
                            <div class="nk-tb-col">
                                <span class="tb-lead">{{ data.field.name }}</span>
                            </div>
                            <div class="nk-tb-col">
                                <span>{{ formatDate(data.collection_date) }}</span>
                            </div>
                            <div class="nk-tb-col">
                                <span class="badge bg-primary">{{ formatDataType(data.data_type) }}</span>
                            </div>
                            <div class="nk-tb-col">
                                <span>{{ formatLocation(data.latitude, data.longitude) }}</span>
                            </div>
                            <div class="nk-tb-col nk-tb-col-tools">
                                <ul class="nk-tb-actions gx-1">
                                    <li>
                                        <div class="drodown">
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
                                                        <Link :href="route('field-data.show', data.id)">
                                                            <em class="icon ni ni-eye"></em>
                                                            <span>View</span>
                                                        </Link>
                                                    </li>
                                                    <li>
                                                        <Link :href="route('field-data.edit', data.id)">
                                                            <em class="icon ni ni-edit"></em>
                                                            <span>Edit</span>
                                                        </Link>
                                                    </li>
                                                    <li class="divider"></li>
                                                    <li>
                                                        <button type="button" @click="confirmDelete(data)">
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

                <div class="card-inner">
                    <div class="d-flex justify-content-center">
                        <Pagination :links="fieldData.links" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { ConfirmModal } from '@/Helpers';
import { formatDataType, formatDate, formatLocation } from '@/format';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    fieldData: Object,
    fields: Array,
    dataTypes: Array,
    filters: Object,
});

const filters = reactive({
    search: props.filters.search || '',
    field_id: props.filters.field_id ? Number(props.filters.field_id) : '',
    data_type: props.filters.data_type || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

const applyFilters = () => {
    const activeFilters = Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== '' && value !== null));

    router.get(
        route('field-data.index'),
        { filter: activeFilters },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const resetFilters = () => {
    Object.keys(filters).forEach((key) => {
        filters[key] = '';
    });
    applyFilters();
};

const confirmDelete = async (data) => {
    const { isConfirmed } = await ConfirmModal.fire({
        icon: 'warning',
        title: 'Delete field data?',
        text: 'Are you sure you want to delete this field data? This action cannot be undone.',
        confirmButtonText: 'Yes, delete',
    });

    if (isConfirmed) {
        router.delete(route('field-data.destroy', data.id));
    }
};
</script>

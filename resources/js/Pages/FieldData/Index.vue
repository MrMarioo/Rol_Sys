<template>
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
    <!-- Filters -->
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
                                    @keyup.enter="applyFilters"
                                />
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label" for="field-filter">Field</label>
                            <select v-model="filters.field_id" @change="applyFilters" class="form-select">
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
                            <select v-model="filters.data_type" @change="applyFilters" class="form-select">
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
                        <button class="btn btn-primary" @click="applyFilters">Filter</button>
                        <button class="btn btn-outline-primary ml-2" @click="resetFilters">Reset</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data List -->
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
                            <div class="nk-tb-col" colspan="5">
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
                                <span v-if="data.latitude && data.longitude">
                                    {{ formatLocation(data.latitude, data.longitude) }}
                                </span>
                                <span v-else>-</span>
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
                                                        <a href="#" @click.prevent="confirmDelete(data)">
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
                <div class="card-inner">
                    <div class="d-flex justify-content-center">
                        <Pagination :links="fieldData.links" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteConfirmModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">Are you sure you want to delete this field data? This action cannot be undone.</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" @click="deleteFieldData">Delete</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Modal } from 'bootstrap';
import SearchForm from '@/Pages/Field/SearchForm.vue';
defineOptions({
    layout: AppLayout,
});
const props = defineProps({
    fieldData: Object,
    fields: Array,
    dataTypes: Array,
    filters: Object,
});

const deleteModal = ref(null);
const dataToDelete = ref(null);
const filters = reactive({
    search: props.filters.search || '',
    field_id: props.filters.field_id || '',
    data_type: props.filters.data_type || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

// Initialize modal
onMounted(() => {
    deleteModal.value = new Modal(document.getElementById('deleteConfirmModal'));
});

// Methods
const applyFilters = () => {
    router.get(route('field-data.index'), filters, {
        preserveState: true,
        replace: true,
    });
};

const resetFilters = () => {
    filters.search = '';
    filters.field_id = '';
    filters.data_type = '';
    filters.date_from = '';
    filters.date_to = '';
    applyFilters();
};

const confirmDelete = (data) => {
    dataToDelete.value = data;
    deleteModal.value.show();
};

const deleteFieldData = () => {
    router.delete(route('field-data.destroy', dataToDelete.value.id), {
        onSuccess: () => {
            deleteModal.value.hide();
        },
    });
};

const formatDate = (dateString) => {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('en-US', options);
};

const formatLocation = (lat, lng) => {
    return `${parseFloat(lat).toFixed(5)}, ${parseFloat(lng).toFixed(5)}`;
};

const formatDataType = (type) => {
    const typeMap = {
        soil_moisture: 'Soil Moisture',
        ndvi: 'NDVI',
        temperature: 'Temperature',
        pest_detection: 'Pest Detection',
        weed_detection: 'Weed Detection',
        crop_health: 'Crop Health',
        other: 'Other',
    };

    return (
        typeMap[type] ||
        type
            .replace(/_/g, ' ')
            .split(' ')
            .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
            .join(' ')
    );
};
</script>

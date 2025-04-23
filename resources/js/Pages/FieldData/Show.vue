<template>
    <AppLayout :title="'Field Data Details'">
        <div class="nk-block-head nk-block-head-sm">
            <div class="nk-block-between g-3">
                <div class="nk-block-head-content">
                    <h3 class="nk-block-title page-title">Field Data Details</h3>
                    <div class="nk-block-des text-soft">
                        <p>Detailed view of collected field data.</p>
                    </div>
                </div>
                <div class="nk-block-head-content">
                    <div class="toggle-wrap nk-block-tools-toggle">
                        <div class="toggle-expand-content">
                            <ul class="nk-block-tools g-3">
                                <li>
                                    <Link :href="route('field-data.index')" class="btn btn-outline-light">
                                        <em class="icon ni ni-arrow-left"></em>
                                        <span>Back</span>
                                    </Link>
                                </li>
                                <li>
                                    <Link :href="route('field-data.edit', fieldData.id)" class="btn btn-primary">
                                        <em class="icon ni ni-edit"></em>
                                        <span>Edit</span>
                                    </Link>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="nk-block">
            <div class="row g-gs">
                <!-- Basic Information -->
                <div class="col-lg-4">
                    <div class="card card-bordered h-100">
                        <div class="card-inner">
                            <div class="card-title-group align-start mb-3">
                                <div class="card-title">
                                    <h6 class="title">Basic Information</h6>
                                </div>
                            </div>
                            <div class="card-content">
                                <div class="nk-info">
                                    <div class="nk-info-item">
                                        <div class="nk-info-icon">
                                            <em class="icon ni ni-map"></em>
                                        </div>
                                        <div class="nk-info-content">
                                            <div class="title">Field</div>
                                            <div class="lead-text">{{ fieldData.field.name }}</div>
                                        </div>
                                    </div>
                                    <div class="nk-info-item">
                                        <div class="nk-info-icon">
                                            <em class="icon ni ni-calender-date"></em>
                                        </div>
                                        <div class="nk-info-content">
                                            <div class="title">Collection Date</div>
                                            <div class="lead-text">{{ formatDate(fieldData.collection_date) }}</div>
                                        </div>
                                    </div>
                                    <div class="nk-info-item">
                                        <div class="nk-info-icon">
                                            <em class="icon ni ni-list"></em>
                                        </div>
                                        <div class="nk-info-content">
                                            <div class="title">Data Type</div>
                                            <div class="lead-text">
                                                <span class="badge bg-primary">{{ formatDataType(fieldData.data_type) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="nk-info-item" v-if="fieldData.latitude && fieldData.longitude">
                                        <div class="nk-info-icon">
                                            <em class="icon ni ni-map-pin"></em>
                                        </div>
                                        <div class="nk-info-content">
                                            <div class="title">Location</div>
                                            <div class="lead-text">{{ formatLocation(fieldData.latitude, fieldData.longitude) }}</div>
                                        </div>
                                    </div>
                                    <div class="nk-info-item">
                                        <div class="nk-info-icon">
                                            <em class="icon ni ni-clock"></em>
                                        </div>
                                        <div class="nk-info-content">
                                            <div class="title">Added</div>
                                            <div class="lead-text">{{ formatDateTime(fieldData.created_at) }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Visualization -->
                <div class="col-lg-8">
                    <div class="card card-bordered h-100">
                        <div class="card-inner">
                            <div class="card-title-group align-start mb-3">
                                <div class="card-title">
                                    <h6 class="title">Measurement Data</h6>
                                </div>
                            </div>
                            <div class="card-content">
                                <DataVisualization :data="fieldData.data" :type="fieldData.data_type" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Metadata -->
                <div class="col-12" v-if="fieldData.metadata">
                    <div class="card card-bordered">
                        <div class="card-inner">
                            <div class="card-title-group align-start mb-3">
                                <div class="card-title">
                                    <h6 class="title">Metadata</h6>
                                </div>
                            </div>
                            <div class="card-content">
                                <pre class="code-block">{{ JSON.stringify(fieldData.metadata, null, 2) }}</pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataVisualization from '@/Components/DataVisualization.vue';

// Single prop definition from controller
const props = defineProps({
    fieldData: Object
});

// Methods
const formatDate = (dateString) => {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('en-US', options);
};

const formatDateTime = (dateTimeString) => {
    const options = {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    return new Date(dateTimeString).toLocaleDateString('en-US', options);
};

const formatLocation = (lat, lng) => {
    return `${parseFloat(lat).toFixed(5)}, ${parseFloat(lng).toFixed(5)}`;
};

const formatDataType = (type) => {
    const typeMap = {
        'soil_moisture': 'Soil Moisture',
        'ndvi': 'NDVI',
        'temperature': 'Temperature',
        'pest_detection': 'Pest Detection',
        'weed_detection': 'Weed Detection',
        'crop_health': 'Crop Health',
        'other': 'Other'
    };

    return typeMap[type] || type.replace(/_/g, ' ')
        .split(' ')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
};
</script>

<style scoped>
.code-block {
    background-color: #f5f6fa;
    border-radius: 4px;
    padding: 1rem;
    overflow-x: auto;
    max-height: 400px;
    overflow-y: auto;
}
</style>

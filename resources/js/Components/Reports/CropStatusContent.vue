<template>
    <div class="crop-status-report">
        <!-- Summary Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card border-primary">
                    <div class="card-inner text-center">
                        <div class="nk-iv-wg2-title">
                            <em class="icon ni ni-growth text-primary"></em>
                        </div>
                        <h4 class="title">{{ content.summary.total_crops }}</h4>
                        <p class="sub-text">Total Crops</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-success">
                    <div class="card-inner text-center">
                        <div class="nk-iv-wg2-title">
                            <em class="icon ni ni-check-circle text-success"></em>
                        </div>
                        <h4 class="title">{{ content.summary.healthy_crops }}</h4>
                        <p class="sub-text">Healthy Crops</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-warning">
                    <div class="card-inner text-center">
                        <div class="nk-iv-wg2-title">
                            <em class="icon ni ni-alert-triangle text-warning"></em>
                        </div>
                        <h4 class="title">{{ content.summary.problem_crops }}</h4>
                        <p class="sub-text">Problematic Crops</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Crops by Growth Stage -->
        <div class="card mb-4">
            <div class="card-inner">
                <h5 class="card-title">Crops by Growth Stage</h5>
                <div class="row g-3">
                    <div v-for="(crops, stage) in cropsByGrowthStage" :key="stage" class="col-md-4 col-lg-2">
                        <div class="card border-light text-center">
                            <div class="card-inner">
                                <em class="icon ni" :class="getGrowthStageIcon(stage)" style="font-size: 1.5rem; margin-bottom: 0.5rem;"></em>
                                <h6>{{ formatGrowthStage(stage) }}</h6>
                                <p class="text-soft">{{ crops.length }} {{ crops.length === 1 ? 'crop' : 'crops' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Crop Status -->
        <div class="card">
            <div class="card-inner">
                <h5 class="card-title">Detailed Crop Status</h5>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>Field</th>
                            <th>Crop</th>
                            <th>Planting Date</th>
                            <th>Growth Stage</th>
                            <th>Days from Planting</th>
                            <th>Days to Harvest</th>
                            <th>Health Status</th>
                            <th>Expected Yield</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="crop in content.crop_status" :key="crop.field_id + crop.crop_name">
                            <td class="fw-bold">{{ crop.field_name }}</td>
                            <td>{{ crop.crop_name }}</td>
                            <td>{{ formatDate(crop.planting_date) }}</td>
                            <td>
                                    <span class="badge badge-outline-info">
                                        <em class="icon ni" :class="getGrowthStageIcon(crop.growth_stage)"></em>
                                        {{ formatGrowthStage(crop.growth_stage) }}
                                    </span>
                            </td>
                            <td>{{ crop.days_from_planting }}</td>
                            <td>
                                    <span :class="getDaysToHarvestClass(crop.days_to_harvest)">
                                        {{ crop.days_to_harvest > 0 ? crop.days_to_harvest : 'Overdue' }}
                                    </span>
                            </td>
                            <td>
                                    <span :class="getHealthStatusBadgeClass(crop.health_status)">
                                        <em class="icon ni" :class="getHealthStatusIcon(crop.health_status)"></em>
                                        {{ formatHealthStatus(crop.health_status) }}
                                    </span>
                            </td>
                            <td>{{ crop.expected_yield ? crop.expected_yield + ' t/ha' : 'N/A' }}</td>
                            <td>
                                    <span :class="getCropStatusBadgeClass(crop.status)">
                                        {{ formatCropStatus(crop.status) }}
                                    </span>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Upcoming Harvests -->
        <div class="card mt-4" v-if="upcomingHarvests.length > 0">
            <div class="card-inner">
                <h5 class="card-title">Upcoming Harvests (Next 30 Days)</h5>
                <div class="table-responsive">
                    <table class="table table-borderless">
                        <thead>
                        <tr>
                            <th>Field</th>
                            <th>Crop</th>
                            <th>Expected Harvest Date</th>
                            <th>Days Remaining</th>
                            <th>Expected Yield</th>
                            <th>Health Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="crop in upcomingHarvests" :key="crop.field_id + crop.crop_name">
                            <td class="fw-bold">{{ crop.field_name }}</td>
                            <td>{{ crop.crop_name }}</td>
                            <td>{{ formatDate(crop.expected_harvest) }}</td>
                            <td>
                                    <span :class="getDaysToHarvestClass(crop.days_to_harvest)">
                                        {{ crop.days_to_harvest }} days
                                    </span>
                            </td>
                            <td>{{ crop.expected_yield ? crop.expected_yield + ' t/ha' : 'N/A' }}</td>
                            <td>
                                    <span :class="getHealthStatusBadgeClass(crop.health_status)">
                                        {{ formatHealthStatus(crop.health_status) }}
                                    </span>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recommendations -->
        <div class="card mt-4">
            <div class="card-inner">
                <h5 class="card-title">Crop Management Recommendations</h5>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="alert alert-success">
                            <h6 class="mb-2">
                                <em class="icon ni ni-check-circle"></em>
                                Immediate Actions
                            </h6>
                            <ul class="mb-0">
                                <li v-if="criticalCrops.length > 0">
                                    <strong>{{ criticalCrops.length }} crop(s)</strong> require immediate attention due to poor health
                                </li>
                                <li v-if="nearHarvestCrops.length > 0">
                                    <strong>{{ nearHarvestCrops.length }} crop(s)</strong> ready for harvest within 7 days
                                </li>
                                <li v-if="overdueCrops.length > 0">
                                    <strong>{{ overdueCrops.length }} crop(s)</strong> are overdue for harvest
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-info">
                            <h6 class="mb-2">
                                <em class="icon ni ni-info-circle"></em>
                                Planning Suggestions
                            </h6>
                            <ul class="mb-0">
                                <li>Schedule soil preparation for fields with completed harvests</li>
                                <li>Plan fertilization for crops in vegetative stage</li>
                                <li>Monitor weather conditions for optimal harvest timing</li>
                                <li>Prepare storage facilities for upcoming harvests</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { computed } from 'vue';

export default {
    props: {
        content: {
            type: Object,
            required: true
        }
    },
    setup(props) {
        const cropsByGrowthStage = computed(() => {
            const stages = {};
            props.content.crop_status.forEach(crop => {
                if (!stages[crop.growth_stage]) {
                    stages[crop.growth_stage] = [];
                }
                stages[crop.growth_stage].push(crop);
            });
            return stages;
        });

        const upcomingHarvests = computed(() => {
            return props.content.crop_status.filter(crop =>
                crop.days_to_harvest > 0 && crop.days_to_harvest <= 30
            ).sort((a, b) => a.days_to_harvest - b.days_to_harvest);
        });

        const criticalCrops = computed(() => {
            return props.content.crop_status.filter(crop => crop.health_status === 'poor');
        });

        const nearHarvestCrops = computed(() => {
            return props.content.crop_status.filter(crop =>
                crop.days_to_harvest > 0 && crop.days_to_harvest <= 7
            );
        });

        const overdueCrops = computed(() => {
            return props.content.crop_status.filter(crop => crop.days_to_harvest <= 0);
        });

        const formatDate = (dateString) => {
            return new Date(dateString).toLocaleDateString();
        };

        const formatGrowthStage = (stage) => {
            return stage.charAt(0).toUpperCase() + stage.slice(1);
        };

        const formatHealthStatus = (status) => {
            return status.charAt(0).toUpperCase() + status.slice(1);
        };

        const formatCropStatus = (status) => {
            return status.charAt(0).toUpperCase() + status.slice(1);
        };

        const getGrowthStageIcon = (stage) => {
            const icons = {
                'germination': 'ni-seed',
                'seedling': 'ni-growth',
                'vegetative': 'ni-tree',
                'flowering': 'ni-star',
                'maturation': 'ni-check-circle'
            };
            return icons[stage] || 'ni-help';
        };

        const getHealthStatusIcon = (status) => {
            const icons = {
                'good': 'ni-check-circle',
                'fair': 'ni-alert-triangle',
                'poor': 'ni-cross-circle',
                'unknown': 'ni-help'
            };
            return icons[status] || 'ni-help';
        };

        const getHealthStatusBadgeClass = (status) => {
            const classes = {
                'good': 'badge badge-success',
                'fair': 'badge badge-warning',
                'poor': 'badge badge-danger',
                'unknown': 'badge badge-secondary'
            };
            return classes[status] || 'badge badge-secondary';
        };

        const getCropStatusBadgeClass = (status) => {
            const classes = {
                'active': 'badge badge-success',
                'harvested': 'badge badge-outline-success',
                'failed': 'badge badge-danger'
            };
            return classes[status] || 'badge badge-outline-primary';
        };

        const getDaysToHarvestClass = (days) => {
            if (days <= 0) return 'text-danger fw-bold';
            if (days <= 7) return 'text-warning fw-bold';
            if (days <= 30) return 'text-info';
            return 'text-muted';
        };

        return {
            cropsByGrowthStage,
            upcomingHarvests,
            criticalCrops,
            nearHarvestCrops,
            overdueCrops,
            formatDate,
            formatGrowthStage,
            formatHealthStatus,
            formatCropStatus,
            getGrowthStageIcon,
            getHealthStatusIcon,
            getHealthStatusBadgeClass,
            getCropStatusBadgeClass,
            getDaysToHarvestClass
        };
    }
};
</script>

<style scoped>
.nk-iv-wg2-title .icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.badge {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    border-radius: 0.25rem;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.card.border-light {
    border-color: #e5e9f2 !important;
}

.card.border-primary {
    border-color: #6576ff !important;
}

.card.border-success {
    border-color: #1ee0ac !important;
}

.card.border-warning {
    border-color: #f4bd0e !important;
}

.table th {
    font-weight: 600;
    color: #364a63;
    border-bottom: 2px solid #e5e9f2;
}

.table td {
    vertical-align: middle;
}

.alert ul {
    padding-left: 1.25rem;
    margin-bottom: 0;
}

.alert li {
    margin-bottom: 0.25rem;
}

.alert h6 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
</style>

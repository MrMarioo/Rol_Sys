<template>
    <div class="crop-status-report">
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
                            <em class="icon ni ni-alert text-warning"></em>
                        </div>
                        <h4 class="title">{{ content.summary.problem_crops }}</h4>
                        <p class="sub-text">Problematic Crops</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-inner">
                <h5 class="card-title">Crops by Growth Stage</h5>
                <div class="row g-3">
                    <div v-for="(crops, stage) in cropsByGrowthStage" :key="stage" class="col-md-4 col-lg-2">
                        <div class="card border-light text-center">
                            <div class="card-inner">
                                <em class="icon ni stage-icon" :class="growthStageIcon(stage)"></em>
                                <h6>{{ formatLabel(stage) }}</h6>
                                <p class="text-soft">{{ crops.length }} {{ crops.length === 1 ? 'crop' : 'crops' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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
                        <tr v-for="crop in content.crop_status" :key="`${crop.field_id}-${crop.crop_name}`">
                            <td class="fw-bold">{{ crop.field_name }}</td>
                            <td>{{ crop.crop_name }}</td>
                            <td>{{ formatDate(crop.planting_date) }}</td>
                            <td>
                                <span class="badge badge-dim bg-outline-info">
                                    <em class="icon ni" :class="growthStageIcon(crop.growth_stage)"></em>
                                    {{ formatLabel(crop.growth_stage) }}
                                </span>
                            </td>
                            <td>{{ crop.days_from_planting }}</td>
                            <td>
                                <span :class="daysToHarvestClass(crop.days_to_harvest)">
                                    {{ crop.days_to_harvest > 0 ? crop.days_to_harvest : 'Overdue' }}
                                </span>
                            </td>
                            <td>
                                <ReportHealthBadge :status="crop.health_status" />
                            </td>
                            <td>{{ formatYield(crop.expected_yield) }}</td>
                            <td>
                                <span :class="cropStatusBadgeClass(crop.status)">
                                    {{ formatLabel(crop.status) }}
                                </span>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

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
                        <tr v-for="crop in upcomingHarvests" :key="`${crop.field_id}-${crop.crop_name}`">
                            <td class="fw-bold">{{ crop.field_name }}</td>
                            <td>{{ crop.crop_name }}</td>
                            <td>{{ formatDate(crop.expected_harvest) }}</td>
                            <td>
                                <span :class="daysToHarvestClass(crop.days_to_harvest)">
                                    {{ formatDays(crop.days_to_harvest) }}
                                </span>
                            </td>
                            <td>{{ formatYield(crop.expected_yield) }}</td>
                            <td>
                                <ReportHealthBadge :status="crop.health_status" />
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

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
                                <em class="icon ni ni-info"></em>
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

<script setup>
import { computed } from 'vue';
import ReportHealthBadge from '@/Components/Reports/ReportHealthBadge.vue';
import { formatDate, formatDays, formatLabel, isNumber } from '@/format';

const props = defineProps({
    content: {
        type: Object,
        required: true,
    },
});

const GROWTH_STAGE_ICONS = {
    germination: 'ni-sun',
    seedling: 'ni-growth',
    vegetative: 'ni-growth-fill',
    flowering: 'ni-star',
    maturation: 'ni-check-circle',
};

const CROP_STATUS_BADGES = {
    active: 'badge bg-success',
    harvested: 'badge badge-dim bg-outline-success',
    failed: 'badge bg-danger',
};

const crops = computed(() => props.content.crop_status ?? []);

const cropsByGrowthStage = computed(() =>
    crops.value.reduce((stages, crop) => {
        (stages[crop.growth_stage] ??= []).push(crop);
        return stages;
    }, {}),
);

const upcomingHarvests = computed(() =>
    crops.value
        .filter((crop) => crop.days_to_harvest > 0 && crop.days_to_harvest <= 30)
        .sort((a, b) => a.days_to_harvest - b.days_to_harvest),
);

const criticalCrops = computed(() => crops.value.filter((crop) => crop.health_status === 'poor'));

const nearHarvestCrops = computed(() => crops.value.filter((crop) => crop.days_to_harvest > 0 && crop.days_to_harvest <= 7));

const overdueCrops = computed(() => crops.value.filter((crop) => crop.days_to_harvest <= 0));

const formatYield = (value) => (isNumber(value) ? `${value} t/ha` : '—');

const growthStageIcon = (stage) => GROWTH_STAGE_ICONS[stage] ?? 'ni-help';

const cropStatusBadgeClass = (status) => CROP_STATUS_BADGES[status] ?? 'badge badge-dim bg-outline-primary';

const daysToHarvestClass = (days) => {
    if (days <= 0) return 'text-danger fw-bold';
    if (days <= 7) return 'text-warning fw-bold';
    if (days <= 30) return 'text-info';
    return 'text-muted';
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

.stage-icon {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
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

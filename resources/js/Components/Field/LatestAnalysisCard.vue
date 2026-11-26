<template>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="card-title mb-0">
                Latest analysis
                <span class="badge badge-dim bg-outline-primary ms-2">{{ analysis.type_label }}</span>
            </h5>
            <span class="text-soft">{{ analysis.date }}</span>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="text-soft small">Vegetation</div>
                    <span v-if="analysis.vegetation?.status" class="badge" :class="statusClass(analysis.vegetation.status)">
                        {{ analysis.vegetation.status }}
                    </span>
                    <span v-else>—</span>
                    <div class="fw-bold mt-1">{{ analysis.vegetation ? formatNdvi(analysis.vegetation.ndvi) : '—' }}</div>
                    <div class="text-soft small">NDVI</div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="text-soft small">Moisture</div>
                    <span v-if="analysis.moisture?.status" class="badge" :class="statusClass(analysis.moisture.status)">
                        {{ analysis.moisture.status }}
                    </span>
                    <span v-else>—</span>
                    <div class="fw-bold mt-1">{{ analysis.moisture ? formatPercent(analysis.moisture.percent) : '—' }}</div>
                    <div class="text-soft small">Soil moisture</div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="text-soft small">Health score</div>
                    <div class="fw-bold fs-4">{{ formatPercentRounded(analysis.health_score) }}</div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="text-soft small">Anomalies</div>
                    <div class="fw-bold fs-4">{{ analysis.anomaly_count ?? '—' }}</div>
                </div>
            </div>
            <Link :href="route('analytics.show', analysis.id)" class="btn btn-outline-primary btn-sm mt-3">
                View details
            </Link>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { formatNdvi, formatPercent, formatPercentRounded, statusClass } from '@/format';

defineProps({
    analysis: {
        type: Object,
        required: true,
    },
});
</script>

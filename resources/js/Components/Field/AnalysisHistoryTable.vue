<template>
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">All analyses</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Vegetation</th>
                        <th>Moisture</th>
                        <th>Recommendations</th>
                        <th>Anomalies</th>
                        <th><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in history" :key="row.id">
                        <td>{{ row.date }}</td>
                        <td>{{ row.type_label }}</td>
                        <td>
                            <template v-if="row.vegetation_status">
                                <span class="badge" :class="statusClass(row.vegetation_status)">{{ row.vegetation_status }}</span>
                                <span class="ms-1 text-soft">{{ formatNdvi(row.ndvi) }}</span>
                            </template>
                            <template v-else>—</template>
                        </td>
                        <td>
                            <template v-if="row.moisture_status">
                                <span class="badge" :class="statusClass(row.moisture_status)">{{ row.moisture_status }}</span>
                                <span class="ms-1 text-soft">{{ formatPercent(row.moisture_percent) }}</span>
                            </template>
                            <template v-else>—</template>
                        </td>
                        <td>{{ row.recommendation_count ?? '—' }}</td>
                        <td>{{ row.anomaly_count ?? '—' }}</td>
                        <td class="text-end">
                            <Link
                                :href="route('analytics.show', row.id)"
                                class="btn btn-icon btn-sm btn-trigger"
                                :aria-label="`View analysis from ${row.date}`"
                            >
                                <em class="icon ni ni-arrow-right" aria-hidden="true"></em>
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { formatNdvi, formatPercent, statusClass } from '@/format';

defineProps({
    history: {
        type: Array,
        default: () => [],
    },
});
</script>

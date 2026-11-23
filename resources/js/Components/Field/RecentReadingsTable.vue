<template>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Recent readings</h5>
            <Link :href="route('field-data.index', { 'filter[field_id]': fieldId })" class="link link-sm">See all</Link>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Value</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="reading in readings" :key="reading.id">
                        <td>{{ reading.date }}</td>
                        <td>{{ reading.label }}</td>
                        <td>{{ reading.display }}</td>
                        <td class="text-end">
                            <Link :href="route('field-data.show', reading.id)" class="btn btn-sm btn-icon btn-trigger">
                                <em class="icon ni ni-chevron-right"></em>
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

defineProps({
    readings: {
        type: Array,
        required: true,
    },
    fieldId: {
        type: Number,
        required: true,
    },
});
</script>

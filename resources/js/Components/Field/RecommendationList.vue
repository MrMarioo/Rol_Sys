<template>
    <ul class="nk-activity recommendation-list">
        <li v-for="(item, index) in items" :key="index" class="recommendation-item">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <span class="recommendation-action">
                    {{ item.action }}
                    <span v-if="compact && item.description" class="recommendation-inline text-soft">— {{ item.description }}</span>
                </span>
                <span class="badge" :class="priorityClass(item.priority)">{{ priorityLabel(item.priority) }}</span>
            </div>
            <template v-if="!compact">
                <p v-if="item.description" class="recommendation-description">{{ item.description }}</p>
                <div class="recommendation-meta">
                    <span v-if="item.urgency_days !== null && item.urgency_days !== undefined" class="badge badge-dim bg-outline-secondary">
                        {{ formatDays(item.urgency_days) }}
                    </span>
                    <span v-if="item.source" class="text-soft">{{ item.source }}</span>
                </div>
            </template>
        </li>
    </ul>
</template>

<script setup>
import { formatDays, priorityClass } from '@/Components/Field/analyticsFormat';

defineProps({
    items: {
        type: Array,
        default: () => [],
    },
    compact: {
        type: Boolean,
        default: false,
    },
});

const priorityLabel = (priority) => (priority ? priority.charAt(0).toUpperCase() + priority.slice(1) : 'N/A');
</script>

<style scoped>
.recommendation-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.recommendation-item {
    padding: 0.75rem 0;
    border-bottom: 1px solid #e5e9f2;
}

.recommendation-item:last-child {
    border-bottom: 0;
}

.recommendation-action {
    font-weight: 500;
    color: #364a63;
}

.recommendation-inline {
    font-weight: 400;
    font-size: 0.875rem;
}

.recommendation-description {
    margin: 0.35rem 0;
    font-size: 0.875rem;
    color: #526484;
}

.recommendation-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8rem;
}
</style>

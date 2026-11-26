<template>
    <span class="badge" :class="config.class">
        <em class="icon ni" :class="config.icon"></em>
        {{ config.text }}
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        required: true,
    },
});

const STATUSES = {
    draft: {
        class: 'badge-outline-warning',
        icon: 'ni-edit',
        text: 'Draft',
    },
    generated: {
        class: 'badge-outline-success',
        icon: 'ni-check',
        text: 'Generated',
    },
    archived: {
        class: 'badge-outline-secondary',
        icon: 'ni-archive',
        text: 'Archived',
    },
};

const config = computed(
    () =>
        STATUSES[props.status] ?? {
            class: 'badge-outline-primary',
            icon: 'ni-file-text',
            text: props.status,
        },
);
</script>

<style scoped>
.badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 0.375rem;
}

.badge-outline-warning {
    color: #f4bd0e;
    background-color: transparent;
    border: 1px solid #f4bd0e;
}

.badge-outline-success {
    color: #1ee0ac;
    background-color: transparent;
    border: 1px solid #1ee0ac;
}

.badge-outline-secondary {
    color: #526484;
    background-color: transparent;
    border: 1px solid #526484;
}

.badge-outline-primary {
    color: #6576ff;
    background-color: transparent;
    border: 1px solid #6576ff;
}
</style>

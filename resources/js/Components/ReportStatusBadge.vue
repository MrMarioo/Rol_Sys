<template>
    <span :class="badgeClass">
        <em class="icon ni" :class="iconClass"></em>
        {{ statusText }}
    </span>
</template>

<script>
import { computed } from 'vue';

export default {
    props: {
        status: {
            type: String,
            required: true
        }
    },
    setup(props) {
        const statusConfig = {
            draft: {
                class: 'badge badge-outline-warning',
                icon: 'ni-edit',
                text: 'Draft'
            },
            generated: {
                class: 'badge badge-outline-success',
                icon: 'ni-check',
                text: 'Generated'
            },
            archived: {
                class: 'badge badge-outline-secondary',
                icon: 'ni-archive',
                text: 'Archived'
            }
        };

        const badgeClass = computed(() => {
            return statusConfig[props.status]?.class || 'badge badge-outline-primary';
        });

        const iconClass = computed(() => {
            return statusConfig[props.status]?.icon || 'ni-file-text';
        });

        const statusText = computed(() => {
            return statusConfig[props.status]?.text || props.status;
        });

        return {
            badgeClass,
            iconClass,
            statusText
        };
    }
};
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

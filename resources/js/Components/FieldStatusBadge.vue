<script>
export default {
    props: {
        status: {
            type: String,
            required: true,
        },
    },
    methods: {
        /**
         * Get the appropriate CSS class for a status badge
         */
        getBadgeClass() {
            switch (this.status) {
                case 'active':
                    return 'badge-success';
                case 'inactive':
                    return 'badge-secondary';
                case 'maintenance':
                    return 'badge-warning';
                default:
                    return 'badge-secondary';
            }
        },

        /**
         * Get human-readable text for status
         */
        getStatusText() {
            switch (this.status) {
                case 'active':
                    return 'Active';
                case 'inactive':
                    return 'Inactive';
                case 'maintenance':
                    return 'Maintenance';
                default:
                    return this.status;
            }
        },
    },
};
</script>

<template>
    <span class="badge" :class="getBadgeClass()">
        {{ getStatusText() }}
    </span>
</template>

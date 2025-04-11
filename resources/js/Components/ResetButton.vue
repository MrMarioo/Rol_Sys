<script>
export default {
    props: {
        form: Object,
    },
    methods: {
        resetForm() {
            Object.keys(this.form.data()).forEach((key) => {
                this.form[key] = null;
            });
        },
    },
};
</script>

<template>
    <button type="button" @click="resetForm" class="btn btn-outline-secondary">
        <em class="icon ni ni-reload me-1"></em>
        Reset
    </button>
</template>

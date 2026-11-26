<script setup>
const props = defineProps({
    form: Object,
});

const resetForm = () => {
    Object.keys(props.form.data()).forEach((key) => {
        props.form[key] = null;
    });
};
</script>

<template>
    <button type="button" class="btn btn-outline-secondary" @click="resetForm">
        <em class="icon ni ni-reload me-1"></em>
        Reset
    </button>
</template>

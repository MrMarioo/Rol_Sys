<script setup>
defineProps({
    message: String,
});
</script>

<template>
    <div v-show="message">
        <p class="invalid-feedback d-block mb-0">
            {{ message }}
        </p>
    </div>
</template>

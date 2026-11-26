<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';

defineProps({
    policy: String,
});
</script>

<template>
    <Head title="Privacy Policy" />

    <div class="legal-page d-flex flex-column align-items-center">
        <div class="mb-4">
            <AuthenticationCardLogo />
        </div>

        <div class="card card-bordered w-100 legal-card">
            <div class="card-inner card-inner-lg entry" v-html="policy" />
        </div>
    </div>
</template>

<style scoped>
.legal-page {
    min-height: 100vh;
    padding: 3rem 1rem;
    background-color: #f5f6fa;
}

.legal-card {
    max-width: 42rem;
}
</style>

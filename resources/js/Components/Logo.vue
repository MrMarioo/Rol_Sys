<template>
    <Link :href="route('home')" class="hidden h-fit w-fit flex-grow min-[640px]:block">
        <img :src="logoPath" class="logo-light logo-img" style="width: 160px" alt="Logo" />
    </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import logoPath from '../../img/logo.png';
</script>

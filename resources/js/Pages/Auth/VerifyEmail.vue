<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    status: String,
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <Head title="Email Verification" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div class="nk-block-head">
            <div class="nk-block-head-content">
                <h4 class="nk-block-title">Verify your email</h4>
                <div class="nk-block-des">
                    <p>Before continuing, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another.</p>
                </div>
            </div>
        </div>

        <div v-if="verificationLinkSent" class="alert alert-success">
            A new verification link has been sent to the email address you provided in your profile settings.
        </div>

        <form @submit.prevent="submit">
            <PrimaryButton class="btn-lg w-100 justify-content-center" :disabled="form.processing">
                Resend Verification Email
            </PrimaryButton>
        </form>

        <div class="d-flex justify-content-center align-items-center gap-3 pt-4">
            <Link :href="route('profile.show')" class="btn btn-link btn-sm p-0">
                Edit Profile
            </Link>
            <Link :href="route('logout')" method="post" as="button" class="btn btn-link btn-sm p-0">
                Log Out
            </Link>
        </div>
    </AuthenticationCard>
</template>

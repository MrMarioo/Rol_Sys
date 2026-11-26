<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
    status: String,
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head title="Forgot Password" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div class="nk-block-head">
            <div class="nk-block-head-content">
                <h4 class="nk-block-title">Reset password</h4>
                <div class="nk-block-des">
                    <p>Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.</p>
                </div>
            </div>
        </div>

        <div v-if="status" class="alert alert-success">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div class="form-group">
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="form-control-lg"
                    required
                    autofocus
                    autocomplete="username"
                />
                <InputError class="mt-1" :message="form.errors.email" />
            </div>

            <div class="form-group mb-0">
                <PrimaryButton class="btn-lg w-100 justify-content-center" :disabled="form.processing">
                    Email Password Reset Link
                </PrimaryButton>
            </div>
        </form>

        <div class="small text-center pt-4">
            <Link :href="route('login')"><strong>Back to sign in</strong></Link>
        </div>
    </AuthenticationCard>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.transform(data => ({
        ...data,
        remember: form.remember ? 'on' : '',
    })).post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Log in" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div class="nk-block-head">
            <div class="nk-block-head-content">
                <h4 class="nk-block-title">Sign in</h4>
                <div class="nk-block-des">
                    <p>Access your fields and analytics with your email and password.</p>
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

            <div class="form-group">
                <div class="d-flex justify-content-between align-items-center">
                    <InputLabel for="password" value="Password" />
                    <Link v-if="canResetPassword" :href="route('password.request')" class="small mb-2">
                        Forgot your password?
                    </Link>
                </div>
                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="form-control-lg"
                    required
                    autocomplete="current-password"
                />
                <InputError class="mt-1" :message="form.errors.password" />
            </div>

            <div class="form-group">
                <div class="form-check">
                    <Checkbox id="remember" v-model:checked="form.remember" name="remember" />
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>
            </div>

            <div class="form-group mb-0">
                <PrimaryButton class="btn-lg w-100 justify-content-center" :disabled="form.processing">
                    Sign in
                </PrimaryButton>
            </div>
        </form>
    </AuthenticationCard>
</template>

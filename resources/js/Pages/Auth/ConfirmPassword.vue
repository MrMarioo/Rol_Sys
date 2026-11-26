<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const form = useForm({
    password: '',
});

const passwordInput = ref(null);

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => {
            form.reset();

            passwordInput.value.focus();
        },
    });
};
</script>

<template>
    <Head title="Secure Area" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div class="nk-block-head">
            <div class="nk-block-head-content">
                <h4 class="nk-block-title">Secure area</h4>
                <div class="nk-block-des">
                    <p>This is a secure area of the application. Please confirm your password before continuing.</p>
                </div>
            </div>
        </div>

        <form @submit.prevent="submit">
            <div class="form-group">
                <InputLabel for="password" value="Password" />
                <TextInput
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="form-control-lg"
                    required
                    autocomplete="current-password"
                    autofocus
                />
                <InputError class="mt-1" :message="form.errors.password" />
            </div>

            <div class="form-group mb-0">
                <PrimaryButton class="btn-lg w-100 justify-content-center" :disabled="form.processing">
                    Confirm
                </PrimaryButton>
            </div>
        </form>
    </AuthenticationCard>
</template>

<script setup>
import { nextTick, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const recovery = ref(false);

const form = useForm({
    code: '',
    recovery_code: '',
});

const recoveryCodeInput = ref(null);
const codeInput = ref(null);

const toggleRecovery = async () => {
    recovery.value = ! recovery.value;

    await nextTick();

    if (recovery.value) {
        recoveryCodeInput.value.focus();
        form.code = '';
    } else {
        codeInput.value.focus();
        form.recovery_code = '';
    }
};

const submit = () => {
    form.post(route('two-factor.login'));
};
</script>

<template>
    <Head title="Two-factor Confirmation" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div class="nk-block-head">
            <div class="nk-block-head-content">
                <h4 class="nk-block-title">Two-factor authentication</h4>
                <div class="nk-block-des">
                    <p v-if="! recovery">
                        Please confirm access to your account by entering the authentication code provided by your authenticator application.
                    </p>
                    <p v-else>
                        Please confirm access to your account by entering one of your emergency recovery codes.
                    </p>
                </div>
            </div>
        </div>

        <form @submit.prevent="submit">
            <div v-if="! recovery" class="form-group">
                <InputLabel for="code" value="Code" />
                <TextInput
                    id="code"
                    ref="codeInput"
                    v-model="form.code"
                    type="text"
                    inputmode="numeric"
                    class="form-control-lg"
                    autofocus
                    autocomplete="one-time-code"
                />
                <InputError class="mt-1" :message="form.errors.code" />
            </div>

            <div v-else class="form-group">
                <InputLabel for="recovery_code" value="Recovery Code" />
                <TextInput
                    id="recovery_code"
                    ref="recoveryCodeInput"
                    v-model="form.recovery_code"
                    type="text"
                    class="form-control-lg"
                    autocomplete="one-time-code"
                />
                <InputError class="mt-1" :message="form.errors.recovery_code" />
            </div>

            <div class="form-group mb-0">
                <PrimaryButton class="btn-lg w-100 justify-content-center" :disabled="form.processing">
                    Log in
                </PrimaryButton>
            </div>
        </form>

        <div class="text-center pt-4">
            <button type="button" class="btn btn-link btn-sm p-0" @click="toggleRecovery">
                {{ recovery ? 'Use an authentication code' : 'Use a recovery code' }}
            </button>
        </div>
    </AuthenticationCard>
</template>

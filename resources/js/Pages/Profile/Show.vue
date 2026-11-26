<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DeleteUserForm from '@/Components/Profile/DeleteUserForm.vue';
import LogoutOtherBrowserSessionsForm from '@/Components/Profile/LogoutOtherBrowserSessionsForm.vue';
import SectionBorder from '@/Components/SectionBorder.vue';
import TwoFactorAuthenticationForm from '@/Components/Profile/TwoFactorAuthenticationForm.vue';
import UpdatePasswordForm from '@/Components/Profile/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from '@/Components/Profile/UpdateProfileInformationForm.vue';

defineOptions({ layout: AppLayout });

defineProps({
    confirmsTwoFactorAuthentication: Boolean,
    sessions: Array,
});
</script>

<template>
    <div>
        <Head title="Profile" />

        <div class="nk-block-head nk-block-head-sm">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Profile</h3>
                <div class="nk-block-des text-soft">
                    <p>Manage your account information and security settings.</p>
                </div>
            </div>
        </div>

        <div class="nk-block">
            <div v-if="$page.props.jetstream.canUpdateProfileInformation">
                <UpdateProfileInformationForm :user="$page.props.auth.user" />

                <SectionBorder />
            </div>

            <div v-if="$page.props.jetstream.canUpdatePassword">
                <UpdatePasswordForm />

                <SectionBorder />
            </div>

            <div v-if="$page.props.jetstream.canManageTwoFactorAuthentication">
                <TwoFactorAuthenticationForm :requires-confirmation="confirmsTwoFactorAuthentication" />

                <SectionBorder />
            </div>

            <LogoutOtherBrowserSessionsForm :sessions="sessions" />

            <template v-if="$page.props.jetstream.hasAccountDeletionFeatures">
                <SectionBorder />

                <DeleteUserForm />
            </template>
        </div>
    </div>
</template>

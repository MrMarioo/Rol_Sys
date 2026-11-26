<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    user: Object,
});

const form = useForm({
    _method: 'PUT',
    name: props.user.name,
    email: props.user.email,
    photo: null,
});

const verificationLinkSent = ref(null);
const photoPreview = ref(null);
const photoInput = ref(null);

const updateProfileInformation = () => {
    if (photoInput.value) {
        form.photo = photoInput.value.files[0];
    }

    form.post(route('user-profile-information.update'), {
        errorBag: 'updateProfileInformation',
        preserveScroll: true,
        onSuccess: () => clearPhotoFileInput(),
    });
};

const sendEmailVerification = () => {
    verificationLinkSent.value = true;
};

const selectNewPhoto = () => {
    photoInput.value.click();
};

const updatePhotoPreview = () => {
    const photo = photoInput.value.files[0];

    if (! photo) return;

    const reader = new FileReader();

    reader.onload = (e) => {
        photoPreview.value = e.target.result;
    };

    reader.readAsDataURL(photo);
};

const deletePhoto = () => {
    router.delete(route('current-user-photo.destroy'), {
        preserveScroll: true,
        onSuccess: () => {
            photoPreview.value = null;
            clearPhotoFileInput();
        },
    });
};

const clearPhotoFileInput = () => {
    if (photoInput.value?.value) {
        photoInput.value.value = null;
    }
};
</script>

<template>
    <FormSection @submitted="updateProfileInformation">
        <template #title>
            Profile Information
        </template>

        <template #description>
            Update your account's profile information and email address.
        </template>

        <template #form>
            <div v-if="$page.props.jetstream.managesProfilePhotos" class="col-12 col-md-8">
                <input
                    id="photo"
                    ref="photoInput"
                    type="file"
                    class="d-none"
                    @change="updatePhotoPreview"
                >

                <InputLabel for="photo" value="Photo" />

                <div v-show="! photoPreview" class="mb-2">
                    <img :src="user.profile_photo_url" :alt="user.name" class="profile-photo rounded-circle">
                </div>

                <div v-show="photoPreview" class="mb-2">
                    <span
                        class="profile-photo profile-photo-preview d-block rounded-circle"
                        :style="{ backgroundImage: `url('${photoPreview}')` }"
                    />
                </div>

                <SecondaryButton class="me-2" type="button" @click.prevent="selectNewPhoto">
                    Select A New Photo
                </SecondaryButton>

                <SecondaryButton
                    v-if="user.profile_photo_path"
                    type="button"
                    @click.prevent="deletePhoto"
                >
                    Remove Photo
                </SecondaryButton>

                <InputError :message="form.errors.photo" class="mt-1" />
            </div>

            <div class="col-12 col-md-8">
                <InputLabel for="name" value="Name" />
                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    required
                    autocomplete="name"
                />
                <InputError :message="form.errors.name" class="mt-1" />
            </div>

            <div class="col-12 col-md-8">
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    autocomplete="username"
                />
                <InputError :message="form.errors.email" class="mt-1" />

                <div v-if="$page.props.jetstream.hasEmailVerification && user.email_verified_at === null">
                    <p class="small mt-2 mb-0">
                        Your email address is unverified.

                        <Link
                            :href="route('verification.send')"
                            method="post"
                            as="button"
                            class="btn btn-link btn-sm p-0 align-baseline"
                            @click.prevent="sendEmailVerification"
                        >
                            Click here to re-send the verification email.
                        </Link>
                    </p>

                    <div v-show="verificationLinkSent" class="mt-2 small fw-medium text-success">
                        A new verification link has been sent to your email address.
                    </div>
                </div>
            </div>
        </template>

        <template #actions>
            <ActionMessage :on="form.recentlySuccessful">
                Saved.
            </ActionMessage>

            <PrimaryButton :disabled="form.processing">
                Save
            </PrimaryButton>
        </template>
    </FormSection>
</template>

<style scoped>
.profile-photo {
    width: 5rem;
    height: 5rem;
    object-fit: cover;
}

.profile-photo-preview {
    background-size: cover;
    background-repeat: no-repeat;
    background-position: center;
}
</style>

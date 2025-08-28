<script setup>
import { ref } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

// Get authenticated user from page props
const user = usePage().props.auth.user;

// Initialize the form object and guard against missing properties
const form = useForm({
    name: user.name ?? '',
    email: user.email ?? '',
    image: null, // Handle as file for upload
    total_experience: user.total_experience ?? '',
});

const imageUrl = ref(user.image ? `/storage/${user.image}` : null);

function onFileChange(e) {
    const file = e.target.files[0];
    if (file) {
        form.image = file;
        imageUrl.value = URL.createObjectURL(file);
    }
}

function submit() {
    form.post(route('profile.update'), {
        forceFormData: true,
    });
}
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Profile Information
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                Update your account's profile information, email address,
                experience, and profile image.
            </p>
        </header>

        <form
            @submit.prevent="submit"
            class="mt-6 space-y-6"
            enctype="multipart/form-data"
        >
            <!-- Profile Image Preview -->
            <div class="flex items-center">
                <div v-if="imageUrl" class="mr-4">
                    <img
                        :src="imageUrl"
                        alt="Profile Image"
                        class="h-20 w-20 rounded-full object-cover"
                    />
                </div>
                <div>
                    <InputLabel for="image" value="Profile Image" />
                    <input
                        id="image"
                        type="file"
                        class="mt-1 block w-full"
                        @change="onFileChange"
                        accept="image/*"
                    />
                    <InputError class="mt-2" :message="form.errors.image" />
                </div>
            </div>

            <div>
                <InputLabel for="name" value="Name" />
                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <!-- Experience Fields -->
            <div>
                <InputLabel
                    for="total_experience"
                    value="Total Experience (Years)"
                />
                <input
                    id="total_experience"
                    type="number"
                    v-model="form.total_experience"
                    min="0"
                    max="100"
                    step="0.1"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                    placeholder="e.g., 3.5"
                />
                <InputError
                    class="mt-2"
                    :message="form.errors.total_experience"
                />
            </div>

            <div
                v-show="status === 'verification-link-sent'"
                class="mt-2 text-sm font-medium text-green-600"
            >
                A new verification link has been sent to your email address.
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>
                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-600"
                    >
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>

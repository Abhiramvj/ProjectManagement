<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const isLocalDev = (
    window.location.hostname === 'localhost' ||
    window.location.hostname === '127.0.0.1' ||
    window.location.hostname.endsWith('.test') ||
    window.location.hostname.endsWith('.herd.run')
);
</script>

<template>
    <Head title="Log in" />

    
    <div class="min-h-screen antialiased flex flex-col items-center justify-center p-6 relative login-background">

        <!-- Logo -->
        <div class="absolute top-0 mt-8 sm:mt-12">
            <div class="flex items-center space-x-2">
                <!-- A simple SVG representation of the logo in the image -->
                <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M16 28C22.6274 28 28 22.6274 28 16C28 9.37258 22.6274 4 16 4C9.37258 4 4 9.37258 4 16C4 22.6274 9.37258 28 16 28Z" stroke="#2D3748" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M21.5 16C21.5 18.4853 19.4853 20.5 17 20.5C14.5147 20.5 12.5 18.4853 12.5 16C12.5 13.5147 14.5147 11.5 17 11.5" stroke="#2D3748" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span class="text-2xl font-semibold text-gray-800">WorkSphere</span>
            </div>
        </div>

        <!-- Login Card -->
        <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8 sm:p-10 mt-20">
            <div class="text-left mb-8">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Log in to your account</h1>
                <p class="mt-2 text-sm text-gray-500">
                    Enter your credentials to access your account.
                </p>
            </div>

            <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <InputLabel for="email" value="Email" class="text-sm font-medium text-gray-700"/>
                    <TextInput
                        id="email"
                        type="email"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="name@iocod.com"
                    />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div>
                    <InputLabel for="password" value="Password" class="text-sm font-medium text-gray-700"/>
                    <div class="relative mt-1">
                        <TextInput
                            id="password"
                            type="password"
                            class="block w-full border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••••"
                        />
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                    </div>
                     <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <div class="text-right">
                     <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-sm text-blue-600 hover:text-blue-500 font-medium transition-colors"
                    >
                        Forgot Password?
                    </Link>
                </div>

                <div>
                    <PrimaryButton class="w-full justify-center py-3 px-4 bg-gray-800 hover:bg-gray-900 focus:bg-gray-900 active:bg-gray-900 focus:ring-indigo-500 text-white font-semibold rounded-lg" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Login
                    </PrimaryButton>
                </div>

                <!-- "or" separator -->
                <div class="flex items-center my-4">
                    <hr class="flex-grow border-gray-200">
                    <span class="mx-4 text-sm font-medium text-gray-400">or</span>
                    <hr class="flex-grow border-gray-200">
                </div>

                <!-- Login with Microsoft Button -->
                <div>
                    <a href="#" class="w-full flex items-center justify-center py-2.5 px-4 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                        <img class="w-5 h-5 mr-2" src="https://upload.wikimedia.org/wikipedia/commons/4/44/Microsoft_logo.svg" alt="Microsoft Logo">
                        Login with Microsoft
                    </a>
                </div>

                <!-- Dev quick login (preserved from original code) -->
                <div v-if="isLocalDev" class="mt-6 pt-4 border-t border-gray-200">
                    <p class="text-xs text-gray-500 mb-2 text-center">Dev quick login:</p>
                    <div class="flex flex-wrap gap-2 justify-center">
                        <a v-for="role in ['admin','hr','project-manager','team-lead','employee']"
                           :key="role"
                           :href="`/dev-login/${role}`"
                           class="py-1 px-3 rounded bg-gray-200 text-gray-700 text-xs hover:bg-blue-500 hover:text-white focus:ring-2 focus:ring-blue-300 transition-colors">
                            Login as {{ role.replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase()) }}
                        </a>
                    </div>
                </div>

            </form>
        </div>

         <!-- Footer -->
        <div class="w-full max-w-md text-center mt-8 pb-4">
            <p class="text-xs text-gray-500">
                ©{{ new Date().getFullYear() }} WorkSphere. All right reserved
            </p>
        </div>
    </div>
</template>

<style>
.login-background {
  
  background-image: url('/images/loginbg.png');
  background-color: #f3f4f6; 
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
}
</style>
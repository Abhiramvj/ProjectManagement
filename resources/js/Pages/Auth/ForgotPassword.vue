<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
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

    <div class="min-h-screen antialiased bg-gray-50 dark:bg-gray-900 flex">
        <!-- Left Panel: Branding & Visual -->
        <div class="hidden lg:flex w-1/2 bg-gradient-to-br from-blue-600 to-blue-800 relative items-center justify-center p-12">
            <!-- Subtle animated background pattern -->
            <div class="absolute inset-0 opacity-5">
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <path 
                        d="M0,50 L100,50 M50,0 L50,100" 
                        stroke="white" 
                        stroke-width="0.5"
                        stroke-dasharray="5 5"
                        class="animate-move-dash"
                    />
                </svg>
            </div>
            
            <!-- Animated work illustration -->
            <div class="relative z-10 text-center text-white">
                <div class="w-40 h-40 mx-auto mb-8">
                    <svg viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <!-- Office building with subtle pulse -->
                        <path 
                            d="M50 50V150H150V50H50Z" 
                            stroke="white" 
                            stroke-width="2" 
                            stroke-linecap="round" 
                            stroke-linejoin="round"
                            class="animate-pulse-opacity"
                        />
                        <path 
                            d="M70 70V100H90V70H70Z" 
                            stroke="white" 
                            stroke-width="1.5" 
                            stroke-linecap="round" 
                            stroke-linejoin="round"
                            class="animate-pulse-opacity"
                            style="animation-delay: 0.3s;"
                        />
                        <path 
                            d="M110 70V100H130V70H110Z" 
                            stroke="white" 
                            stroke-width="1.5" 
                            stroke-linecap="round" 
                            stroke-linejoin="round"
                            class="animate-pulse-opacity"
                            style="animation-delay: 0.6s;"
                        />
                        
                        <!-- Progress bars with continuous growing/shrinking animation -->
                        <rect 
                            x="30" y="30" width="10" height="20" 
                            fill="white" fill-opacity="0.7"
                            class="animate-graph-pulse"
                            style="transform-origin: bottom; animation-delay: 0.4s;"
                        />
                        <rect 
                            x="45" y="40" width="10" height="10" 
                            fill="white" fill-opacity="0.5"
                            class="animate-graph-pulse"
                            style="transform-origin: bottom; animation-delay: 0.6s;"
                        />
                        <rect 
                            x="60" y="20" width="10" height="30" 
                            fill="white" fill-opacity="0.9"
                            class="animate-graph-pulse"
                            style="transform-origin: bottom; animation-delay: 0.8s;"
                        />
                        
                        <!-- Clock with ticking animation -->
                        <circle 
                            cx="170" 
                            cy="40" 
                            r="15" 
                            stroke="white" 
                            stroke-width="1.5"
                            class="animate-rotate"
                            style="transform-origin: 170px 40px; animation-delay: 1s;"
                        />
                        <path 
                            d="M170 40V30" 
                            stroke="white" 
                            stroke-width="1.5" 
                            stroke-linecap="round"
                            class="animate-rotate"
                            style="transform-origin: 170px 40px;"
                        />
                        <path 
                            d="M170 40H175" 
                            stroke="white" 
                            stroke-width="1.5" 
                            stroke-linecap="round"
                            class="animate-rotate-fast"
                            style="transform-origin: 170px 40px; animation-delay: 0.5s;"
                        />
                    </svg>
                </div>
                
                <h1 class="text-4xl font-bold mb-3 animate-fade-in">WorkSphere</h1>
                <p class="text-lg opacity-90 max-w-md mx-auto animate-fade-in" style="animation-delay: 0.3s;">
                    The modern way to manage your team's productivity
                </p>
            </div>
        </div>

        <!-- Right Panel: Forgot Password Form -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-12">
            <div class="w-full max-w-md animate-fade-in" style="animation-delay: 0.2s;">
                <div class="text-center lg:text-left mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Reset Password</h2>
                    <p class="mt-2 text-gray-500 dark:text-gray-400">
                        Enter your email to receive a password reset link
                    </p>
                </div>

                <div v-if="status" class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <InputLabel for="email" value="Email" class="mb-1"/>
                        <TextInput
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="you@company.com"
                        />
                        <InputError class="mt-1" :message="form.errors.email" />
                    </div>

                    <div>
                        <PrimaryButton class="w-full justify-center py-2.5" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            Send Reset Link
                        </PrimaryButton>
                    </div>
                </form>

                <!-- Back to login link -->
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Remember your password?
                        <Link 
                            :href="route('login')" 
                            class="ml-1 font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300"
                        >
                            Sign in
                        </Link>
                    </p>
                </div>

                <p class="mt-8 text-center text-xs text-gray-400 dark:text-gray-500">
                    © {{ new Date().getFullYear() }} WorkSphere. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</template>

<style>
@keyframes move-dash {
    to {
        stroke-dashoffset: 10;
    }
}

@keyframes pulse-opacity {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.8; }
}

@keyframes graph-pulse {
    0%, 100% { transform: scaleY(1); }
    25% { transform: scaleY(0.98); }
    50% { transform: scaleY(1.05); }
    75% { transform: scaleY(0.97); }
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@keyframes rotate-fast {
    from { transform: rotate(0deg); }
    to { transform: rotate(3600deg); }
}

@keyframes fade-in {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-move-dash {
    animation: move-dash 3s linear infinite;
}

.animate-pulse-opacity {
    animation: pulse-opacity 4s ease-in-out infinite;
}

.animate-graph-pulse {
    animation: graph-pulse 6s ease-in-out infinite;
}

.animate-rotate {
    animation: rotate 60s linear infinite;
}

.animate-rotate-fast {
    animation: rotate-fast 10s linear infinite;
}

.animate-fade-in {
    animation: fade-in 0.8s ease-out forwards;
}
</style>
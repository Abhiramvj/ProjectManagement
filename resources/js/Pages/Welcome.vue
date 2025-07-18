<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Head title="Welcome to WorkSphere" />

    <div class="min-h-screen antialiased bg-gray-50 dark:bg-gray-900 flex">
        <!-- Left Panel: Branding & Visuals with Auth Links -->
        <div class="w-full lg:w-1/2 bg-gradient-to-br from-blue-600 to-indigo-800 relative flex items-center justify-center overflow-hidden">
            
            <!-- Animated Shapes -->
            <div class="absolute inset-0 z-0">
                <div class="absolute w-32 h-32 bg-white/10 rounded-full -top-10 -left-10 animate-float" style="animation-duration: 15s;"></div>
                <div class="absolute w-48 h-48 bg-white/5 rounded-full -bottom-20 -right-10 animate-float" style="animation-duration: 12s; animation-delay: 3s;"></div>
                <div class="absolute w-24 h-24 bg-white/10 rounded-full top-1/2 -right-12 animate-float" style="animation-duration: 18s; animation-delay: 5s;"></div>
                <div class="absolute w-20 h-20 bg-white/10 rounded-lg bottom-1/4 -left-16 animate-float" style="animation-duration: 20s; animation-delay: 1s;"></div>
            </div>

            <!-- Content -->
            <div class="relative z-10 text-center text-white px-8 sm:px-12 py-12">
                <svg class="h-24 w-24 mx-auto mb-6 opacity-90" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h1 class="text-4xl font-bold tracking-tight">WorkSphere</h1>
                <p class="mt-4 text-lg max-w-md opacity-80 mb-10">
                    The effortless way for your team to track time, manage projects, and boost productivity.
                </p>
                
                <!-- Auth Links -->
                <div class="space-y-4 max-w-xs mx-auto">
                    <template v-if="$page.props.auth.user">
                        <Link
                            :href="route('dashboard')"
                            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-base font-medium text-blue-700 bg-white hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-white transition"
                        >
                            Go to Dashboard
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            v-if="canLogin"
                            :href="route('login')"
                            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-blue-500 hover:bg-blue-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-300 transition"
                        >
                            Log In
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="w-full flex justify-center py-3 px-4 border border-white rounded-md shadow-sm text-base font-medium text-white hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-white transition"
                        >
                            Register
                        </Link>
                    </template>
                </div>
                 <!-- <p class="text-xs text-white/50 mt-12">
                    Laravel v{{ laravelVersion }} · PHP v{{ phpVersion }}
                 </p> -->
            </div>
        </div>

        <!-- Right Panel: UI Mockup -->
        <div class="hidden lg:flex w-1/2 items-center justify-center p-12">
            <div class="w-full max-w-lg transform transition-transform duration-300 hover:scale-105">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="p-4 bg-gray-100 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700 flex items-center space-x-2">
                        <span class="w-3 h-3 bg-red-400 rounded-full"></span>
                        <span class="w-3 h-3 bg-yellow-400 rounded-full"></span>
                        <span class="w-3 h-3 bg-green-400 rounded-full"></span>
                    </div>
                    <div class="p-6">
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white">Dashboard Overview</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Weekly Progress</p>
                        <div class="flex items-end space-x-2 h-32">
                            <div class="w-full bg-blue-200 dark:bg-blue-800/50 rounded-t-lg" style="height: 40%;"></div>
                            <div class="w-full bg-blue-300 dark:bg-blue-700/50 rounded-t-lg" style="height: 60%;"></div>
                            <div class="w-full bg-blue-200 dark:bg-blue-800/50 rounded-t-lg" style="height: 50%;"></div>
                            <div class="w-full bg-blue-400 dark:bg-blue-600/50 rounded-t-lg" style="height: 80%;"></div>
                            <div class="w-full bg-blue-300 dark:bg-blue-700/50 rounded-t-lg" style="height: 70%;"></div>
                            <div class="w-full bg-blue-200 dark:bg-blue-800/50 rounded-t-lg" style="height: 55%;"></div>
                            <div class="w-full bg-blue-400 dark:bg-blue-600/50 rounded-t-lg" style="height: 75%;"></div>
                        </div>
                        <div class="mt-8 space-y-4">
                            <div class="flex items-center">
                                <span class="w-5 h-5 rounded-full bg-pink-500 flex items-center justify-center text-white text-xs">✓</span>
                                <p class="ml-3 text-gray-600 dark:text-gray-300 ">Design new dashboard layout</p>
                            </div>
                            <div class="flex items-center">
                                <span class="w-5 h-5 rounded-full border-2 border-purple-500"></span>
                                <p class="ml-3 text-gray-800 dark:text-gray-100 font-medium">Develop API endpoints</p>
                            </div>
                            <div class="flex items-center">
                                <span class="w-5 h-5 rounded-full border-2 border-gray-300 dark:border-gray-600"></span>
                                <p class="ml-3 text-gray-600 dark:text-gray-300">User testing & feedback</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
@keyframes float {
    0% {
        transform: translateY(0) rotate(0deg);
        opacity: 0.1;
    }
    50% {
        opacity: 0.2;
    }
    100% {
        transform: translateY(-1000px) rotate(720deg);
        opacity: 0;
    }
}

.animate-float {
    animation: float linear infinite;
}
</style>
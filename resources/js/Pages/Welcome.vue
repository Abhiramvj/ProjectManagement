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
    <Head title="Welcome to Organice" />

    <div class="flex min-h-screen bg-gray-50 antialiased dark:bg-gray-900">
        <!-- Left Panel: Branding & Visuals with Auth Links -->
        <div
            class="relative flex w-full items-center justify-center overflow-hidden bg-gradient-to-br from-blue-600 to-indigo-800 lg:w-1/2"
        >
            <!-- Animated Shapes -->
            <div class="absolute inset-0 z-0">
                <div
                    class="animate-float absolute -left-10 -top-10 h-32 w-32 rounded-full bg-white/10"
                    style="animation-duration: 15s"
                ></div>
                <div
                    class="animate-float absolute -bottom-20 -right-10 h-48 w-48 rounded-full bg-white/5"
                    style="animation-duration: 12s; animation-delay: 3s"
                ></div>
                <div
                    class="animate-float absolute -right-12 top-1/2 h-24 w-24 rounded-full bg-white/10"
                    style="animation-duration: 18s; animation-delay: 5s"
                ></div>
                <div
                    class="animate-float absolute -left-16 bottom-1/4 h-20 w-20 rounded-lg bg-white/10"
                    style="animation-duration: 20s; animation-delay: 1s"
                ></div>
            </div>

            <!-- Content -->
            <div
                class="relative z-10 px-8 py-12 text-center text-white sm:px-12"
            >
                <svg
                    class="mx-auto mb-6 h-24 w-24 opacity-90"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>
                <h1 class="text-4xl font-bold tracking-tight">Organice</h1>
                <p class="mb-10 mt-4 max-w-md text-lg opacity-80">
                    The effortless way for your team to track time, manage
                    projects, and boost productivity.
                </p>

                <!-- Auth Links -->
                <div class="mx-auto max-w-xs space-y-4">
                    <template v-if="$page.props.auth.user">
                        <Link
                            :href="route('dashboard')"
                            class="flex w-full justify-center rounded-md border border-transparent bg-white px-4 py-3 text-base font-medium text-blue-700 shadow-sm transition hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2"
                        >
                            Go to Dashboard
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            v-if="canLogin"
                            :href="route('login')"
                            class="flex w-full justify-center rounded-md border border-transparent bg-blue-500 px-4 py-3 text-base font-medium text-white shadow-sm transition hover:bg-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-300 focus:ring-offset-2"
                        >
                            Log In
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="flex w-full justify-center rounded-md border border-white px-4 py-3 text-base font-medium text-white shadow-sm transition hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2"
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
        <div class="hidden w-1/2 items-center justify-center p-12 lg:flex">
            <div
                class="w-full max-w-lg transform transition-transform duration-300 hover:scale-105"
            >
                <div
                    class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-800"
                >
                    <div
                        class="flex items-center space-x-2 border-b border-gray-200 bg-gray-100 p-4 dark:border-gray-700 dark:bg-gray-700/50"
                    >
                        <span class="h-3 w-3 rounded-full bg-red-400"></span>
                        <span class="h-3 w-3 rounded-full bg-yellow-400"></span>
                        <span class="h-3 w-3 rounded-full bg-green-400"></span>
                    </div>
                    <div class="p-6">
                        <h3
                            class="text-lg font-bold text-gray-800 dark:text-white"
                        >
                            Dashboard Overview
                        </h3>
                        <p
                            class="mb-6 text-sm text-gray-500 dark:text-gray-400"
                        >
                            Weekly Progress
                        </p>
                        <div class="flex h-32 items-end space-x-2">
                            <div
                                class="w-full rounded-t-lg bg-blue-200 dark:bg-blue-800/50"
                                style="height: 40%"
                            ></div>
                            <div
                                class="w-full rounded-t-lg bg-blue-300 dark:bg-blue-700/50"
                                style="height: 60%"
                            ></div>
                            <div
                                class="w-full rounded-t-lg bg-blue-200 dark:bg-blue-800/50"
                                style="height: 50%"
                            ></div>
                            <div
                                class="w-full rounded-t-lg bg-blue-400 dark:bg-blue-600/50"
                                style="height: 80%"
                            ></div>
                            <div
                                class="w-full rounded-t-lg bg-blue-300 dark:bg-blue-700/50"
                                style="height: 70%"
                            ></div>
                            <div
                                class="w-full rounded-t-lg bg-blue-200 dark:bg-blue-800/50"
                                style="height: 55%"
                            ></div>
                            <div
                                class="w-full rounded-t-lg bg-blue-400 dark:bg-blue-600/50"
                                style="height: 75%"
                            ></div>
                        </div>
                        <div class="mt-8 space-y-4">
                            <div class="flex items-center">
                                <span
                                    class="flex h-5 w-5 items-center justify-center rounded-full bg-pink-500 text-xs text-white"
                                    >✓</span
                                >
                                <p
                                    class="ml-3 text-gray-600 dark:text-gray-300"
                                >
                                    Design new dashboard layout
                                </p>
                            </div>
                            <div class="flex items-center">
                                <span
                                    class="h-5 w-5 rounded-full border-2 border-purple-500"
                                ></span>
                                <p
                                    class="ml-3 font-medium text-gray-800 dark:text-gray-100"
                                >
                                    Develop API endpoints
                                </p>
                            </div>
                            <div class="flex items-center">
                                <span
                                    class="h-5 w-5 rounded-full border-2 border-gray-300 dark:border-gray-600"
                                ></span>
                                <p
                                    class="ml-3 text-gray-600 dark:text-gray-300"
                                >
                                    User testing & feedback
                                </p>
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

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
    <Head title="Welcome" />

    <div class="min-h-screen bg-white text-gray-900 flex flex-col font-sans">
        <!-- Stylish Header -->
        <header class="border-b bg-white/80 backdrop-blur shadow-sm sticky top-0 z-10">
            <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
                <h1 class="text-3xl font-extrabold tracking-tight bg-gradient-to-r from-blue-600 via-purple-500 to-pink-500 bg-clip-text text-transparent drop-shadow">
                    Employee Project Tracker
                </h1>
                <div class="space-x-4 text-base">
                    <template v-if="$page.props.auth.user">
                        <Link :href="route('dashboard')" class="text-blue-600 hover:underline font-semibold">
                            Dashboard
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            v-if="canLogin"
                            :href="route('login')"
                            class="text-blue-600 hover:underline font-semibold"
                        >
                            Log in
                    </Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- Trendy Main Section -->
        <main class="flex-1 flex flex-col items-center justify-center relative overflow-hidden">
            <div class="absolute inset-0 pointer-events-none">
                <!-- Decorative Gradient Circles -->
                <div class="absolute -top-32 -left-32 w-96 h-96 bg-gradient-to-br from-blue-100 via-purple-100 to-pink-100 rounded-full blur-3xl opacity-60"></div>
                <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-gradient-to-tr from-pink-100 via-blue-100 to-purple-100 rounded-full blur-3xl opacity-60"></div>
            </div>
            <div class="relative z-10 w-full h-full flex flex-col items-center justify-center px-0 py-0 bg-white/90 rounded-none shadow-none border-0 text-center min-h-[calc(100vh-80px)]">
                <h2 class="text-5xl font-extrabold mb-6 tracking-tight bg-gradient-to-r from-blue-600 via-purple-500 to-pink-500 bg-clip-text text-transparent">
                    Track Employee Hours with Ease
                </h2>
                <p class="text-lg text-gray-600 mb-10">
                    Manage and visualize how your team is contributing to projects in real-time.
                </p>
                <div class="flex justify-center gap-4 mb-10">
                    <Link
                        v-if="canLogin"
                        :href="route('login')"
                        class="px-8 py-3 rounded-full bg-gradient-to-r from-blue-600 to-purple-500 text-white font-bold shadow-lg hover:scale-105 hover:shadow-xl transition"
                    >
                        Log In
                    </Link>
                </div>
                <!-- Trendy Cards Section -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mt-8 max-w-4xl w-full px-8">
                    <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 flex flex-col items-center hover:shadow-xl transition">
                        <div class="bg-gradient-to-br from-blue-500 to-purple-400 text-white rounded-full p-3 mb-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2a4 4 0 014-4h4m0 0V7a4 4 0 00-4-4H7a4 4 0 00-4 4v10a4 4 0 004 4h4"></path>
                            </svg>
                        </div>
                        <h3 class="font-bold text-lg mb-2">Project Overview</h3>
                        <p class="text-gray-500 text-sm">Get a clear summary of all ongoing projects and their statuses.</p>
                    </div>
                    <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 flex flex-col items-center hover:shadow-xl transition">
                        <div class="bg-gradient-to-br from-pink-500 to-purple-400 text-white rounded-full p-3 mb-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="font-bold text-lg mb-2">Time Tracking</h3>
                        <p class="text-gray-500 text-sm">Easily log and monitor employee hours for each project.</p>
                    </div>
                    <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 flex flex-col items-center hover:shadow-xl transition">
                        <div class="bg-gradient-to-br from-blue-500 to-pink-400 text-white rounded-full p-3 mb-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m9-4a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <h3 class="font-bold text-lg mb-2">Team Collaboration</h3>
                        <p class="text-gray-500 text-sm">Collaborate with your team and assign tasks effortlessly.</p>
                    </div>
                </div>
            </div>
        </main>

        <!-- Minimal Footer -->
        <footer class="border-t text-center py-4 text-sm text-gray-400 bg-white/80">
            Laravel v{{ laravelVersion }} · PHP v{{ phpVersion }}
        </footer>
    </div>
</template>

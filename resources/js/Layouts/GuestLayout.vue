<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-indigo-50 via-white to-emerald-50 hero-banner">
        <div class="w-full sm:max-w-md">
            <div class="flex justify-center">
                <Link href="/">
                    <ApplicationLogo class="h-16 w-16 text-primary" />
                </Link>
            </div>
            <div class="mt-6 card p-6">
                <slot />
            </div>
        </div>
    </div>
</template>

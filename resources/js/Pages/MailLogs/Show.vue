<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    mailLog: Object,
});
</script>

<template>
    <Head :title="'Log Details: ' + mailLog.subject" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Mail Log Details</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">

                        <!-- Back to list link -->
                        <div class="mb-6">
                            <Link :href="route('mail-logs.index')" class="text-blue-600 hover:text-blue-800 transition">
                                &larr; Back to Mail Activity Logs
                            </Link>
                        </div>

                        <!-- Subject as the main title -->
                        <div class="border-b pb-4 mb-6">
                            <h3 class="text-2xl font-bold text-gray-900 leading-tight">{{ mailLog.subject }}</h3>
                            <p class="mt-2 text-sm text-gray-600">
                                <span class="font-semibold">Recipient:</span> {{ mailLog.recipient_email }}
                            </p>
                        </div>

                        <!-- This section now directly renders the full HTML email body -->
                        <div
                            class="prose max-w-none text-black"
                            v-if="mailLog.body_html"
                            v-html="mailLog.body_html"
                        ></div>

                        <!-- Fallback message if no HTML body is found -->
                         <div v-else class="text-center text-gray-500 p-6 border rounded-lg">
                            No email body preview available.
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

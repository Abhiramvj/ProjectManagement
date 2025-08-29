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
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Mail Log Details
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200 bg-white p-6">
                        <!-- Back to list link -->
                        <div class="mb-6">
                            <Link
                                :href="route('mail-logs.index')"
                                class="text-blue-600 transition hover:text-blue-800"
                            >
                                &larr; Back to Mail Activity Logs
                            </Link>
                        </div>

                        <!-- Subject as the main title -->
                        <div class="mb-6 border-b pb-4">
                            <h3
                                class="text-2xl font-bold leading-tight text-gray-900"
                            >
                                {{ mailLog.subject }}
                            </h3>
                            <p class="mt-2 text-sm text-gray-600">
                                <span class="font-semibold">Recipient:</span>
                                {{ mailLog.recipient_email }}
                            </p>
                        </div>

                        <!-- This section now directly renders the full HTML email body -->
                        <div
                            class="prose max-w-none text-black"
                            v-if="mailLog.body_html"
                            v-html="mailLog.body_html"
                        ></div>

                        <!-- Fallback message if no HTML body is found -->
                        <div
                            v-else
                            class="rounded-lg border p-6 text-center text-gray-500"
                        >
                            No email body preview available.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

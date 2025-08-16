<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

// The 'mailLog' prop now arrives perfectly formatted from our MailLogResource.
// This component has no internal logic, it just displays what it's given.
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

                        <!-- Subject Section -->
                        <div class="border-b pb-4 mb-6">
                            <h3 class="text-2xl font-bold text-gray-900 leading-tight">{{ mailLog.subject }}</h3>
                            <p class="mt-2 text-sm text-gray-600">
                                <span class="font-semibold">Recipient:</span> {{ mailLog.recipient_email }}
                            </p>
                        </div>

                        <!-- Details from Mail Section -->
                        <div class="prose max-w-none">
                            <h4 class="font-bold text-lg mb-2 text-gray-400">Details from Mail</h4>
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 space-y-3">
                                <div>
                                    <strong class="text-gray-600 font-medium">Leave Period:</strong>
                                    <p class="mt-1 text-gray-900">{{ mailLog.details.leave_period }}</p>
                                </div>
                                <div>
                                    <strong class="text-gray-600 font-medium">Reason Provided:</strong>
                                    <p class="mt-1 text-gray-900">{{ mailLog.details.reason }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Log Information Section -->
                        <div class="mt-6 pt-4 border-t">
                            <h4 class="font-bold text-lg mb-4 text-gray-400">Log Information</h4>
                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                                <div class="sm:col-span-2">
                                    <dt class="font-medium text-gray-500">Associated Leave Application ID</dt>
                                    <dd class="mt-1 text-gray-900 font-mono">{{ mailLog.log_info.application_id }}</dd>
                                </div>
                                <div class="sm:col-span-1">
                                    <dt class="font-medium text-gray-500">Event Type</dt>
                                    <dd class="mt-1 text-gray-900">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            {{ mailLog.log_info.event_type }}
                                        </span>
                                    </dd>
                                </div>
                                <div class="sm:col-span-1">
                                    <dt class="font-medium text-gray-500">Status</dt>
                                    <dd class="mt-1 text-gray-900">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full" :class="mailLog.log_info.status_class">
                                            {{ mailLog.log_info.status }}
                                        </span>
                                    </dd>
                                </div>
                                <div class="sm:col-span-2">
                                    <dt class="font-medium text-gray-500">Sent At</dt>
                                    <dd class="mt-1 text-gray-900">{{ mailLog.log_info.sent_at }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

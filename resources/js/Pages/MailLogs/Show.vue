<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

// The 'mailLog' prop is passed from your MailLogController.
const props = defineProps({
    mailLog: Object,
});

// Helper function to format dates.
const formatDate = (dateString) => {
    if (!dateString) return 'Not available';
    const options = {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    };
    return new Date(dateString).toLocaleString('en-US', options);
};

// Helper function to format the 'event_type'.
const formatEventType = (eventType) => {
    if (!eventType) return 'Unknown';
    return eventType.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};
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

                        <!-- subject -->
                        <div class="border-b pb-4 mb-6">
                            <h3 class="text-2xl font-bold text-gray-900 leading-tight">{{ mailLog.subject }}</h3>
                            <!-- recipient_email -->
                            <p class="mt-2 text-sm text-gray-600">
                                <span class="font-semibold">Recipient:</span> {{ mailLog.recipient_email }}
                            </p>
                        </div>

                        <!-- Main Content Fields (Formatted as Requested) -->
                        <div class="prose max-w-none">
                            <h4 class="font-bold text-lg mb-2">Details from Mail</h4>
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 space-y-3">
                                <!-- Leave Period -->
                                <div>
                                    <strong class="text-gray-600 font-medium">Leave Period:</strong>
                                    <p class="mt-1 text-gray-900">{{ mailLog.leave_period || 'Not available' }}</p>
                                </div>
                                <!-- Reason -->
                                <div>
                                    <strong class="text-gray-600 font-medium">Reason Provided:</strong>
                                    <p class="mt-1 text-gray-900">{{ mailLog.reason || 'Not available' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- All Other Log Metadata -->
                        <div class="mt-6 pt-4 border-t">
                            <h4 class="font-bold text-lg mb-3">Log Information</h4>
                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                                <!-- leave_application_id -->
                                <div class="sm:col-span-2">
                                    <dt class="font-medium text-gray-500">Associated Leave Application ID</dt>
                                    <dd class="mt-1 text-gray-900 font-mono">{{ mailLog.leave_application_id || 'Not available' }}</dd>
                                </div>
                                <!-- event_type -->
                                <div class="sm:col-span-1">
                                    <dt class="font-medium text-gray-500">Event Type</dt>
                                    <dd class="mt-1 text-gray-900">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            {{ formatEventType(mailLog.event_type) }}
                                        </span>
                                    </dd>
                                </div>
                                <!-- status -->
                                <div class="sm:col-span-1">
                                    <dt class="font-medium text-gray-500">Status</dt>
                                    <dd class="mt-1 text-gray-900">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                                              :class="{
                                                  'bg-green-100 text-green-800': mailLog.status === 'sent',
                                                  'bg-red-100 text-red-800': mailLog.status === 'failed',
                                                  'bg-yellow-100 text-yellow-800': mailLog.status !== 'sent' && mailLog.status !== 'failed'
                                              }">
                                            {{ mailLog.status ? mailLog.status.charAt(0).toUpperCase() + mailLog.status.slice(1) : 'Unknown' }}
                                        </span>
                                    </dd>
                                </div>
                                <!-- sent_at -->
                                <div class="sm:col-span-1">
                                    <dt class="font-medium text-gray-500">Sent At</dt>
                                    <dd class="mt-1 text-gray-900">{{ formatDate(mailLog.sent_at) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

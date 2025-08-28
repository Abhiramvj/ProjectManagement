<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link } from '@inertiajs/vue3';
import { defineProps } from 'vue';

// The 'mailLogs' prop is passed directly from our MailLogController.
// It's an object containing the log data, pagination links, etc.
const props = defineProps({
    mailLogs: Object,
});

// Helper function to format the ISO date from MongoDB into a readable format.
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString(undefined, options);
};
</script>

<template>
    <Head title="Mail Logs" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Mail Activity Logs</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recipient</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sent At</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-if="mailLogs.data.length === 0">
                                        <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No mail has been sent yet.</td>
                                    </tr>
                                    <tr v-for="log in mailLogs.data" :key="log._id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ log.recipient_email }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <Link
                                                    :href="route('mail-logs.show', log.id)"
                                                     class="text-blue-600 hover:underline" >
                                            {{ log.subject }}
                                            </Link>
                                            </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 hover:bg-blue-200">
                                                 {{ log.event_type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) }}
                                             </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                {{ log.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(log.sent_at) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Links -->
                        <div v-if="mailLogs.links.length > 3" class="mt-6 flex justify-between items-center">
  <Pagination :links="mailLogs.links" />
</div>


                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

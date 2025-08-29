<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    logs: Object,
    employees: Array,
    filters: Object,
});

const selectedEmployee = ref(props.filters.employee_id || '');

watch(selectedEmployee, (value) => {
    router.get(
        route('leave.logs'),
        { employee_id: value || undefined },
        {
            preserveState: true,
            replace: true,
        },
    );
});

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString('en-US', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
};

const getActionClass = (action) => {
    const classes = {
        created: 'bg-blue-100 text-blue-800',
        approved: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800',
        cancelled: 'bg-yellow-100 text-yellow-800',
        balance_adjusted: 'bg-purple-100 text-purple-800',
        comp_off_credited: 'bg-indigo-100 text-indigo-800',
        balance_accrued: 'bg-sky-100 text-sky-800',
    };
    return classes[action] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <Head title="Leave Audit Logs" />
    <AuthenticatedLayout>
        <div class="p-4 sm:p-6 lg:p-8">
            <div class="mx-auto max-w-7xl">
                <div class="mb-6 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-800">
                            Leave Audit Logs
                        </h1>
                        <p class="mt-1 text-sm text-gray-500">
                            A complete history of all leave-related actions.
                        </p>
                    </div>
                    <div>
                        <select
                            v-model="selectedEmployee"
                            class="mt-2 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:mt-0 sm:w-64"
                        >
                            <option value="">All Employees</option>
                            <option
                                v-for="employee in employees"
                                :key="employee.id"
                                :value="employee.id"
                            >
                                {{ employee.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <div
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                >
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Timestamp
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Employee
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Action By
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Action
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Description & Details
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr v-if="logs.data.length === 0">
                                    <td
                                        colspan="5"
                                        class="px-6 py-12 text-center text-sm text-gray-500"
                                    >
                                        No logs found for the selected criteria.
                                    </td>
                                </tr>
                                <tr
                                    v-for="log in logs.data"
                                    :key="log.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm text-gray-600"
                                    >
                                        {{ formatDate(log.created_at) }}
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900"
                                    >
                                        {{ log.user.name }}
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm text-gray-600"
                                    >
                                        {{ log.actor?.name || 'System' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span
                                            class="rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize"
                                            :class="getActionClass(log.action)"
                                        >
                                            {{ log.action.replace(/_/g, ' ') }}
                                        </span>
                                    </td>
                                    <td
                                        class="max-w-md px-6 py-4 text-sm text-gray-700"
                                    >
                                        <p>{{ log.description }}</p>
                                        <!-- <div v-if="log.details && log.details.old_balance !== undefined" class="text-xs text-gray-500 mt-1">
                                            Balance changed from {{ log.details.old_balance }} to {{ log.details.new_balance }}.
                                        </div> -->
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="logs.links && logs.links.length > 3" class="mt-6">
                    <Pagination :links="logs.links" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

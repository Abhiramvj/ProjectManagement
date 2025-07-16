<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    leaveRequests: Array,
    canManage: Boolean, // Passed from controller
});

const form = useForm({
    start_date: '',
    end_date: '',
    reason: '',
});

const submitApplication = () => {
    form.post(route('leave.store'), {
        onSuccess: () => form.reset(),
    });
};

const updateStatus = (request, newStatus) => {
    router.patch(route('leave.update', { leave_application: request.id }), {
        status: newStatus
    }, {
        preserveScroll: true
    });
};

const statusClass = (status) => {
    if (status === 'approved') return 'bg-green-100 text-green-800';
    if (status === 'rejected') return 'bg-red-100 text-red-800';
    return 'bg-yellow-100 text-yellow-800';
};
</script>

<template>
    <Head title="Leave Applications" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Leave Applications</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Application Form (Only for non-HR users) -->
                <div v-if="!canManage" class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">Submit a New Leave Request</h2>
                        </header>
                        <form @submit.prevent="submitApplication" class="mt-6 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="start_date" value="Start Date" />
                                    <TextInput id="start_date" type="date" class="mt-1 block w-full" v-model="form.start_date" required />
                                    <InputError class="mt-2" :message="form.errors.start_date" />
                                </div>
                                <div>
                                    <InputLabel for="end_date" value="End Date" />
                                    <TextInput id="end_date" type="date" class="mt-1 block w-full" v-model="form.end_date" required />
                                    <InputError class="mt-2" :message="form.errors.end_date" />
                                </div>
                            </div>
                            <div>
                                <InputLabel for="reason" value="Reason for Leave" />
                                <textarea id="reason" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" v-model="form.reason" rows="4" required></textarea>
                                <InputError class="mt-2" :message="form.errors.reason" />
                            </div>
                            <div class="flex items-center gap-4">
                                <PrimaryButton :disabled="form.processing">Submit Request</PrimaryButton>
                            </div>
                        </form>
                    </section>
                </div>

                <!-- List of Requests -->
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <section>
                         <header>
                            <h2 class="text-lg font-medium text-gray-900">{{ canManage ? 'All Employee Leave Requests' : 'My Leave Requests' }}</h2>
                        </header>
                        <div class="mt-6 space-y-4">
                            <div v-if="!leaveRequests.length" class="text-gray-500">No applications found.</div>
                            <div v-for="request in leaveRequests" :key="request.id" class="p-4 border rounded-lg">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p v-if="canManage" class="font-bold text-gray-800">{{ request.user.name }}</p>
                                        <p class="text-sm text-gray-600">
                                            <span class="font-semibold">From:</span> {{ request.start_date }}
                                            <span class="font-semibold">To:</span> {{ request.end_date }}
                                        </p>
                                        <p class="mt-2 text-sm text-gray-800">{{ request.reason }}</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="px-3 py-1 text-xs font-semibold rounded-full" :class="statusClass(request.status)">{{ request.status.toUpperCase() }}</span>
                                    </div>
                                </div>
                                <!-- HR Action Buttons -->
                                <div v-if="canManage && request.status === 'pending'" class="mt-4 pt-4 border-t flex gap-2">
                                     <button @click="updateStatus(request, 'approved')" class="px-3 py-1 text-xs font-semibold text-white bg-green-600 rounded-md hover:bg-green-700">Approve</button>
                                     <button @click="updateStatus(request, 'rejected')" class="px-3 py-1 text-xs font-semibold text-white bg-red-600 rounded-md hover:bg-red-700">Reject</button>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

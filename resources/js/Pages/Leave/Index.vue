<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import Datepicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import { ref, watch } from 'vue'

const formDates = ref([null, null])

watch(formDates, ([start, end]) => {
  form.start_date = start ? start.toISOString().split('T')[0] : ''
  form.end_date = end ? end.toISOString().split('T')[0] : ''
})

// Example: highlight previously applied dates (optional)
const disabledDates = ref([]) // If needed, disable some days here


const props = defineProps({
    leaveRequests: Array,
    canManage: Boolean, // Passed from controller
    highlightedDates: Array,
});

const form = useForm({
    start_date: '',
    end_date: '',
    reason: '',
    leave_type: '',
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

const cancelLeave = (request) => {
    if (confirm('Are you sure you want to cancel this leave request?')) {
        router.delete(route('leave.cancel', { leave_application: request.id }), {
            preserveScroll: true,
        });
    }
};

const highlightedDates = ref(
  props.highlightedDates.map(dateStr => new Date(dateStr))
)
if (!form.start_date && !form.end_date) {
  const today = new Date()
  formDates.value = [today, today]
}



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
                            <div>
  <InputLabel value="Select Leave Date Range" />
  <Datepicker
  v-model="formDates"
  range
  :highlighted="highlightedDates"
  :disabled-dates="disabledDates"
  :min-date="new Date()"
  :clearable="false"
  placeholder="Select Leave Range"
/>
  <InputError class="mt-2" :message="form.errors.start_date || form.errors.end_date" />
</div>

                            <div>
                                <InputLabel for="leave_type" value="Leave Type" />
                                <select id="leave_type" v-model="form.leave_type" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    <option disabled value="">-- Select Leave Type --</option>
                                    <option value="casual">Casual Leave</option>
                                    <option value="sick">Sick Leave</option>
                                    <option value="paid">Paid Leave</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.leave_type" />
                            </div>
                            <div>
                                <InputLabel for="reason" value="Reason for Leave" />
                                <textarea id="reason" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" v-model="form.reason" rows="4" required></textarea>
                                <InputError class="mt-2" :message="form.errors.reason" />
                            </div>
                            <div class="flex items-center gap-4">
                                <PrimaryButton :disabled="form.processing">Submit Request</PrimaryButton>
                            </div>
                            <InputError :message="form.errors.leave" />

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
                                <div class="flex justify-between items-start gap-4">
                                    <!-- Left: Leave details -->
                                    <div class="flex-1">
                                        <p v-if="canManage" class="font-bold text-gray-800">{{ request.user.name }}</p>
                                        <p class="text-sm text-gray-600">
                                            <span class="font-semibold">From:</span> {{ request.start_date }} 
                                            <span class="font-semibold">To:</span> {{ request.end_date }}
                                        </p>
                                        <p class="text-sm text-indigo-600" v-if="request.leave_type">
                                            <span class="font-semibold">Type:</span>
                                            {{ request.leave_type.charAt(0).toUpperCase() + request.leave_type.slice(1) }} Leave
                                        </p>
                                        <p v-else class="text-sm text-gray-400 italic">No leave type</p>
                                        <p class="mt-2 text-sm text-gray-800">{{ request.reason }}</p>
                                    </div>

                                    <!-- Right: Status and Cancel -->
                                    <div class="flex flex-col items-end gap-2">
                                        <span class="px-3 py-1 text-xs font-semibold rounded-full" :class="statusClass(request.status)">
                                        {{ request.status.toUpperCase() }}
                                        </span>

                                        <!-- Cancel Button -->
                                        <button
                                            v-if="!canManage && request.status === 'pending'"
                                            @click="cancelLeave(request)"
                                            class="px-3 py-1 text-xs font-semibold text-white bg-gray-600 rounded-md hover:bg-gray-700"
                                        >
                                            Cancel Request
                                        </button>
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
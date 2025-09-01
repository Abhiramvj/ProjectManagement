<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { ref, watch } from 'vue';

// Props
const props = defineProps({
    leaveRequests: Object,
    filters: Object,
});

// Filter options
const statusOptions = [
    { label: 'All', value: '' },
    { label: 'Pending', value: 'pending' },
    { label: 'Approved', value: 'approved' },
    { label: 'Rejected', value: 'rejected' },
];
const leaveTypeOptions = [
    { label: 'All', value: '' },
    { label: 'Annual', value: 'annual' },
    { label: 'Sick', value: 'sick' },
    { label: 'Personal', value: 'personal' },
    { label: 'Emergency', value: 'emergency' },
    { label: 'Maternity', value: 'maternity' },
    { label: 'Paternity', value: 'paternity' },
];

// Filters
const statusFilter = ref(props.filters.status || '');
const leaveTypeFilter = ref(props.filters.leave_type || '');
function applyFilters() {
    router.get(
        route('leave.fullRequests'),
        {
            status: statusFilter.value || undefined,
            leave_type: leaveTypeFilter.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

// Status helpers
const statusInfo = (status) => ({
    pending: { text: 'Pending', color: 'yellow', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
    approved: { text: 'Approved', color: 'green', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
    rejected: { text: 'Rejected', color: 'red', icon: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z' },
}[status] || { text: 'Unknown', color: 'gray', icon: '' });

const statusCardBorderClass = (status) => ({
    pending: 'border-l-yellow-400',
    approved: 'border-l-green-500',
    rejected: 'border-l-red-500',
}[status] || 'border-l-gray-300');

// Upload state
const uploadingRequest = ref(null);
const selectedFiles = ref({});
const uploadProcessing = ref(false);
const uploadError = ref('');

function handleFileChange(req, event) {
    selectedFiles.value[req.id] = event.target.files[0];
}

function submitUpload(req) {
    const file = selectedFiles.value[req.id];
    if (!file) return;

    uploadingRequest.value = req.id;
    uploadProcessing.value = true;
    uploadError.value = '';

    const formData = new FormData();
    formData.append('supporting_document', file);

    router.post(
        route('leave.uploadDocumentInertia', { leave_application: req.id }), // POST route
        formData,
        {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                selectedFiles.value[req.id] = null;
                uploadingRequest.value = null;
            },
            onError: (errors) => {
                uploadError.value = errors.supporting_document || 'Upload failed.';
            },
            onFinish: () => {
                uploadProcessing.value = false;
            },
        }
    );
}


// Edit/Cancel modal logic
const isEditModalVisible = ref(false);
const editingRequest = ref(null);
const editingReason = ref('');
const editProcessing = ref(false);

function openEditModal(req) {
    editingRequest.value = req;
    editingReason.value = req.reason;
    isEditModalVisible.value = true;
}

function closeEditModal() {
    isEditModalVisible.value = false;
}

function submitEditReason() {
    if (!editingRequest.value) return;
    editProcessing.value = true;
    router.patch(
        route('leave.updateReason', { leave_application: editingRequest.value.id }),
        { reason: editingReason.value },
        {
            preserveScroll: true,
            onSuccess: () => closeEditModal(),
            onFinish: () => editProcessing.value = false,
        },
    );
}

function cancelRequest(req) {
    if (confirm('Are you sure you want to cancel this leave request?')) {
        router.delete(route('leave.cancel', { leave_application: req.id }), {
            preserveScroll: true,
        });
    }
}

// Rejection reason modal
const isReasonModalVisible = ref(false);
const rejectionReasonToShow = ref('');
function showRejectionReason(reason) {
    rejectionReasonToShow.value = reason;
    isReasonModalVisible.value = true;
}
function closeReasonModal() {
    isReasonModalVisible.value = false;
}
watch(isReasonModalVisible, (visible) => { document.body.style.overflow = visible ? 'hidden' : ''; });
watch(isEditModalVisible, (visible) => { document.body.style.overflow = visible ? 'hidden' : ''; });

// Formatting helpers
function formatLeaveDays(days) {
    const num = Number(days);
    if (isNaN(num)) return '0';
    return num % 1 === 0 ? num.toString() : num.toFixed(1);
}
const formatDate = (dateString) =>
    new Date(dateString).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
</script>

<template>
<Head title="My Leave Requests" />
<AuthenticatedLayout>
    <div class="mx-auto max-w-7xl p-4 font-sans sm:p-6 lg:p-8">
        <!-- Header -->
        <header class="mb-8">
            <h1 class="text-4xl font-bold text-slate-900">My Leave Requests</h1>
            <p class="mt-2 text-slate-600">View and manage your past and upcoming leave applications.</p>
        </header>

        <!-- Filters -->
        <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col items-center gap-4 sm:flex-row">
                <div class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2zM3 16a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2z" />
                    </svg>
                    Filter by:
                </div>
                <select v-model="statusFilter" @change="applyFilters" class="form-select w-full sm:w-auto">
                    <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
                        Status: {{ opt.label }}
                    </option>
                </select>
                <select v-model="leaveTypeFilter" @change="applyFilters" class="form-select w-full sm:w-auto">
                    <option v-for="opt in leaveTypeOptions" :key="opt.value" :value="opt.value">
                        Type: {{ opt.label }}
                    </option>
                </select>
            </div>
        </div>

        <!-- Leave Requests -->
        <div class="space-y-4">
            <div v-if="leaveRequests.data.length===0" class="rounded-xl border border-slate-200 bg-white p-12 text-center shadow-sm">
                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-slate-900">No leave requests found</h3>
                <p class="mt-1 text-sm text-slate-500">Try adjusting your filters.</p>
            </div>

            <div v-for="req in leaveRequests.data" :key="req.id" :class="statusCardBorderClass(req.status)"
                class="rounded-lg border border-l-4 border-slate-200 bg-white shadow-sm transition-all hover:border-l-blue-500 hover:shadow-md"
                :data-req-id="req.id">
                
                <div class="grid grid-cols-1 items-center gap-4 p-5 md:grid-cols-12">
                    <!-- Date & Type -->
                    <div class="md:col-span-3">
                        <p class="font-bold text-slate-800">
                            {{ formatDate(req.start_date) }}
                            <span v-if="req.end_date !== req.start_date">- {{ formatDate(req.end_date) }}</span>
                        </p>
                        <p class="text-sm text-slate-600">
                            {{ formatLeaveDays(req.leave_days) }} day<span v-if="req.leave_days!=1">s</span>,
                            <span class="capitalize">{{ req.leave_type }}</span>
                        </p>
                    </div>

                    <!-- Reason -->
                    <div class="md:col-span-4">
                        <p class="truncate text-sm text-slate-700" :title="req.reason">{{ req.reason }}</p>
                        <p class="mt-1 text-xs text-slate-400">Requested: {{ new Date(req.created_at).toLocaleString() }}</p>

                        <!-- Upload section -->
                       <div v-if="req.leave_type === 'sick'">
  <div v-if="!req.supporting_document">
    <input
      type="file"
      accept=".pdf,.jpg,.jpeg,.png"
      @change="(e) => handleFileChange(req, e)"
      :disabled="uploadProcessing"
      class="form-input"
    />
    <button
      type="button"
      v-if="selectedFiles[req.id]"
      @click="submitUpload(req)"
      :disabled="uploadProcessing"
      class="btn-primary-sm"
    >
      {{ uploadProcessing ? 'Uploading...' : 'Upload Document' }}
    </button>
    <p v-if="uploadError && uploadingRequest === req.id" class="text-red-600 text-sm">{{ uploadError }}</p>
  </div>

  <div v-else>
    <a :href="req.supporting_document" target="_blank" class="text-green-600 underline">
      View uploaded document
    </a>
  </div>
</div>



                        <p v-if="uploadError && uploadingRequest==req.id" class="text-red-600 text-sm">{{ uploadError }}</p>
                    </div>

                    <!-- Status -->
                    <div class="flex items-center gap-2 md:col-span-2">
                        <span :class="`text-${statusInfo(req.status).color}-600`">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="statusInfo(req.status).icon" />
                            </svg>
                        </span>
                        <p :class="`font-semibold text-${statusInfo(req.status).color}-700`">{{ statusInfo(req.status).text }}</p>
                        <button v-if="req.status==='rejected' && req.rejection_reason" @click="showRejectionReason(req.rejection_reason)" class="ml-1 text-xs text-red-600 hover:underline">(reason)</button>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-start gap-3 md:col-span-3 md:justify-end">
                        <template v-if="req.status==='pending'">
                            <button @click="openEditModal(req)" class="btn-primary-sm text-blue-600">Edit</button>
                            <button @click="cancelRequest(req)" class="btn-danger-sm text-red-600">Cancel</button>
                        </template>
                        <span v-else class="text-sm text-slate-400">No actions available</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <Pagination :links="leaveRequests.links" />
    </div>

    <!-- Edit Modal -->
    <teleport to="body">
        <div v-if="isEditModalVisible" @click.self="closeEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <form @submit.prevent="submitEditReason" class="flex w-full max-w-md flex-col gap-4 rounded-xl bg-white p-6 shadow-2xl">
                <div class="mb-2 flex items-center gap-3">
                    <div class="rounded-full bg-blue-100 p-2">
                        <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-slate-800">Edit Reason</h2>
                </div>
                <textarea v-model="editingReason" rows="4" required class="form-textarea" :disabled="editProcessing" maxlength="500"></textarea>
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="closeEditModal" class="btn-secondary">Cancel</button>
                    <PrimaryButton type="submit" :disabled="editProcessing">{{ editProcessing ? 'Saving...' : 'Save Changes' }}</PrimaryButton>
                </div>
            </form>
        </div>
    </teleport>

    <!-- Rejection Modal -->
    <teleport to="body">
        <div v-if="isReasonModalVisible" @click.self="closeReasonModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl" role="dialog">
                <div class="mb-4 flex items-center gap-3">
                    <div class="rounded-full bg-red-100 p-2">
                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800">Reason for Rejection</h3>
                </div>
                <div class="max-h-[50vh] overflow-y-auto rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <p class="whitespace-pre-line break-words text-slate-700">{{ rejectionReasonToShow }}</p>
                </div>
                <div class="mt-6 flex justify-end">
                    <button @click="closeReasonModal" class="btn-secondary">Close</button>
                </div>
            </div>
        </div>
    </teleport>
</AuthenticatedLayout>
</template>

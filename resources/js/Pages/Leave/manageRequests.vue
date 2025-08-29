<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { ref } from 'vue';

// Props: Paginator object of leaveRequests, no canManage as always admin/HR view
const props = defineProps({
    leaveRequests: Object,
});

// Status display config
const statusConfig = {
    approved: { class: 'bg-green-100 text-green-800', icon: '✅' },
    rejected: { class: 'bg-red-100 text-red-800', icon: '❌' },
    pending: { class: 'bg-yellow-100 text-yellow-800', icon: '⏳' },
};

// Format date string nicely
function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return 'Invalid Date';
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

// Format leave days with halves (e.g., 1.5)
function formatLeaveDays(days) {
    const num = Number(days);
    if (isNaN(num)) return '0';
    if (num % 1 === 0.5) return `${Math.floor(num)}.5`;
    return num % 1 === 0 ? num.toString() : num.toFixed(1);
}

// Upload Document modal state
const isUploadModalVisible = ref(false);
const uploadFile = ref(null);
const uploadErrors = ref({});
const uploadProcessing = ref(false);
const currentUploadLeaveId = ref(null);

function openUploadModal(leaveId) {
    currentUploadLeaveId.value = leaveId;
    uploadFile.value = null;
    uploadErrors.value = {};
    uploadProcessing.value = false;
    isUploadModalVisible.value = true;
}

function closeUploadModal() {
    isUploadModalVisible.value = false;
    currentUploadLeaveId.value = null;
    uploadFile.value = null;
    uploadErrors.value = {};
    uploadProcessing.value = false;
}

function onUploadFileChange(event) {
    uploadFile.value = event.target.files[0];
}

function submitUpload() {
    if (!uploadFile.value) {
        uploadErrors.value.supporting_document = 'Please select a file.';
        return;
    }
    uploadProcessing.value = true;
    const formData = new FormData();
    formData.append('supporting_document', uploadFile.value);

    router.post(
        route('leave.uploadDocument', {
            leave_application: currentUploadLeaveId.value,
        }),
        formData,
        {
            preserveScroll: true,
            headers: { 'Content-Type': 'multipart/form-data' },
            onSuccess: () => {
                uploadProcessing.value = false;
                closeUploadModal();
                router.reload();
            },
            onError: (errors) => {
                uploadProcessing.value = false;
                uploadErrors.value = errors;
            },
        },
    );
}

// Leave Details modal state
const isDetailsModalOpen = ref(false);
const selectedLeave = ref(null);

function openDetailsModal(request) {
    selectedLeave.value = request;
    isDetailsModalOpen.value = true;
}

function closeDetailsModal() {
    selectedLeave.value = null;
    isDetailsModalOpen.value = false;
}

// Approve/Reject leave actions
const updateStatus = (request, newStatus) => {
    router.patch(
        route('leave.update', { leave_application: request.id }),
        { status: newStatus },
        { preserveScroll: true },
    );
};

const isRejectModalOpen = ref(false);
const rejectReason = ref('');
const rejectProcessing = ref(false);
const rejectingLeave = ref(null);

function openRejectModal(request) {
    rejectingLeave.value = request;
    rejectReason.value = '';
    isRejectModalOpen.value = true;
}
function closeRejectModal() {
    isRejectModalOpen.value = false;
    rejectingLeave.value = null;
    rejectReason.value = '';
    rejectProcessing.value = false;
}

function submitRejection() {
    if (!rejectingLeave.value) return;
    rejectProcessing.value = true;
    router.patch(
        route('leave.update', { leave_application: rejectingLeave.value.id }),
        { status: 'rejected', rejection_reason: rejectReason.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                rejectProcessing.value = false;
                closeRejectModal();
                closeDetailsModal();
                router.reload();
            },
            onError: () => {
                rejectProcessing.value = false;
            },
        },
    );
}
</script>

<template>
    <Head title="Leave Logs" />
    <AuthenticatedLayout>
        <div class="mx-auto max-w-6xl space-y-8 p-6 font-sans">
            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <h1
                    class="text-3xl font-extrabold tracking-tight text-gray-900"
                >
                    Leave Applications
                </h1>
            </div>

            <!-- Empty State -->
            <div
                v-if="leaveRequests.data.length === 0"
                class="rounded-xl border border-gray-200 bg-white p-16 text-center shadow"
            >
                <p class="text-lg text-gray-500">No leave requests found.</p>
            </div>

            <!-- Table -->
            <div
                v-else
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-md"
            >
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wide text-gray-600"
                            >
                                Employee
                            </th>
                            <th
                                class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wide text-gray-600"
                            >
                                Status
                            </th>
                            <th
                                class="px-6 py-4 text-center text-sm font-semibold uppercase tracking-wide text-gray-600"
                            >
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr
                            v-for="request in leaveRequests.data"
                            :key="request.id"
                            class="transition hover:bg-gray-50"
                        >
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-100 font-semibold text-indigo-700"
                                    >
                                        {{
                                            request.user?.name
                                                ?.charAt(0)
                                                ?.toUpperCase() || '?'
                                        }}
                                    </div>
                                    <span
                                        class="truncate font-medium text-gray-900"
                                        >{{
                                            request.user?.name || 'Unknown'
                                        }}</span
                                    >
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span
                                    :class="statusConfig[request.status].class"
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                                >
                                    <span class="mr-1">{{
                                        statusConfig[request.status].icon
                                    }}</span>
                                    {{
                                        request.status.charAt(0).toUpperCase() +
                                        request.status.slice(1)
                                    }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button
                                    @click="openDetailsModal(request)"
                                    class="text-sm font-semibold text-indigo-600 transition hover:text-indigo-800"
                                >
                                    View Details
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="border-t border-gray-200 bg-gray-50 px-4 py-3">
                    <Pagination :links="leaveRequests.links" />
                </div>
            </div>

            <!-- Details Modal -->
            <div
                v-if="isDetailsModalOpen"
                @click.self="closeDetailsModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
            >
                <div
                    class="flex max-h-[90vh] w-full max-w-lg flex-col rounded-2xl bg-white shadow-2xl"
                >
                    <!-- Header -->
                    <div
                        class="flex items-center justify-between border-b px-6 py-4"
                    >
                        <h2 class="text-lg font-bold text-gray-800">
                            Leave Application
                        </h2>
                        <button
                            class="text-xl text-gray-400 hover:text-gray-600"
                            @click="closeDetailsModal"
                        >
                            ×
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="space-y-4 overflow-y-auto p-6 text-sm">
                        <!-- Employee -->
                        <div class="flex items-start gap-3">
                            <span class="text-indigo-500">👤</span>
                            <div>
                                <span class="font-medium text-gray-900"
                                    >Employee:</span
                                >
                                <p class="text-gray-700">
                                    {{ selectedLeave.user?.name }}
                                </p>
                            </div>
                        </div>

                        <!-- Leave Type -->
                        <div class="flex items-start gap-3">
                            <span class="text-blue-500">📌</span>
                            <div>
                                <span class="font-medium text-gray-900"
                                    >Leave Type:</span
                                >
                                <p class="capitalize text-gray-700">
                                    {{ selectedLeave.leave_type }}
                                </p>
                            </div>
                        </div>

                        <!-- Dates -->
                        <div class="flex items-start gap-3">
                            <span class="text-green-500">📅</span>
                            <div>
                                <span class="font-medium text-gray-900"
                                    >Dates:</span
                                >
                                <p class="text-gray-700">
                                    {{ formatDate(selectedLeave.start_date) }}
                                    <span
                                        v-if="
                                            selectedLeave.start_date !==
                                            selectedLeave.end_date
                                        "
                                    >
                                        –
                                        {{ formatDate(selectedLeave.end_date) }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <!-- Duration -->
                        <div class="flex items-start gap-3">
                            <span class="text-purple-500">⏳</span>
                            <div>
                                <span class="font-medium text-gray-900"
                                    >Duration:</span
                                >
                                <p class="text-gray-700">
                                    {{
                                        formatLeaveDays(
                                            selectedLeave.leave_days,
                                        )
                                    }}
                                    days
                                </p>
                            </div>
                        </div>

                        <!-- Sessions -->
                        <div class="flex items-start gap-3">
                            <span class="text-orange-500">🕒</span>
                            <div>
                                <span class="font-medium text-gray-900"
                                    >Sessions:</span
                                >
                                <p class="capitalize text-gray-700">
                                    {{
                                        selectedLeave.day_type === 'half'
                                            ? selectedLeave.start_half_session +
                                              (selectedLeave.start_date !==
                                              selectedLeave.end_date
                                                  ? ' → ' +
                                                    selectedLeave.end_half_session
                                                  : '')
                                            : 'Full Day'
                                    }}
                                </p>
                            </div>
                        </div>

                        <!-- Reason -->
                        <div class="flex items-start gap-3">
                            <span class="text-pink-500">📝</span>
                            <div class="flex-1">
                                <span class="font-medium text-gray-900"
                                    >Reason:</span
                                >
                                <p
                                    class="mt-1 whitespace-pre-wrap rounded-md border bg-gray-50 p-2 text-gray-700"
                                >
                                    {{ selectedLeave.reason }}
                                </p>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="flex items-start gap-3">
                            <span class="text-gray-500">📊</span>
                            <div>
                                <span class="font-medium text-gray-900"
                                    >Status:</span
                                >
                                <span
                                    :class="{
                                        'rounded bg-yellow-100 px-2 py-0.5 text-xs text-yellow-800':
                                            selectedLeave.status === 'pending',
                                        'rounded bg-green-100 px-2 py-0.5 text-xs text-green-800':
                                            selectedLeave.status === 'approved',
                                        'rounded bg-red-100 px-2 py-0.5 text-xs text-red-800':
                                            selectedLeave.status === 'rejected',
                                    }"
                                    class="ml-2"
                                >
                                    {{ selectedLeave.status }}
                                </span>
                            </div>
                        </div>

                        <!-- Rejection Reason -->
                        <div
                            v-if="
                                selectedLeave.status === 'rejected' &&
                                selectedLeave.rejection_reason
                            "
                            class="flex items-start gap-3"
                        >
                            <span class="text-red-500">❌</span>
                            <div class="flex-1">
                                <span class="font-medium text-red-700"
                                    >Rejection:</span
                                >
                                <p
                                    class="mt-1 rounded border border-red-200 bg-red-50 p-2 text-red-700"
                                >
                                    {{ selectedLeave.rejection_reason }}
                                </p>
                            </div>
                        </div>

                        <!-- Document -->
                        <div class="flex items-start gap-3">
                            <span class="text-indigo-500">📂</span>
                            <div>
                                <span class="font-medium text-gray-900"
                                    >Supporting Document:</span
                                >
                                <span
                                    v-if="
                                        selectedLeave.supporting_document_path
                                    "
                                >
                                    <a
                                        :href="`/storage/${selectedLeave.supporting_document_path}`"
                                        target="_blank"
                                        class="ml-2 text-indigo-600 hover:underline"
                                    >
                                        View Document
                                    </a>
                                </span>
                                <span v-else class="ml-2 italic text-gray-500"
                                    >None</span
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div
                        v-if="selectedLeave?.status === 'pending'"
                        class="flex justify-end gap-3 border-t px-6 py-4"
                    >
                        <PrimaryButton
                            @click="
                                () => {
                                    updateStatus(selectedLeave, 'approved');
                                    closeDetailsModal();
                                }
                            "
                            class="bg-green-600 hover:bg-green-700"
                        >
                            ✅ Approve
                        </PrimaryButton>
                        <PrimaryButton
                            @click="
                                () => {
                                    openRejectModal(selectedLeave);
                                }
                            "
                            class="bg-red-600 hover:bg-red-700"
                        >
                            ❌ Reject
                        </PrimaryButton>
                    </div>
                </div>
            </div>

            <!-- Upload Document Modal -->
            <div
                v-if="isUploadModalVisible"
                @click.self="closeUploadModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
            >
                <form
                    @submit.prevent="submitUpload"
                    enctype="multipart/form-data"
                    class="w-full max-w-md space-y-4 rounded-xl bg-white p-6 shadow-xl"
                >
                    <h3 class="text-lg font-semibold text-gray-900">
                        Upload Document
                    </h3>
                    <input
                        type="file"
                        @change="onUploadFileChange"
                        accept=".pdf,.jpg,.jpeg,.png"
                        required
                        class="block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100"
                    />
                    <p
                        v-if="uploadErrors.supporting_document"
                        class="text-sm text-red-600"
                    >
                        {{ uploadErrors.supporting_document }}
                    </p>
                    <div class="flex justify-end gap-2">
                        <button
                            type="button"
                            @click="closeUploadModal"
                            class="rounded border border-gray-300 px-4 py-2 text-sm hover:bg-gray-100"
                        >
                            Cancel
                        </button>
                        <PrimaryButton
                            type="submit"
                            :disabled="uploadProcessing"
                            >{{
                                uploadProcessing ? 'Uploading...' : 'Upload'
                            }}</PrimaryButton
                        >
                    </div>
                </form>
            </div>

            <!-- Reject Reason Modal -->
            <div
                v-if="isRejectModalOpen"
                @click.self="closeRejectModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
            >
                <form
                    @submit.prevent="submitRejection"
                    class="w-full max-w-md space-y-4 rounded-xl bg-white p-6 shadow-2xl"
                >
                    <h2 class="text-lg font-semibold text-gray-900">
                        Reason for Rejection
                    </h2>
                    <textarea
                        v-model="rejectReason"
                        required
                        rows="3"
                        maxlength="500"
                        class="w-full rounded-md border-gray-300 p-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    ></textarea>
                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            @click="closeRejectModal"
                            class="rounded bg-gray-200 px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-300"
                        >
                            Cancel
                        </button>
                        <PrimaryButton
                            type="submit"
                            :disabled="rejectProcessing || !rejectReason"
                            class="bg-red-600 hover:bg-red-700"
                        >
                            {{ rejectProcessing ? 'Rejecting...' : 'Reject' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

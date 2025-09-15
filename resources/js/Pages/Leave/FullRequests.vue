<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head } from '@inertiajs/vue3';
import { ref, reactive, watch } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const props = defineProps({
    leaveRequests: Object,
    filters: Object,
});

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

const statusFilter = ref(props.filters.status || '');
const leaveTypeFilter = ref(props.filters.leave_type || '');

function applyFilters() {
    router.get(
        route('leave.fullRequests'),
        {
            status: statusFilter.value || undefined,
            leave_type: leaveTypeFilter.value || undefined,
        },
        { preserveState: true, replace: true }
    );
}

const statusInfo = status => ({
    pending: { 
        text: 'Pending', 
        color: 'yellow',
        badge: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800'
    },
    approved: { 
        text: 'Approved', 
        color: 'green',
        badge: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800'
    },
    rejected: { 
        text: 'Rejected', 
        color: 'red',
        badge: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800'
    },
}[status] || { 
    text: 'Unknown', 
    color: 'gray',
    badge: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800'
});

const statusCardBorderClass = status => ({
    pending: 'border-yellow-500',
    approved: 'border-green-500',
    rejected: 'border-red-500',
}[status] || 'border-gray-300');

const page = usePage();
const uploadingRequest = ref(null);
const selectedFiles = ref({});
const uploadProcessing = ref(false);
const uploadError = ref('');
const fileInputRefs = reactive({});

function handleFileChange(reqId, event) {
    const file = event.target.files[0];
    if (!file) { delete selectedFiles.value[reqId]; return; }
    const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
    if (!allowedTypes.includes(file.type)) { uploadError.value='Only PDF, JPG, JPEG, PNG allowed'; event.target.value=''; return; }
    if (file.size > 5*1024*1024) { uploadError.value='File must be <5MB'; event.target.value=''; return; }
    selectedFiles.value[reqId] = file;
    uploadError.value='';
}

function submitUpload(req) {
    const file = selectedFiles.value[req.id];
    if (!file) { uploadError.value = 'Please select a file'; return; }

    uploadingRequest.value = req.id;
    uploadProcessing.value = true;
    uploadError.value = '';

    const formData = new FormData();
    formData.append('supporting_document', file);

    router.post(route('leave.uploadDocumentInertia', { leave_application: req.id }), formData, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: (page) => {
            const updatedRequest = page.props.leaveRequests.data.find(l => l.id === req.id);
            if (updatedRequest) {
                Object.assign(selectedRequest.value, updatedRequest);
            }

            delete selectedFiles.value[req.id];
            if (fileInputRefs[req.id]) fileInputRefs[req.id].value = '';
            uploadingRequest.value = null;

            page.props.flash = { success: 'Document uploaded successfully ✅' };
        },
        onError: (errors) => { uploadError.value = errors.supporting_document || 'Upload failed'; },
        onFinish: () => { uploadProcessing.value = false; }
    });
}

function clearFileSelection(reqId) { 
    delete selectedFiles.value[reqId]; 
    if(fileInputRefs[reqId]) fileInputRefs[reqId].value=''; 
    uploadError.value=''; 
}

const isEditModalVisible = ref(false);
const editingRequest = ref(null);
const editingReason = ref('');
const editProcessing = ref(false);

function openEditModal(req) { 
    editingRequest.value=req; 
    editingReason.value=req.reason; 
    isEditModalVisible.value=true; 
}
function closeEditModal() { 
    isEditModalVisible.value=false; 
}

function submitEditReason() {
    if(!editingRequest.value) return;
    editProcessing.value = true;

    router.patch(
        route('leave.updateReason', { leave_application: editingRequest.value.id }),
        { reason: editingReason.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                if (selectedRequest.value && selectedRequest.value.id === editingRequest.value.id) {
                    selectedRequest.value.reason = editingReason.value;
                }
                closeEditModal();
            },
            onFinish: () => editProcessing.value = false
        }
    );
}

const cancelRequest = (request) => {
    if (!confirm("Are you sure you want to cancel this leave request?")) return;

    router.delete(route('leave.cancel', request.id), {
        onSuccess: () => {
            isDetailModalVisible.value = false;
            selectedRequest.value = null;
        },
        onError: (errors) => {
            console.error(errors);
        }
    });
};

const isDetailModalVisible = ref(false);
const selectedRequest = ref(null);
function openDetailModal(req) { 
    selectedRequest.value=req; 
    isDetailModalVisible.value=true; 
}
function closeDetailModal() { 
    isDetailModalVisible.value=false; 
}

const formatDate = dateString => new Date(dateString).toLocaleDateString('en-US',{year:'numeric',month:'short',day:'numeric'});
const formatDateRange = (startDate, endDate, startHalf, endHalf) => {
    let result = formatDate(startDate);
    if (startHalf) {
        result += ` (${startHalf === 'morning' ? 'Morning' : 'Afternoon'})`;
    }
    if (endDate !== startDate) {
        result += ` - ${formatDate(endDate)}`;
        if (endHalf) {
            result += ` (${endHalf === 'morning' ? 'Morning' : 'Afternoon'})`;
        }
    }
    return result;
};

function getFileName(url){ 
    if(!url) return ''; 
    return url.split('/').pop(); 
}
</script>

<template>
<Head title="My Leave Requests"/>
<AuthenticatedLayout>
<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 space-y-6">

    <!-- Header -->
    <header class="space-y-1">
        <h1 class="text-3xl font-bold text-gray-900">My Leave Requests</h1>
        <p class="text-sm text-gray-500">Minimal overview. Click "View Details" for more info.</p>
    </header>

    <!-- Filters -->
    <section class="bg-white rounded-lg shadow p-4 flex flex-col sm:flex-row gap-4 sm:items-center">
        <span class="font-semibold text-gray-700">Filter by:</span>
        <select v-model="statusFilter" @change="applyFilters" class="form-select rounded-md border-gray-300 w-40">
            <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
        </select>
        <select v-model="leaveTypeFilter" @change="applyFilters" class="form-select rounded-md border-gray-300 w-40">
            <option v-for="opt in leaveTypeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
        </select>
    </section>

    <!-- Leave Cards -->
    <div class="grid gap-4 text-black">
        <div v-for="req in leaveRequests.data" :key="req.id"
             class="bg-white rounded-xl shadow-md p-5 flex items-center justify-between border-l-4 cursor-pointer"
             :class="statusCardBorderClass(req.status)">
            <div>
                <p class="font-semibold">
                    {{ formatDateRange(req.start_date, req.end_date, req.start_half_session, req.end_half_session) }}
                </p>
                <p class="text-gray-500 text-sm capitalize">{{ req.leave_type }}</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-white text-sm font-medium px-3 py-1 rounded text-center min-w-[90px] inline-block"
                      :class="{
                          'bg-yellow-500': req.status==='pending',
                          'bg-green-600': req.status==='approved',
                          'bg-red-600': req.status==='rejected'
                      }">{{ statusInfo(req.status).text }}</span>
                <button @click="openDetailModal(req)" class="bg-indigo-50 text-indigo-700 px-3 py-1 rounded text-sm hover:bg-indigo-100">View Details</button>
            </div>
        </div>
    </div>

    <Pagination :links="leaveRequests.links"/>
</div>

<!-- Detail Modal -->
<teleport to="body">
    <div v-if="isDetailModalVisible" @click.self="closeDetailModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 font-sans">
        <div class="flex w-full max-w-2xl flex-col rounded-2xl bg-white shadow-2xl overflow-hidden animate-fadeIn">
            <!-- Header -->
            <header class="flex justify-between items-center border-b border-slate-200 px-6 py-4 bg-slate-50">
                <h2 class="text-lg font-bold text-slate-800">Leave Request Details</h2>
                <button @click="closeDetailModal" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </header>

            <!-- Body -->
            <div class="px-6 py-5 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-3 text-sm">
                        <p>
                            <span class="font-semibold text-slate-600">Date:</span>
                            <span class="text-slate-700">{{ formatDateRange(selectedRequest.start_date, selectedRequest.end_date, selectedRequest.start_half_session, selectedRequest.end_half_session) }}</span>
                        </p>
                        <p>
                            <span class="font-semibold text-slate-600">Type:</span> 
                            <span class="capitalize text-slate-700">{{ selectedRequest.leave_type }}</span>
                        </p>
                        <p>
                            <span class="font-semibold text-slate-600">Status:</span>
                            <span :class="statusInfo(selectedRequest.status).badge">
                                {{ statusInfo(selectedRequest.status).text }}
                            </span>
                        </p>
                        <p>
                            <span class="font-semibold text-slate-600">Requested At:</span> 
                            <span class="text-slate-700">{{ new Date(selectedRequest.created_at).toLocaleString() }}</span>
                        </p>
                    </div>
                    
                    <div v-if="selectedRequest.status==='rejected' && selectedRequest.rejection_reason" class="space-y-2">
                        <p class="font-semibold text-red-600">Rejection Reason:</p>
                        <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-red-700 text-sm">{{ selectedRequest.rejection_reason }}</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <p class="font-semibold text-slate-600">Reason:</p>
                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg">
                        <p class="text-slate-700 text-sm">{{ selectedRequest.reason }}</p>
                    </div>
                </div>

                <!-- Supporting Document -->
                <div v-if="selectedRequest.leave_type==='sick'" class="space-y-3">
                    <p class="font-semibold text-slate-600">Supporting Document</p>
                    
                    <div v-if="selectedRequest.supporting_document" class="flex items-center gap-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                        <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <a :href="selectedRequest.supporting_document" target="_blank" class="font-medium text-green-700 hover:text-green-800 hover:underline transition-colors">
                            View Document
                        </a>
                    </div>
                    
                    <div v-if="selectedRequest.status==='pending'" class="space-y-3 p-3 bg-slate-50 border border-slate-200 rounded-lg">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                            <input 
                                :ref="el => { if(el) fileInputRefs[selectedRequest.id]=el }" 
                                type="file" 
                                @change="e=>handleFileChange(selectedRequest.id,e)" 
                                accept=".pdf,.jpg,.jpeg,.png" 
                                class="file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-slate-300 rounded-md text-sm w-full sm:w-auto"
                            />
                            <div class="flex gap-2">
                                <button v-if="selectedFiles[selectedRequest.id]" @click="clearFileSelection(selectedRequest.id)" class="px-3 py-1.5 text-xs font-medium text-slate-600 border border-slate-300 rounded-md hover:bg-slate-100 transition-colors">
                                    Clear
                                </button>
                                <button 
                                    @click="submitUpload(selectedRequest)" 
                                    :disabled="uploadProcessing && uploadingRequest===selectedRequest.id" 
                                    class="px-4 py-1.5 text-xs font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-50 transition-colors min-w-[80px]"
                                >
                                    {{ uploadProcessing && uploadingRequest===selectedRequest.id ? 'Uploading...' : (selectedRequest.supporting_document?'Replace':'Upload') }}
                                </button>
                            </div>
                        </div>
                        <p v-if="uploadError" class="text-red-600 text-xs">{{ uploadError }}</p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <footer class="flex justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
                <button 
                    v-if="selectedRequest.status==='pending'" 
                    @click="openEditModal(selectedRequest)" 
                    class="px-3 py-1.5 text-xs font-semibold text-slate-700 border border-slate-300 rounded-md hover:bg-slate-100 transition-colors"
                >
                    Edit
                </button>
                <button 
                    v-if="selectedRequest.status==='pending'" 
                    @click="cancelRequest(selectedRequest)" 
                    class="px-3 py-1.5 text-xs font-semibold text-red-600 border border-red-300 rounded-md hover:bg-red-50 transition-colors"
                >
                    Cancel
                </button>
                <button 
                    @click="closeDetailModal" 
                    class="px-3 py-1.5 text-xs font-semibold text-slate-600 border border-slate-300 rounded-md hover:bg-slate-100 transition-colors"
                >
                    Close
                </button>
            </footer>
        </div>
    </div>
</teleport>

<!-- Edit Modal -->
<teleport to="body">
    <div v-if="isEditModalVisible" @click.self="closeEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 font-sans">
        <form @submit.prevent="submitEditReason" class="flex w-full max-w-md flex-col rounded-2xl bg-white shadow-2xl overflow-hidden animate-fadeIn">
            <!-- Header -->
            <header class="flex justify-between items-center border-b border-slate-200 px-6 py-4 bg-slate-50">
                <h2 class="text-lg font-bold text-slate-800">Edit Leave Reason</h2>
                <button type="button" @click="closeEditModal" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </header>
            
            <!-- Body -->
            <div class="px-6 py-5 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Leave Reason</label>
                    <textarea 
                        v-model="editingReason" 
                        rows="4" 
                        maxlength="500" 
                        required 
                        :disabled="editProcessing" 
                        class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 disabled:opacity-50 disabled:bg-slate-100" 
                        placeholder="Update your leave reason..."
                    ></textarea>
                    <p class="text-xs text-slate-500 mt-1">{{ editingReason.length }}/500 characters</p>
                </div>
            </div>
            
            <!-- Footer -->
            <footer class="flex justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
                <button 
                    type="button" 
                    @click="closeEditModal" 
                    class="px-3 py-1.5 text-xs font-semibold text-slate-600 border border-slate-300 rounded-md hover:bg-slate-100 transition-colors"
                >
                    Cancel
                </button>
                <button 
                    type="submit"
                    :disabled="editProcessing" 
                    class="px-4 py-1.5 text-xs font-semibold text-white bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-50 transition-colors min-w-[100px]"
                >
                    {{ editProcessing ? 'Saving...' : 'Save Changes' }}
                </button>
            </footer>
        </form>
    </div>
</teleport>

</AuthenticatedLayout>
</template>

<style>
@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}

.animate-fadeIn {
    animation: fadeIn 0.15s ease-out;
}
</style>
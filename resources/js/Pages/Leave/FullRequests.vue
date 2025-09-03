<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head } from '@inertiajs/vue3';
import { ref, reactive, watch } from 'vue';
import { usePage, router } from '@inertiajs/vue3'; // <-- correct place for usePage

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
    pending: { text: 'Pending', color: 'yellow' },
    approved: { text: 'Approved', color: 'green' },
    rejected: { text: 'Rejected', color: 'red' },
}[status] || { text: 'Unknown', color: 'gray' });

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
            // Update the selectedRequest with the fresh data from server
            const updatedRequest = page.props.leaveRequests.data.find(l => l.id === req.id);
            if (updatedRequest) {
                Object.assign(selectedRequest.value, updatedRequest);
            }

            // Reset file input
            delete selectedFiles.value[req.id];
            if (fileInputRefs[req.id]) fileInputRefs[req.id].value = '';
            uploadingRequest.value = null;

            // Show flash
            page.props.flash = { success: 'Document uploaded successfully ✅' };
        },
        onError: (errors) => { uploadError.value = errors.supporting_document || 'Upload failed'; },
        onFinish: () => { uploadProcessing.value = false; }
    });
}


function clearFileSelection(reqId) { delete selectedFiles.value[reqId]; if(fileInputRefs[reqId]) fileInputRefs[reqId].value=''; uploadError.value=''; }

const isEditModalVisible = ref(false);
const editingRequest = ref(null);
const editingReason = ref('');
const editProcessing = ref(false);

function openEditModal(req) { editingRequest.value=req; editingReason.value=req.reason; isEditModalVisible.value=true; }
function closeEditModal() { isEditModalVisible.value=false; }

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
function openDetailModal(req) { selectedRequest.value=req; isDetailModalVisible.value=true; }
function closeDetailModal() { isDetailModalVisible.value=false; }

const formatDate=dateString=>new Date(dateString).toLocaleDateString('en-US',{year:'numeric',month:'short',day:'numeric'});
function getFileName(url){ if(!url)return ''; return url.split('/').pop(); }



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
    <div class="grid gap-4">
        <div v-for="req in leaveRequests.data" :key="req.id"
             class="bg-white rounded-xl shadow-md p-5 flex items-center justify-between border-l-4 cursor-pointer"
             :class="statusCardBorderClass(req.status)">
            <div>
                <p class="font-semibold">
  {{ formatDate(req.start_date) }}
  <template v-if="req.start_half_session">
    ({{ req.start_half_session === 'morning' ? 'Morning' : 'Afternoon' }})
  </template>
  <span v-if="req.end_date !== req.start_date">
    - {{ formatDate(req.end_date) }}
    <template v-if="req.end_half_session">
      ({{ req.end_half_session === 'morning' ? 'Morning' : 'Afternoon' }})
    </template>
  </span>
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
    <div v-if="isDetailModalVisible" @click.self="closeDetailModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
        <div class="w-full max-w-3xl bg-white rounded-2xl p-6 shadow-xl space-y-6 overflow-auto max-h-[80vh]">

            <header class="flex justify-between items-center border-b pb-2">
                <h2 class="text-xl font-bold text-gray-800">{{ selectedRequest.reason }}</h2>
                <button @click="closeDetailModal" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            </header>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <p>
  <span class="font-semibold">Dates:</span>
  {{ formatDate(selectedRequest.start_date) }}
  <template v-if="selectedRequest.start_half_session">
    ({{ selectedRequest.start_half_session === 'morning' ? 'Morning' : 'Afternoon' }})
  </template>
  <span v-if="selectedRequest.end_date !== selectedRequest.start_date">
    - {{ formatDate(selectedRequest.end_date) }}
    <template v-if="selectedRequest.end_half_session">
      ({{ selectedRequest.end_half_session === 'morning' ? 'Morning' : 'Afternoon' }})
    </template>
  </span>
</p>

                    <p><span class="font-semibold">Leave Type:</span> {{ selectedRequest.leave_type }}</p>
                    <p><span class="font-semibold">Requested At:</span> {{ new Date(selectedRequest.created_at).toLocaleString() }}</p>
                    <p><span class="font-semibold">Status:</span> {{ statusInfo(selectedRequest.status).text }}</p>
                </div>
                <div v-if="selectedRequest.status==='rejected' && selectedRequest.rejection_reason">
                    <p class="font-semibold text-red-600">Rejection Reason:</p>
                    <p class="text-gray-600">{{ selectedRequest.rejection_reason }}</p>
                </div>
            </div>

            <!-- Supporting Document -->
            <div v-if="selectedRequest.leave_type==='sick'" class="mt-4">
                <p class="font-semibold mb-2">Supporting Document</p>
                <div v-if="selectedRequest.supporting_document" class="flex items-center gap-3 p-3 bg-green-50 border border-green-300 rounded-md">
                    <a :href="selectedRequest.supporting_document" target="_blank" class="font-medium text-green-700 hover:underline"> View Document</a>
                </div>
                <div v-if="selectedRequest.status==='pending'" class="flex flex-col sm:flex-row sm:items-center gap-3 mt-2">
                    <input :ref="el => { if(el) fileInputRefs[selectedRequest.id]=el }" type="file" @change="e=>handleFileChange(selectedRequest.id,e)" accept=".pdf,.jpg,.jpeg,.png" class="border rounded px-2 py-1 w-full sm:w-auto"/>
                    <button v-if="selectedFiles[selectedRequest.id]" @click="clearFileSelection(selectedRequest.id)" class="bg-gray-100 px-3 py-1 rounded text-sm hover:bg-gray-200">Clear</button>
                    <button @click="submitUpload(selectedRequest)" :disabled="uploadProcessing && uploadingRequest===selectedRequest.id" class="bg-indigo-600 text-white px-4 py-1 rounded text-sm hover:bg-indigo-700 min-w-[80px]">
                        {{ uploadProcessing && uploadingRequest===selectedRequest.id ? 'Uploading...' : (selectedRequest.supporting_document?'Replace':'Upload') }}
                    </button>
                </div>
                <p v-if="uploadError" class="text-red-600 text-sm mt-1">{{ uploadError }}</p>
            </div>

            <!-- Actions -->
            <div class="flex flex-wrap gap-3 mt-4">
                <button v-if="selectedRequest.status==='pending'" @click="openEditModal(selectedRequest)" class="bg-indigo-50 text-indigo-700 px-4 py-2 rounded text-sm hover:bg-indigo-100 min-w-[80px]">Edit</button>
                <button v-if="selectedRequest.status==='pending'" @click="cancelRequest(selectedRequest)" class="bg-red-50 text-red-700 px-4 py-2 rounded text-sm hover:bg-red-100 min-w-[80px]">Cancel</button>
            </div>

        </div>
    </div>
</teleport>

<!-- Edit Modal -->
<teleport to="body">
    <div v-if="isEditModalVisible" @click.self="closeEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
        <form @submit.prevent="submitEditReason" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl space-y-4">
            <header class="flex items-center gap-3 border-b pb-2">
                <h2 class="text-xl font-bold text-gray-800">Edit Leave Reason</h2>
            </header>
            <textarea v-model="editingReason" rows="4" maxlength="500" required :disabled="editProcessing" class="w-full rounded border border-gray-300 px-3 py-2" placeholder="Update your leave reason..."></textarea>
            <footer class="flex justify-end gap-3">
                <button type="button" @click="closeEditModal" class="rounded border border-gray-300 px-4 py-1 text-gray-700 hover:bg-gray-50">Cancel</button>
                <PrimaryButton :disabled="editProcessing" type="submit">{{ editProcessing ? 'Saving...' : 'Save Changes' }}</PrimaryButton>
            </footer>
        </form>
    </div>
</teleport>

</AuthenticatedLayout>
</template>

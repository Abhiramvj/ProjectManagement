<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import Pagination from '@/Components/Pagination.vue'; // <-- Assumes this component exists
import { ref, watch } from 'vue'

// Props
const props = defineProps({
  leaveRequests: Object,
  filters: Object,
})

const statusOptions = [
  { label: 'All Statuses', value: '' },
  { label: 'Pending', value: 'pending' },
  { label: 'Approved', value: 'approved' },
  { label: 'Rejected', value: 'rejected' },
]

const leaveTypeOptions = [
  { label: 'All Leave Types', value: '' },
  { label: 'Annual', value: 'annual' },
  { label: 'Sick', value: 'sick' },
  { label: 'Personal', value: 'personal' },
  { label: 'Emergency', value: 'emergency' },
  { label: 'Maternity', value: 'maternity' },
  { label: 'Paternity', value: 'paternity' },
  { label: 'Compensatory', value: 'compensatory' },
  { label: 'WFH', value: 'wfh' },
]

// Reactive filter state
const statusFilter = ref(props.filters.status || '')
const leaveTypeFilter = ref(props.filters.leave_type || '')

// Watch filters and reload page
function applyFilters() {
  router.get(route('leave.fullRequests'), {
    status: statusFilter.value || undefined,
    leave_type: leaveTypeFilter.value || undefined,
  }, { preserveState: true, replace: true })
}

watch(statusFilter, applyFilters);
watch(leaveTypeFilter, applyFilters);

// Helper for status badge colors
const statusClass = (status) => {
  return {
    pending: 'bg-amber-100 text-amber-800',
    approved: 'bg-emerald-100 text-emerald-800',
    rejected: 'bg-rose-100 text-rose-800',
  }[status] || 'bg-gray-100 text-gray-800'
}

const isEditModalVisible = ref(false)
const editingRequest = ref(null)
const editingReason = ref('')
const editProcessing = ref(false)

function openEditModal(req) {
  editingRequest.value = req
  editingReason.value = req.reason
  isEditModalVisible.value = true
}
function closeEditModal() {
  isEditModalVisible.value = false
  editingRequest.value = null
  editingReason.value = ''
  editProcessing.value = false
}

function submitEditReason() {
  if (!editingRequest.value) return
  editProcessing.value = true
  router.patch(
    route('leave.updateReason', { leave_application: editingRequest.value.id }),
    { reason: editingReason.value },
    {
      preserveScroll: true,
      onSuccess: () => {
        closeEditModal()
      },
      onFinish: () => { editProcessing.value = false }
    }
  )
}
function cancelRequest(req) {
  if (confirm('Are you sure you want to cancel this leave request?')) {
    router.delete(route('leave.cancel', { leave_application: req.id }), {
      preserveScroll: true,
    })
  }
}

const isReasonModalVisible = ref(false)
const rejectionReasonToShow = ref('')

function showRejectionReason(reason) {
  rejectionReasonToShow.value = reason
  isReasonModalVisible.value = true
}

function closeReasonModal() {
  isReasonModalVisible.value = false
  rejectionReasonToShow.value = ''
}

watch(isReasonModalVisible, (visible) => {
  if (visible) {
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
})

function formatLeaveDays(days) {
  const num = Number(days)
  if (isNaN(num)) return '0'
  if (num % 1 === 0.5) return `${Math.floor(num)}.5`
  return num % 1 === 0 ? num.toString() : num.toFixed(1)
}

const formatDate = (dateString) => new Date(dateString).toLocaleDateString('en-CA'); // YYYY-MM-DD format is clean and universal
</script>

<template>
  <Head title="My Leave Requests" />
  <AuthenticatedLayout>
    <div class="p-4 sm:p-6 lg:p-8">
      <div class="max-w-7xl mx-auto">
        <div class="sm:flex sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-800">My Leave Requests</h1>
                <p class="mt-1 text-sm text-gray-500">A complete history of all your submitted leave applications.</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
          <select v-model="statusFilter" @change="applyFilters" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
          <select v-model="leaveTypeFilter" @change="applyFilters" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            <option v-for="opt in leaveTypeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
        </div>

        <!-- Leave Requests Table Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr class="text-left">
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Date Range</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-if="leaveRequests.data.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">No leave requests found for the selected filters.</td>
                        </tr>
                        <tr v-for="req in leaveRequests.data" :key="req.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-gray-900">{{ formatDate(req.start_date) }} to {{ formatDate(req.end_date) }}</div>
                                <div class="text-gray-500">{{ formatLeaveDays(req.leave_days) }} day<span v-if="req.leave_days != 1">s</span></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap capitalize text-gray-700">{{ req.leave_type }}</td>
                            <td class="px-6 py-4 max-w-xs truncate text-gray-600" :title="req.reason">{{ req.reason }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="statusClass(req.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize">
                                    {{ req.status }}
                                </span>
                                <button v-if="req.status === 'rejected' && req.rejection_reason"
                                        @click="showRejectionReason(req.rejection_reason)"
                                        type="button"
                                        class="ml-2 text-blue-600 hover:underline text-xs font-medium"
                                >(View Reason)</button>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right font-medium">
                                <button v-if="req.status === 'pending'" @click="openEditModal(req)" class="text-indigo-600 hover:text-indigo-900">Edit</button>
                                <button v-if="req.status === 'pending'" @click="cancelRequest(req)" class="ml-4 text-rose-600 hover:text-rose-900">Cancel</button>
                                <span v-else class="text-gray-400">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Pagination -->
        <div v-if="leaveRequests.links.length > 3" class="mt-6">
            <Pagination :links="leaveRequests.links" />
        </div>
      </div>
    </div>

    <!-- Edit Reason Modal -->
    <div v-if="isEditModalVisible" @click.self="closeEditModal" class="fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center p-4">
        <form @submit.prevent="submitEditReason" class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 flex flex-col gap-4">
            <h2 class="text-lg font-semibold mb-1">Edit Reason</h2>
            <p class="text-sm text-gray-600 mb-2">Update the reason for your leave application:</p>
            <textarea v-model="editingReason" rows="4" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" :disabled="editProcessing" maxlength="500"></textarea>
            <div class="flex gap-2 justify-end pt-2">
                <button type="button" @click="closeEditModal" class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 text-sm font-semibold">Cancel</button>
                <PrimaryButton type="submit" :disabled="editProcessing">
                    {{ editProcessing ? 'Saving...' : 'Save Changes' }}
                </PrimaryButton>
            </div>
        </form>
    </div>

    <!-- Rejection Reason Modal -->
    <teleport to="body">
      <div v-if="isReasonModalVisible" @click.self="closeReasonModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg max-w-md w-full p-6 shadow-lg overflow-auto max-h-[70vh]" role="dialog" aria-modal="true" aria-labelledby="modal-title">
          <h3 id="modal-title" class="text-lg font-semibold mb-4 text-rose-700">Reason for Rejection</h3>
          <p class="whitespace-pre-wrap text-gray-800 break-words">
            {{ rejectionReasonToShow }}
          </p>
          <div class="mt-6 flex justify-end">
            <button @click="closeReasonModal" class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 font-semibold text-sm">Close</button>
          </div>
        </div>
      </div>
    </teleport>
  </AuthenticatedLayout>
</template>
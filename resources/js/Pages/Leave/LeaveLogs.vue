<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import { ref } from 'vue'

// Props: Paginator object of leaveRequests, no canManage as always admin/HR view
const props = defineProps({
  leaveRequests: Object,
})

// Status display config
const statusConfig = {
  approved: { class: 'bg-green-100 text-green-800', icon: '✅' },
  rejected: { class: 'bg-red-100 text-red-800', icon: '❌' },
  pending: { class: 'bg-yellow-100 text-yellow-800', icon: '⏳' },
}

// Format date string nicely
function formatDate(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  if (isNaN(date.getTime())) return 'Invalid Date';
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

// Format leave days with halves (e.g., 1.5)
function formatLeaveDays(days) {
  const num = Number(days)
  if (isNaN(num)) return '0'
  if (num % 1 === 0.5) return `${Math.floor(num)}.5`
  return (num % 1 === 0) ? num.toString() : num.toFixed(1)
}

// Upload Document modal state
const isUploadModalVisible = ref(false)
const uploadFile = ref(null)
const uploadErrors = ref({})
const uploadProcessing = ref(false)
const currentUploadLeaveId = ref(null)

function openUploadModal(leaveId) {
  currentUploadLeaveId.value = leaveId
  uploadFile.value = null
  uploadErrors.value = {}
  uploadProcessing.value = false
  isUploadModalVisible.value = true
}

function closeUploadModal() {
  isUploadModalVisible.value = false
  currentUploadLeaveId.value = null
  uploadFile.value = null
  uploadErrors.value = {}
  uploadProcessing.value = false
}

function onUploadFileChange(event) {
  uploadFile.value = event.target.files[0]
}

function submitUpload() {
  if (!uploadFile.value) {
    uploadErrors.value.supporting_document = 'Please select a file.'
    return
  }
  uploadProcessing.value = true
  const formData = new FormData()
  formData.append('supporting_document', uploadFile.value)

  router.post(route('leave.uploadDocument', { leave_application: currentUploadLeaveId.value }), formData, {
    preserveScroll: true,
    headers: { 'Content-Type': 'multipart/form-data' },
    onSuccess: () => {
      uploadProcessing.value = false
      closeUploadModal()
      router.reload()
    },
    onError: (errors) => {
      uploadProcessing.value = false
      uploadErrors.value = errors
    },
  })
}

// Leave Details modal state
const isDetailsModalOpen = ref(false)
const selectedLeave = ref(null)

function openDetailsModal(request) {
  selectedLeave.value = request
  isDetailsModalOpen.value = true
}

function closeDetailsModal() {
  selectedLeave.value = null
  isDetailsModalOpen.value = false
}

// Approve/Reject leave actions
const updateStatus = (request, newStatus) => {
  router.patch(route('leave.update', { leave_application: request.id }), { status: newStatus }, { preserveScroll: true })
}

const isRejectModalOpen = ref(false)
const rejectReason = ref('')
const rejectProcessing = ref(false)
const rejectingLeave = ref(null)

function openRejectModal(request) {
  rejectingLeave.value = request
  rejectReason.value = ''
  isRejectModalOpen.value = true
}
function closeRejectModal() {
  isRejectModalOpen.value = false
  rejectingLeave.value = null
  rejectReason.value = ''
  rejectProcessing.value = false
}

function submitRejection() {
  if (!rejectingLeave.value) return
  rejectProcessing.value = true
  router.patch(
    route('leave.update', { leave_application: rejectingLeave.value.id }),
    { status: 'rejected', rejection_reason: rejectReason.value },
    {
      preserveScroll: true,
      onSuccess: () => {
        rejectProcessing.value = false
        closeRejectModal()
        closeDetailsModal()
        router.reload()
      },
      onError: () => { rejectProcessing.value = false }
    }
  )
}
</script>

<template>
  <Head title="Leave Logs" />
  <AuthenticatedLayout>
    <div class="p-6 max-w-5xl mx-auto space-y-6 font-sans">

      <h1 class="text-3xl font-bold text-gray-900 mb-4">Leave Application Logs</h1>

      <div v-if="leaveRequests.data.length === 0" class="p-12 text-center text-gray-600 text-lg">
        No leave requests found.
      </div>

      <div v-else class="bg-white rounded-lg shadow border border-gray-200 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide w-2/3">
                Employee
              </th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide w-1/6">
                Status
              </th>
              <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide w-1/6">
                Details
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="request in leaveRequests.data" :key="request.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 whitespace-nowrap">
                <div class="flex items-center space-x-3">
                  <div class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center text-gray-700 font-semibold">
                    {{ request.user?.name?.charAt(0)?.toUpperCase() || '?' }}
                  </div>
                  <div class="text-gray-900 font-medium truncate">
                    {{ request.user?.name || 'Unknown' }}
                  </div>
                </div>
              </td>

              <td class="px-4 py-3 whitespace-nowrap">
                <span
                  :class="statusConfig[request.status].class"
                  class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold"
                >
                  <span class="mr-1">{{ statusConfig[request.status].icon }}</span>
                  {{ request.status.charAt(0).toUpperCase() + request.status.slice(1) }}
                </span>
              </td>

              <td class="px-4 py-3 whitespace-nowrap text-center">
                <button
                  @click="openDetailsModal(request)"
                  class="text-blue-600 hover:underline text-sm font-semibold focus:outline-none"
                >
                  View Details
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination Links -->
        <div v-if="leaveRequests.links.length > 3" class="px-4 py-5 border-t border-gray-200">
          <div class="flex flex-wrap justify-center -m-1">
            <template v-for="(link, index) in leaveRequests.links" :key="index">
              <span
                v-if="!link.url"
                class="m-1 px-4 py-2 text-gray-400 border rounded cursor-default"
                v-html="link.label"
              ></span>
              <a
                v-else
                :href="link.url"
                class="m-1 px-4 py-2 border rounded hover:bg-white hover:text-indigo-600"
                :class="{ 'bg-indigo-600 text-white': link.active }"
                v-html="link.label"
              ></a>
            </template>
          </div>
        </div>
      </div>

      <!-- Leave Details Modal -->
      <div
        v-if="isDetailsModalOpen"
        @click.self="closeDetailsModal"
        class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4"
      >
        <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full p-6 flex flex-col">
          <div class="flex justify-between items-center mb-4 pb-3 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-800">Leave Application Details</h2>
            <button class="text-2xl text-gray-500 hover:text-gray-800" @click="closeDetailsModal">×</button>
          </div>

          <!-- START: MODIFIED CONTENT -->
          <div v-if="selectedLeave" class="space-y-4 text-sm">
            <div><span class="font-medium text-gray-900">Employee:</span> <span class="text-gray-700">{{ selectedLeave.user?.name }}</span></div>
            <div><span class="font-medium text-gray-900">Leave Type:</span> <span class="text-gray-700 capitalize">{{ selectedLeave.leave_type }}</span></div>
            <div>
              <span class="font-medium text-gray-900">Dates:</span>
              <span class="text-gray-700">{{ formatDate(selectedLeave.start_date) }}</span>
              <span v-if="selectedLeave.start_date !== selectedLeave.end_date" class="text-gray-700"> - {{ formatDate(selectedLeave.end_date) }}</span>
            </div>
            <div>
              <span class="font-medium text-gray-900">Duration:</span>
              <span class="text-gray-700">{{ formatLeaveDays(selectedLeave.leave_days) }} day<span v-if="selectedLeave.leave_days !== 1">s</span></span>
            </div>
            <div>
              <span class="font-medium text-gray-900">Sessions:</span>
              <span class="text-gray-700 capitalize">
                <template v-if="selectedLeave.day_type === 'half'">
                  {{ selectedLeave.start_half_session }}
                  <span v-if="selectedLeave.start_date !== selectedLeave.end_date"> to {{ selectedLeave.end_half_session }}</span>
                </template>
                <template v-else>Full Day</template>
              </span>
            </div>
            <div>
              <span class="font-medium text-gray-900">Reason:</span>
              <p class="whitespace-pre-wrap mt-1 text-gray-700 bg-gray-50 p-2 border rounded-md">{{ selectedLeave.reason }}</p>
            </div>
            <div><span class="font-medium text-gray-900">Status:</span> <span class="text-gray-700 capitalize">{{ selectedLeave.status }}</span></div>
            <div v-if="selectedLeave.status === 'rejected' && selectedLeave.rejection_reason">
              <span class="font-medium text-red-800">Reason for Rejection:</span>
              <p class="whitespace-pre-wrap mt-1 text-red-700 bg-red-50 p-2 border border-red-200 rounded-md">{{ selectedLeave.rejection_reason }}</p>
            </div>
            <div>
              <span class="font-medium text-gray-900">Supporting Document:</span>
              <span v-if="selectedLeave.supporting_document_path" class="ml-2">
                <a
                  :href="`/storage/${selectedLeave.supporting_document_path}`"
                  target="_blank"
                  class="text-blue-600 hover:text-blue-800 underline font-semibold"
                >
                  View Document
                </a>
              </span>
              <span v-else class="text-gray-500 ml-2 italic">None provided</span>
            </div>
            <!-- END: MODIFIED CONTENT -->

            <!-- Approve/Reject buttons (only if pending) -->
            <div v-if="selectedLeave.status === 'pending'" class="!mt-6 flex gap-3 border-t border-gray-200 pt-4">
              <PrimaryButton
                @click="() => { updateStatus(selectedLeave, 'approved'); closeDetailsModal(); }"
                class="bg-green-600 hover:bg-green-700 focus:ring-green-500"
              >
                Approve
              </PrimaryButton>
              <PrimaryButton
                @click="() => { openRejectModal(selectedLeave) }"
                class="bg-red-600 hover:bg-red-700 focus:ring-red-500"
              >
                Reject
              </PrimaryButton>
            </div>
          </div>
          <div class="mt-6 flex justify-end">
            <button @click="closeDetailsModal" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300 font-medium text-sm text-gray-800">
              Close
            </button>
          </div>
        </div>
      </div>

      <!-- Upload Supporting Document Modal -->
      <div
        v-if="isUploadModalVisible"
        @click.self="closeUploadModal"
        class="fixed inset-0 bg-black bg-opacity-75 flex justify-center items-center z-50 p-4 transition-opacity duration-300"
      >
        <form
          @submit.prevent="submitUpload"
          enctype="multipart/form-data"
          class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 space-y-4"
        >
          <h3 class="text-lg font-semibold text-gray-800 mb-2">Upload Supporting Document</h3>
          <input
            id="upload_file"
            type="file"
            @change="onUploadFileChange"
            accept=".pdf,.jpg,.jpeg,.png"
            required
            class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
          />
          <div v-if="uploadErrors.supporting_document" class="text-sm text-red-600">{{ uploadErrors.supporting_document }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button
              type="button"
              @click="closeUploadModal"
              class="px-4 py-2 text-sm rounded border border-gray-300 hover:bg-gray-100"
            >
              Cancel
            </button>
            <PrimaryButton type="submit" :disabled="uploadProcessing">
              {{ uploadProcessing ? 'Uploading...' : 'Upload' }}
            </PrimaryButton>
          </div>
        </form>
      </div>

      <!-- Reject Reason Modal -->
      <div
        v-if="isRejectModalOpen"
        @click.self="closeRejectModal"
        class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4"
      >
        <form
          @submit.prevent="submitRejection"
          class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 flex flex-col gap-4"
        >
          <h2 class="text-lg font-semibold mb-1 text-gray-900">Reason for Rejection</h2>
          <textarea
            v-model="rejectReason"
            required
            rows="3"
            maxlength="500"
            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-gray-700"
            :disabled="rejectProcessing"
            placeholder="State the reason for rejection..."
          ></textarea>
          <div class="flex gap-2 justify-end pt-2">
            <button type="button" @click="closeRejectModal"
              class="px-4 py-2 bg-gray-200 rounded text-sm font-semibold text-gray-800 hover:bg-gray-300">Cancel</button>
            <PrimaryButton type="submit" :disabled="rejectProcessing || !rejectReason" class="bg-red-600 hover:bg-red-700 focus:ring-red-500">
              {{ rejectProcessing ? 'Rejecting...' : 'Reject Application' }}
            </PrimaryButton>
          </div>
        </form>
      </div>

    </div>
  </AuthenticatedLayout>
</template>

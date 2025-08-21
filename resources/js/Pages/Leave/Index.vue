<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import { Head, useForm, router, usePage } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
const showColors = ref(true)



const props = defineProps({
  leaveRequests: Object,
  highlightedDates: Array,
  remainingLeaveBalance: Number,
  compOffBalance: Number,
})

const page = usePage()
const currentUserId = page.props.auth.user.id

const leaveColors = {
  pending: '#fbbf24',
  annual: '#3b82f6',
  sick: '#22c55e',
  personal: '#f59e0b',
  emergency: '#f97316',
  maternity: '#ec4899',
  paternity: '#6366f1',
  rejected: '#b91c1c',
  wfh: '#0ea5e9',
  compensatory: '#16a34a',
  holiday: '#f43f5e',
}

const leaveFormSection = ref(null)
const isPolicyModalVisible = ref(false)
const selectedDates = ref([null, null])
const today = new Date()
today.setHours(0, 0, 0, 0)

const form = useForm({
  start_date: '',
  end_date: '',
  reason: '',
  leave_type: 'sick',
  day_type: 'full',
  start_half_session: '',
  end_half_session: '',
  supporting_document: null,
})

const supportingDocument = ref(null)
function onSupportingDocumentChange(event) {
  supportingDocument.value = event.target.files[0] || null
  form.supporting_document = supportingDocument.value
}

const scrollToLeaveForm = () => {
  if (leaveFormSection.value) {
    leaveFormSection.value.scrollIntoView({ behavior: 'smooth' })
  }
}

const openPolicyModal = () => { isPolicyModalVisible.value = true }
const closePolicyModal = () => { isPolicyModalVisible.value = false }

function toISODateOnly(date) {
  if (!date) return ''
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const calendarEvents = computed(() =>
  (props.highlightedDates || [])
    .filter(ev => ev.user_id === currentUserId || ev.user_id === null)
    .map(ev => ({
      display: 'background',
      start: ev.start,
      end: ev.end
        ? toISODateOnly(new Date(new Date(ev.end + 'T00:00:00').getTime() + 86400000))
        : ev.start,
      backgroundColor: showColors.value
        ? (leaveColors[ev.color_category] || '#9ca3af')
        : 'transparent',   // Use transparent instead of gray
      borderColor: 'transparent', // Remove border color as well
      title: ev.title,
    }))
)



function getSelectionBackground() {
  const [start, end] = selectedDates.value
  if (start && end) {
    return [{
      display: 'background',
      start: toISODateOnly(start),
      end: toISODateOnly(new Date(end.getTime() + 86400000)),
      color: '#2563eb',
    }]
  }
  if (start) {
    return [{
      display: 'background',
      start: toISODateOnly(start),
      end: toISODateOnly(new Date(start.getTime() + 86400000)),
      color: '#ea580c',
    }]
  }
  return []
}

function updateFormDates() {
  const [start, end] = selectedDates.value
  form.start_date = start ? toISODateOnly(start) : ''
  form.end_date = end ? toISODateOnly(end) : ''
}

const handleDateClick = (info) => {
  const clicked = new Date(info.date)
  clicked.setHours(0, 0, 0, 0)
  if (clicked < today) {
    alert('Please select a date that is today or after today.')
    return
  }
  const [start, end] = selectedDates.value
  if (!start) {
    selectedDates.value = [clicked, null]
  } else if (!end) {
    if (clicked >= start) {
      selectedDates.value = [start, clicked]
    } else {
      selectedDates.value = [clicked, null]
    }
  } else {
    selectedDates.value = [clicked, null]
  }
  updateFormDates()
}

const submitApplication = () => {
  if (form.day_type === 'half') {
    if (!form.start_half_session) {
      alert('Please select morning or afternoon session for start date.')
      return
    }
    if (form.end_date && form.start_date !== form.end_date && !form.end_half_session) {
      alert('Please select morning or afternoon session for end date.')
      return
    }
  }
  if (!form.start_date) {
    alert('Please select at least a start date.')
    return
  }
  if (!form.end_date) {
    form.end_date = form.start_date
    selectedDates.value = [new Date(form.start_date), new Date(form.start_date)]
    if (form.day_type === 'half') {
      form.end_half_session = form.start_half_session
    }
  }
  if (form.day_type === 'full') {
    form.start_half_session = null
    form.end_half_session = null
  }

  const startDate = new Date(form.start_date)
  const timeDiff = startDate.getTime() - today.getTime()
  const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24))
  if (form.leave_type === 'annual' && daysDiff < 7) {
    if (!confirm('Warning: Annual leaves should be requested at least 7 days in advance. Do you still want to submit?')) return
  }
  if (form.leave_type === 'personal' && daysDiff < 3) {
    if (!confirm('Warning: Personal leaves should be requested at least 3 days in advance. Do you still want to submit?')) return
  }

  const formData = new FormData()
  for (const [key, val] of Object.entries(form.data())) {
    formData.append(key, val ?? '')
  }
  if (supportingDocument.value) formData.append('supporting_document', supportingDocument.value)

  router.post(route('leave.store'), formData, {
    preserveScroll: true,
    headers: { 'Content-Type': 'multipart/form-data' },
    onSuccess: () => {
      form.reset()
      form.leave_type = 'sick'
      form.day_type = 'full'
      form.start_half_session = ''
      form.end_half_session = ''
      selectedDates.value = [null, null]
      supportingDocument.value = null
    },
    onError: (errors) => {
      if (errors.message) alert(errors.message)
    }
  })
}

const statusConfig = {
  approved: { class: 'bg-green-100 text-green-800', icon: '✅' },
  rejected: { class: 'bg-red-100 text-red-800', icon: '❌' },
  pending: { class: 'bg-yellow-100 text-yellow-800', icon: '⏳' },
}

const updateStatus = (request, newStatus) => {
  router.patch(route('leave.update', { leave_application: request.id }), {
    status: newStatus,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      router.reload({ only: ['leaveRequests', 'remainingLeaveBalance', 'compOffBalance'] })
    }
  })
}


const cancelLeave = (request) => {
  if (confirm('Are you sure you want to cancel this leave request?')) {
    router.delete(route('leave.cancel', { leave_application: request.id }), {
      preserveScroll: true,
    })
  }
}

const calendarOptions = ref({
  plugins: [dayGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  selectable: true,
  selectMirror: true,
  height: 'auto',
  events: [...calendarEvents.value, ...getSelectionBackground()],
  dateClick: handleDateClick,
  headerToolbar: {
    left: 'title',
    center: '',
    right: 'prev,next',
  },
})

watch([calendarEvents, selectedDates, showColors], () => {
  calendarOptions.value.events = [...calendarEvents.value, ...getSelectionBackground()]
}, { deep: true })




const leaveTypeDescriptions = {
  annual: {
  title: "Annual Leave",
  summary: "Planned time off with advance notice",
  details: [
    "For vacations, personal time, or other planned absences",
    "Should be requested at least 7 days in advance",
    "Requires manager approval",
    "Balance accrued based on tenure (typically 15-25 days/year)"
  ],
},
personal: {
  title: "Personal Leave",
  summary: "Leave for personal matters that require time off",
  details: [
    "Used for personal errands, family commitments, or important events",
    "Typically requires 3 days advance notice",
    "Needs manager approval",
    "May be limited in total number of days per year"
  ],
},
sick: {
  title: "Sick Leave",
  summary: "Leave granted for health-related issues",
  details: [
    "For medical appointments, illness, or recovery",
    "Usually requires a medical certificate for extended absences",
    "Accrued based on company policy and local law",
    "May be paid or unpaid as per policy"
  ],
},
emergency: {
  title: "Emergency Leave",
  summary: "Leave granted for urgent, unforeseen personal emergencies",
  details: [
    "For unexpected situations like medical emergencies, accidents, or urgent family matters",
    "Typically granted on short notice with flexible approval process",
    "Usually paid leave, separate from annual or personal leave balances",
    "Helps employees manage critical situations without penalty"
  ],
},
maternity: {
  title: "Maternity Leave",
  summary: "Leave for childbirth and related recovery",
  details: [
    "Granted to employees during pregnancy and after birth",
    "Duration varies by jurisdiction (typically 12-26 weeks)",
    "May require medical documentation",
    "Protected under employment law"
  ],
},
paternity: {
  title: "Paternity Leave",
  summary: "Leave for fathers around the time of childbirth",
  details: [
    "Allows bonding with the newborn and support for the family",
    "Usually shorter duration than maternity leave (e.g., 1-2 weeks)",
    "Requires notice as per company policy",
    "Protected under employment law"
  ],
},
wfh: {
  title: "Work From Home",
  summary: "Remote work arrangements without reducing leave balance",
  details: [
    "Allows employees to work remotely for full or partial days",
    "Usually does not deduct from leave balance",
    "Requires manager’s approval",
    "Supports flexible work-life balance"
  ],
},
compensatory: {
  title: "Compensatory Leave",
  summary: "Leave earned by working extra hours or on holidays",
  details: [
    "Credited when working during official holidays",
    "Used as paid time off in lieu of extra hours worked",
    "Requires manager approval to grant and to use",
    "Usually tracked separately to enforce usage policies"
  ],
},

}

const leaveTypeTags = {
  sick: 'bg-pink-100 text-pink-600',
  casual: 'bg-blue-100 text-blue-600',
  compensatory: 'bg-green-100 text-green-600',
  wfh: 'bg-cyan-100 text-cyan-600',
  default: 'bg-gray-100 text-gray-600',
}

const getTagClass = (type) => {
  if (!type || typeof type !== 'string') {
    return leaveTypeTags.default
  }
  return leaveTypeTags[type.toLowerCase()] || leaveTypeTags.default
}

function formatLeaveDays(days) {
  const num = Number(days)
  if (isNaN(num)) return '0'
  if (num % 1 === 0.5) return `${Math.floor(num)}.5`
  return num % 1 === 0 ? num.toString() : num.toFixed(1)
}

// Upload Document modal state
const isUploadModalVisible = ref(false)
const uploadFile = ref(null)
const uploadErrors = ref({})
const uploadProcessing = ref(false)
const currentUploadLeaveId = ref(null)

const openUploadModal = (leaveId) => {
  currentUploadLeaveId.value = leaveId
  uploadFile.value = null
  uploadErrors.value = {}
  uploadProcessing.value = false
  isUploadModalVisible.value = true
}

const closeUploadModal = () => {
  isUploadModalVisible.value = false
  currentUploadLeaveId.value = null
  uploadFile.value = null
  uploadErrors.value = {}
  uploadProcessing.value = false
}

function onUploadFileChange(event) {
  uploadFile.value = event.target.files[0]
}

const submitUpload = () => {
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

// Your Requests Modal state and methods
const isRequestsModalVisible = ref(false)
function openRequestsModal() {
  isRequestsModalVisible.value = true
}
function closeRequestsModal() {
  isRequestsModalVisible.value = false
}

// Compute the most recent 5 requests for modal display
const recentRequests = computed(() => {
  return (props.leaveRequests.data || []).slice(0, 5)
})

const isEditModalVisible = ref(false)
const editingRequest = ref(null)
const editingReason = ref('')
const editProcessing = ref(false)

function openEditModal(request) {
  editingRequest.value = request
  editingReason.value = request.reason
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
        editProcessing.value = false
        closeEditModal()
        router.reload()
      },
      onError: () => {
        editProcessing.value = false
      }
    }
  )
}

const approvedUpcomingRequests = computed(() =>
  (props.leaveRequests.data || [])
    .filter(lr =>
      lr.status === 'approved' &&
      new Date(lr.end_date) >= new Date()
    )
    .slice(0, 3)
)


</script>

<template>
  <Head title="Leave" />
  <AuthenticatedLayout>
    <div class="p-6 bg-gray-50 min-h-screen">

     

      <!-- Leave Request Form -->
      <div ref="leaveFormSection" class="bg-white rounded-lg shadow overflow-hidden mb-6">
        

        <form @submit.prevent="submitApplication" class="grid grid-cols-1 lg:grid-cols-3 gap-6 p-6" enctype="multipart/form-data">
          <div class="lg:col-span-2 space-y-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center mb-3 space-x-3">
                <label class="text-sm font-medium text-gray-700 select-none">
                  Color-code for leave types
                </label>
                <button
                  type="button"
                  role="switch"
                  :aria-checked="showColors.toString()"
                  @click="showColors = !showColors"
                  :class="[
                    'relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500',
                    showColors ? 'bg-blue-600' : 'bg-gray-300',
                  ]"
                >
                <span
                  :class="[
                    'inline-block h-4 w-4 rounded-full bg-white shadow transform transition-transform',
                    showColors ? 'translate-x-6' : 'translate-x-1',
                ]"
                ></span>
                </button>
              </div>
              <InputLabel value="" class="text-sm font-medium text-gray-700" />
              <div class="text-xs font-medium px-3 py-1 rounded-full bg-blue-100 text-blue-800">
                <template v-if="form.start_date && !form.end_date">
                  {{ form.start_date }}<span v-if="form.day_type === 'half'">({{ form.start_half_session || 'full day' }})</span>
                </template>
                <template v-else-if="form.start_date && form.end_date">
                  {{ form.start_date }} to {{ form.end_date }}
                </template>
                <template v-else>No dates selected</template>
              </div>
            </div>
            <div class="border border-gray-200 rounded-lg overflow-hidden">
              <FullCalendar :options="calendarOptions" />
            </div>

            <InputError :message="form.errors.start_date" />
          </div>
          <div class="space-y-4">
            <div class="bg-blue-50 p-4 rounded-lg">
              <div class="text-12 font-bold text-blue-600 mt-1">Remaining Leave Balance : <template v-if="form.leave_type === 'compensatory'">
                    {{ props.compOffBalance }} day{{ props.compOffBalance !== 1 ? 's' : '' }}
                  </template>
                  <template v-else>
                    {{ props.remainingLeaveBalance }} day{{ props.remainingLeaveBalance !== 1 ? 's' : '' }}
                  </template>
              </div>
                <!-- <div class="text-2xl font-bold text-blue-600 mt-1">
                  <template v-if="form.leave_type === 'compensatory'">
                    {{ props.compOffBalance }} day{{ props.compOffBalance !== 1 ? 's' : '' }}
                  </template>
                  <template v-else>
                    {{ props.remainingLeaveBalance }} day{{ props.remainingLeaveBalance !== 1 ? 's' : '' }}
                  </template>
                </div> -->
              </div>
            <div>
              <InputLabel for="leave_type" value="Leave Type" class="text-sm font-medium text-gray-700 mb-1" />
              <select id="leave_type" v-model="form.leave_type" required
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                <option value="annual">Annual Leave</option>
                <option value="sick">Sick Leave</option>
                <option value="personal">Personal Leave</option>
                <option value="emergency">Emergency Leave</option>
                <option value="maternity">Maternity Leave</option>
                <option value="paternity">Paternity Leave</option>
                <option value="wfh">Work From Home</option>
                <option value="compensatory">Compensatory Off</option>
              </select>
              <InputError :message="form.errors.leave_type" />
              <div class="mt-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                <h4 class="font-medium text-gray-800 mb-1">
                  {{ leaveTypeDescriptions[form.leave_type]?.title || 'Unknown Leave Type' }}
                </h4>
                <p class="text-sm text-gray-600 mb-2">
                  {{ leaveTypeDescriptions[form.leave_type]?.summary || 'Details not available' }}
                </p>
              </div>
            </div>
            <div>
              <InputLabel value="Day Type" class="text-sm font-medium text-gray-700 mb-1" />
              <div class="flex space-x-4 mt-1">
                <label class="inline-flex items-center">
                  <input type="radio" v-model="form.day_type" value="full" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300" />
                  <span class="ml-2 text-sm text-gray-700">Full Day</span>
                </label>
                <label class="inline-flex items-center">
                  <input type="radio" v-model="form.day_type" value="half" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300" />
                  <span class="ml-2 text-sm text-gray-700">Half Day</span>
                </label>
              </div>
            </div>

            <template v-if="form.day_type === 'half'">
              <div>
                <InputLabel value="Start Session" class="text-sm font-medium text-gray-700 mb-1" />
                <div class="flex space-x-4 mt-1">
                  <label class="inline-flex items-center">
                    <input type="radio" v-model="form.start_half_session" value="morning" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300" />
                    <span class="ml-2 text-sm text-gray-700">Morning (AM)</span>
                  </label>
                  <label class="inline-flex items-center">
                    <input type="radio" v-model="form.start_half_session" value="afternoon" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300" />
                    <span class="ml-2 text-sm text-gray-700">Afternoon (PM)</span>
                  </label>
                </div>
                <InputError :message="form.errors.start_half_session" />
              </div>
              <div v-if="form.end_date && form.start_date !== form.end_date" class="mt-2">
                <InputLabel value="End Session" class="text-sm font-medium text-gray-700 mb-1" />
                <div class="flex space-x-4 mt-1">
                  <label class="inline-flex items-center">
                    <input type="radio" v-model="form.end_half_session" value="morning" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300" />
                    <span class="ml-2 text-sm text-gray-700">Morning (AM)</span>
                  </label>
                  <label class="inline-flex items-center">
                    <input type="radio" v-model="form.end_half_session" value="afternoon" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300" />
                    <span class="ml-2 text-sm text-gray-700">Afternoon (PM)</span>
                  </label>
                </div>
                <InputError :message="form.errors.end_half_session" />
              </div>
            </template>

            <div>
              <InputLabel for="reason" value="Reason" class="text-sm font-medium text-gray-700 mb-1" />
              <textarea id="reason" v-model="form.reason" rows="3" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="Brief explanation for your leave request"></textarea>
              <InputError :message="form.errors.reason" />
            </div>

            <div v-if="form.leave_type === 'sick'">
              <InputLabel for="supporting_document" value="Supporting Document (Optional)" />
              <input id="supporting_document" type="file" @change="onSupportingDocumentChange" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full" />
              <InputError :message="form.errors.supporting_document" />
            </div>

            <div class="pt-2">
              <PrimaryButton :disabled="form.processing" class="w-full justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                {{ form.processing ? 'Submitting...' : 'Submit Leave Request' }}
              </PrimaryButton>
            </div>
            <div class="space-y-3 flex flex-col justify-between">

          
          <button @click="openRequestsModal" class="w-full text-left bg-gray-200 text-gray-800 p-4 rounded-lg shadow-sm hover:bg-gray-300 transition font-medium">
            Your Requests
          </button>
          <button @click="openPolicyModal" class="w-full text-left bg-white text-gray-700 p-4 rounded-lg shadow-sm hover:bg-gray-100 transition font-medium">
            View Leave Policy
          </button>
        </div>
          </div>
        </form>
      </div>

      <!-- Your Requests Modal -->
      <div
        v-if="isRequestsModalVisible"
        @click.self="closeRequestsModal"
        class="fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center p-4"
      >
        <div class="bg-white rounded-lg max-w-4xl w-full max-h-[80vh] overflow-auto shadow-lg">
          <header class="flex justify-between items-center p-4 border-b">
            <h2 class="text-xl font-semibold text-gray-800">My Recent Leave Requests</h2>
            <button
              @click="closeRequestsModal"
              class="text-gray-500 hover:text-gray-800 text-3xl leading-none font-bold focus:outline-none"
              aria-label="Close"
              title="Close"
            >
              &times;
            </button>
          </header>

          <section class="p-4">
            <div v-if="recentRequests.length === 0" class="text-center text-gray-600 py-12">
              No recent leave requests found.
            </div>
            <div v-else class="overflow-x-auto">
              <table class="min-w-full text-sm">
                <thead>
                  <tr class="text-left text-gray-500 font-medium">
                    <th class="py-2 px-3">Date</th>
                    <th class="py-2 px-3">Type</th>
                    <th class="py-2 px-3">Duration</th>
                    <th class="py-2 px-3">Reason</th>
                    <th class="py-2 px-3">Requested</th>
                    <th class="py-2 px-3 text-center">Document</th>
                    <th class="py-2 px-3 text-right">Actions</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                  <tr v-for="request in recentRequests" :key="request.id" class="text-gray-700 hover:bg-gray-50">
                    <td class="py-3 px-3">
                      <p class="font-semibold">
                        {{ new Date(request.start_date).toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' }) }}
                      </p>
                    </td>
                    <td class="py-3 px-3">
                      <span :class="getTagClass(request.leave_type)" class="px-2 py-1 text-xs font-medium rounded-full capitalize">
                        {{ request.leave_type }}
                      </span>
                    </td>
                    <td class="py-3 px-3 text-center">
  {{ formatLeaveDays(request.leave_days) }} day<span v-if="request.leave_days !== 1">s</span>
</td>

                    <td class="py-3 px-3 max-w-[200px] truncate">{{ request.reason }}</td>
                    <td class="py-3 px-3">
                      <p>
                        {{ new Date(request.created_at).toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' }) }}
                      </p>
                      <p class="text-xs text-gray-500">
                        {{ new Date(request.created_at).toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' }) }}
                      </p>
                    </td>
                    <td class="py-3 px-3 text-center whitespace-nowrap space-y-1">
                      <div>
                        <a
                          v-if="request.supporting_document_path"
                          :href="`/storage/${request.supporting_document_path}`"
                          target="_blank"
                          class="text-indigo-600 hover:underline inline-flex items-center justify-center gap-1"
                          title="View supporting document"
                        >
                          View
                        </a>
                        <span v-else class="text-gray-400 text-xs italic">—</span>
                      </div>
                      <div v-if="request.leave_type === 'sick' && ['pending', 'approved'].includes(request.status)">
                        <button @click="() => { openUploadModal(request.id); closeRequestsModal(); }" class="text-blue-600 hover:underline text-xs mt-1">
                          {{ request.supporting_document_path ? 'Replace Document' : 'Upload Document' }}
                        </button>
                      </div>
                    </td>
                    <td class="py-3 px-3 text-right">
  <button
    v-if="request.status === 'pending'"
    @click="openEditModal(request)"
    class="text-blue-600 hover:underline font-semibold text-sm"
  >Edit</button>
  <button
    v-if="request.status === 'pending'"
    @click="cancelLeave(request)"
    class="ml-2 text-red-600 hover:text-red-900 font-semibold text-sm"
  >Cancel</button>
  <span v-else class="text-gray-400">-</span>
</td>

                  </tr>
                </tbody>
              </table>

              <!-- View All Requests Button -->
              <div class="mt-4 text-center">
                <PrimaryButton
                  @click="() => { closeRequestsModal(); router.visit(route('leave.fullRequests')) }"
                  class="inline-block"
                >
                  View All Requests
                </PrimaryButton>
              </div>

            </div>
          </section>
        </div>
      </div>

      <!-- Edit Reason Modal -->
<div
  v-if="isEditModalVisible"
  @click.self="closeEditModal"
  class="fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center p-4"
>
  <form
    @submit.prevent="submitEditReason"
    class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 flex flex-col gap-4"
  >
    <h2 class="text-lg font-semibold mb-1">Edit Reason</h2>
    <p class="text-sm text-gray-600 mb-2">Update the reason for your leave application:</p>
    <textarea
      v-model="editingReason"
      rows="4"
      required
      class="w-full border rounded px-3 py-2"
      :disabled="editProcessing"
    ></textarea>
    <div class="flex gap-2 justify-end pt-2">
      <button type="button" @click="closeEditModal"
        class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Cancel</button>
      <PrimaryButton type="submit" :disabled="editProcessing">
        {{ editProcessing ? 'Saving...' : 'Save' }}
      </PrimaryButton>
    </div>
  </form>
</div>


      <!-- Leave Policy Modal -->
      <div v-if="isPolicyModalVisible" @click.self="closePolicyModal" class="fixed inset-0 bg-black bg-opacity-60 flex justify-center items-center z-50 p-4 transition-opacity duration-300">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] flex flex-col transform transition-all duration-300 scale-95" :class="{ 'scale-100': isPolicyModalVisible }">
          <div class="p-5 border-b flex justify-between items-center">
            <h3 class="text-xl font-semibold text-gray-800">Company Leave Policy</h3>
            <button @click="closePolicyModal" class="text-gray-400 hover:text-gray-800 text-3xl leading-none">×</button>
          </div>
          <div class="p-6 overflow-y-auto space-y-6">
            <div v-for="(policy, type) in leaveTypeDescriptions" :key="type" class="pb-4 border-b last:border-b-0">
              <h4 class="text-lg font-semibold text-gray-700 mb-2 capitalize">{{ policy.title }}</h4>
              <p class="text-sm text-gray-600 mb-3 italic">"{{ policy.summary }}"</p>
              <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li v-for="(detail, index) in policy.details" :key="index">{{ detail }}</li>
              </ul>
            </div>
          </div>
          <div class="p-4 bg-gray-50 text-right rounded-b-xl">
            <button @click="closePolicyModal" class="bg-gray-200 text-gray-800 px-5 py-2 rounded-lg hover:bg-gray-300 font-semibold text-sm">Close</button>
          </div>
        </div>
      </div>

      <!-- Upload Supporting Document Modal -->
      <div v-if="isUploadModalVisible" @click.self="closeUploadModal" class="fixed inset-0 bg-black bg-opacity-60 flex justify-center items-center z-50 p-4 transition-opacity duration-300">
        <form @submit.prevent="submitUpload" enctype="multipart/form-data" class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 space-y-4">
          <h3 class="text-lg font-semibold text-gray-800 mb-2">Upload Supporting Document</h3>
          <input id="upload_file" type="file" @change="onUploadFileChange" accept=".pdf,.jpg,.jpeg,.png" required class="w-full" />
          <div v-if="uploadErrors.supporting_document" class="text-sm text-red-600">{{ uploadErrors.supporting_document }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="closeUploadModal" class="px-4 py-2 text-sm rounded border border-gray-300 hover:bg-gray-100">Cancel</button>
            <PrimaryButton type="submit" :disabled="uploadProcessing">{{ uploadProcessing ? 'Uploading...' : 'Upload' }}</PrimaryButton>
          </div>
        </form>
      </div>
       <!-- Controls -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Upcoming/Pending leave requests -->
<div class="lg:col-span-3 bg-white p-6 rounded-lg shadow-sm">
  <h2 class="text-lg font-semibold text-gray-700 mb-4">Upcoming Approved Leave</h2>
  <div class="space-y-3" v-if="approvedUpcomingRequests.length">
    <div
      v-for="request in approvedUpcomingRequests"
      :key="request.id"
      class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
    >
      <div class="flex items-center gap-4">
        <div class="p-3 rounded-full"
             :class="getTagClass(request.leave_type)">
        </div>
        <div>
          <p class="font-semibold text-gray-800">
            {{ new Date(request.start_date).toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' }) }}
            <span v-if="request.start_date !== request.end_date">
              - {{ new Date(request.end_date).toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' }) }}
            </span>
          </p>
          <p class="text-sm text-gray-500 capitalize">{{ request.leave_type }}</p>
        </div>
      </div>
      <p class="text-sm text-gray-600 hidden md:block truncate max-w-[180px]">{{ request.reason }}</p>
      <div class="flex items-center gap-2">
        <span class="w-2.5 h-2.5 rounded-full bg-green-400"></span>
        <p class="text-sm font-medium text-green-500">Approved</p>
      </div>
    </div>
  </div>
  <div v-else class="text-gray-500 text-sm">
    No approved upcoming leave requests.
  </div>
</div>



        <!-- <div class="space-y-3 flex flex-col justify-between">

          
          <button @click="openRequestsModal" class="w-full text-left bg-gray-200 text-gray-800 p-4 rounded-lg shadow-sm hover:bg-gray-300 transition font-medium">
            Your Requests
          </button>
          <button @click="openPolicyModal" class="w-full text-left bg-white text-gray-700 p-4 rounded-lg shadow-sm hover:bg-gray-100 transition font-medium">
            View Leave Policy
          </button>
        </div> -->
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style>
.fc {
  @apply text-gray-800;
}
.fc .fc-toolbar.fc-header-toolbar { margin-bottom: 0.5em; }
.fc .fc-toolbar-title { font-size: 1.1em; @apply font-semibold; }
.fc .fc-button { @apply bg-white border-gray-300 text-gray-700 hover:bg-gray-50; }
.fc .fc-button-primary:not(:disabled).fc-button-active { @apply bg-blue-600 text-white; }
.fc .fc-daygrid-day-frame { @apply min-h-[2em]; }
.fc .fc-daygrid-day-number { @apply p-1 text-gray-800 hover:text-blue-600; }
.fc .fc-daygrid-day.fc-day-today { @apply bg-blue-50; }
.fc .fc-daygrid-day-top { @apply flex-col items-start; }
.fc .fc-col-header-cell { @apply bg-gray-50; }
.fc .fc-col-header-cell-cushion { @apply text-gray-700 text-sm font-medium py-2; }
.fc .fc-daygrid-day-events { @apply mt-1; }
.fc .fc-event { @apply text-white text-xs rounded p-1 mb-1; }
.fc .fc-daygrid-day.fc-day-disabled { @apply bg-gray-50; }
.fc .fc-daygrid-day.fc-day-disabled .fc-daygrid-day-number { @apply text-gray-400; }
select option { position: relative; }
</style>
 
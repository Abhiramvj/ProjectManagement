<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

// --- FULLCALENDAR & CSS ---
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'



// --- APP PROPS ---
const props = defineProps({
  leaveRequests: Array,
  canManage: Boolean,
  highlightedDates: Array, // [{ start, end, class }]
})

// --- DATE SELECTION LOGIC ---

const selectedDates = ref([null, null])
const today = new Date()

const form = useForm({
  start_date: '',
  end_date: '',
  reason: '',
  leave_type: '',
})

// --- MAP BACKEND LEAVES TO FULLCAL BG EVENTS ---
function getBackgroundEvents() {
  return [
    ...(props.highlightedDates || []).map(ev => ({
      display: 'background',
      start: ev.start,
      end: ev.end
        ? new Date(new Date(ev.end).getTime() + 24 * 60 * 60 * 1000).toISOString().slice(0, 10)
        : ev.start,
      color: ev.class === 'approved' ? '#4caf50' : '#ff9800'
    }))
  ]
}

function getSelectionBackground() {
  const [start, end] = selectedDates.value
  if (start && end) {
    return [{
      display: 'background',
      start: start.toISOString().slice(0, 10),
      end: new Date(end.getTime() + 24 * 60 * 60 * 1000).toISOString().slice(0, 10),
      color: '#2563eb'
    }]
  }
  if (start) {
    return [{
      display: 'background',
      start: start.toISOString().slice(0, 10),
      end: new Date(start.getTime() + 24 * 60 * 60 * 1000).toISOString().slice(0, 10),
      color: '#ea580c'
    }]
  }
  return []
}

// --- HANDLE CALENDAR CELL CLICK ---
const handleDateClick = (info) => {
  const clicked = new Date(info.dateStr)
  let [start, end] = selectedDates.value
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

function updateFormDates() {
  const [start, end] = selectedDates.value
  form.start_date = start ? start.toISOString().slice(0, 10) : ''
  form.end_date = end ? end.toISOString().slice(0, 10) : ''
}

// --- SUBMIT FORM ---
const submitApplication = () => {
  if (!form.start_date || !form.end_date) {
    alert('Please select both start and end dates.')
    return
  }
  form.post(route('leave.store'), {
    onSuccess: () => {
      form.reset()
      selectedDates.value = [null, null]
    }
  })
}

// --- ADMIN/ERC MANAGEMENT ---
const statusClass = (status) => {
  if (status === 'approved') return 'bg-green-100 text-green-800'
  if (status === 'rejected') return 'bg-red-100 text-red-800'
  return 'bg-yellow-100 text-yellow-800'
}

const updateStatus = (request, newStatus) => {
  router.patch(route('leave.update', { leave_application: request.id }), {
    status: newStatus,
  }, { preserveScroll: true })
}
const cancelLeave = (request) => {
  if (confirm('Are you sure you want to cancel this leave request?')) {
    router.delete(route('leave.cancel', { leave_application: request.id }), {
      preserveScroll: true,
    })
  }
}

// --- FULLCALENDAR OPTIONS ---
const calendarOptions = ref({
  plugins: [dayGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  selectable: true,
  selectMirror: true,
  height: 600,
  events: [
    ...getBackgroundEvents(),
    ...getSelectionBackground(),
  ],
  dateClick: handleDateClick,
})

// UPDATE EVENTS WHEN SELECTION CHANGES
watch(selectedDates, () => {
  calendarOptions.value.events = [
    ...getBackgroundEvents(),
    ...getSelectionBackground(),
  ]
})
// ...and also update events if leave props change!
watch(() => props.highlightedDates, () => {
  calendarOptions.value.events = [
    ...getBackgroundEvents(),
    ...getSelectionBackground(),
  ]
})
</script>

<template>
  <Head title="Leave Applications" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Leave Applications</h2>
    </template>

    <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

      <div v-if="!canManage" class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
        <section>
          <header>
            <h2 class="text-lg font-medium text-gray-900">Submit a New Leave Request</h2>
          </header>

          <form @submit.prevent="submitApplication" class="mt-6 space-y-6">
            <div>
              <InputLabel value="Select Leave Date Range" />
              <FullCalendar :options="calendarOptions" />
              <InputError class="mt-2" :message="form.errors.start_date || form.errors.end_date" />

              <div class="mt-2 text-sm text-blue-700">
                <span v-if="form.start_date && !form.end_date">
                  Selected: <span class="font-bold">{{ form.start_date }}</span>
                </span>
                <span v-else-if="form.start_date && form.end_date">
                  Selected: <span class="font-bold">{{ form.start_date }}</span>
                  to <span class="font-bold">{{ form.end_date }}</span>
                </span>
                <span v-else>
                  Selected: <span class="text-gray-400">none</span>
                </span>
              </div>
            </div>

            <div>
              <InputLabel for="leave_type" value="Leave Type" />
              <select
                id="leave_type"
                v-model="form.leave_type"
                required
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
              >
                <option disabled value="">-- Select Leave Type --</option>
                <option value="casual">Casual Leave</option>
                <option value="sick">Sick Leave</option>
                <option value="paid">Paid Leave</option>
              </select>
              <InputError class="mt-2" :message="form.errors.leave_type" />
            </div>

            <div>
              <InputLabel for="reason" value="Reason for Leave" />
              <textarea
                id="reason"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                v-model="form.reason"
                rows="4"
                required
              ></textarea>
              <InputError class="mt-2" :message="form.errors.reason" />
            </div>

            <div class="flex items-center gap-4">
              <PrimaryButton :disabled="form.processing">Submit Request</PrimaryButton>
            </div>
            <InputError :message="form.errors.leave" />
          </form>
        </section>
      </div>

      <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
        <section>
          <header>
            <h2 class="text-lg font-medium text-gray-900">
              {{ canManage ? 'All Employee Leave Requests' : 'My Leave Requests' }}
            </h2>
          </header>

          <div class="mt-6 space-y-4">
            <div v-if="!leaveRequests.length" class="text-gray-500">No applications found.</div>

            <div
              v-for="request in leaveRequests"
              :key="request.id"
              class="p-4 border rounded-lg"
            >
              <div class="flex justify-between items-start gap-4">
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
                  <p class="mt-2 text-sm text-gray-800">{{ request.reason }}</p>
                </div>
                <div class="flex flex-col items-end gap-2">
                  <span class="px-3 py-1 text-xs font-semibold rounded-full" :class="statusClass(request.status)">
                    {{ request.status.toUpperCase() }}
                  </span>
                  <button
                    v-if="!canManage && request.status === 'pending'"
                    @click="cancelLeave(request)"
                    class="px-3 py-1 text-xs font-semibold text-white bg-gray-600 rounded-md hover:bg-gray-700"
                  >
                    Cancel Request
                  </button>
                </div>
              </div>
              <div v-if="canManage && request.status === 'pending'" class="mt-4 pt-4 border-t flex gap-2">
                <button
                  @click="updateStatus(request, 'approved')"
                  class="px-3 py-1 text-xs font-semibold text-white bg-green-600 rounded-md hover:bg-green-700"
                >
                  Approve
                </button>
                <button
                  @click="updateStatus(request, 'rejected')"
                  class="px-3 py-1 text-xs font-semibold text-white bg-red-600 rounded-md hover:bg-red-700"
                >
                  Reject
                </button>
              </div>
            </div>
          </div>
        </section>
      </div>

    </div>
  </AuthenticatedLayout>
</template>

<style>
/* Highlight today's cell background for FullCalendar */
.fc-day-today {
  background: #dbeafe !important;
}
</style>

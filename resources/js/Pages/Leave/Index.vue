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
import { onMounted } from 'vue'

// --- APP PROPS ---
const props = defineProps({
  leaveRequests: Array,
  canManage: Boolean,
  highlightedDates: Array, // [{ start, end, class }]
})

// --- DATE SELECTION LOGIC ---
const selectedDates = ref([null, null])
const today = new Date()
today.setHours(0, 0, 0, 0) // Normalize today's date

const form = useForm({
  start_date: '',
  end_date: '',
  reason: '',
  leave_type: '',
  day_type: 'full',
  start_time: '09:00',
  end_time: '17:00'
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

function updateFormDates() {
  const [start, end] = selectedDates.value
  form.start_date = start ? start.toLocaleDateString('en-CA') : ''
  form.end_date = end ? end.toLocaleDateString('en-CA') : ''
}

// --- HANDLE CALENDAR CELL CLICK ---
const handleDateClick = (info) => {
  const clicked = new Date(info.dateStr)
  clicked.setHours(0, 0, 0, 0)

  if (clicked < today) {
    alert('Please select a date that is today or after today.')
    return
  }

  let [start, end] = selectedDates.value
  if (!start) {
    selectedDates.value = [clicked, null]
  } else if (!end) {
    if (clicked >= start) {
      selectedDates.value = [start, clicked]
    } else {
      if (clicked < today) {
        alert('Please select a date that is today or after today.')
        return
      }
      selectedDates.value = [clicked, null]
    }
  } else {
    if (clicked < today) {
      alert('Please select a date that is today or after today.')
      return
    }
    selectedDates.value = [clicked, null]
  }
  updateFormDates()
}

// --- SUBMIT FORM ---
const submitApplication = () => {
  if (form.day_type === 'half') {
    if (!form.start_time || !form.end_time) {
      alert('Please select both start and end times for half day leave.')
      return
    }
    
    const startTime = new Date(`2000-01-01T${form.start_time}`)
    const endTime = new Date(`2000-01-01T${form.end_time}`)
    
    if (startTime >= endTime) {
      alert('End time must be after start time for half day leave.')
      return
    }
    
    const durationHours = (endTime - startTime) / (1000 * 60 * 60)
    if (durationHours > 4) {
      if (!confirm('Half day leave is typically 4 hours or less. Do you want to proceed?')) {
        return
      }
    }
  }

  if (!form.start_date) {
    alert('Please select at least a start date.')
    return
  }

  if (!form.end_date) {
    form.end_date = form.start_date
    selectedDates.value = [new Date(form.start_date), new Date(form.start_date)]
  }

  const startDate = new Date(form.start_date)
  const timeDiff = startDate.getTime() - today.getTime()
  const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24))

  if ((form.leave_type === 'casual' || form.leave_type === 'paid') && daysDiff < 7) {
    if (!confirm(`Warning: ${form.leave_type} leaves should be requested at least 7 days in advance. Do you still want to submit?`)) {
      return
    }
  }

  form.post(route('leave.store'), {
    preserveScroll: true,
    only: ['leaveRequests'],
    onSuccess: () => {
      form.reset()
      form.day_type = 'full'
      form.start_time = '09:00'
      form.end_time = '17:00'
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
  height: 350, // Fixed compact height
  aspectRatio: 1.3, // Wider aspect ratio
  events: [
    ...getBackgroundEvents(),
    ...getSelectionBackground(),
  ],
  dateClick: handleDateClick,
  headerToolbar: {
    left: 'title',
    center: '',
    right: 'prev,next'
  },
  viewDidMount: function() {
    const calendarEl = document.querySelector('.fc-view-harness');
    calendarEl.classList.add('animate-view-change');
    setTimeout(() => {
      calendarEl.classList.remove('animate-view-change');
    }, 300);
  }
})

// Watchers and onMounted
watch(selectedDates, () => {
  calendarOptions.value.events = [
    ...getBackgroundEvents(),
    ...getSelectionBackground(),
  ]
}, { deep: true })

watch(() => props.highlightedDates, () => {
  calendarOptions.value.events = [
    ...getBackgroundEvents(),
    ...getSelectionBackground(),
  ]
}, { deep: true })

onMounted(() => {
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

    <div class="py-8 max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
      <div v-if="!canManage" class="p-4 sm:p-6 bg-white shadow rounded-xl">
        <section>
          <header class="mb-6">
            <h2 class="text-lg font-medium text-gray-900">Submit a New Leave Request</h2>
            <p class="mt-1 text-sm text-gray-600">Select your leave dates and provide details</p>
          </header>

          <form @submit.prevent="submitApplication" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column - Calendar -->
            <div class="lg:col-span-2 bg-gradient-to-br from-blue-50 to-indigo-50 p-4 rounded-xl border border-blue-100">
              <div class="flex items-center justify-between mb-3">
                <InputLabel value="Select Dates" class="!text-sm !font-medium text-blue-800" />
                <div class="text-xs font-medium px-2 py-1 rounded bg-blue-100 text-blue-800">
                  <template v-if="form.start_date && !form.end_date">
                    {{ form.start_date }} 
                    <span v-if="form.day_type === 'half'">({{ form.start_time }} - {{ form.end_time }})</span>
                  </template>
                  <template v-else-if="form.start_date && form.end_date">
                    {{ form.start_date }} to {{ form.end_date }}
                    <span v-if="form.day_type === 'half'">({{ form.start_time }} - {{ form.end_time }})</span>
                  </template>
                  <template v-else>
                    No dates selected
                  </template>
                </div>
              </div>
              
              <div class="border border-blue-200 rounded-lg overflow-hidden bg-white">
                <FullCalendar 
                  :options="calendarOptions" 
                  class="compact-calendar no-scroll-calendar"
                />
              </div>
            </div>

            <!-- Right Column - Form Fields -->
            <div class="space-y-5">
              <!-- Leave Type with Descriptions -->
              <div class="bg-gradient-to-br from-purple-50 to-pink-50 p-4 rounded-xl border border-purple-100">
                <InputLabel for="leave_type" value="Leave Type" class="!text-sm !font-medium text-purple-800 mb-2" />
                <select
                  id="leave_type"
                  v-model="form.leave_type"
                  required
                  class="mt-1 block w-full border border-purple-200 rounded-lg shadow-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 sm:text-sm bg-white text-purple-900"
                >
                  <option disabled value="">Select Leave Type</option>
                  <option value="casual" class="text-blue-600">Casual Leave</option>
                  <option value="sick" class="text-green-600">Sick Leave</option>
                  <option value="paid" class="text-amber-600">Paid Leave</option>
                </select>
                <div v-if="form.leave_type" class="mt-2 text-xs text-gray-600 p-2 bg-purple-50 rounded">
                  <template v-if="form.leave_type === 'casual'">
                    <strong>Casual Leave:</strong> For personal matters. Must be requested at least 7 days in advance.
                  </template>
                  <template v-else-if="form.leave_type === 'sick'">
                    <strong>Sick Leave:</strong> For health-related absences. Can be requested anytime with proper documentation.
                  </template>
                  <template v-else-if="form.leave_type === 'paid'">
                    <strong>Paid Leave:</strong> For planned vacations. Must be requested at least 7 days in advance.
                  </template>
                </div>
                <InputError class="mt-1 text-xs" :message="form.errors.leave_type" />
              </div>

              <!-- Day Type Selection -->
              <div class="bg-gradient-to-br from-amber-50 to-orange-50 p-4 rounded-xl border border-amber-100">
                <InputLabel value="Day Type" class="!text-sm !font-medium text-amber-800 mb-2" />
                <div class="flex space-x-4 mt-1">
                  <label class="inline-flex items-center">
                    <input 
                      type="radio" 
                      v-model="form.day_type" 
                      value="full" 
                      class="form-radio h-4 w-4 text-amber-600 focus:ring-amber-500 border-amber-200"
                    >
                    <span class="ml-2 text-sm text-gray-700">Full Day</span>
                  </label>
                  <label class="inline-flex items-center">
                    <input 
                      type="radio" 
                      v-model="form.day_type" 
                      value="half" 
                      class="form-radio h-4 w-4 text-amber-600 focus:ring-amber-500 border-amber-200"
                    >
                    <span class="ml-2 text-sm text-gray-700">Half Day</span>
                  </label>
                </div>
              </div>

              <!-- Time Selection (only shown for half day) -->
              <div v-if="form.day_type === 'half'" class="bg-gradient-to-br from-cyan-50 to-blue-50 p-4 rounded-xl border border-cyan-100">
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <InputLabel for="start_time" value="From" class="!text-sm !font-medium text-cyan-800 mb-1" />
                    <input
                      id="start_time"
                      type="time"
                      v-model="form.start_time"
                      class="mt-1 block w-full border border-cyan-200 rounded-lg shadow-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm bg-white text-gray-800"
                      required
                    >
                  </div>
                  <div>
                    <InputLabel for="end_time" value="To" class="!text-sm !font-medium text-cyan-800 mb-1" />
                    <input
                      id="end_time"
                      type="time"
                      v-model="form.end_time"
                      class="mt-1 block w-full border border-cyan-200 rounded-lg shadow-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm bg-white text-gray-800"
                      required
                    >
                  </div>
                </div>
                <InputError class="mt-1 text-xs" :message="form.errors.start_time" />
                <InputError class="mt-1 text-xs" :message="form.errors.end_time" />
              </div>

              <!-- Reason -->
              <div class="bg-gradient-to-br from-emerald-50 to-teal-50 p-4 rounded-xl border border-emerald-100">
                <InputLabel for="reason" value="Reason for Leave" class="!text-sm !font-medium text-emerald-800 mb-2" />
                <textarea
                  id="reason"
                  class="mt-1 block w-full border border-emerald-200 rounded-lg shadow-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm bg-white text-gray-800"
                  v-model="form.reason"
                  rows="4"
                  required
                  placeholder="Briefly explain your reason"
                ></textarea>
                <InputError class="mt-1 text-xs" :message="form.errors.reason" />
              </div>

              <!-- Submit Button -->
              <div class="pt-2">
                <PrimaryButton 
                  :disabled="form.processing" 
                  class="w-full py-2 text-sm bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 shadow-md"
                >
                  <span class="text-white font-medium">Submit Leave Request</span>
                </PrimaryButton>
              </div>
            </div>
          </form>
        </section>
      </div>

      <!-- Leave Requests Section -->
      <div class="p-4 sm:p-6 bg-white shadow rounded-xl">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ props.canManage ? 'All Employee Leave Requests' : 'Your Leave Requests' }}</h3>
        
        <div v-if="props.leaveRequests.length === 0" class="text-sm text-gray-500">
          {{ props.canManage ? 'No employee leave requests found.' : 'You haven\'t submitted any leave requests yet.' }}
        </div>

        <table v-else class="w-full table-auto text-sm text-left">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-4 py-2">Date(s)</th>
              <th class="px-4 py-2">Type</th>
              <th class="px-4 py-2">Details</th>
              <th class="px-4 py-2">Reason</th>
              <th class="px-4 py-2">Status</th>
              <th v-if="props.canManage" class="px-4 py-2">Actions</th>
              <th v-else class="px-4 py-2">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr 
              v-for="request in props.leaveRequests" 
              :key="request.id" 
              class="border-t hover:bg-gray-50"
            >
              <td class="px-4 py-2">
                <div v-if="request.start_date === request.end_date">
                  {{ request.start_date }}
                  <div v-if="request.day_type === 'half'" class="text-xs text-gray-500">
                    {{ request.start_time }} - {{ request.end_time }}
                  </div>
                </div>
                <div v-else>
                  {{ request.start_date }} to {{ request.end_date }}
                  <div v-if="request.day_type === 'half'" class="text-xs text-gray-500">
                    Daily: {{ request.start_time }} - {{ request.end_time }}
                  </div>
                </div>
              </td>
              <td class="px-4 py-2 capitalize">
                {{ request.leave_type }}
                <div class="text-xs text-gray-500 capitalize">
                  {{ request.day_type }} day
                </div>
              </td>
              <td class="px-4 py-2">
                <div v-if="request.day_type === 'half'">
                  Half Day: {{ request.start_time }} - {{ request.end_time }}
                </div>
                <div v-else>
                  Full Day
                </div>
              </td>
              <td class="px-4 py-2">{{ request.reason }}</td>
              <td class="px-4 py-2">
                <span :class="statusClass(request.status)" class="px-2 py-1 text-xs rounded font-medium">
                  {{ request.status }}
                </span>
              </td>
              <td v-if="props.canManage" class="px-4 py-2 space-x-2">
                <button 
                  v-if="request.status === 'pending'"
                  class="text-green-600 hover:underline text-xs"
                  @click="updateStatus(request, 'approved')"
                >Approve</button>
                <button 
                  v-if="request.status === 'pending'"
                  class="text-red-600 hover:underline text-xs"
                  @click="updateStatus(request, 'rejected')"
                >Reject</button>
              </td>
              <td v-else class="px-4 py-2">
                <button 
                  v-if="request.status === 'pending'" 
                  class="text-red-500 text-xs hover:underline"
                  @click="cancelLeave(request)"
                >
                  Cancel
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style>
/* Compact Calendar Theme */
.compact-calendar {
  --fc-small-font-size: .65rem;
  --fc-page-bg-color: #fff;
  --fc-neutral-bg-color: rgba(208, 208, 208, 0.3);
  --fc-border-color: #e2e8f0;
  --fc-button-text-color: #fff;
  --fc-button-bg-color: #3b82f6;
  --fc-button-border-color: #3b82f6;
  --fc-button-hover-bg-color: #2563eb;
  --fc-button-hover-border-color: #2563eb;
  --fc-button-active-bg-color: #1d4ed8;
  --fc-button-active-border-color: #1d4ed8;
  --fc-event-bg-color: #3b82f6;
  --fc-event-border-color: #3b82f6;
  --fc-event-text-color: #fff;
  --fc-today-bg-color: #dbeafe;
}

.compact-calendar .fc-toolbar {
  padding: 0.25rem;
}

.compact-calendar .fc-toolbar-title {
  font-size: 0.8rem;
}

.compact-calendar .fc-col-header-cell {
  padding: 0.1rem 0;
}

.compact-calendar .fc-col-header-cell-cushion {
  padding: 0.1rem;
  font-size: 0.6rem;
}

.compact-calendar .fc-daygrid-day-frame {
  padding: 0.05rem;
}

.compact-calendar .fc-daygrid-day-number {
  font-size: 0.6rem;
  padding: 0.1rem;
}

.compact-calendar .fc-daygrid-event {
  font-size: 0.5rem;
  margin: 0.1rem;
}

/* Month Transition Animations */
.fc-view-harness {
  overflow: hidden;
}

.fc-view-harness.animate-view-change {
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.fc-prev-button, .fc-next-button {
  transition: all 0.2s ease;
}

.fc-prev-button:hover, .fc-next-button:hover {
  transform: scale(1.1);
}

/* Existing styles */
.fc .fc-highlight {
  background: rgba(37, 99, 235, 0.3) !important;
}

.fc .fc-daygrid-day.fc-day-today {
  background-color: #dbeafe !important;
}

.fc-event-approved {
  background-color: #4caf50 !important;
  border-color: #4caf50 !important;
}

.fc-event-pending {
  background-color: #ff9800 !important;
  border-color: #ff9800 !important;
}

.no-scroll-calendar {
  height: auto !important;
  overflow: hidden;
}

.fc .fc-view-harness {
  height: auto !important;
  min-height: 300px;
}

.fc .fc-daygrid-body {
  width: 100% !important;
}

.fc .fc-scrollgrid-section-body table {
  width: 100% !important;
}

.fc .fc-toolbar-title {
  font-size: 0.95rem;
  font-weight: 600;
}

.fc .fc-button {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
  background-color: white;
  color: #3b82f6;
  border: none;
  border-radius: 0.25rem;
}

.fc .fc-button:hover {
  background-color: #e0e7ff;
}

.fc .fc-button:active {
  background-color: #dbeafe;
}

.fc .fc-col-header-cell {
  padding: 0.25rem 0;
  background-color: #f8fafc;
  border-color: #e2e8f0;
}

.fc .fc-col-header-cell-cushion {
  font-size: 0.7rem;
  font-weight: 500;
  padding: 0.25rem;
  color: #475569;
}

.fc .fc-daygrid-day-frame {
  padding: 0.1rem;
}

.fc .fc-daygrid-day-number {
  font-size: 0.7rem;
  padding: 0.1rem;
  color: #334155;
}

.fc-day-today {
  background-color: #dbeafe !important;
}

.fc .fc-daygrid-day.fc-day-today .fc-daygrid-day-number {
  color: #1d4ed8;
  font-weight: bold;
}

.fc-highlight {
  background: rgba(59, 130, 246, 0.3) !important;
}

.fc-day-sat, .fc-day-sun {
  background-color: #f8fafc;
}

input[type="time"]::-webkit-calendar-picker-indicator {
  filter: invert(0.5);
}

input[type="time"] {
  appearance: none;
  -webkit-appearance: none;
}

.form-radio {
  border-width: 1px;
}

.form-radio:checked {
  background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3ccircle cx='8' cy='8' r='3'/%3e%3c/svg%3e");
  border-color: transparent;
  background-color: currentColor;
  background-size: 100% 100%;
  background-position: center;
  background-repeat: no-repeat;
}
</style>
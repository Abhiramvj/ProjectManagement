<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import VueCal from 'vue-cal';
import 'vue-cal/dist/vuecal.css';

const props = defineProps({
  leaveRequests: Array,
  canManage: Boolean,
  highlightedDates: Array,
});

function toDate(val) {
  if (val instanceof Date) return val;
  if (typeof val === 'string' || typeof val === 'number') {
    const d = new Date(val);
    if (isNaN(d.getTime())) return null;
    return d;
  }
  return null;
}

const form = useForm({
  start_date: '',
  end_date: '',
  reason: '',
  leave_type: '',
});

const today = new Date();
const selectedDates = ref([null, null]);
const tempRangeEvent = ref([]);

// Compose VueCal event list (backend + current selection overlay)
const vuecalEvents = computed(() => [
  ...props.highlightedDates.map(ev => ({
    ...ev,
    start: toDate(ev.start),
    end: toDate(ev.end),
    title: '', // no label!
  })),
  ...tempRangeEvent.value
]);

// Selection logic (no label on temp events)
watch(selectedDates, ([start, end]) => {
  if (start instanceof Date && end instanceof Date) {
    tempRangeEvent.value = [
      { start, end, class: 'selected-range', title: '' },
      { start, end: start, class: 'selected-start', title: '' },
      { start: end, end: end, class: 'selected-range-end', title: '' },
    ];
  } else if (start instanceof Date) {
    tempRangeEvent.value = [
      { start, end: start, class: 'selected-start', title: '' },
    ];
  } else {
    tempRangeEvent.value = [];
  }
});

// Handle clicks
const onCellClick = (...args) => {
  let date;
  if (args.length === 1 && args[0] && typeof args[0] === 'object' && 'date' in args[0]) {
    date = args[0].date;
  } else if (args.length >= 1 && args[0] instanceof Date) {
    date = args[0];
  }
  if (!(date instanceof Date) || isNaN(date.getTime())) return;

  let [start, end] = selectedDates.value;
  if (!start || !(start instanceof Date)) {
    selectedDates.value = [date, null];
  } else if (!end || !(end instanceof Date)) {
    if (date >= start) {
      selectedDates.value[1] = date;
    } else {
      selectedDates.value = [date, null];
    }
  } else {
    selectedDates.value = [date, null];
  }
  updateFormDates();
};

const updateFormDates = () => {
  const [start, end] = selectedDates.value;
  form.start_date = (start instanceof Date && !isNaN(start.getTime())) ? start.toISOString().slice(0,10) : '';
  form.end_date   = (end   instanceof Date && !isNaN(end.getTime()))   ? end.toISOString().slice(0,10)   : '';
};

const submitApplication = () => {
  if (!form.start_date || !form.end_date) {
    alert('Please select both start and end dates.');
    return;
  }
  form.post(route('leave.store'), {
    onSuccess: () => {
      form.reset();
      selectedDates.value = [null, null];
    },
  });
};

const statusClass = status => {
  if (status === 'approved') return 'bg-green-100 text-green-800';
  if (status === 'rejected') return 'bg-red-100 text-red-800';
  return 'bg-yellow-100 text-yellow-800';
};

const updateStatus = (request, newStatus) => {
  router.patch(route('leave.update', { leave_application: request.id }), {
    status: newStatus,
  }, { preserveScroll: true });
};

const cancelLeave = (request) => {
  if (confirm('Are you sure you want to cancel this leave request?')) {
    router.delete(route('leave.cancel', { leave_application: request.id }), {
      preserveScroll: true,
    });
  }
};
</script>

<template>
  <Head title="Leave Applications" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Leave Applications</h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div v-if="!canManage" class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
          <section>
            <header>
              <h2 class="text-lg font-medium text-gray-900">Submit a New Leave Request</h2>
            </header>

            <form @submit.prevent="submitApplication" class="mt-6 space-y-6">
              <div>
                <InputLabel value="Select Leave Date Range" />
                <VueCal
                  style="height: 600px"
                  default-view="month"
                  active-view="month"
                  :from-page="today"
                  :data-split="false"
                  :events-on-month-view="true"
                  :hide-events="false"
                  :disable-views="['years', 'year', 'week', 'day']"
                  :time="false"
                  :events="vuecalEvents"
                  @cell-click="onCellClick"
                />
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
    </div>
  </AuthenticatedLayout>
</template>

<style>
.vuecal__cell-content {
  position: relative !important;
  min-height: 50px;
  box-sizing: border-box;
  border: 1px solid #cbd5e1 !important;
  overflow: hidden;
}

/* Full-cell overlays for events (no label/text override) */
.vuecal__event.approved,
.vuecal__event.pending,
.vuecal__event.selected-range,
.vuecal__event.selected-start,
.vuecal__event.selected-range-end {
  position: absolute !important;
  inset: 0 !important;
  width: 100% !important;
  height: 100% !important;
  z-index: 1;
  border-radius: 0 !important;
  box-sizing: border-box;
  pointer-events: none;
  font-size: 0 !important;    /* HIDE event label text */
  line-height: 0 !important;  /* HIDE event label text */
  padding: 0 !important;
  justify-content: unset !important;
  align-items: unset !important;
}

.vuecal__event.approved       { background: #4caf50 !important; }
.vuecal__event.pending        { background: #ff9800 !important; }
.vuecal__event.selected-range { background: #2563eb !important; opacity: 0.9; }
.vuecal__event.selected-start { background: #ea580c !important; border-left: 3px solid #1e40af !important; }
.vuecal__event.selected-range-end {
  background: #ffe066 !important;
  border-right: 3px solid #7038d1 !important;
  z-index: 3 !important;
  width: 97% !important; height: 97% !important; margin: 1.5%;
  box-shadow: 0 0 0 2px #7038d144;
}
.vuecal__event.selected-start,
.vuecal__event.selected-range-end { opacity: 1 !important; }

.vuecal__cell--today {
  background: #dbeafe !important;
  border: 2px solid #2563eb !important;
  z-index: 10;
}

/* Make sure the day number is always visible and above overlays */
.vuecal__cell-date {
  position: relative;
  z-index: 2;
  color: #222 !important;
  background: transparent !important;
  font-size: 1.05em;
  font-weight: bold;
}
</style>

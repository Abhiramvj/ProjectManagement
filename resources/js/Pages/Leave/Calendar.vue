<script setup>
import { reactive, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { eachDayOfInterval, format, isWithinInterval, parseISO, startOfMonth, endOfMonth, isWeekend } from 'date-fns';
import { pickBy } from 'lodash-es';
import flatPickr from 'vue-flatpickr-component';
import 'flatpickr/dist/flatpickr.css';


const props = defineProps({
  usersWithLeaves: Array,
  teams: Array,
  filters: Object,
  dateRange: Object,
});


// State: initialize with props from the backend
const filterForm = reactive({
  search: props.filters.search ?? '',
  team_id: props.filters.team_id ?? '',
  start_date: props.dateRange.start,
  end_date: props.dateRange.end,
  absent_only: !!props.filters.absent_only,
});


const singleDateConfig = {
  mode: 'single',
  dateFormat: 'Y-m-d',
  altInput: true,
  altFormat: 'M j, Y',
};


// **NEW**: Manual filter function that triggers only when button is clicked
const applyFilters = () => {
  // Validate that both dates are present
  if (!filterForm.start_date || !filterForm.end_date) {
    alert('Please select both start and end dates');
    return;
  }

  // Use lodash's pickBy to create a clean query object, removing any empty/null values
  const params = pickBy({
    search: filterForm.search,
    team_id: filterForm.team_id,
    absent_only: filterForm.absent_only ? 1 : undefined,
    start_date: filterForm.start_date,
    end_date: filterForm.end_date,
  });

  router.get(route('leaves.calendar'), params, {
    preserveState: true,
    replace: true,
  });
};


// --- Button Click Handlers ---
const showToday = () => {
  const today = format(new Date(), 'yyyy-MM-dd');
  filterForm.start_date = today;
  filterForm.end_date = today;
  filterForm.absent_only = true;
  // Auto-apply filters for quick actions
  applyFilters();
};


const setCurrentMonth = () => {
  const today = new Date();
  filterForm.start_date = format(startOfMonth(today), 'yyyy-MM-dd');
  filterForm.end_date = format(endOfMonth(today), 'yyyy-MM-dd');
  filterForm.absent_only = false;
  // Auto-apply filters for quick actions
  applyFilters();
};


const setCurrentWeek = () => {
  const today = new Date();
  const dayOfWeek = today.getDay() === 0 ? 7 : today.getDay(); // Monday=1, Sunday=7
  const startOfWeek = new Date(today);
  startOfWeek.setDate(today.getDate() - dayOfWeek + 1);
  const endOfWeek = new Date(startOfWeek);
  endOfWeek.setDate(startOfWeek.getDate() + 6);

  filterForm.start_date = format(startOfWeek, 'yyyy-MM-dd');
  filterForm.end_date = format(endOfWeek, 'yyyy-MM-dd');
  filterForm.absent_only = false;
  // Auto-apply filters for quick actions
  applyFilters();
};


const resetFilters = () => {
  filterForm.search = '';
  filterForm.team_id = '';
  filterForm.absent_only = false;
  const today = new Date();
  filterForm.start_date = format(startOfMonth(today), 'yyyy-MM-dd');
  filterForm.end_date = format(endOfMonth(today), 'yyyy-MM-dd');
  // Auto-apply filters for reset action
  applyFilters();
};


// **IMPROVEMENT**: Refined date handlers that only prevent illogical ranges
const handleStartDateChange = () => {
  if (filterForm.end_date && filterForm.start_date > filterForm.end_date) {
    filterForm.end_date = filterForm.start_date;
  }
};


const handleEndDateChange = () => {
  if (filterForm.start_date && filterForm.end_date < filterForm.start_date) {
    filterForm.start_date = filterForm.end_date;
  }
};


// --- Computed Properties & Helpers ---
const calendarDays = computed(() => {
  if (!props.dateRange.start || !props.dateRange.end) return [];
  try {
    const start = parseISO(props.dateRange.start);
    const end = parseISO(props.dateRange.end);
    return eachDayOfInterval({ start, end }).map(day => ({
      date: format(day, 'yyyy-MM-dd'),
      dayOfMonth: format(day, 'd'),
      isWeekend: isWeekend(day),
    }));
  } catch (error) {
    console.error('Error generating calendar days:', error);
    return [];
  }
});


const isLeaveDay = (user, date) => {
  return user.leave_applications?.some(leave =>
    isWithinInterval(parseISO(date), {
      start: parseISO(leave.start_date),
      end: parseISO(leave.end_date),
    })
  );
};


const getLeaveReason = (user, date) => {
  const leave = user.leave_applications?.find(l =>
    isWithinInterval(parseISO(date), {
      start: parseISO(l.start_date),
      end: parseISO(l.end_date),
    })
  );
  return leave?.reason || '';
};
</script>


<template>
  <Head title="Leave Calendar" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Leave Calendar</h2>
    </template>


    <div class="py-12">
      <div class="max-w-full mx-auto sm:px-6 lg:px-8">
        <div class="bg-white shadow sm:rounded-lg p-6 text-gray-900">


          <!-- Filter Bar -->
          <div class="mb-6 p-4 bg-gray-50 rounded">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-7 gap-4 items-end">
              <div>
                <label for="search" class="block text-sm font-medium mb-1">Employee</label>
                <input
                  v-model="filterForm.search"
                  id="search"
                  type="text"
                  placeholder="Search name…"
                  class="block w-full px-3 py-2 border rounded text-sm border-gray-300"
                  @keyup.enter="applyFilters"
                >
              </div>
              <div>
                <label for="team" class="block text-sm font-medium mb-1">Department</label>
                <select
                  v-model="filterForm.team_id"
                  id="team"
                  class="block w-full px-3 py-2 border rounded text-sm border-gray-300"
                >
                  <option value="">All Departments</option>
                  <option v-for="team in teams" :key="team.id" :value="team.id">
                    {{ team.name }}
                  </option>
                </select>
              </div>
              <div>
                <label for="start-date" class="block text-sm font-medium mb-1">Start Date</label>
                <flat-pickr
                  v-model="filterForm.start_date"
                  id="start-date"
                  :config="singleDateConfig"
                  class="block w-full px-3 py-2 border rounded text-sm border-gray-300"
                  placeholder="Start date..."
                  @on-change="handleStartDateChange"
                />
              </div>
              <div>
                <label for="end-date" class="block text-sm font-medium mb-1">End Date</label>
                <flat-pickr
                  v-model="filterForm.end_date"
                  id="end-date"
                  :config="singleDateConfig"
                  class="block w-full px-3 py-2 border rounded text-sm border-gray-300"
                  placeholder="End date..."
                  @on-change="handleEndDateChange"
                />
              </div>
              <div class="flex items-center">
                <label for="absent-toggle" class="text-sm mr-2 font-medium text-gray-700">
                  Absent Only
                </label>
                <input
                  v-model="filterForm.absent_only"
                  id="absent-toggle"
                  type="checkbox"
                  class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                >
              </div>

              <!-- **NEW**: Apply Filter Button -->
              <div>
                <button
                  @click="applyFilters"
                  class="w-full bg-indigo-600 text-white px-4 py-2 rounded text-sm hover:bg-indigo-700 transition-colors font-medium"
                >
                  Apply Filter
                </button>
              </div>

              <!-- Quick Action Buttons -->
              <div class="flex gap-1">
                <button
                  @click="showToday"
                  class="flex-1 bg-red-500 text-white px-2 py-2 rounded text-xs hover:bg-red-600 transition-colors"
                  title="Show today's absences"
                >
                  Today
                </button>
                <button
                  @click="setCurrentWeek"
                  class="flex-1 bg-green-500 text-white px-2 py-2 rounded text-xs hover:bg-green-600 transition-colors"
                  title="Show current week"
                >
                  Week
                </button>
                <button
                  @click="setCurrentMonth"
                  class="flex-1 bg-blue-500 text-white px-2 py-2 rounded text-xs hover:bg-blue-600 transition-colors"
                  title="Show current month"
                >
                  Month
                </button>
                <button
                  @click="resetFilters"
                  class="flex-1 bg-gray-500 text-white px-2 py-2 rounded text-xs hover:bg-gray-600 transition-colors"
                  title="Reset all filters"
                >
                  Reset
                </button>
              </div>
            </div>
          </div>


          <!-- Info banner -->
          <div class="mb-4 p-3 bg-blue-50 border-l-4 border-blue-400">
            <div class="flex justify-between items-center">
              <div>
                <p class="text-sm text-blue-700">
                  <strong>Period:</strong>
                  <template v-if="dateRange.start && dateRange.start === dateRange.end">
                    {{ format(parseISO(dateRange.start), 'MMM d, yyyy') }}
                  </template>
                  <template v-else-if="dateRange.start && dateRange.end">
                    {{ format(parseISO(dateRange.start), 'MMM d, yyyy') }} –
                    {{ format(parseISO(dateRange.end), 'MMM d, yyyy') }}
                  </template>
                </p>
                <p class="text-xs text-blue-600">
                  {{ usersWithLeaves.length }} employees • {{ calendarDays.length }} days
                </p>
              </div>
            </div>
          </div>


          <!-- Calendar Table -->
          <div class="overflow-x-auto border rounded">
            <table class="min-w-full border-collapse">
              <thead>
                <tr class="bg-gray-100">
                  <th class="sticky left-0 bg-gray-100 z-10 px-4 py-2 text-sm text-left border">
                    Employee
                  </th>
                  <th
                    v-for="day in calendarDays"
                    :key="day.date"
                    class="px-2 py-2 text-center text-xs border min-w-[30px]"
                    :class="{ 'bg-gray-200 text-gray-400': day.isWeekend }"
                  >
                    {{ day.dayOfMonth }}
                  </th>
                </tr>
              </thead>
              <tbody v-if="usersWithLeaves.length > 0">
                <tr v-for="user in usersWithLeaves" :key="user.id" class="hover:bg-gray-50">
                  <td class="sticky left-0 bg-white z-10 px-4 py-2 text-sm font-medium border">
                    {{ user.name }}
                  </td>
                  <td
                    v-for="day in calendarDays"
                    :key="user.id + day.date"
                    class="text-center text-xs border h-10 min-w-[30px]"
                    :class="{
                      'bg-red-200': isLeaveDay(user, day.date),
                      'bg-gray-100': day.isWeekend && !isLeaveDay(user, day.date),
                      'bg-green-50': !day.isWeekend && !isLeaveDay(user, day.date)
                    }"
                    :title="isLeaveDay(user, day.date)
                      ? `${user.name} on leave — ${getLeaveReason(user, day.date)}`
                      : (day.isWeekend ? 'Weekend' : 'Working Day')"
                  >
                    <span v-if="isLeaveDay(user, day.date)" class="text-red-800 font-bold">L</span>
                    <span v-else-if="day.isWeekend" class="text-gray-400">·</span>
                    <span v-else-if="new Date(day.date) <= new Date()" class="text-green-500">✓</span>
                    <span v-else> </span>
                  </td>
                </tr>
              </tbody>
              <tbody v-else>
                <tr>
                  <td :colspan="calendarDays.length + 1" class="text-center py-10 text-gray-500">
                    <p class="font-semibold">No employees found for the selected criteria.</p>
                    <button
                      @click="resetFilters"
                      class="mt-2 text-sm text-blue-600 hover:underline"
                    >
                      Reset Filters
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>


        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>


<style scoped>
.sticky {
  position: sticky;
}


tr:hover .sticky {
  background-color: #f9fafb; /* bg-gray-50 on hover */
}


/* Ensure flatpickr input is properly styled inside the layout */
:deep(.flatpickr-input) {
  background-color: white;
}
</style>

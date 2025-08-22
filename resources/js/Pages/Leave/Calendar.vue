<script setup>
import { router } from '@inertiajs/vue3';
import { format, startOfWeek, endOfWeek, startOfMonth, endOfMonth } from 'date-fns';
import { watch, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

// Props from backend
const props = defineProps({
  calendarData: Array,
  dateRange: Array,
  teams: Array,
  filters: Object,
});

// Reactive filter state initialized from props
const search = ref(props.filters.search || '');
const team_id = ref(props.filters.team_id || '');
const start_date = ref(props.filters.start_date);
const end_date = ref(props.filters.end_date);
const absent_only = ref(props.filters.absent_only || false);

// Debounced filtering timeout
let filterTimeout = null;

// Apply backend filters (triggers Inertia reload)
const applyFilters = () => {
  const queryData = {
    search: search.value,
    team_id: team_id.value,
    start_date: start_date.value,
    end_date: end_date.value,
    absent_only: absent_only.value ? '1' : '0',
  };

  router.get(route('leaves.calendar'), queryData, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
};

const applyFiltersDebounced = () => {
  clearTimeout(filterTimeout);
  filterTimeout = setTimeout(applyFilters, 500); // 500ms debounce
};

// Watch for changes to filter inputs
watch(search, applyFiltersDebounced);
watch(team_id, applyFilters);
watch(start_date, applyFilters);
watch(end_date, applyFilters);
watch(absent_only, applyFilters);

// Date range shortcuts and filter reset
const resetFilters = () => {
  search.value = '';
  team_id.value = '';
  start_date.value = format(startOfMonth(new Date()), 'yyyy-MM-dd');
  end_date.value = format(endOfMonth(new Date()), 'yyyy-MM-dd');
  absent_only.value = false;
  // applyFilters will be triggered automatically by the watchers
};

const setDateRangeAndApply = (period) => {
  const today = new Date();
  if (period === 'today') {
    start_date.value = format(today, 'yyyy-MM-dd');
    end_date.value = format(today, 'yyyy-MM-dd');
  } else if (period === 'week') {
    start_date.value = format(startOfWeek(today, { weekStartsOn: 1 }), 'yyyy-MM-dd');
    end_date.value = format(endOfWeek(today, { weekStartsOn: 1 }), 'yyyy-MM-dd');
  } else if (period === 'month') {
    start_date.value = format(startOfMonth(new Date()), 'yyyy-MM-dd');
    end_date.value = format(endOfMonth(new Date()), 'yyyy-MM-dd');
  }
};

const getDay = (dateString) => {
  if (!dateString) return '';
  return format(new Date(dateString), 'd');
};
</script>

<template>
    <AuthenticatedLayout title="Leave Calendar">
        <div class="p-4 sm:p-6 lg:p-8">
            <h1 class="text-2xl font-bold mb-4 text-gray-800">Leave Calendar</h1>
            
            <!-- Filter Section -->
            <div class="p-4 bg-white rounded-lg shadow-sm mb-6 border border-gray-200">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label for="employee_search" class="block text-sm font-medium text-gray-700">Employee</label>
                        <input 
                            v-model="search" 
                            type="text" 
                            id="employee_search" 
                            placeholder="Search name..." 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                        >
                    </div>
                    <div>
                        <label for="team_id" class="block text-sm font-medium text-gray-700">Team</label>
                        <select 
                            v-model="team_id" 
                            id="team_id" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                        >
                            <option value="">All Teams</option>
                            <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                        <input 
                            v-model="start_date" 
                            type="date" 
                            id="start_date" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                        >
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                        <input 
                            v-model="end_date" 
                            type="date" 
                            id="end_date" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                        >
                    </div>
                </div>
                
                <div class="flex flex-wrap items-center justify-between mt-4 gap-4">
                    <div class="flex items-center">
                        <input 
                            v-model="absent_only" 
                            type="checkbox" 
                            id="absent_only" 
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        >
                        <label for="absent_only" class="ml-2 block text-sm text-gray-900">
                            Show absent only
                            <span v-if="absent_only" class="ml-1 px-1 bg-green-100 text-green-800 text-xs rounded">ON</span>
                        </label>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <button @click="setDateRangeAndApply('today')" type="button" class="px-3 py-1.5 text-sm font-semibold text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 border border-gray-300">Today</button>
                        <button @click="setDateRangeAndApply('week')" type="button" class="px-3 py-1.5 text-sm font-semibold text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 border border-gray-300">This Week</button>
                        <button @click="setDateRangeAndApply('month')" type="button" class="px-3 py-1.5 text-sm font-semibold text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 border border-gray-300">This Month</button>
                        <button @click="resetFilters" class="text-sm text-blue-600 hover:underline font-semibold">Reset Filters</button>
                    </div>
                </div>
            </div>
            
            <!-- Calendar Table -->
            <div class="bg-white rounded-lg shadow-sm overflow-x-auto border border-gray-200">
                <div class="p-4 border-b flex justify-between items-center">
                    <p class="font-semibold text-gray-700">
                        Showing period: 
                        <span class="font-bold">{{ filters.start_date }}</span> 
                        to 
                        <span class="font-bold">{{ filters.end_date }}</span>
                        <span v-if="filters.absent_only" class="ml-2 px-2 py-1 bg-red-100 text-red-800 text-xs rounded">ABSENT ONLY</span>
                    </p>
                    <p class="text-sm text-gray-600">
                        {{ calendarData.length }} employee(s) found
                    </p>
                </div>
                
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-50 z-10 w-48">Employee</th>
                            <th v-for="date in dateRange" :key="date" scope="col" class="w-10 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ getDay(date) }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="user in calendarData" :key="user.id" class="hover:bg-gray-50">
                            <td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-900 sticky left-0 bg-white hover:bg-gray-50 z-10 w-48">
                                <div>
                                    <div class="font-medium">{{ user.name }}</div>
                                    <div v-if="user.teams" class="text-xs text-gray-500">{{ user.teams }}</div>
                                </div>
                            </td>
                            <td v-for="date in dateRange" :key="date" class="text-center py-2">
                                <div class="flex items-center justify-center h-full">
                                    <div v-if="user.daily_statuses[date] && user.daily_statuses[date].status === 'Leave'" :title="`Type: ${user.daily_statuses[date].details.leave_type}`" class="w-6 h-6 rounded flex items-center justify-center text-white font-bold text-xs cursor-help" :style="{ backgroundColor: user.daily_statuses[date].details.color }">{{ user.daily_statuses[date].details.code }}</div>
                                    <div v-else-if="user.daily_statuses[date] && user.daily_statuses[date].status === 'Working'" class="text-green-500 text-lg" title="Present">✓</div>
                                    <div v-else-if="user.daily_statuses[date] && user.daily_statuses[date].status === 'Future'" class="text-gray-300 text-sm" title="Future date">○</div>
                                    <div v-else class="text-gray-400" title="Weekend/Holiday">·</div>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="calendarData.length === 0">
                             <td :colspan="dateRange.length + 1" class="text-center py-10 text-gray-500">
                                <div v-if="filters.absent_only">
                                    <div class="text-lg mb-2">👥</div>
                                    <div class="font-medium">No employees with absences found</div>
                                    <div class="text-sm text-gray-400 mt-1">Try expanding the date range or uncheck "Show absent only"</div>
                                </div>
                                <div v-else>No data available for the selected period and filters.</div>
                             </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Legend -->
            <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                <h3 class="text-sm font-medium text-gray-700 mb-2">Info:</h3>
                <div class="flex flex-wrap gap-4 text-xs text-gray-600">
                    <div class="flex items-center gap-1"><span class="text-green-500 text-lg">✓</span><span>Present</span></div>
                    <div class="flex items-center gap-1"><div class="w-4 h-4 rounded bg-red-400 flex items-center justify-center text-white text-xs">A</div><span>Annual Leave</span></div>
                    <div class="flex items-center gap-1"><div class="w-4 h-4 rounded bg-orange-400 flex items-center justify-center text-white text-xs">S</div><span>Sick Leave</span></div>
                    <div class="flex items-center gap-1"><span class="text-gray-300 text-sm">○</span><span>Future date</span></div>
                    <div class="flex items-center gap-1"><span class="text-gray-400">·</span><span>Weekend/Holiday</span></div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
th.sticky, td.sticky {
    position: -webkit-sticky;
    position: sticky;
    left: 0;
    box-shadow: 1px 0 3px rgba(0,0,0,0.1);
}
thead th.sticky {
    z-index: 20;
}
tbody td.sticky {
    z-index: 10;
}
</style>
<script setup>
// --- FIX: Import the Link component for pagination ---
import { Head, router, Link } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import {
    format,
    startOfWeek,
    endOfWeek,
    startOfMonth,
    endOfMonth,
} from 'date-fns';
import { watch, ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

// Props from backend
const props = defineProps({
    // --- FIX: calendarData is now a Paginator Object, not a simple Array ---
    calendarData: Object,
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
};

const setDateRangeAndApply = (period) => {
    const today = new Date();
    if (period === 'today') {
        start_date.value = format(today, 'yyyy-MM-dd');
        end_date.value = format(today, 'yyyy-MM-dd');
    } else if (period === 'week') {
        start_date.value = format(
            startOfWeek(today, { weekStartsOn: 1 }),
            'yyyy-MM-dd',
        );
        end_date.value = format(
            endOfWeek(today, { weekStartsOn: 1 }),
            'yyyy-MM-dd',
        );
    } else if (period === 'month') {
        start_date.value = format(startOfMonth(new Date()), 'yyyy-MM-dd');
        end_date.value = format(endOfMonth(new Date()), 'yyyy-MM-dd');
    }
};

const getDay = (dateString) => {
    if (!dateString) return '';
    return format(new Date(dateString), 'd');
};

// --- FIX: Computed property to safely access pagination links ---
const paginationLinks = computed(() => props.calendarData.links || []);
</script>

<template>
    <Head title="Leave Calendar" />
    <AuthenticatedLayout>
        <div class="p-4 sm:p-6 lg:p-8">
            <h1 class="mb-4 text-2xl font-bold text-gray-800">
                Leave Calendar
            </h1>

            <!-- Filter Section (Unchanged) -->
            <div
                class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm"
            >
                <div
                    class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4"
                >
                    <div>
                        <label
                            for="employee_search"
                            class="block text-sm font-medium text-gray-700"
                            >Employee</label
                        >
                        <input
                            v-model="search"
                            type="text"
                            id="employee_search"
                            placeholder="Search name..."
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                        />
                    </div>
                    <div>
                        <label
                            for="team_id"
                            class="block text-sm font-medium text-gray-700"
                            >Team</label
                        >
                        <select
                            v-model="team_id"
                            id="team_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                        >
                            <option value="">All Teams</option>
                            <option
                                v-for="team in teams"
                                :key="team.id"
                                :value="team.id"
                            >
                                {{ team.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label
                            for="start_date"
                            class="block text-sm font-medium text-gray-700"
                            >Start Date</label
                        >
                        <input
                            v-model="start_date"
                            type="date"
                            id="start_date"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                        />
                    </div>
                    <div>
                        <label
                            for="end_date"
                            class="block text-sm font-medium text-gray-700"
                            >End Date</label
                        >
                        <input
                            v-model="end_date"
                            type="date"
                            id="end_date"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                        />
                    </div>
                </div>

                <div
                    class="mt-4 flex flex-wrap items-center justify-between gap-4"
                >
                    <div class="flex items-center">
                        <input
                            v-model="absent_only"
                            type="checkbox"
                            id="absent_only"
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <label
                            for="absent_only"
                            class="ml-2 block text-sm text-gray-900"
                        >
                            Show absent only
                        </label>
                    </div>

                    <div class="flex items-center space-x-2">
                        <button
                            @click="setDateRangeAndApply('today')"
                            type="button"
                            class="rounded-md border border-gray-300 bg-gray-100 px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-200"
                        >
                            Today
                        </button>
                        <button
                            @click="setDateRangeAndApply('week')"
                            type="button"
                            class="rounded-md border border-gray-300 bg-gray-100 px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-200"
                        >
                            This Week
                        </button>
                        <button
                            @click="setDateRangeAndApply('month')"
                            type="button"
                            class="rounded-md border border-gray-300 bg-gray-100 px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-200"
                        >
                            This Month
                        </button>
                        <button
                            @click="resetFilters"
                            class="text-sm font-semibold text-blue-600 hover:underline"
                        >
                            Reset Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Calendar Table -->
            <div
                class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm"
            >
                <div class="flex items-center justify-between border-b p-4">
                    <p class="font-semibold text-gray-700">
                        Showing period:
                        <span class="font-bold">{{ filters.start_date }}</span>
                        to
                        <span class="font-bold">{{ filters.end_date }}</span>
                    </p>
                    <!-- FIX: Use the paginator's total count -->
                    <p class="text-sm text-gray-600">
                        {{ calendarData.total }} employee(s) found
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    scope="col"
                                    class="sticky left-0 z-10 w-48 bg-gray-50 px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                >
                                    Employee
                                </th>
                                <th
                                    v-for="date in dateRange"
                                    :key="date"
                                    scope="col"
                                    class="w-10 text-center text-xs font-medium uppercase tracking-wider text-gray-500"
                                >
                                    {{ getDay(date) }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <!-- FIX: Loop over `calendarData.data` instead of `calendarData` -->
                            <tr
                                v-for="user in calendarData.data"
                                :key="user.id"
                                class="hover:bg-gray-50"
                            >
                                <td
                                    class="sticky left-0 z-10 w-48 whitespace-nowrap bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-50"
                                >
                                    <div>
                                        <div class="font-medium">
                                            {{ user.name }}
                                        </div>
                                        <div
                                            v-if="user.teams"
                                            class="text-xs text-gray-500"
                                        >
                                            {{ user.teams }}
                                        </div>
                                    </div>
                                </td>
                                <td
                                    v-for="date in dateRange"
                                    :key="date"
                                    class="py-2 text-center"
                                >
                                    <div
                                        class="flex h-full items-center justify-center"
                                    >
                                        <div
                                            v-if="
                                                user.daily_statuses[date] &&
                                                user.daily_statuses[date]
                                                    .status === 'Leave'
                                            "
                                            :title="`Type: ${user.daily_statuses[date].details.leave_type}`"
                                            class="flex h-6 w-6 cursor-help items-center justify-center rounded text-xs font-bold text-white"
                                            :style="{
                                                backgroundColor:
                                                    user.daily_statuses[date]
                                                        .details.color,
                                            }"
                                        >
                                            {{
                                                user.daily_statuses[date]
                                                    .details.code
                                            }}
                                        </div>
                                        <div
                                            v-else-if="
                                                user.daily_statuses[date] &&
                                                user.daily_statuses[date]
                                                    .status === 'Working'
                                            "
                                            class="text-lg text-green-500"
                                            title="Present"
                                        >
                                            ✓
                                        </div>
                                        <div
                                            v-else-if="
                                                user.daily_statuses[date] &&
                                                user.daily_statuses[date]
                                                    .status === 'Future'
                                            "
                                            class="text-sm text-gray-300"
                                            title="Future date"
                                        >
                                            ○
                                        </div>
                                        <div
                                            v-else
                                            class="text-gray-400"
                                            title="Weekend/Holiday"
                                        >
                                            ·
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <!-- FIX: Check `calendarData.data.length` for the no results message -->
                            <tr v-if="calendarData.data.length === 0">
                                <td
                                    :colspan="dateRange.length + 1"
                                    class="py-10 text-center text-gray-500"
                                >
                                    <div v-if="filters.absent_only">
                                        <div class="mb-2 text-lg">👥</div>
                                        <div class="font-medium">
                                            No employees with absences found
                                        </div>
                                        <div class="mt-1 text-sm text-gray-400">
                                            Try expanding the date range or
                                            uncheck "Show absent only"
                                        </div>
                                    </div>
                                    <div v-else>
                                        No data available for the selected
                                        period and filters.
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- FIX: ADD PAGINATION CONTROLS -->
                <div
                    v-if="calendarData.total > 0"
                    class="flex items-center justify-between border-t border-gray-200 p-4"
                >
                    <div class="text-sm text-gray-600">
                        Showing {{ calendarData.from }} to
                        {{ calendarData.to }} of
                        {{ calendarData.total }} results
                    </div>
                    <nav
                        class="isolate inline-flex -space-x-px rounded-md shadow-sm"
                    >
                        <Pagination :links="paginationLinks" />
                    </nav>
                </div>
            </div>

            <!-- Legend (Unchanged) -->
            <div class="mt-4 rounded-lg bg-gray-50 p-3">
                <h3 class="mb-2 text-sm font-medium text-gray-700">Info:</h3>
                <div class="flex flex-wrap gap-4 text-xs text-gray-600">
                    <div class="flex items-center gap-1">
                        <span class="text-lg text-green-500">✓</span
                        ><span>Present</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <div
                            class="flex h-4 w-4 items-center justify-center rounded bg-red-400 text-xs text-white"
                        >
                            A
                        </div>
                        <span>Annual Leave</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <div
                            class="flex h-4 w-4 items-center justify-center rounded bg-orange-400 text-xs text-white"
                        >
                            S
                        </div>
                        <span>Sick Leave</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="text-sm text-gray-300">○</span
                        ><span>Future date</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="text-gray-400">·</span
                        ><span>Weekend/Holiday</span>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
th.sticky,
td.sticky {
    position: -webkit-sticky;
    position: sticky;
    left: 0;
    box-shadow: 1px 0 3px rgba(0, 0, 0, 0.1);
}
thead th.sticky {
    z-index: 20;
}
tbody td.sticky {
    z-index: 10;
}
</style>

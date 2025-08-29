<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import axios from 'axios'; // Import axios for API calls

// Import Chart.js components
import { Bar, Doughnut } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
    ArcElement,
} from 'chart.js';

ChartJS.register(
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
    ArcElement,
);

const props = defineProps({
    employee: Object,
    taskStats: Object,
    timeStats: Object,
    weeklyHours: Array,
    projectHours: Array,
    leaveStats: Object,
});

// --- State Variables for AI Summary ---
const generatedSummary = ref('');
const isLoadingSummary = ref(false);
const summaryError = ref('');

// Computed property for the overall performance score
const performanceScore = computed(() => {
    // Ensure stats are present before calculating
    if (!props.taskStats || !props.timeStats || !props.leaveStats) {
        return NaN; // Return Not-a-Number if data is missing
    }
    const taskScore = props.taskStats.completion_rate;
    const timeScore = Math.min(
        100,
        (props.timeStats.current_month / 160) * 100,
    );
    const totalLeaveAllowance =
        props.leaveStats.balance > 0 ? props.leaveStats.balance : 1;
    const leaveScore = Math.max(
        0,
        100 - (props.leaveStats.current_year / totalLeaveAllowance) * 100,
    );
    return Math.round((taskScore + timeScore + leaveScore) / 3);
});

// --- [+] Proactive check to enable/disable the summary button ---
const isDataReadyForSummary = computed(() => {
    return (
        props.taskStats &&
        props.timeStats &&
        props.leaveStats &&
        typeof performanceScore.value === 'number' &&
        !isNaN(performanceScore.value)
    );
});

// --- [+] Function to fetch AI summary with DETAILED error handling ---
const fetchAiSummary = async () => {
    isLoadingSummary.value = true;
    summaryError.value = '';
    generatedSummary.value = '';

    try {
        const response = await axios.post(
            route('performance.generateSummary', { user: props.employee.id }),
            {
                taskStats: props.taskStats,
                timeStats: props.timeStats,
                leaveStats: props.leaveStats,
                performanceScore: performanceScore.value,
            },
        );
        generatedSummary.value = response.data.summary;
    } catch (error) {
        console.error('Error generating AI summary:', error.response); // Log the full response for easier debugging

        if (error.response && error.response.status === 422) {
            // Handle Laravel Validation Errors (422)
            const errors = error.response.data.errors;
            const firstErrorKey = Object.keys(errors)[0]; // Get the first field that failed
            const firstErrorMessage = errors[firstErrorKey][0]; // Get its error message
            summaryError.value = `Validation Failed: ${firstErrorMessage}`;
        } else {
            // Fallback for other errors (500, network issues, etc.)
            summaryError.value =
                error.response?.data?.error ||
                'An unexpected error occurred. Please check the server logs.';
        }
    } finally {
        isLoadingSummary.value = false;
    }
};

const getPerformanceGrade = (score) => {
    if (score >= 90)
        return {
            grade: 'A+',
            color: 'text-green-600',
            bgColor: 'bg-green-100',
        };
    if (score >= 80)
        return { grade: 'A', color: 'text-green-600', bgColor: 'bg-green-100' };
    if (score >= 70)
        return { grade: 'B+', color: 'text-blue-600', bgColor: 'bg-blue-100' };
    if (score >= 60)
        return { grade: 'B', color: 'text-blue-600', bgColor: 'bg-blue-100' };
    if (score >= 50)
        return {
            grade: 'C',
            color: 'text-yellow-600',
            bgColor: 'bg-yellow-100',
        };
    return { grade: 'D', color: 'text-red-600', bgColor: 'bg-red-100' };
};

// --- Chart.js Data Configurations ---
const taskChartData = computed(() => ({
    labels: ['Completed', 'In Progress', 'Pending'],
    datasets: [
        {
            backgroundColor: ['#10b981', '#f59e0b', '#3b82f6'],
            data: [
                props.taskStats?.completed || 0,
                props.taskStats?.in_progress || 0,
                props.taskStats?.pending || 0,
            ],
            borderWidth: 0,
        },
    ],
}));

const weeklyHoursChartData = computed(() => ({
    labels: props.weeklyHours.map((w) => w.week),
    datasets: [
        {
            label: 'Hours Worked',
            backgroundColor: '#3b82f6',
            borderRadius: 4,
            data: props.weeklyHours.map((w) => w.hours),
        },
    ],
}));

const chartOptions = { responsive: true, maintainAspectRatio: false };
const barChartOptions = {
    ...chartOptions,
    plugins: { legend: { display: false } },
    scales: {
        y: { beginAtZero: true, grid: { drawBorder: false } },
        x: { grid: { display: false } },
    },
};
const doughnutChartOptions = {
    ...chartOptions,
    cutout: '75%',
    plugins: {
        legend: { position: 'right', labels: { boxWidth: 12, padding: 15 } },
    },
};
</script>

<template>
    <Head :title="'Performance: ' + employee.name" />

    <AuthenticatedLayout>
        <div class="p-4 font-sans sm:p-6 lg:p-8">
            <div class="mx-auto max-w-7xl space-y-6">
                <!-- Page Header -->
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900">
                            Performance Review
                        </h1>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ employee.name }} • {{ employee.email }}
                        </p>
                    </div>
                    <div class="flex items-center gap-4">
                        <!-- [+] BUTTON UPDATED with smarter :disabled logic -->
                        <button
                            @click="fetchAiSummary"
                            :disabled="
                                isLoadingSummary || !isDataReadyForSummary
                            "
                            class="inline-flex items-center justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white ring-blue-300 transition duration-150 ease-in-out hover:bg-blue-700 focus:border-blue-800 focus:outline-none focus:ring active:bg-blue-800 disabled:opacity-50"
                            :title="
                                !isDataReadyForSummary
                                    ? 'Cannot generate summary: Missing performance data.'
                                    : 'Generate AI-powered summary'
                            "
                        >
                            <span v-if="isLoadingSummary">Generating...</span>
                            <span v-else>Generate Summary</span>
                        </button>

                        <div class="flex items-center space-x-2 text-right">
                            <div>
                                <div class="text-xs text-slate-500">
                                    Overall Score
                                </div>
                                <div
                                    class="text-3xl font-bold"
                                    :class="
                                        getPerformanceGrade(performanceScore)
                                            .color
                                    "
                                >
                                    <span v-if="isDataReadyForSummary"
                                        >{{ performanceScore }}%</span
                                    >
                                    <span v-else class="text-slate-400"
                                        >N/A</span
                                    >
                                </div>
                            </div>
                            <div
                                v-if="isDataReadyForSummary"
                                :class="
                                    getPerformanceGrade(performanceScore)
                                        .bgColor
                                "
                                class="rounded-full px-3 py-1 text-sm font-semibold"
                            >
                                <span
                                    :class="
                                        getPerformanceGrade(performanceScore)
                                            .color
                                    "
                                    >Grade
                                    {{
                                        getPerformanceGrade(performanceScore)
                                            .grade
                                    }}</span
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- [+] AI SUMMARY DISPLAY SECTION with specific error message -->
                <div
                    v-if="isLoadingSummary || generatedSummary || summaryError"
                    class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
                >
                    <h3 class="mb-3 text-lg font-semibold text-slate-900">
                        AI-Generated Summary
                    </h3>

                    <div
                        v-if="isLoadingSummary"
                        class="animate-pulse space-y-3"
                    >
                        <div class="h-4 w-full rounded bg-slate-200"></div>
                        <div class="h-4 w-5/6 rounded bg-slate-200"></div>
                        <div class="h-4 w-full rounded bg-slate-200"></div>
                    </div>

                    <p
                        v-if="generatedSummary"
                        class="animate-fade-in whitespace-pre-wrap leading-relaxed text-slate-700"
                    >
                        {{ generatedSummary }}
                    </p>

                    <div
                        v-if="summaryError"
                        class="animate-fade-in rounded-lg border border-red-200 bg-red-100 p-4 text-red-700"
                    >
                        <p class="font-bold">Could not generate summary</p>
                        <p class="text-sm">{{ summaryError }}</p>
                    </div>
                </div>

                <!-- Key Metrics Cards -->
                <div
                    class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4"
                >
                    <!-- Cards ... (no changes needed below this line) -->
                    <div
                        class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                    >
                        <p class="text-sm font-medium text-slate-500">
                            Task Completion
                        </p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">
                            {{ taskStats?.completion_rate || 0 }}%
                        </p>
                        <p class="mt-1 text-xs text-slate-400">
                            {{ taskStats?.completed || 0 }} of
                            {{ taskStats?.total || 0 }} tasks completed
                        </p>
                    </div>
                    <div
                        class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                    >
                        <p class="text-sm font-medium text-slate-500">
                            Total Hours Logged
                        </p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">
                            {{ timeStats?.total_hours || 0 }}h
                        </p>
                        <p class="mt-1 text-xs text-slate-400">
                            {{ timeStats?.current_month || 0 }}h this month
                        </p>
                    </div>
                    <div
                        class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                    >
                        <p class="text-sm font-medium text-slate-500">
                            Leave Days (This Year)
                        </p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">
                            {{ leaveStats?.current_year || 0 }}
                            <span class="text-lg font-medium text-slate-400"
                                >/ {{ leaveStats?.balance || 0 }}</span
                            >
                        </p>
                        <p class="mt-1 text-xs text-slate-400">
                            {{ leaveStats?.remaining || 0 }} days remaining
                        </p>
                    </div>
                    <div
                        class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                    >
                        <p class="text-sm font-medium text-slate-500">
                            Avg. Daily Hours
                        </p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">
                            {{ timeStats?.daily_average || 0 }}h
                        </p>
                        <p class="mt-1 text-xs text-slate-400">
                            Average over all logged days
                        </p>
                    </div>
                </div>

                <!-- Chart Cards -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
                    <div
                        class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2"
                    >
                        <h3 class="mb-4 text-lg font-semibold text-slate-900">
                            Task Distribution
                        </h3>
                        <div class="h-64">
                            <Doughnut
                                :data="taskChartData"
                                :options="doughnutChartOptions"
                            />
                        </div>
                    </div>
                    <div
                        class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-3"
                    >
                        <h3 class="mb-4 text-lg font-semibold text-slate-900">
                            Weekly Hours (Last 8 Weeks)
                        </h3>
                        <div class="h-64">
                            <Bar
                                :data="weeklyHoursChartData"
                                :options="barChartOptions"
                            />
                        </div>
                    </div>
                </div>

                <!-- Hours by Project Table -->
                <div
                    class="rounded-xl border border-slate-200 bg-white shadow-sm"
                >
                    <header class="border-b border-slate-200 p-4 sm:px-6">
                        <h3 class="text-lg font-semibold text-slate-900">
                            Hours by Project
                        </h3>
                    </header>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th
                                        scope="col"
                                        class="px-6 py-3.5 text-left text-xs font-semibold uppercase text-slate-500"
                                    >
                                        Project Name
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3.5 text-left text-xs font-semibold uppercase text-slate-500"
                                    >
                                        Total Hours
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3.5 text-left text-xs font-semibold uppercase text-slate-500"
                                    >
                                        Percentage of Time
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr v-if="!projectHours.length">
                                    <td
                                        colspan="3"
                                        class="px-6 py-8 text-center text-slate-500"
                                    >
                                        No time logged against specific
                                        projects.
                                    </td>
                                </tr>
                                <tr
                                    v-for="project in projectHours"
                                    :key="project.project"
                                    class="hover:bg-slate-50"
                                >
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900"
                                    >
                                        {{ project.project }}
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-slate-600"
                                    >
                                        {{ project.hours }}h
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm text-slate-600"
                                    >
                                        <div class="flex items-center">
                                            <div
                                                class="mr-4 h-2 w-full rounded-full bg-slate-200"
                                            >
                                                <div
                                                    class="h-2 rounded-full bg-blue-500"
                                                    :style="{
                                                        width:
                                                            (project.hours /
                                                                (timeStats.total_hours ||
                                                                    1)) *
                                                                100 +
                                                            '%',
                                                    }"
                                                ></div>
                                            </div>
                                            <span
                                                >{{
                                                    Math.round(
                                                        (project.hours /
                                                            (timeStats.total_hours ||
                                                                1)) *
                                                            100,
                                                    )
                                                }}%</span
                                            >
                                        </div>
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

<style>
/* Simple fade-in animation */
.animate-fade-in {
    animation: fadeIn 0.5s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}
</style>

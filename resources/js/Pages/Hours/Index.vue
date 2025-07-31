<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    timeLogs: Object, // Laravel pagination object
    canViewAll: Boolean,
    assignableProjects: Array,
});

const form = useForm({
    work_date: new Date().toISOString().split('T')[0], // Defaults to today
    hours_worked: '',
    description: '',
    project_id: '',
});

const submitHours = () => {
    form.post(route('hours.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('hours_worked', 'description');
        },
    });
};

const logs = computed(() => props.timeLogs.data);
const paginationLinks = computed(() => props.timeLogs.links);

const dailyProgress = computed(() => {
    const today = new Date().toISOString().split('T')[0];
    const targetHours = 8;

    const totalHoursToday = logs.value
        .filter(log => log.work_date === today)
        .reduce((sum, log) => sum + parseFloat(log.hours_worked), 0);

    const percentage = (totalHoursToday / targetHours) * 100;
    return Math.min(percentage, 100);
});

</script>

<template>
    <Head title="Working Hours" />

    <AuthenticatedLayout>
        <div class="p-4 sm:p-6 lg:p-8 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto space-y-8">

                <!-- Page Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <h1 class="text-3xl font-bold text-gray-800">Working Hours</h1>
                    <p v-if="!canViewAll" class="mt-1 text-sm text-gray-600">Log your daily work and track your project contributions.</p>
                    <p v-else class="mt-1 text-sm text-gray-600">Review and manage time logs for all employees.</p>
                </div>

                <!-- Card 1: Form to Add Hours (Only for standard employees) -->
                <div v-if="!canViewAll" class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <header class="p-6 border-b border-gray-200 flex items-center gap-4">
                        <div class="bg-indigo-100 text-indigo-600 p-3 rounded-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Log Your Working Hours</h2>
                            <p class="text-sm text-gray-600 mt-0.5">Fill out the details below to submit your time entry.</p>
                        </div>
                    </header>

                    <!-- START: Stylish Daily Progress Bar -->
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-center mb-2">
                            <div>
                                <h3 class="text-sm font-medium text-gray-700">Today's Progress</h3>
                                <p class="text-xs text-gray-500">8-hour daily goal</p>
                            </div>
                            <span class="text-lg font-bold" :class="{
                                'text-green-600': dailyProgress >= 100,
                                'text-indigo-600': dailyProgress >= 50 && dailyProgress < 100,
                                'text-yellow-500': dailyProgress > 0 && dailyProgress < 50,
                                'text-gray-400': dailyProgress === 0
                            }">
                                {{ dailyProgress.toFixed(0) }}%
                            </span>
                        </div>
                        
                        <!-- Animated progress bar with gradient -->
                        <div class="relative pt-1">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="h-3 rounded-full bg-gray-200 overflow-hidden shadow-inner">
                                        <div 
                                            class="h-full rounded-full transition-all duration-1000 ease-out"
                                            :class="{
                                                'bg-gradient-to-r from-green-400 to-green-600': dailyProgress >= 100,
                                                'bg-gradient-to-r from-indigo-400 to-indigo-600': dailyProgress >= 50 && dailyProgress < 100,
                                                'bg-gradient-to-r from-yellow-400 to-yellow-500': dailyProgress > 0 && dailyProgress < 50,
                                                'bg-gray-300': dailyProgress === 0
                                            }"
                                            :style="{ width: dailyProgress + '%' }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-between mt-1 text-xs text-gray-500">
                                <span>0h</span>
                                <span>{{ (8 * dailyProgress / 100).toFixed(1) }}h logged</span>
                                <span>8h</span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            <span v-if="dailyProgress >= 100" class="text-green-600 font-medium">🎉 Great job! You've reached your daily goal!</span>
                            <span v-else-if="dailyProgress >= 75" class="text-indigo-600">Almost there! Just {{ (8 - 8 * dailyProgress / 100).toFixed(1) }}h more to go.</span>
                            <span v-else-if="dailyProgress > 0" class="text-gray-600">Keep going! {{ (8 - 8 * dailyProgress / 100).toFixed(1) }}h remaining.</span>
                            <span v-else class="text-gray-400">No hours logged yet today.</span>
                        </p>
                    </div>
                    <!-- END: Stylish Daily Progress Bar -->

                    <form @submit.prevent="submitHours" class="p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Project Select -->
                            <div class="relative">
                                <label for="project_id" class="block text-sm font-medium text-gray-700 mb-1">Project</label>
                                <select v-model="form.project_id" id="project_id" required
                                        class="pl-10 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="" disabled>-- Select a project --</option>
                                    <option v-for="project in assignableProjects" :key="project.id" :value="project.id">
                                        {{ project.name }}
                                    </option>
                                </select>
                                <div class="absolute inset-y-0 left-0 pl-3 top-6 flex items-center pointer-events-none">
                                  <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                </div>
                                <InputError class="mt-2" :message="form.errors.project_id" />
                            </div>
                            <!-- Work Date Input -->
                            <div class="relative">
                                <label for="work_date" class="block text-sm font-medium text-gray-700 mb-1">Work Date</label>
                                <input v-model="form.work_date" id="work_date" type="date" required
                                       class="pl-10 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                                <div class="absolute inset-y-0 left-0 pl-3 top-6 flex items-center pointer-events-none">
                                   <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                </div>
                                <InputError class="mt-2" :message="form.errors.work_date" />
                            </div>
                            <!-- Hours Worked Input -->
                             <div class="relative">
                                <label for="hours_worked" class="block text-sm font-medium text-gray-700 mb-1">Hours Worked</label>
                                <input v-model="form.hours_worked" id="hours_worked" type="number" step="0.25" min="0.25" max="24" required placeholder="e.g., 8 or 4.5"
                                       class="pl-10 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                                <div class="absolute inset-y-0 left-0 pl-3 top-6 flex items-center pointer-events-none">
                                   <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <InputError class="mt-2" :message="form.errors.hours_worked" />
                            </div>
                        </div>

                        <!-- Description Textarea -->
                        <div class="relative">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description / Notes</label>
                            <textarea v-model="form.description" id="description" rows="3" placeholder="Summarize your work for the day (optional)..."
                                      class="pl-10 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                            <div class="absolute left-0 top-0 pl-3 pt-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                            </div>
                            <InputError class="mt-2" :message="form.errors.description" />
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="flex items-center justify-end pt-4">
                             <button type="submit" :disabled="form.processing"
                                     class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg v-if="!form.processing" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                {{ form.processing ? 'Submitting...' : 'Log Hours' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Card 2: List of Time Logs -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <header class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">{{ canViewAll ? 'All Employee Hours' : 'My Logged Hours' }}</h2>
                    </header>
                    <div class="flow-root">
                        <div class="overflow-x-auto">
                            <div class="inline-block min-w-full align-middle">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th v-if="canViewAll" scope="col" class="py-3.5 px-6 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Employee</th>
                                            <th scope="col" class="py-3.5 px-6 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Project</th>
                                            <th scope="col" class="py-3.5 px-6 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date</th>
                                            <th scope="col" class="py-3.5 px-6 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Hours</th>
                                            <th scope="col" class="py-3.5 px-6 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Description</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 bg-white">
                                        <tr v-if="!logs.length">
                                            <td :colspan="canViewAll ? 5 : 4" class="px-6 py-12 text-center text-gray-500">
                                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" /></svg>
                                                <h3 class="mt-2 text-sm font-semibold text-gray-800">No hours logged</h3>
                                                <p class="mt-1 text-sm text-gray-600">Get started by filling out the form above.</p>
                                            </td>
                                        </tr>
                                        <tr v-for="log in logs" :key="log.id" class="hover:bg-gray-50 text-sm">
                                            <td v-if="canViewAll" class="whitespace-nowrap py-4 px-6 font-medium text-gray-800">{{ log.user.name }}</td>
                                            <td class="whitespace-nowrap py-4 px-6 text-gray-600">{{ log.project ? log.project.name : 'N/A' }}</td>
                                            <td class="whitespace-nowrap py-4 px-6 text-gray-600">{{ new Date(log.work_date).toLocaleDateString() }}</td>
                                            <td class="whitespace-nowrap py-4 px-6 text-indigo-600 font-bold">{{ log.hours_worked }}</td>
                                            <td class="py-4 px-6 text-gray-600 max-w-xs truncate" :title="log.description">{{ log.description || '-' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination Links -->
                    <div v-if="paginationLinks.length > 3" class="p-6 border-t border-gray-200 flex items-center justify-between">
                        <div class="text-sm text-gray-600">
                            Showing <span class="font-semibold">{{ timeLogs.from }}</span> to <span class="font-semibold">{{ timeLogs.to }}</span> of <span class="font-semibold">{{ timeLogs.total }}</span> results
                        </div>
                        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                             <Link v-for="(link, index) in paginationLinks" :key="index"
                                  :href="link.url"
                                  v-html="link.label"
                                  :class="{
                                      'bg-indigo-600 text-white focus-visible:outline-indigo-600': link.active,
                                      'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-100 focus:z-20': !link.active,
                                      'rounded-l-md': index === 0,
                                      'rounded-r-md': index === paginationLinks.length - 1
                                  }"
                                  class="relative inline-flex items-center px-3 py-2 text-sm font-semibold focus:z-20"
                                  :disabled="!link.url"
                                  preserve-scroll
                            />
                        </nav>
                    </div>

                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
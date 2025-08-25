<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { PlusIcon, ChevronLeftIcon, ChevronRightIcon, DocumentMagnifyingGlassIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    timeLogs: Object,
    canViewAll: Boolean,
    assignableProjects: Array,
});

const form = useForm({
    work_date: new Date().toISOString().split('T')[0],
    hours_worked: '',
    description: '',
    project_id: '',
});

// --- Logic for Description Autofill ---
const recentDescriptions = ref([]);
const showRecentDescriptions = ref(false);

onMounted(() => {
    const saved = localStorage.getItem('recentTimeLogDescriptions');
    if (saved) {
        recentDescriptions.value = JSON.parse(saved);
    }
});

const submitHours = () => {
    form.post(route('hours.store'), {
        preserveScroll: true,
        onSuccess: () => {
            if (form.description && !recentDescriptions.value.includes(form.description)) {
                recentDescriptions.value.unshift(form.description);
                recentDescriptions.value = recentDescriptions.value.slice(0, 10);
                localStorage.setItem('recentTimeLogDescriptions', JSON.stringify(recentDescriptions.value));
            }
            form.reset('hours_worked', 'description', 'project_id');
        },
    });
};

function selectDescription(description) {
    form.description = description;
    showRecentDescriptions.value = false;
}
// --- End Autofill Logic ---

const logs = computed(() => props.timeLogs.data);
const paginationLinks = computed(() => props.timeLogs.links);

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString(undefined, {
        year: 'numeric', month: 'long', day: 'numeric', timeZone: 'UTC', 
    });
};
</script>

<template>
    <Head title="Working Hours" />

    <AuthenticatedLayout>
        <div class="p-4 sm:p-6 lg:p-8">
            <div class="space-y-8">

                <header>
                    <h1 class="text-2xl font-semibold text-gray-800">Working Hours</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ canViewAll ? 'Review and manage all employee time entries.' : 'Log your daily work and view your history.' }}
                    </p>
                </header>

                <div v-if="!canViewAll" class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">Log New Entry</h2>
                    <form @submit.prevent="submitHours" class="mt-4">
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                            <div class="md:col-span-2">
                                <label for="project_id" class="block text-sm font-medium text-gray-700">Project</label>
                                <select v-model="form.project_id" id="project_id" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    <option value="" disabled>Select a project</option>
                                    <option v-for="project in assignableProjects" :key="project.id" :value="project.id">
                                        {{ project.name }}
                                    </option>
                                </select>
                                <InputError class="mt-1" :message="form.errors.project_id" />
                            </div>
                            <div>
                                <label for="work_date" class="block text-sm font-medium text-gray-700">Work Date</label>
                                <input v-model="form.work_date" id="work_date" type="date" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                                <InputError class="mt-1" :message="form.errors.work_date" />
                            </div>
                             <div>
                                <label for="hours_worked" class="block text-sm font-medium text-gray-700">Hours Worked</label>
                                <input v-model="form.hours_worked" id="hours_worked" type="number" step="0.25" min="0.25" max="24" required placeholder="e.g., 8.5"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                                <InputError class="mt-1" :message="form.errors.hours_worked" />
                            </div>
                            
                            <div class="md:col-span-5 relative">
                                <label for="description" class="block text-sm font-medium text-gray-700">Description / Tasks Accomplished</label>
                                <div class="relative mt-1">
                                    <textarea v-model="form.description" id="description" rows="3"
                                              @focus="showRecentDescriptions = true"
                                              @blur="setTimeout(() => showRecentDescriptions = false, 150)"
                                              placeholder="Briefly describe the work you completed..."
                                              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"></textarea>
                                    <div v-if="recentDescriptions.length > 0" class="absolute inset-y-0 right-0 flex items-start pt-2 pr-3">
                                        <button @click.prevent="showRecentDescriptions = !showRecentDescriptions" type="button" class="p-1 text-gray-400 hover:text-gray-600 rounded-full">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                                        </button>
                                    </div>
                                </div>
                                <div v-if="showRecentDescriptions && recentDescriptions.length > 0" class="absolute z-10 mt-1 w-full bg-white shadow-lg rounded-md border border-gray-200 max-h-48 overflow-y-auto">
                                    <ul>
                                        <li v-for="(desc, index) in recentDescriptions" :key="index" @click="selectDescription(desc)"
                                            class="px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 cursor-pointer truncate">
                                            {{ desc }}
                                        </li>
                                    </ul>
                                </div>
                                <InputError class="mt-1" :message="form.errors.description" />
                            </div>
                        </div>
                        
                        <!-- ================================================================= -->
                        <!-- START OF FIX: Log Hours Button Added Back -->
                        <!-- ================================================================= -->
                        <div class="flex items-center justify-end pt-4">
                             <PrimaryButton :disabled="form.processing">
                                <PlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" />
                                <span>Log Hours</span>
                            </PrimaryButton>
                        </div>
                        <!-- ================================================================= -->
                        <!-- END OF FIX -->
                        <!-- ================================================================= -->
                    </form>
                </div>

                <!-- Logged Hours Table Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <header class="p-4 sm:p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-800">{{ canViewAll ? 'All Employee Hours' : 'My Logged Hours' }}</h2>
                        <p class="mt-1 text-sm text-gray-500">A historical list of all time entries.</p>
                    </header>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-left">
                                    <th v-if="canViewAll" class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Project</th>
                                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <!-- ================================================================= -->
                                    <!-- START OF FIX: Hours Column Header Added Back -->
                                    <!-- ================================================================= -->
                                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Hours</th>
                                    <!-- ================================================================= -->
                                    <!-- END OF FIX -->
                                    <!-- ================================================================= -->
                                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-if="!logs.length">
                                    <td :colspan="canViewAll ? 5 : 4" class="px-6 py-24 text-center">
                                        <DocumentMagnifyingGlassIcon class="mx-auto h-12 w-12 text-gray-400" />
                                        <h3 class="mt-2 text-sm font-semibold text-gray-900">No Time Logs Found</h3>
                                        <p class="mt-1 text-sm text-gray-500">
                                            {{ canViewAll ? 'No employees have logged hours yet.' : 'Get started by logging your first time entry above.' }}
                                        </p>
                                    </td>
                                </tr>
                                <tr v-for="log in logs" :key="log.id" class="hover:bg-gray-50 transition-colors">
                                    <td v-if="canViewAll" class="px-6 py-4 whitespace-nowrap font-medium text-gray-800">{{ log.user.name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span v-if="log.project" class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-800">
                                            {{ log.project.name }}
                                        </span>
                                        <span v-else class="text-gray-400">N/A</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ formatDate(log.work_date) }}</td>
                                    <!-- ================================================================= -->
                                    <!-- START OF FIX: Hours Column Data Added Back -->
                                    <!-- ================================================================= -->
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-900 font-bold tabular-nums">{{ log.hours_worked }}</td>
                                    <!-- ================================================================= -->
                                    <!-- END OF FIX -->
                                    <!-- ================================================================= -->
                                    <td class="px-6 py-4 text-gray-600 max-w-md truncate" :title="log.description">{{ log.description || '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="paginationLinks.length > 3" class="p-4 sm:p-6 flex items-center justify-between border-t border-gray-200">
                        <div class="text-sm text-gray-600">
                            Showing <span class="font-medium">{{ timeLogs.from }}</span> to <span class="font-medium">{{ timeLogs.to }}</span> of <span class="font-medium">{{ timeLogs.total }}</span> results
                        </div>
                        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                             <Link v-for="(link, index) in paginationLinks" :key="index"
                                  :href="link.url"
                                  class="relative inline-flex items-center px-3 py-2 text-sm font-semibold focus:z-20"
                                  :class="[
                                      { 'bg-indigo-600 text-white focus-visible:outline-indigo-600': link.active },
                                      { 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50': !link.active },
                                      { 'rounded-l-md': index === 0 },
                                      { 'rounded-r-md': index === paginationLinks.length - 1 },
                                      { 'text-gray-400 pointer-events-none': !link.url }
                                  ]"
                                  preserve-scroll
                            >
                                <span v-if="link.label.includes('Previous')" class="sr-only">Previous</span>
                                <ChevronLeftIcon v-if="link.label.includes('Previous')" class="h-5 w-5" aria-hidden="true" />
                                <ChevronRightIcon v-else-if="link.label.includes('Next')" class="h-5 w-5" aria-hidden="true" />
                                <span v-else>{{ link.label }}</span>
                            </Link>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
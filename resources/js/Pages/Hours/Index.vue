<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import { PlusIcon, ChevronLeftIcon, ChevronRightIcon, DocumentMagnifyingGlassIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    timeLogs: Object,
    canViewAll: Boolean,
    assignableProjects: Array,
});

// Your form
const form = useForm({
    work_date: new Date().toISOString().split('T')[0],
    hours_worked: '',
    description: '',
    project_id: '',
});

// List of previous descriptions for autocomplete
const previousDescriptions = ref([
    'Fixed login bug',
    'Client meeting and notes',
    'Implemented new feature X',
    'Code review and unit tests',
    'Updated documentation',
    'Refactored old code',
]);

const showSuggestions = ref(false);

const submitHours = () => {
    form.post(route('hours.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('hours_worked', 'description', 'project_id');
        },
    });
};

const logs = computed(() => props.timeLogs.data);
const paginationLinks = computed(() => props.timeLogs.links);

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        timeZone: 'UTC',
    });
};

const filteredSuggestions = computed(() => {
    if (!form.description) return [];
    return previousDescriptions.value.filter((desc) =>
        desc.toLowerCase().includes(form.description.toLowerCase())
    );
});

const onDescriptionInput = () => {
    showSuggestions.value = filteredSuggestions.value.length > 0;
};

const selectSuggestion = (suggestion) => {
    form.description = suggestion;
    showSuggestions.value = false;
};

const hideSuggestions = () => {
    // Delay hiding so click can register
    setTimeout(() => {
        showSuggestions.value = false;
    }, 100);
};
</script>

<template>
    <Head title="Working Hours" />

    <AuthenticatedLayout>
        <div class="bg-slate-50 min-h-full font-sans">
            <div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8 space-y-8">

                <header>
                    <h1 class="text-3xl font-bold tracking-tight text-slate-900">Working Hours</h1>
                    <p class="mt-1 text-md text-slate-600">
                        {{ canViewAll ? 'Review and manage all employee time entries.' : 'Log your daily work and view your history.' }}
                    </p>
                </header>

                <div v-if="!canViewAll" class="bg-white p-6 rounded-xl shadow-lg border border-slate-200/80">
                    <header>
                        <h2 class="text-xl font-semibold text-slate-900">Log New Entry</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Fill out the details below to record your work for a specific project and date.
                        </p>
                    </header>
                    <form @submit.prevent="submitHours" class="mt-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div class="md:col-span-2">
                                <label for="project_id" class="block text-sm font-medium text-slate-700">Project</label>
                                <select v-model="form.project_id" id="project_id" required
                                    class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    <option value="" disabled>-- Select a project --</option>
                                    <option v-for="project in assignableProjects" :key="project.id" :value="project.id">
                                        {{ project.name }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.project_id" />
                            </div>
                            <div>
                                <label for="work_date" class="block text-sm font-medium text-slate-700">Work Date</label>
                                <input v-model="form.work_date" id="work_date" type="date" required
                                    class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                                <InputError class="mt-2" :message="form.errors.work_date" />
                            </div>
                            <div>
                                <label for="hours_worked" class="block text-sm font-medium text-slate-700">Hours Worked</label>
                                <input v-model="form.hours_worked" id="hours_worked" type="number" step="0.25" min="0.25" max="24" required placeholder="e.g., 8"
                                    class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                                <InputError class="mt-2" :message="form.errors.hours_worked" />
                            </div>
                        </div>

                        <div class="relative">
                            <label for="description" class="block text-sm font-medium text-slate-700">Description / Tasks</label>
                            <textarea
                                v-model="form.description"
                                id="description"
                                name="description"
                                autocomplete="on"
                                rows="3"
                                placeholder="What did you work on?"
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                @input="onDescriptionInput"
                                @focus="showSuggestions = true"
                                @blur="hideSuggestions"
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.description" />

                            <ul 
                                v-if="showSuggestions && filteredSuggestions.length"
                                class="absolute z-10 w-full max-h-40 overflow-auto border border-gray-300 bg-white rounded shadow"
                            >
                                <li
                                    v-for="(suggestion, index) in filteredSuggestions"
                                    :key="index"
                                    @mousedown.prevent="selectSuggestion(suggestion)"
                                    class="cursor-pointer px-3 py-1 hover:bg-blue-100"
                                >
                                    {{ suggestion }}
                                </li>
                            </ul>
                        </div>

                        <div class="flex items-center justify-end pt-2">
                            <button type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <PlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" />
                                <span>Log Hours</span>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="bg-white rounded-xl shadow-lg border border-slate-200/80">
                    <header class="p-6 border-b border-slate-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-xl font-semibold text-slate-900">{{ canViewAll ? 'All Employee Hours' : 'My Logged Hours' }}</h2>
                                <p class="mt-1 text-sm text-slate-500">A list of all time entries recorded in the system.</p>
                            </div>
                            <div class="relative">
                            </div>
                        </div>
                    </header>

                    <div class="flow-root">
                        <div class="overflow-x-auto">
                            <div class="inline-block min-w-full align-middle">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th v-if="canViewAll" scope="col" class="py-3.5 px-6 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Employee</th>
                                            <th scope="col" class="py-3.5 px-6 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Project</th>
                                            <th scope="col" class="py-3.5 px-6 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Date</th>
                                            <th scope="col" class="py-3.5 px-6 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Hours</th>
                                            <th scope="col" class="py-3.5 px-6 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Description</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        <tr v-if="!logs.length">
                                            <td :colspan="canViewAll ? 5 : 4" class="px-6 py-24 text-center">
                                                <DocumentMagnifyingGlassIcon class="mx-auto h-12 w-12 text-slate-400" />
                                                <h3 class="mt-2 text-sm font-semibold text-slate-900">No time logs found</h3>
                                                <p class="mt-1 text-sm text-slate-500">
                                                    {{ canViewAll ? 'No employees have logged hours yet.' : 'Get started by logging your first time entry above.' }}
                                                </p>
                                            </td>
                                        </tr>
                                        <tr v-for="log in logs" :key="log.id" class="hover:bg-slate-50 transition-colors">
                                            <td v-if="canViewAll" class="whitespace-nowrap py-4 px-6 text-sm font-medium text-slate-800">{{ log.user.name }}</td>
                                            <td class="whitespace-nowrap py-4 px-6 text-sm text-slate-600">
                                                <span v-if="log.project" class="inline-flex items-center rounded-full bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                                                    {{ log.project.name }}
                                                </span>
                                                <span v-else class="text-slate-400">N/A</span>
                                            </td>
                                            <td class="whitespace-nowrap py-4 px-6 text-sm text-slate-600">{{ formatDate(log.work_date) }}</td>
                                            <td class="whitespace-nowrap py-4 px-6 text-sm text-slate-900 font-bold tabular-nums">{{ log.hours_worked }}</td>
                                            <td class="py-4 px-6 text-sm text-slate-600 max-w-md truncate" :title="log.description">{{ log.description || '–' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div v-if="paginationLinks.length > 3" class="p-4 sm:p-6 flex items-center justify-between border-t border-slate-200">
                        <div class="text-sm text-slate-600">
                            Showing <span class="font-medium">{{ timeLogs.from }}</span> to <span class="font-medium">{{ timeLogs.to }}</span> of <span class="font-medium">{{ timeLogs.total }}</span> results
                        </div>
                        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                            <Link v-for="(link, index) in paginationLinks" :key="index"
                                :href="link.url"
                                :class="[
                                    'relative inline-flex items-center px-3 py-2 text-sm font-semibold focus:z-20',
                                    { 'bg-blue-600 text-white focus-visible:outline-blue-600': link.active },
                                    { 'text-slate-900 ring-1 ring-inset ring-slate-300 hover:bg-slate-50': !link.active },
                                    { 'rounded-l-md': index === 0 },
                                    { 'rounded-r-md': index === paginationLinks.length - 1 },
                                    { 'pointer-events-none text-slate-400': !link.url }
                                ]"
                                preserve-scroll
                            >
                                <ChevronLeftIcon v-if="link.label.includes('Previous')" class="h-5 w-5" />
                                <ChevronRightIcon v-else-if="link.label.includes('Next')" class="h-5 w-5" />
                                <span v-else>{{ link.label }}</span>
                            </Link>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

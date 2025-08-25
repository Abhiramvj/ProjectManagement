<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
    projects: Array,
    teams: Array,
});

// State management
const isCreateModalVisible = ref(false);
const searchQuery = ref('');
const selectedTeam = ref('');
const selectedStatus = ref('');
const viewMode = ref('grid'); // 'grid' or 'table'
const sortBy = ref('created_at');
const sortOrder = ref('desc');
const isLoading = ref(false);

// Animation state
const isLoaded = ref(false);

onMounted(() => {
    setTimeout(() => {
        isLoaded.value = true;
    }, 100);
});

// Modal management
const openCreateModal = () => {
    isCreateModalVisible.value = true;
};

const closeModal = () => {
    isCreateModalVisible.value = false;
    form.reset();
};

// Form handling
const form = useForm({
    name: '',
    description: '',
    team_id: '',
    end_date: '',
    total_hours_required: 100,
    priority: 'medium',
    status: 'pending',
});

const submit = () => {
    isLoading.value = true;
    form.post(route('projects.store'), {
        onSuccess: () => {
            closeModal();
            isLoading.value = false;
        },
        onError: () => {
            isLoading.value = false;
        },
        preserveScroll: true,
    });
};

// Computed properties
const filteredProjects = computed(() => {
    let filtered = [...props.projects];


    // Search filter
    if (searchQuery.value) {
        filtered = filtered.filter(project =>
            project.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            project.team.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            project.project_manager.name.toLowerCase().includes(searchQuery.value.toLowerCase())
        );
    }

    // Team filter
    if (selectedTeam.value) {
        filtered = filtered.filter(project => project.team_id === parseInt(selectedTeam.value));
    }

    // Status filter
    if (selectedStatus.value) {
        filtered = filtered.filter(project => project.status === selectedStatus.value);
    }

    // Sorting
    return filtered.sort((a, b) => {
        const aValue = a[sortBy.value];
        const bValue = b[sortBy.value];

        if (sortOrder.value === 'asc') {
            return aValue > bValue ? 1 : -1;
        } else {
            return aValue < bValue ? 1 : -1;
        }
    });
});

const projectStats = computed(() => {
    const total = props.projects.length;
    const active = props.projects.filter(p => p.status !== 'completed' && (!p.end_date || new Date(p.end_date) >= new Date())).length;
    const completed = props.projects.filter(p => p.status === 'completed').length;
    const overdue = props.projects.filter(p => p.end_date && new Date(p.end_date) < new Date() && p.status !== 'completed').length;

    return { total, active, completed, overdue };
});

// Utility functions
const getStatusColor = (status) => {
    const colors = {
        'active': 'bg-blue-100 text-blue-800',
        'completed': 'bg-green-100 text-green-800',
        'on-hold': 'bg-yellow-100 text-yellow-800',
        'cancelled': 'bg-red-100 text-red-800'
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const getPriorityColor = (priority) => {
    const colors = {
        'low': 'bg-gray-100 text-gray-800',
        'medium': 'bg-blue-100 text-blue-800',
        'high': 'bg-orange-100 text-orange-800',
        'urgent': 'bg-red-100 text-red-800'
    };
    return colors[priority] || 'bg-gray-100 text-gray-800';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const getProgressPercentage = (project) => {
    // Use backend-provided computed field when available
    if (typeof project.hours_progress === 'number') {
        return project.hours_progress;
    }
    if (project.total_hours_required === 0) return 0;
    return Math.min(Math.round((project.hours_logged || 0) / project.total_hours_required * 100), 100);
};

const clearFilters = () => {
    searchQuery.value = '';
    selectedTeam.value = '';
    selectedStatus.value = '';
};
</script>

<template>
    <Head title="Projects" />

    <AuthenticatedLayout>
        <div class="p-4 sm:p-6 lg:p-8 font-sans bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto space-y-8">

                <!-- Header Section with Stats -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8" :class="{ 'animate-fade-in-up': isLoaded }">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between mb-8">
                        <div>
                            <h1 class="text-4xl font-bold text-gray-900 mb-2">Projects</h1>
                            <p class="text-gray-600">Manage and track your team's projects</p>
                        </div>
                        <button @click="openCreateModal"
                                class="group inline-flex items-center justify-center rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-lg transition-all duration-200 hover:bg-blue-700 hover:shadow-xl hover:scale-105 focus:outline-none focus:ring-4 focus:ring-blue-500/50">
                            <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Create Project
                        </button>
                    </div>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6 border border-blue-200">
                            <div class="flex items-center">
                                <div class="p-3 bg-blue-600 rounded-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-blue-600">Total Projects</p>
                                    <p class="text-2xl font-bold text-blue-900">{{ projectStats.total }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 rounded-xl p-6 border border-emerald-200">
                            <div class="flex items-center">
                                <div class="p-3 bg-emerald-600 rounded-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-emerald-600">Active</p>
                                    <p class="text-2xl font-bold text-emerald-900">{{ projectStats.active }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-6 border border-green-200">
                            <div class="flex items-center">
                                <div class="p-3 bg-green-600 rounded-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-green-600">Completed</p>
                                    <p class="text-2xl font-bold text-green-900">{{ projectStats.completed }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl p-6 border border-red-200">
                            <div class="flex items-center">
                                <div class="p-3 bg-red-600 rounded-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-red-600">Overdue</p>
                                    <p class="text-2xl font-bold text-red-900">{{ projectStats.overdue }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters and Search -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6" :class="{ 'animate-fade-in-up delay-100': isLoaded }">
                    <div class="flex flex-col lg:flex-row gap-4 items-center justify-between">
                        <!-- Search -->
                        <div class="relative flex-1 max-w-md">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input v-model="searchQuery" type="text" placeholder="Search projects..."
                                   class="block w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        </div>

                        <!-- Filters
                        <div class="flex flex-wrap gap-3">
                            <select v-model="selectedTeam" class="px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                <option value="">All Teams</option>
                                <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
                            </select>

                            <select v-model="selectedStatus" class="px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                <option value="">All Status</option>
                                <option value="completed">Completed</option>
                                <option value="pending">Pending</option>
                                <option value="on-hold">On Hold</option>
                                <option value="cancelled">Cancelled</option>
                            </select>

                            <button @click="clearFilters" class="px-4 py-3 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-xl transition-colors">
                                Clear Filters
                            </button>
                        </div> -->

                        <!-- View Mode Toggle -->
                        <div class="flex bg-gray-100 rounded-xl p-1">
                            <button @click="viewMode = 'grid'" :class="[viewMode === 'grid' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-600 hover:text-gray-900']"
                                    class="p-2 rounded-lg transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                            </button>
                            <button @click="viewMode = 'table'" :class="[viewMode === 'table' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-600 hover:text-gray-900']"
                                    class="p-2 rounded-lg transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Projects Content -->
                <div v-if="!filteredProjects.length" class="bg-white rounded-2xl shadow-sm border border-gray-200 p-12 text-center">
                    <svg class="mx-auto h-24 w-24 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No projects found</h3>
                    <p class="text-gray-500 mb-6">Get started by creating your first project.</p>
                    <button @click="openCreateModal" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white hover:bg-blue-700 transition-colors">
                        Create Project
                    </button>
                </div>

                <!-- Grid View -->
                <div v-else-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6" :class="{ 'animate-fade-in-up delay-200': isLoaded }">
                    <div v-for="(project, index) in filteredProjects" :key="project.id"
                         class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 hover:shadow-lg transition-all duration-200 transform hover:-translate-y-1 group"
                         :style="{ animationDelay: `${index * 50}ms` }">

                        <!-- Project Header -->
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900 mb-1 group-hover:text-blue-600 transition-colors">
                                    <Link :href="route('projects.show', project.id)" class="hover:underline">
                                        {{ project.name }}
                                    </Link>
                                </h3>
                                <p class="text-sm text-gray-600">{{ project.team.name }}</p>
                            </div>
                            <div class="flex space-x-2">
                                <span :class="getStatusColor(project.status)" class="px-2 py-1 text-xs font-medium rounded-full capitalize">
                                    {{ project.status }}
                                </span>
                                <span v-if="project.priority" :class="getPriorityColor(project.priority)" class="px-2 py-1 text-xs font-medium rounded-full capitalize">
                                    {{ project.priority }}
                                </span>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mb-4">
                            <div class="flex justify-between text-sm text-gray-600 mb-2">
                                <span>Progress</span>
                                <span>{{ getProgressPercentage(project) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-2 rounded-full transition-all duration-300"
                                     :style="{ width: `${getProgressPercentage(project)}%` }"></div>
                            </div>
                        </div>

                        <!-- Project Details -->
                        <div class="space-y-3">
                            <div class="flex items-center text-sm text-gray-600">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                {{ project.project_manager.name }}
                            </div>
                            <div class="flex items-center text-sm text-gray-600">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v6a2 2 0 002 2h6a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                {{ project.tasks_count || 0 }} tasks
                            </div>
                            <div class="flex items-center text-sm text-gray-600">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Due {{ formatDate(project.end_date) }}
                            </div>
                        </div>

                        <!-- Action Button -->
                        <div class="mt-6 pt-4 border-t border-gray-100">
                            <Link :href="route('projects.show', project.id)"
                                  class="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-400 transition-colors group">
                                View Project
                                <svg class="ml-2 w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Table View -->
                <div v-else-if="viewMode === 'table'" class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden" :class="{ 'animate-fade-in-up delay-200': isLoaded }">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="py-4 px-6 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Project</th>
                                    <th scope="col" class="py-4 px-6 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Team</th>
                                    <th scope="col" class="py-4 px-6 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Manager</th>
                                    <th scope="col" class="py-4 px-6 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Progress</th>
                                    <th scope="col" class="py-4 px-6 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="py-4 px-6 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Due Date</th>
                                    <th scope="col" class="relative py-4 px-6"><span class="sr-only">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="project in filteredProjects" :key="project.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="py-4 px-6 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ project.name }}</div>
                                            <div class="text-sm text-gray-500">{{ project.tasks_count || 0 }} tasks</div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 whitespace-nowrap text-sm text-gray-900">{{ project.team.name }}</td>
                                    <td class="py-4 px-6 whitespace-nowrap text-sm text-gray-900">{{ project.project_manager.name }}</td>
                                    <td class="py-4 px-6 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-16 bg-gray-200 rounded-full h-2 mr-3">
                                                <div class="bg-blue-600 h-2 rounded-full" :style="{ width: `${getProgressPercentage(project)}%` }"></div>
                                            </div>
                                            <span class="text-sm text-gray-900">{{ getProgressPercentage(project) }}%</span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 whitespace-nowrap">
                                        <span :class="getStatusColor(project.status)" class="px-3 py-1 text-xs font-medium rounded-full">
                                            {{ project.status }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 whitespace-nowrap text-sm text-gray-900">{{ formatDate(project.end_date) }}</td>
                                    <td class="py-4 px-6 whitespace-nowrap text-right text-sm font-medium">
                                        <Link :href="route('projects.show', project.id)" class="text-blue-600 hover:text-blue-900 transition-colors">View</Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

<Modal :show="isCreateModalVisible" @close="closeModal">
    <div class="p-6 bg-white rounded-xl shadow-xl w-full max-w-2xl mx-auto">
        <!-- Header -->
        <div class="flex items-start justify-between mb-6">
            <div class="flex items-center space-x-4">
                <div class="flex items-center justify-center bg-indigo-100 rounded-lg h-12 w-12 flex-shrink-0">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v1a3 3 0 006 0V7" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9a2 2 0 012-2z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Create New Project</h2>
                    <p class="text-sm text-gray-500">Please fill out the details below.</p>
                </div>
            </div>
             <button @click="closeModal" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <!-- Form -->
        <form @submit.prevent="submit" class="space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6">
                <!-- Project Name -->
                <div>
                    <label for="name" class="form-label">Project Name</label>
                    <input v-model="form.name" id="name" type="text" required
                        class="form-input"
                        placeholder="e.g., Q4 Marketing Campaign" />
                    <InputError class="mt-1" :message="form.errors.name" />
                </div>

                <!-- Assign to Team -->
                <div>
                    <label for="team_id" class="form-label">Assign to Team</label>
                    <select v-model="form.team_id" id="team_id" required class="form-select">
                        <option value="" disabled>-- Select a team --</option>
                        <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
                    </select>
                    <InputError class="mt-1" :message="form.errors.team_id" />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-5">
                 <!-- Due Date -->
                <div>
                    <label for="end_date" class="form-label">Due Date</label>
                    <input v-model="form.end_date" id="end_date" type="date" required
                        class="form-input" />
                    <InputError class="mt-1" :message="form.errors.end_date" />
                </div>

                <!-- Estimated Hours -->
                <div>
                    <label for="total_hours_required" class="form-label">Estimated Hours</label>
                    <input v-model="form.total_hours_required" id="total_hours_required" type="number" min="1" required
                        class="form-input" placeholder="e.g., 100" />
                    <InputError class="mt-1" :message="form.errors.total_hours_required" />
                </div>

                <!-- Priority -->
                <div>
                    <label for="priority" class="form-label">Priority</label>
                    <select v-model="form.priority" id="priority" class="form-select">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                    <InputError class="mt-1" :message="form.errors.priority" />
                </div>
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="form-label">Description</label>
                <textarea v-model="form.description" id="description" rows="4"
                    class="form-textarea"
                    placeholder="Describe the project goals, key deliverables, and requirements..."></textarea>
                <InputError class="mt-1" :message="form.errors.description" />
            </div>

            <!-- Action Buttons -->
            <div class="pt-5 flex justify-end gap-4 border-t border-gray-200 mt-6">
                <button type="button" @click="closeModal"
                    class="btn-secondary" :disabled="form.processing">
                    Cancel
                </button>
                <button type="submit" :disabled="form.processing"
                    class="btn-primary">
                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ form.processing ? 'Creating...' : 'Create Project' }}
                </button>
            </div>
        </form>
    </div>
</Modal>
    </AuthenticatedLayout>
</template>

<style>
@keyframes fade-in-up {
    0% {
        opacity: 0;
        transform: translateY(20px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in-up {
    animation: fade-in-up 0.6s ease-out;
}

.delay-100 {
    animation-delay: 100ms;
}

.delay-200 {
    animation-delay: 200ms;
}

.delay-300 {
    animation-delay: 300ms;
}
</style>

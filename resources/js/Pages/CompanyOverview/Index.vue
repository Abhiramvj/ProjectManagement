<template>
    <AuthenticatedLayout>
        <template #header>
            Company Overview
        </template>

        <div class="p-6 space-y-6">
            <!-- Company Info -->
            <section class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                <div class="flex items-center justify-between">
                    <h1 class="text-3xl font-bold text-gray-800">{{ companyInfo.name }}</h1>
                    <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-800 text-sm font-medium">Overview</span>
                </div>
                <p class="mt-3 text-gray-600">{{ companyInfo.description }}</p>
                <ul class="mt-4 list-disc list-inside space-y-1 text-gray-600">
                    <li v-for="policy in companyInfo.policies" :key="policy">{{ policy }}</li>
                </ul>
            </section>

            <!-- Active Projects -->
            <section class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">Active Projects</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div
                        v-for="project in projects"
                        :key="project.id"
                        class="p-4 border rounded-lg hover:shadow-lg transition-shadow duration-200 bg-gray-50 cursor-pointer"
                        @click="toggleProjectDescription(project.id)"
                    >
                        <h3 class="font-bold text-lg text-gray-800">{{ project.title }}</h3>
                        <p class="mt-1 text-sm text-gray-600">Status: <span class="font-medium">{{ project.status }}</span></p>
                        <p class="text-sm text-gray-600">Deadline: <span class="font-medium">{{ project.deadline }}</span></p>
                        <p class="text-sm text-gray-600">Team: <span class="font-medium">{{ project.team?.name ?? 'N/A' }}</span></p>

                        <!-- Project Description -->
                        <div v-if="expandedProjectId === project.id" class="mt-2 p-2 bg-white border rounded">
                            <p class="text-sm text-gray-700">{{ project.description ?? 'No description provided.' }}</p>

                            <!-- Admin/HR Edit -->
                            <div v-if="isAdminOrHR" class="mt-2 flex space-x-2">
                                <input
                                    v-model="editingDescription"
                                    placeholder="Add/Edit project description"
                                    class="w-full p-2 border rounded text-sm"
                                />
                                <button
                                    class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700"
                                    @click.stop="saveDescription(project.id)"
                                >
                                    Save
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Teams Summary (Optional) -->
            <section v-if="teams.length" class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">Teams & Leads</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div
                        v-for="team in teams"
                        :key="team.id"
                        class="p-4 border rounded-lg bg-gray-50 hover:shadow-md transition-shadow duration-200"
                    >
                        <h3 class="font-bold text-lg text-gray-800">{{ team.name }}</h3>
                        <p class="text-sm text-gray-600">Lead: <span class="font-medium">{{ team.team_lead?.name ?? 'N/A' }}</span></p>
                        <p class="text-sm text-gray-600">Members: <span class="font-medium">{{ team.users?.length ?? 0 }}</span></p>
                    </div>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    companyInfo: Object,
    projects: Array,
    teams: {
        type: Array,
        default: () => []
    },
    userRole: String, // current logged-in user role
});

// Admin/HR can edit project description
const isAdminOrHR = computed(() => ['admin', 'hr'].includes(props.userRole));

// Track which project description is expanded
const expandedProjectId = ref(null);
const editingDescription = ref('');

// Functions
function toggleProjectDescription(projectId) {
    if (expandedProjectId.value === projectId) {
        expandedProjectId.value = null;
        editingDescription.value = '';
    } else {
        expandedProjectId.value = projectId;
        const project = props.projects.find(p => p.id === projectId);
        editingDescription.value = project?.description ?? '';
    }
}

function saveDescription(projectId) {
    const project = props.projects.find(p => p.id === projectId);
    if (project) {
        project.description = editingDescription.value;
        // TODO: Call API to save description in backend
        alert('Project description saved!');
    }
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold text-gray-800">Company Overview</h1>
            </div>
        </template>

        <div class="p-6 space-y-8">
            <!-- Company Info -->
<section class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
    <h2 class="text-2xl font-semibold text-gray-800 mb-4">Company Policies</h2>

    <div class="space-y-3">
        <p><span class="font-semibold">Work Hours:</span> {{ companyInfo.work_hours }}</p>
        <p><span class="font-semibold">Code of Conduct:</span> {{ companyInfo.code_of_conduct }}</p>
        <p><span class="font-semibold">Policies:</span></p>
        <ul class="list-disc list-inside ml-6 text-gray-600">
            <li v-for="policy in companyInfo.policies" :key="policy">{{ policy }}</li>
        </ul>
    </div>

    <div v-if="isAdminOrHR" class="mt-4">
        <button
            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
            @click="openCompanyInfoModal"
        >
            Edit Company Info
        </button>
    </div>
</section>


            <!-- Active Projects -->
            <section class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                <h2 class="text-2xl font-semibold text-gray-800 mb-6">Active Projects</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div
    v-for="project in projects"
    :key="project.id"
    class="p-4 border rounded-lg hover:shadow-lg transition-shadow duration-200 bg-gray-50 cursor-pointer"
    @click="toggleProjectDescription(project.id)"
>
    <h3 class="font-bold text-lg text-gray-800">{{ project.title }}</h3>
    <p class="mt-1 text-sm text-gray-600">Name: <span class="font-medium">{{ project.name }}</span></p>
    <p class="text-sm text-gray-600">Team: <span class="font-medium">{{ project.team?.name ?? 'N/A' }}</span></p>

    <div v-if="expandedProjectId === project.id" class="mt-2 p-2 bg-white border rounded">
        <p class="text-sm text-gray-700 mb-2">{{ project.description ?? 'No description provided.' }}</p>

        <div v-if="isAdminOrHR" class="mt-2">
            <button
                class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700"
                @click.stop="openEditModal(project)"
            >
                Edit Description
            </button>
        </div>
    </div>
</div>


                </div>
            </section>

            <!-- Teams Summary -->
            <section v-if="teams.length" class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                <h2 class="text-2xl font-semibold text-gray-800 mb-6">Teams & Leads</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div
                        v-for="team in teams"
                        :key="team.id"
                        class="p-4 border rounded-lg bg-gray-50 hover:shadow-md transition-shadow duration-200"
                    >
                        <h3 class="font-bold text-lg text-gray-800">{{ team.name }}</h3>
                        <p class="text-gray-600">Lead: <span class="font-medium">{{ team.team_lead?.name ?? 'N/A' }}</span></p>
                        <p class="text-gray-600">Members: <span class="font-medium">{{ team.users?.length ?? 0 }}</span></p>
                    </div>
                </div>
            </section>
<!-- Company Info Modal (Placeholder) -->
 <div v-if="isCompanyInfoModalOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-lg">
        <h2 class="text-xl font-semibold mb-4">Edit Company Info</h2>

        <label class="block mb-2 font-medium">Work Hours</label>
        <input v-model="modalWorkHours" class="w-full p-2 border rounded mb-4" placeholder="e.g. 9 AM – 6 PM">

        <label class="block mb-2 font-medium">Code of Conduct</label>
        <textarea v-model="modalCodeOfConduct" rows="4" class="w-full p-2 border rounded mb-4"></textarea>

        <label class="block mb-2 font-medium">Policies (one per line)</label>
        <textarea v-model="modalPolicies" rows="4" class="w-full p-2 border rounded mb-4"></textarea>

        <div class="flex justify-end space-x-2">
            <button
                class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500"
                @click="closeCompanyInfoModal"
            >
                Cancel
            </button>
            <button
                class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
                @click="saveCompanyInfo"
            >
                Save
            </button>
        </div>
    </div>
</div>


            <!-- Edit Description Modal -->
<div v-if="isModalOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
        <h2 class="text-xl font-semibold mb-4">Edit Project Description</h2>
        <textarea
            v-model="modalEditingDescription"
            rows="5"
            class="w-full p-2 border rounded text-sm"
        ></textarea>

        <div class="mt-4 flex justify-end space-x-2">
            <button
                class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500"
                @click="closeModal"
            >
                Cancel
            </button>
            <button
                class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
                @click="saveDescription(modalProject.id)"
            >
                Save
            </button>
        </div>
    </div>
</div>

        </div>
    </AuthenticatedLayout>
</template>

<script setup>import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    companyInfo: Object,
    projects: Array,
    teams: {
        type: Array,
        default: () => []
    },
    userRole: String,
});

const isAdminOrHR = computed(() => ['admin', 'hr'].includes(props.userRole));
const expandedProjectId = ref(null);
const isModalOpen = ref(false);
const modalEditingDescription = ref('');
const modalProject = ref(null);
const isCompanyInfoModalOpen = ref(false);
const modalWorkHours = ref('');
const modalCodeOfConduct = ref('');
const modalPolicies = ref('');

// Open modal pre-filled
function openCompanyInfoModal() {
    modalWorkHours.value = props.companyInfo.work_hours;
    modalCodeOfConduct.value = props.companyInfo.code_of_conduct;
    modalPolicies.value = props.companyInfo.policies.join('\n');
    isCompanyInfoModalOpen.value = true;
}

function closeCompanyInfoModal() {
    isCompanyInfoModalOpen.value = false;
}

function saveCompanyInfo() {
    props.companyInfo.work_hours = modalWorkHours.value;
    props.companyInfo.code_of_conduct = modalCodeOfConduct.value;
    props.companyInfo.policies = modalPolicies.value.split('\n').filter(p => p.trim());

    isCompanyInfoModalOpen.value = false;

    alert('Company info updated successfully!');
    // TODO: Add API call to persist backend updates
}


function toggleProjectDescription(projectId) {
    if (expandedProjectId.value === projectId) {
        expandedProjectId.value = null;
    } else {
        expandedProjectId.value = projectId;
    }
}

function openEditModal(project) {
    modalProject.value = project;
    modalEditingDescription.value = project.description ?? '';
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    modalProject.value = null;
    modalEditingDescription.value = '';
}

function saveDescription(projectId) {
    const project = props.projects.find(p => p.id === projectId);
    if (project) {
        project.description = modalEditingDescription.value;
        isModalOpen.value = false;
        alert('Project description updated successfully!');
        // TODO: Add API call here to persist in the backend
    }
}
</script>

<style scoped>
textarea {
    resize: vertical;
}
</style>

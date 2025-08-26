<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue'; // UPDATED: Import computed

const props = defineProps({
    project: Object,
    tasks: Array,
    teamMembers: Array,
    userTeams: Array, // UPDATED: Prop for the PM's teams
});

const authUser = usePage().props.auth.user;

// --- Form for creating new tasks ---
const taskForm = useForm({ name: '', assigned_to_id: '' });
const submitTask = () => {
    taskForm.post(route('tasks.store', { project: props.project.id }), { onSuccess: () => taskForm.reset() });
};

// --- UPDATED: Form for assigning project to a team ---
const assignTeamForm = useForm({
    team_id: '',
});
const submitTeamAssignment = () => {
    assignTeamForm.patch(route('projects.assignTeam', props.project.id), {
        preserveScroll: true,
    });
};

// --- UPDATED: Computed properties for cleaner template logic ---
// Determines if the "Assign Team" form should be visible
const shouldShowAssignForm = computed(() => {
    return !props.project.team_id && props.project.project_manager_id === authUser.id;
});

// Determines if the "Add Task" form should be visible
const canCreateTasks = computed(() => {
    // User must have permission AND the project must have a team assigned.
    return (authUser.permissions?.includes('assign tasks') ?? false) && !!props.project.team_id;
});
</script>

<template>
    <Head :title="'Project: ' + project.name" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Project: {{ project.name }}</h2>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Project Progress Bars Section -->
                <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Progress (Based on Hours)</h3>
                        <p class="text-sm text-gray-500">Compares hours logged vs. total hours required.</p>
                        <div class="mt-2 flex items-center">
                            <div class="w-full bg-gray-200 rounded-full h-4"><div class="bg-blue-600 h-4 rounded-full" :style="{ width: (project.hours_progress || 0) + '%' }"></div></div>
                            <span class="ml-4 font-semibold text-gray-700">{{ project.hours_progress || 0 }}%</span>
                        </div>
                    </div>
                </div>

                <!-- =================================================================== -->
                <!-- UPDATED: NEW "ASSIGN TEAM" FORM SECTION -->
                <!-- =================================================================== -->
                <div v-if="shouldShowAssignForm" class="p-4 sm:p-8 bg-yellow-50 border border-yellow-300 shadow sm:rounded-lg">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-yellow-900">Action Required: Assign Team</h2>
                            <p class="mt-1 text-sm text-yellow-700">This project was assigned to you but does not have a team yet. Please assign it to one of your teams to begin.</p>
                        </header>
                        <form @submit.prevent="submitTeamAssignment" class="mt-6 flex items-center gap-4">
                            <div class="flex-grow">
                                <InputLabel for="team_id_assign" value="Select Team" class="sr-only" />
                                <select id="team_id_assign" v-model="assignTeamForm.team_id" required class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="" disabled>-- Select one of your teams --</option>
                                    <option v-for="team in userTeams" :key="team.id" :value="team.id">{{ team.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="assignTeamForm.errors.team_id" />
                            </div>
                            <PrimaryButton :disabled="assignTeamForm.processing">Assign to Team</PrimaryButton>
                        </form>
                    </section>
                </div>


                <!-- Main content grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- =================================================================== -->
                    <!-- UPDATED: "ADD TASK" FORM IS NOW CONDITIONAL -->
                    <!-- =================================================================== -->
                    <div v-if="canCreateTasks" class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                        <section>
                            <header><h2 class="text-lg font-medium text-gray-900">Add New Task</h2></header>
                            <form @submit.prevent="submitTask" class="mt-6 space-y-6">
                                <div>
                                    <InputLabel for="task_name" value="Task Name" />
                                    <TextInput id="task_name" type="text" class="mt-1 block w-full" v-model="taskForm.name" required />
                                    <InputError class="mt-2" :message="taskForm.errors.name" />
                                </div>
                                <div>
                                    <InputLabel for="assigned_to_id" value="Assign To" />
                                    <select id="assigned_to_id" v-model="taskForm.assigned_to_id" required class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                        <option value="" disabled>-- Select a team member --</option>
                                        <option v-for="member in teamMembers" :key="member.id" :value="member.id">{{ member.name }}</option>
                                    </select>
                                    <InputError class="mt-2" :message="taskForm.errors.assigned_to_id" />
                                </div>
                                <div class="flex items-center gap-4">
                                    <PrimaryButton :disabled="taskForm.processing">Add Task</PrimaryButton>
                                    <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out"><p v-if="taskForm.recentlySuccessful" class="text-sm text-gray-600">Added.</p></Transition>
                                </div>
                            </form>
                        </section>
                    </div>

                    <!-- Right Column: Existing Tasks List -->
                     <!-- UPDATED: The class binding is adjusted to make this full-width if the task form is hidden -->
                    <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg" :class="{ 'md:col-span-2': !canCreateTasks }">
                        <section>
                            <header><h2 class="text-lg font-medium text-gray-900">Tasks</h2></header>
                            <div class="mt-6 space-y-4">
                                <!-- UPDATED: Display helpful message if team isn't assigned yet -->
                                <div v-if="!project.team_id" class="text-gray-500 text-center p-4 bg-gray-50 rounded-lg">
                                    A team must be assigned to this project before tasks can be added or displayed.
                                </div>
                                <div v-else-if="tasks.length === 0" class="text-gray-500">
                                    No tasks have been created for this project yet.
                                </div>
                                <div v-else v-for="task in tasks" :key="task.id" class="p-4 rounded-md" :class="task.status === 'done' ? 'bg-green-100' : 'bg-gray-100'">
                                    <p class="font-semibold">{{ task.name }}</p>
                                    <p class="text-sm text-gray-700">Assigned to: <span class="font-medium">{{ task.assigned_to.name }}</span></p>
                                    <p class="text-sm text-gray-500">Status: <span class="uppercase font-semibold">{{ task.status }}</span></p>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
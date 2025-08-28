<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import { Head, useForm, router } from '@inertiajs/vue3';

// The props are passed from our TeamController@index method
const props = defineProps({
    teams: Array,
    potentialLeads: Array,
});

// --- All of your existing script logic remains the same. It's perfect. ---

// --- Create Form ---
const createForm = useForm({
    name: '',
    team_lead_id: '',
});

const submitCreate = () => {
    createForm.post(route('teams.store'), {
        onSuccess: () => createForm.reset(),
    });
};

// --- Edit & Delete Logic ---
const editingTeam = ref(null);

const editForm = useForm({
    name: '',
    team_lead_id: '',
});

const openEditModal = (team) => {
    editingTeam.value = team;
    editForm.name = team.name;
    editForm.team_lead_id = team.team_lead.id;
};

const closeModal = () => {
    editingTeam.value = null;
    editForm.reset();
    editForm.clearErrors();
};

const submitUpdate = () => {
    editForm.put(route('teams.update', editingTeam.value.id), {
        onSuccess: () => closeModal(),
        preserveScroll: true,
    });
};

const deleteTeam = (teamId) => {
    if (
        confirm(
            'Are you sure you want to delete this team? This action cannot be undone.',
        )
    ) {
        router.delete(route('teams.destroy', teamId), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Manage Teams" />

    <AuthenticatedLayout>
        <div class="p-4 font-sans sm:p-6 lg:p-8">
            <div class="mx-auto max-w-7xl space-y-6">
                <!-- Page Header -->
                <div class="flex items-center justify-between">
                    <h1 class="text-3xl font-bold text-slate-900">
                        Manage Teams
                    </h1>
                </div>

                <!-- Card 1: Form to Add New Team -->
                <div
                    class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
                >
                    <header>
                        <h2 class="text-lg font-semibold text-slate-900">
                            Create New Team
                        </h2>
                        <p class="mt-1 text-sm text-slate-600">
                            Create a new team and assign an existing user with
                            the 'Team Lead' role.
                        </p>
                    </header>

                    <form
                        @submit.prevent="submitCreate"
                        class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2"
                    >
                        <!-- Team Name Input -->
                        <div class="sm:col-span-1">
                            <label
                                for="name"
                                class="block text-sm font-medium text-slate-700"
                                >Team Name</label
                            >
                            <input
                                v-model="createForm.name"
                                id="name"
                                type="text"
                                required
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                            <InputError
                                class="mt-2"
                                :message="createForm.errors.name"
                            />
                        </div>

                        <!-- Assign Team Lead Select -->
                        <div class="sm:col-span-1">
                            <label
                                for="team_lead_id"
                                class="block text-sm font-medium text-slate-700"
                                >Assign Team Lead</label
                            >
                            <select
                                v-model="createForm.team_lead_id"
                                id="team_lead_id"
                                required
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            >
                                <option value="" disabled>
                                    -- Select a Team Lead --
                                </option>
                                <option
                                    v-for="lead in potentialLeads"
                                    :key="lead.id"
                                    :value="lead.id"
                                >
                                    {{ lead.name }}
                                </option>
                            </select>
                            <InputError
                                class="mt-2"
                                :message="createForm.errors.team_lead_id"
                            />
                        </div>

                        <!-- Submit Button -->
                        <div
                            class="flex items-center justify-end sm:col-span-2"
                        >
                            <button
                                type="submit"
                                :disabled="createForm.processing"
                                class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-slate-700 disabled:opacity-50"
                            >
                                Create Team
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Card 2: List of Existing Teams -->
                <div
                    class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
                >
                    <header>
                        <h2 class="text-lg font-semibold text-slate-900">
                            Existing Teams
                        </h2>
                    </header>
                    <div class="mt-4 flow-root">
                        <div class="-mx-6 -my-2 overflow-x-auto">
                            <div
                                class="inline-block min-w-full py-2 align-middle"
                            >
                                <table
                                    class="min-w-full divide-y divide-slate-200"
                                >
                                    <thead>
                                        <tr>
                                            <th
                                                scope="col"
                                                class="px-6 py-3.5 text-left text-xs font-semibold uppercase text-slate-500"
                                            >
                                                Team Name
                                            </th>
                                            <th
                                                scope="col"
                                                class="px-6 py-3.5 text-left text-xs font-semibold uppercase text-slate-500"
                                            >
                                                Team Lead
                                            </th>
                                            <th
                                                scope="col"
                                                class="px-6 py-3.5 text-left text-xs font-semibold uppercase text-slate-500"
                                            >
                                                Members
                                            </th>
                                            <th
                                                scope="col"
                                                class="relative px-6 py-3.5"
                                            >
                                                <span class="sr-only"
                                                    >Actions</span
                                                >
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-if="!teams.length">
                                            <td
                                                colspan="4"
                                                class="py-8 text-center text-slate-500"
                                            >
                                                No teams have been created yet.
                                            </td>
                                        </tr>
                                        <tr
                                            v-for="team in props.teams"
                                            :key="team.id"
                                        >
                                            <td
                                                class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900"
                                            >
                                                {{ team.name }}
                                            </td>
                                            <td
                                                class="whitespace-nowrap px-6 py-4 text-sm text-slate-600"
                                            >
                                                {{ team.team_lead.name }}
                                            </td>
                                            <td
                                                class="whitespace-nowrap px-6 py-4 text-sm text-slate-600"
                                            >
                                                {{ team.members_count }}
                                            </td>
                                            <td
                                                class="relative whitespace-nowrap px-6 py-4 text-right text-sm font-medium"
                                            >
                                                <div
                                                    class="flex items-center justify-end space-x-4"
                                                >
                                                    <button
                                                        @click="
                                                            openEditModal(team)
                                                        "
                                                        class="text-indigo-600 hover:text-indigo-900"
                                                    >
                                                        <svg
                                                            class="h-5 w-5"
                                                            fill="none"
                                                            stroke="currentColor"
                                                            viewBox="0 0 24 24"
                                                        >
                                                            <path
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.5L16.732 3.732z"
                                                            ></path>
                                                        </svg>
                                                    </button>
                                                    <button
                                                        @click="
                                                            deleteTeam(team.id)
                                                        "
                                                        class="text-red-600 hover:text-red-900"
                                                    >
                                                        <svg
                                                            class="h-5 w-5"
                                                            fill="none"
                                                            stroke="currentColor"
                                                            viewBox="0 0 24 24"
                                                        >
                                                            <path
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                            ></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Team Modal (Restyled) -->
        <Modal :show="editingTeam !== null" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-bold text-slate-900">
                    Edit Team: {{ editingTeam?.name }}
                </h2>
                <p class="mt-1 text-sm text-slate-600">
                    Update the team's name or assign a different team lead.
                </p>

                <form @submit.prevent="submitUpdate" class="mt-6 space-y-6">
                    <div>
                        <label
                            for="edit_name"
                            class="block text-sm font-medium text-slate-700"
                            >Team Name</label
                        >
                        <input
                            v-model="editForm.name"
                            id="edit_name"
                            type="text"
                            required
                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        />
                        <InputError
                            class="mt-2"
                            :message="editForm.errors.name"
                        />
                    </div>

                    <div>
                        <label
                            for="edit_team_lead_id"
                            class="block text-sm font-medium text-slate-700"
                            >Assign Team Lead</label
                        >
                        <select
                            v-model="editForm.team_lead_id"
                            id="edit_team_lead_id"
                            required
                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option
                                v-for="lead in potentialLeads"
                                :key="lead.id"
                                :value="lead.id"
                            >
                                {{ lead.name }}
                            </option>
                        </select>
                        <InputError
                            class="mt-2"
                            :message="editForm.errors.team_lead_id"
                        />
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            type="button"
                            @click="closeModal"
                            class="inline-flex items-center justify-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-slate-700 disabled:opacity-50"
                        >
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

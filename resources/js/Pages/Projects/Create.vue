<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    teams: Array, // This is passed from ProjectController@create
});

// Your script logic is perfect and remains unchanged.
const form = useForm({
    name: '',
    description: '',
    team_id: '',
    end_date: '',
    total_hours_required: 100,
    priority: 'medium',
});

const submit = () => {
    form.post(route('projects.store'));
};
</script>

<template>
    <Head title="Create Project" />

    <AuthenticatedLayout>
        <div class="p-4 font-sans sm:p-6 lg:p-8">
            <div class="mx-auto max-w-4xl space-y-6">
                <!-- Page Header -->
                <div class="flex items-center justify-between">
                    <h1 class="text-3xl font-bold text-slate-900">
                        Create New Project
                    </h1>
                </div>

                <!-- Form Card -->
                <div
                    class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
                >
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Row 1: Project Name & Team -->
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label
                                    for="name"
                                    class="block text-sm font-medium text-slate-700"
                                    >Project Name</label
                                >
                                <input
                                    v-model="form.name"
                                    id="name"
                                    type="text"
                                    required
                                    autofocus
                                    class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                />
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.name"
                                />
                            </div>
                            <div>
                                <label
                                    for="team_id"
                                    class="block text-sm font-medium text-slate-700"
                                    >Assign to Team</label
                                >
                                <select
                                    v-model="form.team_id"
                                    id="team_id"
                                    required
                                    class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                >
                                    <option value="" disabled>
                                        -- Select a team --
                                    </option>
                                    <option
                                        v-for="team in teams"
                                        :key="team.id"
                                        :value="team.id"
                                    >
                                        {{ team.name }} (Lead:
                                        {{ team.team_lead.name }})
                                    </option>
                                </select>
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.team_id"
                                />
                            </div>
                        </div>

                        <!-- Row 2: End Date, Hours & Priority -->
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                            <div>
                                <label
                                    for="end_date"
                                    class="block text-sm font-medium text-slate-700"
                                    >Expected End Date</label
                                >
                                <input
                                    v-model="form.end_date"
                                    id="end_date"
                                    type="date"
                                    required
                                    class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                />
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.end_date"
                                />
                            </div>
                            <div>
                                <label
                                    for="total_hours_required"
                                    class="block text-sm font-medium text-slate-700"
                                    >Total Hours Required</label
                                >
                                <input
                                    v-model="form.total_hours_required"
                                    id="total_hours_required"
                                    type="number"
                                    min="1"
                                    required
                                    class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                />
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.total_hours_required"
                                />
                            </div>
                            <div>
                                <label
                                    for="priority"
                                    class="block text-sm font-medium text-slate-700"
                                    >Priority</label
                                >
                                <select
                                    v-model="form.priority"
                                    id="priority"
                                    class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                >
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.priority"
                                />
                            </div>
                        </div>

                        <!-- Row 3: Description -->
                        <div>
                            <label
                                for="description"
                                class="block text-sm font-medium text-slate-700"
                                >Description (Optional)</label
                            >
                            <textarea
                                v-model="form.description"
                                id="description"
                                rows="4"
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            ></textarea>
                            <InputError
                                class="mt-2"
                                :message="form.errors.description"
                            />
                        </div>

                        <!-- Form Actions -->
                        <div
                            class="flex items-center justify-end border-t border-slate-200 pt-6"
                        >
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-slate-700 disabled:opacity-50"
                            >
                                Create Project
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

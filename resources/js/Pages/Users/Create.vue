<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import { watch } from 'vue'; // Keep the watch function

// THE FIX:
// We need to accept the `theAdmin` prop for automatic assignment.
// This data should be provided by your UserController@create method.
const props = defineProps({
  roles: Array,
  teams: Array,
  potential_managers: Object,
  theAdmin: Object, // Will be an object like { id: 1, name: 'Admin' } or null
});

const form = useForm({
  name: '',
  email: '',
  password: '',
  role: '',
  team_id: '',
  parent_id: '',
});

watch(() => form.role, (newRole, oldRole) => {
  // Only proceed if the role has actually changed.
  if (newRole === oldRole) {
    return;
  }

  // Case 1: If the new role is 'project-manager' and we have an Admin...
  if (newRole === 'project-manager' && props.theAdmin) {
    // ...automatically set the parent_id. No user action is needed.
    form.parent_id = props.theAdmin.id;
  } else {
    // Case 2: For ANY OTHER role change, reset the parent_id.
    // This is vital. It forces the user to make a new, conscious selection for
    // the new role and prevents submitting an old, incorrect parent_id.
    form.parent_id = '';
  }
});

const submit = () => {
  form.post(route('users.store'), {
    onFinish: () => form.reset('password'),
  });
};
</script>

<template>
  <Head title="Add Employee" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add New Employee</h2>
    </template>

    <div class="py-12">
      <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
          <form @submit.prevent="submit" class="space-y-6">
            <!-- Name, Email, Password, and Role sections are unchanged and correct -->
            <div>
              <InputLabel for="name" value="Name" />
              <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required />
              <InputError class="mt-2" :message="form.errors.name" />
            </div>
            <div>
              <InputLabel for="email" value="Email" />
              <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required />
              <InputError class="mt-2" :message="form.errors.email" />
            </div>
            <div>
              <InputLabel for="password" value="Password" />
              <TextInput id="password" type="password" class="mt-1 block w-full" v-model="form.password" required />
              <InputError class="mt-2" :message="form.errors.password" />
            </div>
            <div>
              <InputLabel for="role" value="Role" />
              <select id="role" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" v-model="form.role" required>
                <option value="" disabled>Select a role</option>
                <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
              </select>
              <InputError class="mt-2" :message="form.errors.role" />
            </div>

            <!-- DYNAMIC MANAGER (PARENT) SECTION - COMPLETELY REVISED -->
            <div>
              <!-- Case 1: Role is Project Manager (Automatic Assignment) -->
              <div v-if="form.role === 'project-manager'">
                <InputLabel value="Reports To" />
                <div v-if="theAdmin" class="mt-1 p-2 bg-gray-100 border border-gray-200 rounded-md">
                  <p class="text-sm font-medium text-gray-700">{{ theAdmin.name }} (Automatically Assigned)</p>
                </div>
              </div>

              <!-- Case 2: Role is Team Lead (Manual Selection from PMs) -->
              <div v-if="form.role === 'team-lead'">
                <InputLabel for="parent_id_pm" value="Reports To (Project Manager) *" />
                <select id="parent_id_pm" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" v-model="form.parent_id" required>
                  <option value="" disabled>Select a Project Manager</option>
                  <option v-for="manager in potential_managers.project_managers" :key="manager.id" :value="manager.id">{{ manager.name }}</option>
                </select>
              </div>

              <!-- Case 3: Role is Employee (Manual Selection from TLs) -->
              <div v-if="form.role === 'employee'">
                <InputLabel for="parent_id_lead" value="Reports To (Team Lead) *" />
                <select id="parent_id_lead" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" v-model="form.parent_id" required>
                  <option value="" disabled>Select a Team Lead</option>
                  <option v-for="manager in potential_managers.team_leads" :key="manager.id" :value="manager.id">{{ manager.name }}</option>
                </select>
              </div>
              <InputError class="mt-2" :message="form.errors.parent_id" />
            </div>

            <!-- Team Section (unchanged and correct) -->
            <div v-if="form.role === 'employee'">
              <InputLabel for="team_id" value="Team" />
              <select id="team_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" v-model="form.team_id" required>
                <option value="" disabled>Select a team</option>
                <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
              </select>
              <InputError class="mt-2" :message="form.errors.team_id" />
            </div>

            <!-- Buttons (unchanged and correct) -->
            <div class="flex items-center gap-4">
              <PrimaryButton :disabled="form.processing">Create Employee</PrimaryButton>
              <Link :href="route('users.index')" class="text-sm text-gray-600 hover:underline">Cancel</Link>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

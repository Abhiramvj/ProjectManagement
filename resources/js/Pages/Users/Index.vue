<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
  users: Object,
  roles: Array,
  teams: Array,
  potential_managers: Object,
  theAdmin: Object
});

const showModal = ref(false);

const form = useForm({
  name: '',
  email: '',
  password: '',
  role: '',
  team_id: '',
  parent_id: '',
});

const toggleModal = () => {
  showModal.value = !showModal.value;
};

// Watch for role-based logic
watch(() => form.role, (newRole, oldRole) => {
  if (newRole === oldRole) return;

  if (newRole === 'project-manager' && props.theAdmin) {
    form.parent_id = props.theAdmin.id;
  } else {
    form.parent_id = '';
  }
});

// Submit form
const submit = () => {
  form.post(route('users.store'), {
    onSuccess: () => {
      form.reset();
      showModal.value = false;
      router.reload({ only: ['users'] });
    },
    onError: () => {
      // Keep data visible
    },
    onFinish: () => form.reset('password'),
  });
};
</script>

<template>
  <Head title="Manage Employees" />

  <AuthenticatedLayout>
    <!-- Header -->
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0 sm:space-x-4">
        <h2 class="text-2xl font-semibold text-gray-800 tracking-tight">
          Manage Employees
        </h2>
        <button
          @click="toggleModal"
          class="inline-block px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md shadow hover:bg-indigo-700 transition"
        >
          Add Employee
        </button>
      </div>
    </template>

    <!-- Employee Table -->
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                <th></th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="user in users.data" :key="user.id">
                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ user.name }}</td>
                <td class="px-6 py-4 text-sm text-gray-500">{{ user.email }}</td>
                <td class="px-6 py-4 text-sm text-gray-500 capitalize">
                  {{ user.roles.map(r => r.name).join(', ') }}
                </td>
                <td class="px-6 py-4 text-right text-sm font-medium">
                  <Link :href="route('performance.show', user.id)" class="text-indigo-600 hover:text-indigo-900">
                    View Performance
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal Form -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 p-3">
      <div class="bg-white w-full max-w-xl rounded-lg shadow-lg p-6 overflow-y-auto max-h-[90vh]">
        <div class="flex justify-between items-center border-b pb-4 mb-4">
          <h3 class="text-xl font-semibold text-gray-800">Add New Employee</h3>
          <button @click="toggleModal" class="text-xl text-gray-500 hover:text-gray-700">&times;</button>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
          <!-- Name -->
          <div>
            <InputLabel for="name" value="Name" />
            <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
            <InputError :message="form.errors.name" class="mt-2" />
          </div>

          <!-- Email -->
          <div>
            <InputLabel for="email" value="Email" />
            <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" required />
            <InputError :message="form.errors.email" class="mt-2" />
          </div>

          <!-- Password -->
          <div>
            <InputLabel for="password" value="Password" />
            <TextInput id="password" v-model="form.password" type="password" class="mt-1 block w-full" required />
            <InputError :message="form.errors.password" class="mt-2" />
          </div>

          <!-- Role -->
          <div>
            <InputLabel for="role" value="Role" />
            <select id="role" v-model="form.role" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
              <option value="" disabled>Select a role</option>
              <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
            </select>
            <InputError class="mt-2" :message="form.errors.role" />
          </div>

          <!-- Manager Assignment -->
          <div>
            <!-- Admin Assigned for Project Manager -->
            <div v-if="form.role === 'project-manager' && theAdmin">
              <InputLabel value="Reports To" />
              <div class="p-2 mt-1 bg-gray-100 border border-gray-300 text-sm rounded">
                {{ theAdmin.name }} (Automatically assigned)
              </div>
            </div>

            <!-- Project Manager Select for TL -->
            <div v-if="form.role === 'team-lead'">
              <InputLabel for="parent_pm" value="Reports To (Project Manager)" />
              <select v-model="form.parent_id" id="parent_pm" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                <option disabled value="">Select Project Manager</option>
                <option v-for="pm in potential_managers.project_managers" :key="pm.id" :value="pm.id">
                  {{ pm.name }}
                </option>
              </select>
            </div>

            <!-- Team Lead Select for Employee -->
            <div v-if="form.role === 'employee'">
              <InputLabel for="parent_tl" value="Reports To (Team Lead)" />
              <select v-model="form.parent_id" id="parent_tl" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                <option disabled value="">Select Team Lead</option>
                <option v-for="tl in potential_managers.team_leads" :key="tl.id" :value="tl.id">
                  {{ tl.name }}
                </option>
              </select>
            </div>

            <InputError :message="form.errors.parent_id" class="mt-2" />
          </div>

          <!-- Team (only for employee role) -->
          <div v-if="form.role === 'employee'">
            <InputLabel for="team_id" value="Team" />
            <select id="team_id" v-model="form.team_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
              <option disabled value="">Select Team</option>
              <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
            </select>
            <InputError :message="form.errors.team_id" class="mt-2" />
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-4 pt-4 border-t">
            <button
              type="button"
              @click="toggleModal"
              class="bg-gray-100 px-4 py-2 rounded-md hover:bg-gray-200 text-gray-700 text-sm"
            >Cancel</button>

            <PrimaryButton type="submit" :disabled="form.processing">Create Employee</PrimaryButton>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

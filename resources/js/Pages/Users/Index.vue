<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import Pagination from '@/Components/Pagination.vue';
// Import usePage to access shared data like validation errors from Laravel
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { debounce } from 'lodash';
import { onMounted } from 'vue';

// Tell Inertia to use the AuthenticatedLayout by default for this page.
defineOptions({ layout: AuthenticatedLayout });

// =========== PROPS ===========
// These props are populated by the `GetUsers` action in your backend.
const props = defineProps({
    users: Object, // Paginated list of users
    roles: Array,
    teams: Array,
    potential_managers: Object,
    theAdmin: Object,
    filters: Object, // Current search filters
    workModes: Array,
});

// =========== STATE MANAGEMENT ===========
const isModalVisible = ref(false);
const modalMode = ref('create');
const editingUser = ref(null);
const search = ref(props.filters?.search || '');
const imagePreview = ref(null);

// State for the new import modal
const isImportModalVisible = ref(false);
const page = usePage(); // Hook to access page-level props, including errors

// =========== FORM HANDLING ===========
// Form for creating/editing a single user
const form = useForm({
    _method: null,
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: '',
    designation: '',
    work_mode: '',
    team_id: '',
    parent_id: '',
    image: null,
});

// Form for handling the Excel file import
const importForm = useForm({
    file: null,
});

// =========== DYNAMIC FORM LOGIC ===========
// Automatically sets the manager for a project manager or clears fields when the role changes.
watch(() => form.role, (newRole, oldRole) => {
    if (newRole === 'project-manager' && props.theAdmin) {
        form.parent_id = props.theAdmin.id;
    } else if (newRole !== oldRole) {
        form.parent_id = '';
        if (newRole !== 'employee') {
            form.team_id = '';
        }
    }
});

onMounted(() => {
    window.Echo.private('user-import')
        .listen('UserImportCompleted', (e) => {
            alert(`Import completed for file: ${e.filePath}`);
        });
});

// =========== MODAL CONTROLS ===========
const openCreateModal = () => {
    form.reset();
    imagePreview.value = null;
    modalMode.value = 'create';
    editingUser.value = null;
    form._method = 'post';
    isModalVisible.value = true;
};

const openEditModal = (user) => {
    form.reset();
    modalMode.value = 'edit';
    editingUser.value = user;
    form._method = 'put';

    // Populate the form with the existing user's data
    form.name = user.name;
    form.email = user.email;
    form.role = user.roles[0]?.name || '';
    form.designation = user.designation || '';
    form.work_mode = user.work_mode || '';
    form.team_id = user.team_id || '';
    form.parent_id = user.parent_id || '';
    imagePreview.value = user.image ? `/storage/${user.image}` : null;

    isModalVisible.value = true;
};

const closeModal = () => {
    isModalVisible.value = false;
    form.reset();
    form.clearErrors();
};

// Controls for the new import modal
const openImportModal = () => {
    importForm.reset();
    // Clear any previous import errors when opening the modal
    page.props.errors = {};
    isImportModalVisible.value = true;
};

const closeImportModal = () => {
    isImportModalVisible.value = false;
    importForm.reset();
    importForm.clearErrors();
};


// =========== CRUD OPERATIONS ===========
const submit = () => {
    const routeName = modalMode.value === 'create'
        ? route('users.store')
        : route('users.update', editingUser.value.id);

    form.post(routeName, {
        onSuccess: () => closeModal(),
        preserveScroll: true,
    });
};

const deleteUser = (userId) => {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        router.delete(route('users.destroy', userId), {
            preserveScroll: true,
        });
    }
};

// Submit method for the file import
const submitImport = () => {
    importForm.post(route('users.import'), {
        onSuccess: () => {
            closeImportModal();
            // You can add a success notification here if you have a system for it
        },
        // If there are validation errors, Inertia will handle them automatically.
        // The modal will stay open, and the errors will be displayed.
    });
};

// =========== FEATURES ===========
const searchUsers = debounce(() => {
    router.get(route('users.index'), { search: search.value }, { preserveState: true, replace: true });
}, 300);

const paginationLinks = computed(() => props.users.links);

function handleImageUpload(e) {
    const file = e.target.files[0];
    if (file) {
        form.image = file;
        imagePreview.value = URL.createObjectURL(file);
    }
}
</script>

<template>
    <Head title="Manage Users" />

    <div class="p-4 sm:p-6 lg:p-8 font-sans">
        <div class="max-w-7xl mx-auto space-y-6">

            <!-- Page Header -->
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h1 class="text-3xl font-bold text-slate-900">Manage Users</h1>
                <div class="flex items-center gap-3">
                    <button @click="openImportModal" class="inline-flex items-center justify-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 mr-2 -ml-1"><path fill-rule="evenodd" d="M9.25 13.25a.75.75 0 0 0 1.5 0V4.636l2.162 2.163a.75.75 0 1 0 1.06-1.06l-3.5-3.5a.75.75 0 0 0-1.06 0l-3.5 3.5a.75.75 0 1 0 1.06 1.06L9.25 4.636v8.614Z" clip-rule="evenodd" /><path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z" /></svg>
                        Import Users
                    </button>
                    <button @click="openCreateModal" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-slate-700">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 mr-2 -ml-1"><path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" /></svg>
                        Add User
                    </button>
                </div>
            </div>

            <!-- Users List Card -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200">
                 <div class="p-4 sm:px-6 border-b border-slate-200">
                    <input v-model="search" @input="searchUsers" type="text" placeholder="Search by name or email..." class="block w-full max-w-xs rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="py-3.5 px-6 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Name</th>
                                <th scope="col" class="py-3.5 px-6 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Role</th>
                                <th scope="col" class="py-3.5 px-6 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Work Mode</th>
                                <th scope="col" class="py-3.5 px-6 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Joined On</th>
                                <th scope="col" class="py-3.5 px-6 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider"><span class="sr-only text-slate-500">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-if="!users.data.length">
                                <td colspan="5" class="px-6 py-8 text-center text-slate-500">No users found.</td>
                            </tr>
                            <tr v-for="user in users.data" :key="user.id" class="hover:bg-slate-50 transition-colors">
                                <td class="whitespace-nowrap py-4 px-6 text-sm">
                                    <div class="flex items-center gap-3">
                                        <img class="h-10 w-10 rounded-full object-cover" :src="user.image ? `/storage/${user.image}` : `https://ui-avatars.com/api/?name=${user.name}&background=random`" alt="Profile avatar">
                                        <div>
                                            <div class="font-medium text-slate-900">{{ user.name }}</div>
                                            <div class="text-slate-500">{{ user.email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap py-4 px-6 text-sm text-slate-600 capitalize">{{ user.roles[0]?.name.replace('-', ' ') || 'N/A' }}</td>
                                <td class="whitespace-nowrap py-4 px-6 text-sm text-slate-600">{{ user.work_mode || 'N/A' }}</td>
                                <td class="whitespace-nowrap py-4 px-6 text-sm text-slate-600">{{ new Date(user.created_at).toLocaleDateString() }}</td>
                                <td class="whitespace-nowrap py-4 px-6 text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-4">
                                        <Link :href="route('performance.show', user.id)" class="text-green-600 hover:text-green-900">Performance</Link>
                                        <button @click="openEditModal(user)" class="text-indigo-600 hover:text-indigo-900">Edit</button>
                                        <button @click="deleteUser(user.id)" class="text-red-600 hover:text-red-900">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <Pagination :links="paginationLinks" :meta="users" />

            </div>
        </div>

        <!-- Create / Edit User Modal -->
        <Modal :show="isModalVisible" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-bold text-slate-900">{{ modalMode === 'create' ? 'Create New User' : 'Edit User' }}</h2>
                <form @submit.prevent="submit" class="mt-6 space-y-6">
                    <!-- Name, Email, Image, Role -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700">Name</label>
                        <input v-model="form.name" id="name" type="text" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                        <input v-model="form.email" id="email" type="email" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                     <div>
                        <label for="designation" class="block text-sm font-medium text-slate-700">Designation / Job Title</label>
                        <input v-model="form.designation" id="designation" type="text" placeholder="e.g., Software Engineer" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                        <InputError class="mt-2" :message="form.errors.designation" />
                    </div>

                    <div>
                        <label for="image" class="block text-sm font-medium text-slate-700">Profile Image</label>
                        <div class="mt-2 flex items-center gap-4">
                            <img v-if="imagePreview" :src="imagePreview" alt="Preview" class="w-16 h-16 rounded-full object-cover border" />
                            <div v-else class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                               <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                            </div>
                            <input id="image" type="file" accept="image/*" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" @change="handleImageUpload"/>
                        </div>
                        <InputError class="mt-2" :message="form.errors.image" />
                    </div>
                    <div>
                        <label for="role" class="block text-sm font-medium text-slate-700">Role</label>
                        <select v-model="form.role" id="role" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm capitalize">
                            <option value="" disabled>-- Assign a role --</option>
                            <option v-for="role in roles" :key="role" :value="role" class="capitalize">{{ role.replace('-', ' ') }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.role" />
                    </div>

                    <div>
                        <label for="work_mode" class="block text-sm font-medium text-slate-700">Work Mode</label>
                        <select v-model="form.work_mode" id="work_mode" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">-- Not Set --</option>
                            <option v-for="mode in workModes" :key="mode" :value="mode">
                                {{ mode }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.work_mode" />
                    </div>

                    <!-- Reports To and Team -->
                   <div>
                        <div v-if="form.role === 'project-manager'">
                            <label class="block text-sm font-medium text-slate-700">Reports To</label>
                            <div v-if="theAdmin" class="mt-1 p-2 bg-slate-100 border border-slate-200 rounded-md">
                                <p class="text-sm font-medium text-slate-700">{{ theAdmin.name }} (Auto-assigned)</p>
                            </div>
                        </div>
                        <div v-if="form.role === 'team-lead'">
                            <label for="parent_id_pm" class="block text-sm font-medium text-slate-700">Reports To (Project Manager)</label>
                            <select id="parent_id_pm" v-model="form.parent_id" required class="mt-1 block w-full border-slate-300 rounded-md shadow-sm">
                                <option value="" disabled>Select a Project Manager</option>
                                <option v-for="manager in potential_managers.project_managers" :key="manager.id" :value="manager.id">{{ manager.name }}</option>
                            </select>
                        </div>
                        <div v-if="form.role === 'employee'">
                            <label for="parent_id_lead" class="block text-sm font-medium text-slate-700">Reports To (Team Lead)</label>
                            <select id="parent_id_lead" v-model="form.parent_id" required class="mt-1 block w-full border-slate-300 rounded-md shadow-sm">
                                <option value="" disabled>Select a Team Lead</option>
                                <option v-for="manager in potential_managers.team_leads" :key="manager.id" :value="manager.id">{{ manager.name }}</option>
                            </select>
                        </div>
                        <InputError class="mt-2" :message="form.errors.parent_id" />
                    </div>
                    <div v-if="form.role === 'employee'">
                         <label for="team_id" class="block text-sm font-medium text-slate-700">Team</label>
                        <select v-model="form.team_id" id="team_id" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">-- Assign a team --</option>
                            <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.team_id" />
                    </div>

                   <div v-if="modalMode === 'create'">
  <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
  <input v-model="form.password" id="password" type="password" required class="mt-1 block w-full rounded-md border-slate-300" />
  <InputError :message="form.errors.password" />
</div>
<div v-if="modalMode === 'create'">
  <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirm Password</label>
  <input v-model="form.password_confirmation" id="password_confirmation" type="password" required class="mt-1 block w-full rounded-md border-slate-300" />
  <InputError :message="form.errors.password_confirmation" />
</div>

                    <!-- Form Actions -->
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="closeModal" class="inline-flex items-center justify-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-slate-700 disabled:opacity-50">
                            {{ modalMode === 'create' ? 'Create User' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Import Users Modal -->
        <Modal :show="isImportModalVisible" @close="closeImportModal">
            <div class="p-6">
                <h2 class="text-lg font-bold text-slate-900">Import Users from Excel</h2>
                <form @submit.prevent="submitImport" class="mt-6 space-y-6">
                    <div class="p-4 border border-blue-200 bg-blue-50 rounded-lg text-sm text-blue-700">
                        <p class="font-semibold">Instructions:</p>
                        <ul class="list-disc list-inside mt-2 space-y-1">
                            <li>Download the template to ensure column headers are correct.</li>
                            <li>Required columns: <strong>name, email, designation, role, doj_dd_mm_yyyy</strong>.</li>
                            <li>The 'role' must exactly match a system role (e.g., 'employee').</li>
                        </ul>
                        <div class="mt-4">
                            <a :href="route('users.import.template')" class="font-semibold text-blue-800 hover:underline">Download Template (.xlsx)</a>
                        </div>
                    </div>
                    <div>
                        <label for="import_file" class="block text-sm font-medium text-slate-700">Excel File (.xlsx, .xls, .csv)</label>
                        <input
                            id="import_file"
                            type="file"
                            accept=".xlsx, .xls, .csv"
                            class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                            @input="importForm.file = $event.target.files[0]"
                            required
                        />
                        <progress v-if="importForm.progress" :value="importForm.progress.percentage" max="100" class="w-full mt-2">
                            {{ importForm.progress.percentage }}%
                        </progress>
                        <InputError class="mt-2" :message="importForm.errors.file" />

                        <!-- Displaying validation errors returned from the backend's Excel import -->
                        <div v-if="page.props.errors && Object.keys(page.props.errors).length > 0" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                           <p class="text-sm font-bold text-red-700">The following errors were found in your file:</p>
                            <!-- NEW CORRECTED CODE -->
<ul class="mt-2 list-disc list-inside text-sm text-red-600 space-y-1">
    <!-- Loop through the error messages and display them directly -->
    <li v-for="(error, index) in page.props.errors" :key="index">
        {{ error }}
    </li>
</ul>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="closeImportModal" class="inline-flex items-center justify-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Cancel</button>
                        <button type="submit" :disabled="importForm.processing" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-slate-700 disabled:opacity-50">
                            {{ importForm.processing ? 'Processing...' : 'Upload & Import' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { debounce } from 'lodash';
import axios from 'axios';  // Added axios import for API calls


// =========== PROPS ===========
// Data passed from the UserController@index method in Laravel
const props = defineProps({
    users: Object, // Laravel paginated object
    roles: Array,
    teams: Array,
    potential_managers: Object,
    theAdmin: Object,
    filters: Object,
});


// =========== STATE MANAGEMENT ===========
const isModalVisible = ref(false);
const modalMode = ref('create'); // Can be 'create' or 'edit'
const editingUser = ref(null); // Stores the user object being edited
const search = ref(props.filters?.search || ''); // Search input state

// =========== AI Evaluation State ===========
const aiResult = ref('');
const aiUserName = ref('');
const evaluationLoading = ref({});


// =========== FORM HANDLING ===========
// A single reactive form for both creating and editing users
const form = useForm({
    _method: null, // Will be 'post' for create, 'put' for edit (for method spoofing)
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: '',
    team_id: '',
    parent_id: '',
    image: null, // Holds the file object for new avatar uploads
    imagePreview: null, // Holds the URL for the image preview
});



// =========== DYNAMIC FORM LOGIC ===========
// Watch for role changes to automatically set the manager or clear dependencies
watch(() => form.role, (newRole, oldRole) => {
    // Auto-assign the admin as the manager for Project Managers
    if (newRole === 'project-manager' && props.theAdmin) {
        form.parent_id = props.theAdmin.id;
    }
    // If the role is changed *from* something else, clear the old manager/team
    else if (newRole !== oldRole) {
        form.parent_id = '';
        if (newRole !== 'employee') {
            form.team_id = '';
        }
    }
});



// =========== MODAL CONTROLS ===========
const openCreateModal = () => {
    form.reset(); // Clear form fields and errors
    modalMode.value = 'create';
    editingUser.value = null;
    form._method = 'post'; // Set method for creation
    isModalVisible.value = true;
};


const openEditModal = (user) => {
    form.reset();
    modalMode.value = 'edit';
    editingUser.value = user;
    form._method = 'put'; // Set method for editing/updating


    // Populate form with the user's existing data
    form.name = user.name;
    form.email = user.email;
    form.role = user.roles[0]?.name || '';
    form.team_id = user.team_id || '';
    form.parent_id = user.parent_id || '';
    form.imagePreview = user.image ? `/storage/${user.image}` : null;


    isModalVisible.value = true;
};


const closeModal = () => {
    isModalVisible.value = false;
    form.reset();
    form.clearErrors();
};



// =========== CRUD OPERATIONS ===========
const submit = () => {
    const routeName = modalMode.value === 'create'
        ? route('users.store')
        : route('users.update', editingUser.value.id);


    // Always use form.post() because it correctly handles multipart/form-data for file uploads.
    // The `form._method` property ('post' or 'put') tells Laravel how to handle the request.
    form.post(routeName, {
        onSuccess: () => closeModal(),
        preserveScroll: true, // Keep the user's scroll position on the page
    });
};


const deleteUser = (userId) => {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        router.delete(route('users.destroy', userId), {
            preserveScroll: true,
        });
    }
};



// =========== FEATURES ===========
// Debounced search function to filter users without overwhelming the server
const searchUsers = debounce(() => {
    router.get(route('users.index'), { search: search.value }, { preserveState: true, replace: true });
}, 300);


// Computed property for easy access to pagination links
const paginationLinks = computed(() => props.users.links);


// Handles new image selection and creates a local preview URL
function handleImageUpload(e) {
    const file = e.target.files[0];
    if (file) {
        form.image = file;
        form.imagePreview = URL.createObjectURL(file);
    }
}


// ====== AI Evaluation Method =======
const getAIEvaluation = async (user) => {
    aiResult.value = '';
    aiUserName.value = user.name;
    evaluationLoading.value = { ...evaluationLoading.value, [user.id]: true };

    let prompt = `Provide a brief professional evaluation of the following employee:
- Name: ${user.name}
- Role: ${user.roles[0]?.name || 'N/A'}
- Joined: ${new Date(user.created_at).toLocaleDateString()}`;

    try {
        const { data } = await axios.post('/api/employee-evaluate', { prompt });
        aiResult.value = data.summary;
    } catch (e) {
        aiResult.value = 'Unable to fetch AI evaluation. Please try again.';
    } finally {
        evaluationLoading.value = { ...evaluationLoading.value, [user.id]: false };
    }
};
</script>

<template>
    <Head title="Manage Users" />

    <AuthenticatedLayout>
        <div class="p-4 sm:p-6 lg:p-8 font-sans">
            <div class="max-w-7xl mx-auto space-y-6">


                <!-- Page Header -->
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <h1 class="text-3xl font-bold text-slate-900">Manage Users</h1>
                    <button @click="openCreateModal" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-slate-700">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 mr-2 -ml-1">
                            <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
                        </svg>
                        Add User
                    </button>
                </div>


                <!-- Users List Card -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200">
                    <div class="p-4 sm:px-6 border-b border-slate-200">
                        <input v-model="search" @input="searchUsers" type="text" placeholder="Search by name or email..."
                               class="block w-full max-w-xs rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th scope="col" class="py-3.5 px-6 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Name</th>
                                    <th scope="col" class="py-3.5 px-6 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Role</th>
                                    <th scope="col" class="py-3.5 px-6 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Joined On</th>
                                    <th scope="col" class="relative py-3.5 px-6"><span class="sr-only">Actions</span></th>
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
                                    <td class="whitespace-nowrap py-4 px-6 text-sm text-slate-600">{{ new Date(user.created_at).toLocaleDateString() }}</td>
                                    <td class="px-6 py-4 text-right text-sm font-medium">
                                        <Link :href="route('performance.show', user.id)" class="text-indigo-600 hover:text-indigo-900">
                                            View Performance
                                        </Link>
                                        <button
                                            @click="getAIEvaluation(user)"
                                            class="ml-2 text-violet-600 hover:text-violet-900"
                                            :disabled="evaluationLoading[user.id]"
                                        >
                                            <span v-if="!evaluationLoading[user.id]">Get AI Evaluation</span>
                                            <span v-else>Loading...</span>
                                        </button>
                                    </td>
                                    <td class="whitespace-nowrap py-4 px-6 text-right text-sm font-medium">
                                        <div class="flex items-center justify-end space-x-4">
                                            <button @click="openEditModal(user)" class="text-indigo-600 hover:text-indigo-900">Edit</button>
                                            <button @click="deleteUser(user.id)" class="text-red-600 hover:text-red-900">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                     <!-- Pagination Links -->
                    <div v-if="paginationLinks.length > 3" class="p-4 sm:px-6 border-t border-slate-200 flex items-center justify-between">
                        <div class="text-sm text-slate-600">Showing {{ users.from }} to {{ users.to }} of {{ users.total }} results</div>
                        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm">
                             <Link v-for="(link, index) in paginationLinks" :key="index"
                               :href="link.url"
                               v-html="link.label"
                               :class="{ 'bg-slate-900 text-white': link.active, 'text-slate-900 ring-1 ring-inset ring-slate-300 hover:bg-slate-50': !link.active, 'rounded-l-md': index === 0, 'rounded-r-md': index === paginationLinks.length - 1 }"
                               class="relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20"
                               :disabled="!link.url"
                               preserve-scroll />
                        </nav>
                    </div>
                </div>

                <!-- AI Evaluation Result Display -->
                <div v-if="aiResult" class="max-w-3xl mx-auto my-8 bg-white border border-slate-200 rounded-lg p-4 shadow-sm">
                    <strong>AI Evaluation for {{ aiUserName }}:</strong>
                    <p class="mt-2 whitespace-pre-line">{{ aiResult }}</p>
                </div>
            </div>
        </div>

        <!-- Create / Edit User Modal -->
        <Modal :show="isModalVisible" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-bold text-slate-900">{{ modalMode === 'create' ? 'Create New User' : 'Edit User' }}</h2>
                <form @submit.prevent="submit" class="mt-6 space-y-6">
                    <!-- General Info -->
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


                    <!-- Image Upload -->
                    <div>
                        <label for="image" class="block text-sm font-medium text-slate-700">Profile Image</label>
                        <div class="mt-2 flex items-center gap-4">
                            <img v-if="form.imagePreview" :src="form.imagePreview" alt="Preview" class="w-16 h-16 rounded-full object-cover border" />
                            <div v-else class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                               <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                </svg>
                            </div>
                            <input id="image" type="file" accept="image/*" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" @change="handleImageUpload"/>
                        </div>
                        <InputError class="mt-2" :message="form.errors.image" />
                    </div>


                    <!-- Role & Hierarchy -->
                    <div>
                        <label for="role" class="block text-sm font-medium text-slate-700">Role</label>
                        <select v-model="form.role" id="role" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm capitalize">
                            <option value="" disabled>-- Assign a role --</option>
                            <option v-for="role in roles" :key="role" :value="role" class="capitalize">{{ role.replace('-', ' ') }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.role" />
                    </div>


                    <!-- DYNAMIC MANAGER (PARENT) SECTION -->
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


                    <!-- Team Assignment for Employees -->
                    <div v-if="form.role === 'employee'">
                        <label for="team_id" class="block text-sm font-medium text-slate-700">Team</label>
                        <select v-model="form.team_id" id="team_id" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">-- Assign a team --</option>
                            <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.team_id" />
                    </div>


                    <!-- Flexible Password Section -->
                    <div class="border-t border-slate-200 pt-6">
                        <h3 class="text-base font-semibold text-slate-800">{{ modalMode === 'create' ? 'Set Password' : 'Change Password (Optional)' }}</h3>
                        <div class="mt-4 space-y-6">
                          <div>
                               <label for="password" class="block text-sm font-medium text-slate-700">New Password</label>
                               <input v-model="form.password" id="password" type="password" :required="modalMode === 'create'" autocomplete="new-password" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                               <InputError class="mt-2" :message="form.errors.password" />
                           </div>
                           <div>
                               <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirm New Password</label>
                               <input v-model="form.password_confirmation" id="password_confirmation" type="password" :required="modalMode === 'create'" autocomplete="new-password" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                           </div>
                        </div>
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
    </AuthenticatedLayout>
</template>

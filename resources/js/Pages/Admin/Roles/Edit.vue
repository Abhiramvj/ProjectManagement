<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

// The props are passed from RoleController@edit
const props = defineProps({
    role: Object,
    allPermissions: Array,
});

// The form is pre-filled with the role's current data
const form = useForm({
    name: props.role.name,
    permissions: props.role.permissions, // This will pre-check the correct boxes
});

const submit = () => {
    // We use PUT/PATCH for updates
    form.put(route('roles.update', props.role.id));
};

// Grouping for per-category controls and nice labels
// Group permissions by semantic category keywords (e.g., leave, projects, tasks, hours, roles, employees, teams, announcements)
const getCategoryForPermission = (permission) => {
    const p = permission.toLowerCase();
    if (p.includes('leave')) return 'leave';
    if (p.includes('project')) return 'projects';
    if (p.includes('task')) return 'tasks';
    if (p.includes('hour')) return 'hours';
    if (p.includes('role')) return 'roles';
    if (p.includes('employee') || p.includes('user')) return 'employees';
    if (p.includes('team')) return 'teams';
    if (p.includes('announcement')) return 'announcements';
    return 'misc';
};

const groupedPermissions = computed(() => {
    return props.allPermissions.reduce((acc, permission) => {
        const category = getCategoryForPermission(permission);
        if (!acc[category]) acc[category] = [];
        acc[category].push(permission);
        return acc;
    }, {});
});

const formatLabel = (text) => text.replaceAll('-', ' ');

// Global select all
const isAllSelected = computed(() => form.permissions.length === props.allPermissions.length && props.allPermissions.length > 0);
const toggleSelectAll = (checked) => {
    form.permissions = checked ? [...props.allPermissions] : [];
};

// Per-category select
const isCategoryAllSelected = (categoryPermissions) => categoryPermissions.every((p) => form.permissions.includes(p)) && categoryPermissions.length > 0;
const toggleCategory = (checked, categoryPermissions) => {
    if (checked) {
        const next = new Set(form.permissions);
        categoryPermissions.forEach((p) => next.add(p));
        form.permissions = Array.from(next);
    } else {
        const categorySet = new Set(categoryPermissions);
        form.permissions = form.permissions.filter((p) => !categorySet.has(p));
    }
};

// Derived collections: categories with multiple permissions vs singletons
const groupedCategories = computed(() => {
    return Object.entries(groupedPermissions.value)
        .filter(([, perms]) => perms.length > 1)
        .map(([category, permissions]) => ({ category, permissions }));
});

const singlePermissions = computed(() => {
    return Object.entries(groupedPermissions.value)
        .filter(([, perms]) => perms.length === 1)
        .map(([, perms]) => perms[0]);
});
</script>

<template>
    <Head :title="'Edit Role: ' + form.name" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Role</h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">Update Role Information</h2>
                        </header>

                        <form @submit.prevent="submit" class="mt-6 space-y-6">
                            <div>
                                <InputLabel for="name" value="Role Name" />
                                <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <!-- Permissions Checkbox Section -->
                            <div>
                                <InputLabel value="Update Permissions" />

                                <!-- Global Select All -->
                                <div class="mt-2 mb-3 flex items-center gap-2">
                                    <Checkbox :checked="isAllSelected" @update:checked="toggleSelectAll" />
                                    <span class="text-sm text-gray-700">Select all permissions</span>
                                </div>

                                <div class="space-y-2 border p-4 rounded-md">
                                    <!-- Categories with multiple permissions -->
                                    <div v-for="group in groupedCategories" :key="group.category" class="flex flex-wrap items-baseline gap-x-4 gap-y-2 border-b border-gray-200 py-3 last:border-b-0">
                                        <div class="w-28 shrink-0 flex items-center gap-2">
                                            <strong class="font-semibold text-gray-900 capitalize">{{ formatLabel(group.category) }}:</strong>
                                            <button type="button" class="text-xs text-indigo-600 hover:text-indigo-800"
                                                @click="toggleCategory(!isCategoryAllSelected(group.permissions), group.permissions)">
                                                {{ isCategoryAllSelected(group.permissions) ? 'Clear' : 'Select all' }}
                                            </button>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
                                            <div v-for="permission in group.permissions" :key="permission" class="flex items-center">
                                                <Checkbox :id="'permission_' + permission" :value="permission" v-model:checked="form.permissions" />
                                                <label :for="'permission_' + permission" class="ms-2 text-sm text-gray-600 capitalize">{{ formatLabel(permission) }}</label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Single-permission categories shown flat -->
                                    <div v-if="singlePermissions.length" class="flex flex-wrap items-center gap-x-6 gap-y-2 py-2">
                                        <div v-for="permission in singlePermissions" :key="permission" class="flex items-center">
                                            <Checkbox :id="'permission_' + permission" :value="permission" v-model:checked="form.permissions" />
                                            <label :for="'permission_' + permission" class="ms-2 text-sm text-gray-600 capitalize">{{ formatLabel(permission) }}</label>
                                        </div>
                                    </div>
                                </div>
                                <InputError class="mt-2" :message="form.errors.permissions" />
                            </div>

                            <div class="flex items-center gap-4">
                                <PrimaryButton :disabled="form.processing">Save Changes</PrimaryButton>
                                <Link :href="route('roles.index')" class="text-sm text-gray-600 hover:underline">Cancel</Link>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
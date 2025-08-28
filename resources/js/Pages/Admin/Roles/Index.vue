<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

// The props are passed from the RoleController@index method
const props = defineProps({
    roles: Array,
    allPermissions: Array,
});

// Set up the form for creating a new role
const form = useForm({
    name: '',
    permissions: [],
});

// Group permissions by their prefix (e.g., 'posts-create', 'posts-edit' become a 'posts' group)
const groupedPermissions = computed(() => {
    return props.allPermissions.reduce((acc, permission) => {
        const category = permission.split('-')[0];
        if (!acc[category]) {
            acc[category] = [];
        }
        acc[category].push(permission);
        return acc;
    }, {});
});

// Function to handle the form submission
const submit = () => {
    form.post(route('roles.store'), {
        onSuccess: () => form.reset(),
    });
};

// Helper to format text
const formatLabel = (text) => {
    return text.replaceAll('-', ' ');
};
</script>

<template>
    <Head title="Manage Roles" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Manage Roles & Permissions
            </h2>
        </template>

        <div class="py-12">
            <div
                class="mx-auto grid max-w-7xl gap-6 sm:px-6 md:grid-cols-2 lg:px-8"
            >
                <!-- Left Column: Form to Add New Role -->
                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">
                                Add New Role
                            </h2>
                            <p class="mt-1 text-sm text-gray-600">
                                Create a new job role and assign the permissions
                                it should have.
                            </p>
                        </header>

                        <form @submit.prevent="submit" class="mt-6 space-y-6">
                            <div>
                                <InputLabel for="name" value="Role Name" />
                                <TextInput
                                    id="name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.name"
                                    required
                                    autofocus
                                    placeholder="e.g. Project Manager"
                                />
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.name"
                                />
                            </div>

                            <!-- ========================================================== -->
                            <!-- === MODIFIED: Permissions Checkbox Section (Row-based) === -->
                            <!-- ========================================================== -->
                            <div>
                                <InputLabel value="Assign Permissions" />
                                <div
                                    class="mt-2 max-h-96 space-y-2 overflow-y-auto rounded-md border p-4"
                                >
                                    <!-- Loop for each category row -->
                                    <div
                                        v-for="(
                                            permissions, category
                                        ) in groupedPermissions"
                                        :key="category"
                                        class="flex flex-wrap items-baseline gap-x-4 gap-y-2 border-b border-gray-200 py-3 last:border-b-0"
                                    >
                                        <!-- Category Title -->
                                        <div class="w-28 shrink-0">
                                            <strong
                                                class="font-semibold capitalize text-gray-900"
                                                >{{
                                                    formatLabel(category)
                                                }}:</strong
                                            >
                                        </div>

                                        <!-- Container for the checkboxes in this row -->
                                        <div
                                            class="flex flex-wrap items-center gap-x-6 gap-y-2"
                                        >
                                            <!-- Loop for permissions within the category -->
                                            <div
                                                v-for="permission in permissions"
                                                :key="permission"
                                                class="flex items-center"
                                            >
                                                <Checkbox
                                                    :id="
                                                        'permission_' +
                                                        permission
                                                    "
                                                    :value="permission"
                                                    v-model:checked="
                                                        form.permissions
                                                    "
                                                />
                                                <label
                                                    :for="
                                                        'permission_' +
                                                        permission
                                                    "
                                                    class="ms-2 text-sm capitalize text-gray-600"
                                                >
                                                    {{
                                                        formatLabel(
                                                            permission.substring(
                                                                permission.indexOf(
                                                                    '-',
                                                                ) + 1,
                                                            ),
                                                        )
                                                    }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.permissions"
                                />
                            </div>
                            <!-- ========================================================== -->
                            <!-- ===================== END OF CHANGE ====================== -->
                            <!-- ========================================================== -->

                            <div class="flex items-center gap-4">
                                <PrimaryButton :disabled="form.processing"
                                    >Create Role</PrimaryButton
                                >
                                <Transition
                                    enter-from-class="opacity-0"
                                    leave-to-class="opacity-0"
                                    class="transition ease-in-out"
                                >
                                    <p
                                        v-if="form.recentlySuccessful"
                                        class="text-sm text-gray-600"
                                    >
                                        Created.
                                    </p>
                                </Transition>
                            </div>
                        </form>
                    </section>
                </div>

                <!-- Right Column: Simplified List of Existing Roles -->
                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">
                                Existing Roles
                            </h2>
                        </header>
                        <div class="mt-4 space-y-3">
                            <div
                                v-if="!props.roles.length"
                                class="py-4 text-center text-gray-500"
                            >
                                No roles have been created yet.
                            </div>
                            <div
                                v-for="role in props.roles"
                                :key="role.id"
                                class="flex items-center justify-between rounded-md border bg-gray-50 px-4 py-3"
                            >
                                <h3
                                    class="font-semibold capitalize text-gray-800"
                                >
                                    {{ role.name }}
                                </h3>
                                <Link
                                    v-if="role.name !== 'admin'"
                                    :href="route('roles.edit', role.id)"
                                    class="text-sm font-medium text-indigo-600 hover:text-indigo-800 hover:underline"
                                >
                                    Edit
                                </Link>
                                <span
                                    v-else
                                    class="cursor-not-allowed text-sm text-gray-400"
                                    >Cannot Edit</span
                                >
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';

// Define all the props that can be passed from the backend controller
defineProps({
    projects: Array,
    myTasks: Array,
    pendingLeaveRequests: Array,
    stats: Object,
});

// Get the full authenticated user object to check permissions and display name
const user = usePage().props.auth.user;

// Function to handle updating the status of a task
const updateTaskStatus = (task, newStatus) => {
    router.patch(route('tasks.update', { task: task.id }), {
        status: newStatus
    }, {
        preserveScroll: true // This prevents the page from jumping on update
    });
};
</script>

<template>
    <Head title="Dashboard" />
    

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Section 1: At-a-Glance Stat Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Employee Count Card (Shows for HR/Admin if data exists) -->
                    <div v-if="stats.employee_count !== undefined" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <p class="text-sm font-medium text-gray-500 truncate">Total Employees</p>
                            <p class="mt-1 text-3xl font-semibold text-gray-900">{{ stats.employee_count }}</p>
                        </div>
                    </div>
                    <!-- Project Count Card (Shows for PM/Admin if data exists) -->
                    <div v-if="stats.project_count !== undefined" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <p class="text-sm font-medium text-gray-500 truncate">Total Projects</p>
                            <p class="mt-1 text-3xl font-semibold text-gray-900">{{ stats.project_count }}</p>
                        </div>
                    </div>
                </div>

                <!-- Panel 1: Pending Leave Requests (Shows for HR and Admin if data exists) -->
                <div v-if="pendingLeaveRequests && pendingLeaveRequests.length" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-medium text-gray-900">Pending Leave Requests</h3>
                            <Link :href="route('leave.index')" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                                Manage All →
                            </Link>
                        </div>
                        <div class="mt-4 space-y-4">
                            <div v-for="request in pendingLeaveRequests" :key="request.id" class="p-4 rounded-lg bg-yellow-50 border border-yellow-200">
                                <p class="font-semibold text-gray-800">{{ request.user.name }}</p>
                                <p class="text-sm text-indigo-600" v-if="request.leave_type">
                                    <span class="font-semibold">Type:</span>
                                        {{ request.leave_type.charAt(0).toUpperCase() + request.leave_type.slice(1) }} Leave
                                </p>
                                <p v-else class="text-sm text-gray-400 italic">No leave type</p>

                                <p class="text-sm text-gray-600">
                                    <span class="font-semibold">From:</span> {{ request.start_date }}
                                    <span class="font-semibold">To:</span> {{ request.end_date }}
                                </p>
                                <p class="mt-1 text-sm text-gray-500 truncate">{{ request.reason }}</p>
                                
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel 2: Projects List (Shows for PM, Team Lead, and Admin if data exists) -->
                <div v-if="projects && projects.length" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900">Active Projects</h3>
                        <ul class="mt-4 space-y-3">
                            <li v-for="project in projects" :key="project.id" class="p-4 bg-gray-50 rounded-lg border border-gray-200 flex justify-between items-center">
                                <div>
                                    <p class="font-semibold text-gray-800">{{ project.name }}</p>
                                    <span class="text-sm text-gray-600 block">Status: {{ project.status }}</span>
                                </div>
                                <div>
                                    <Link :href="route('projects.show', project.id)" class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-md hover:bg-indigo-700 shadow-sm">
                                        <!-- The button text changes based on the user's permissions -->
                                        <span v-if="user.permissions.includes('assign tasks')">View / Assign Tasks</span>
                                        <span v-else>View Progress</span>
                                    </Link>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Panel 3: My Assigned Tasks (Shows for Employees, Team Leads, and Admin if data exists) -->
                <div v-if="myTasks && myTasks.length" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900">My Assigned Tasks</h3>
                        <div class="mt-4 space-y-4">
                            <div v-for="task in myTasks" :key="task.id" class="p-4 rounded-lg border flex justify-between items-center" :class="{ 'bg-green-50 border-green-200': task.status === 'done', 'bg-yellow-50 border-yellow-200': task.status === 'in-progress' }">
                                <div>
                                    <p class="font-semibold text-gray-800">{{ task.name }}</p>
                                    <p class="text-sm text-gray-500">Project: <span class="font-medium text-gray-700">{{ task.project.name }}</span></p>
                                    <p class="text-sm text-gray-500">Status: <span class="font-semibold uppercase">{{ task.status }}</span></p>
                                </div>
                                <div class="flex gap-2">
                                    <button v-if="task.status === 'todo'" @click="updateTaskStatus(task, 'in-progress')" class="px-3 py-1 text-xs font-semibold text-white bg-blue-500 rounded-md hover:bg-blue-600 shadow-sm">Start Progress</button>
                                    <button v-if="task.status === 'in-progress'" @click="updateTaskStatus(task, 'done')" class="px-3 py-1 text-xs font-semibold text-white bg-green-500 rounded-md hover:bg-green-600 shadow-sm">Mark as Done</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel 4: Welcome Message (Only shows if all other panels are empty) -->
                <div v-if="!pendingLeaveRequests.length && !projects.length && !myTasks.length" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        Welcome, {{ user.name }}! There are no active items on your dashboard right now.
                    </div>
                </div>

            </div>
        </div><pre>{{ JSON.stringify(pendingLeaveRequests, null, 2) }}</pre>

    </AuthenticatedLayout>
</template>
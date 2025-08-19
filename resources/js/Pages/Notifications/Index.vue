<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { computed } from 'vue';

const props = defineProps({
    notifications: Object, // Laravel pagination object
});

// --- Modernized API Calls using Inertia Router ---

const markAsRead = (notificationId) => {
    router.post(route('notifications.read', notificationId), {}, {
        preserveScroll: true, // Keep the user's scroll position
    });
};

const markAllAsRead = () => {
    router.post(route('notifications.mark-all-read'), {}, {
        preserveScroll: true,
    });
};

// --- Your excellent helper functions are kept, with SVG icons replacing emojis ---

const icons = {
    leave_request: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>`,
    leave_approved: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`,
    leave_rejected: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`,
    default: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>`,
};

const getNotificationIcon = (type) => icons[type] || icons.default;

const getNotificationColorClasses = (type, isRead) => {
    if (isRead) {
        return 'bg-white';
    }
    switch (type) {
        case 'leave_request': return 'bg-blue-50';
        case 'leave_approved': return 'bg-green-50';
        case 'leave_rejected': return 'bg-red-50';
        default: return 'bg-slate-50';
    }
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString('en-US', {
        month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit'
    });
};

const paginationLinks = computed(() => props.notifications.links);
const hasUnreadNotifications = computed(() => props.notifications.data.some(n => !n.read_at));

// Determine where to route when clicking Details
const page = usePage();
const user = computed(() => page.props.auth.user);
const canManageLeaves = computed(() => user.value?.permissions?.includes('manage leave applications'));
const getDetailsRoute = () => canManageLeaves.value ? route('leave.logs') : route('leave.index');
</script>

<template>
    <Head title="Notifications" />

    <AuthenticatedLayout>
        <div class="p-4 sm:p-6 lg:p-8 font-sans">
            <div class="max-w-4xl mx-auto space-y-6">
                
                <!-- Page Header -->
                <div class="flex items-center justify-between">
                    <h1 class="text-3xl font-bold text-slate-900">Notifications</h1>
                    <button v-if="hasUnreadNotifications" @click="markAllAsRead"
                            class="px-4 py-2 text-sm font-semibold bg-white border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors shadow-sm">
                        Mark All as Read
                    </button>
                </div>

                <!-- Notifications Card -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200">
                    <div v-if="notifications.data.length === 0" class="text-center py-20 text-slate-500">
                        <div class="inline-block bg-slate-100 p-4 rounded-full">
                            <svg class="w-12 h-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>
                        </div>
                        <h3 class="mt-4 text-lg font-semibold text-slate-900">No Notifications</h3>
                        <p class="mt-1 text-sm">You're all caught up!</p>
                    </div>

                    <ul v-else class="divide-y divide-slate-200">
                        <li v-for="notification in notifications.data" :key="notification.id"
                            :class="getNotificationColorClasses(notification.data.type, notification.read_at)"
                            class="p-4 sm:p-6 hover:bg-slate-50 transition-colors">
                            <div class="flex items-start space-x-4">
                                <!-- Icon -->
                                <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center bg-white border"
                                     :class="[!notification.read_at ? 'border-blue-200' : 'border-slate-200']"
                                     v-html="getNotificationIcon(notification.data.type)">
                                </div>
                                
                                <div class="flex-1 min-w-0">
                                    <!-- Title and Timestamp -->
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <h4 class="text-base font-semibold text-slate-900">
                                                {{ notification.data.title }}
                                                <span v-if="!notification.read_at" class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">New</span>
                                            </h4>
                                        </div>
                                        <div class="flex-shrink-0 ml-4">
                                            <span class="text-xs text-slate-500">{{ formatDate(notification.created_at) }}</span>
                                        </div>
                                    </div>
                                    <!-- Message Body -->
                                    <p class="text-sm text-slate-700 mt-1">
                                        {{ notification.data.message }}
                                    </p>
                                    <!-- Action Buttons -->
                                    <div class="mt-3 flex items-center space-x-4">
                                        <button v-if="!notification.read_at" @click="markAsRead(notification.id)"
                                                class="text-sm text-blue-600 hover:text-blue-800 font-semibold">
                                            Mark as read
                                        </button>
                                        <Link :href="getDetailsRoute()"
                                              class="text-sm text-slate-600 hover:text-slate-800 font-semibold flex items-center">
                                            View Details <span class="ml-1">→</span>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>

                    <!-- Pagination -->
                    <div v-if="paginationLinks.length > 3" class="p-4 sm:px-6 border-t border-slate-200 flex items-center justify-between">
                        <div class="text-sm text-slate-600">
                            Showing {{ notifications.from }} to {{ notifications.to }} of {{ notifications.total }} results
                        </div>
                        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm">
                             <Link v-for="(link, index) in paginationLinks" :key="index"
                                  :href="link.url"
                                  v-html="link.label"
                                  :class="{
                                      'bg-slate-900 text-white': link.active,
                                      'text-slate-900 ring-1 ring-inset ring-slate-300 hover:bg-slate-50': !link.active,
                                      'rounded-l-md': index === 0,
                                      'rounded-r-md': index === paginationLinks.length - 1
                                  }"
                                  class="relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20"
                                  :disabled="!link.url"
                                  preserve-scroll
                            />
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
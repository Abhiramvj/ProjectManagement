<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue'; 
import { computed } from 'vue';

const props = defineProps({
    notifications: Object, // Laravel pagination object
});

// --- API Calls using Inertia Router ---
const markAsRead = (notificationId) => {
    router.post(
        route('notifications.read', notificationId),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                router.reload(); // Reload the page to update the read status
            },
        },
    );
};


const markAllAsRead = () => {
    router.post(
        route('notifications.mark-all-read'),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                router.reload(); // Refresh notifications
            },
        },
    );
};


// --- Helper functions ---
const icons = {
    leave_request: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>`,
    leave_approved: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`,
    leave_rejected: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`,
    default: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>`,
};
const getNotificationIcon = (type) => icons[type] || icons.default;
const getNotificationColorClasses = (type, isRead) => {
    if (isRead) return 'bg-white';
    switch (type) {
        case 'leave_request':
            return 'bg-blue-50';
        case 'leave_approved':
            return 'bg-green-50';
        case 'leave_rejected':
            return 'bg-red-50';
        default:
            return 'bg-slate-50';
    }
};
const formatDate = (dateString) =>
    new Date(dateString).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });

const paginationLinks = computed(() => props.notifications.links);
const hasUnreadNotifications = computed(() =>
    props.notifications.data.some((n) => !n.read_at),
);

const page = usePage();
const user = computed(() => page.props.auth.user);
const canManageLeaves = computed(() =>
    user.value?.permissions?.includes('manage leave applications'),
);
const getDetailsRoute = (notification) => {
    return notification.data.type === 'leave_request'
        ? route('leave.manageRequests')
        : route('leave.fullRequests');
};

</script>

<template>
    <Head title="Notifications" />

    <AuthenticatedLayout>
        <div class="p-4 font-sans sm:p-6 lg:p-8">
            <div class="mx-auto max-w-4xl space-y-6">
                <!-- Page Header -->
                <div class="flex items-center justify-between">
                    <h1 class="text-3xl font-bold text-slate-900">
                        Notifications
                    </h1>
                    <button
                        v-if="hasUnreadNotifications"
                        @click="markAllAsRead"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition-colors hover:bg-slate-50"
                    >
                        Mark All as Read
                    </button>
                </div>

                <!-- Notifications Card -->
                <div
                    class="rounded-xl border border-slate-200 bg-white shadow-sm"
                >
                    <div
                        v-if="notifications.data.length === 0"
                        class="py-20 text-center text-slate-500"
                    >
                        <div class="inline-block rounded-full bg-slate-100 p-4">
                            <svg
                                class="h-12 w-12 text-slate-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"
                                />
                            </svg>
                        </div>
                        <h3 class="mt-4 text-lg font-semibold text-slate-900">
                            No Notifications
                        </h3>
                        <p class="mt-1 text-sm">You're all caught up!</p>
                    </div>

                    <ul v-else class="divide-y divide-slate-200">
                        <li
                            v-for="notification in notifications.data"
                            :key="notification.id"
                            :class="
                                getNotificationColorClasses(
                                    notification.data.type,
                                    notification.read_at,
                                )
                            "
                            class="p-4 transition-colors hover:bg-slate-50 sm:p-6"
                        >
                            <div class="flex items-start space-x-4">
                                <div
                                    class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full border bg-white"
                                    :class="[
                                        !notification.read_at
                                            ? 'border-blue-200'
                                            : 'border-slate-200',
                                    ]"
                                    v-html="
                                        getNotificationIcon(
                                            notification.data.type,
                                        )
                                    "
                                ></div>
                                <div class="min-w-0 flex-1">
                                    <div
                                        class="flex items-start justify-between"
                                    >
                                        <div class="flex-1">
                                            <h4
                                                class="text-base font-semibold text-slate-900"
                                            >
                                                <span
                                                    v-if="notification.user"
                                                    class="font-bold"
                                                    >{{
                                                        notification.user.name
                                                    }}:
                                                </span>
                                                {{ notification.data.title }}
                                                <span
                                                    v-if="!notification.read_at"
                                                    class="ml-2 inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800"
                                                    >New</span
                                                >
                                            </h4>
                                        </div>
                                        <div class="ml-4 flex-shrink-0">
                                            <span
                                                class="text-xs text-slate-500"
                                                >{{
                                                    formatDate(
                                                        notification.created_at,
                                                    )
                                                }}</span
                                            >
                                        </div>
                                    </div>
                                    <p class="mt-1 text-sm text-slate-700">
                                        {{ notification.data.message }}
                                    </p>
                                    <div
                                        class="mt-3 flex items-center space-x-4"
                                    >
                                        <button
                                            v-if="!notification.read_at"
                                            @click="markAsRead(notification.id)"
                                            class="text-sm font-semibold text-blue-600 hover:text-blue-800"
                                        >
                                            Mark as read
                                        </button>
                                        <Link
    :href="getDetailsRoute(notification)"
    class="flex items-center text-sm font-semibold text-slate-600 hover:text-slate-800"
>
    View Details
    <span class="ml-1">→</span>
</Link>

                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>

                    <!-- ================================================================= -->
                    <!-- START OF THE FIX -->
                    <!-- ================================================================= -->
                    <div
                        v-if="paginationLinks.length > 3"
                        class="flex items-center justify-between border-t border-slate-200 p-4 sm:px-6"
                    >
                        <div class="text-sm text-slate-600">
                            Showing {{ notifications.from }} to
                            {{ notifications.to }} of
                            {{ notifications.total }} results
                        </div>
                        <Pagination :links="paginationLinks" />
                    </div>
                    <!-- ================================================================= -->
                    <!-- END OF THE FIX -->
                    <!-- ================================================================= -->
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

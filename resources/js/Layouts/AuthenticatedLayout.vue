<script setup>
// --- SCRIPT SETUP IS UNCHANGED ---
import { ref, computed, onMounted, onUnmounted, defineAsyncComponent } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import axios from 'axios';
import FlashMessage from '@/Components/FlashMessage.vue';

const ApplicationLogo = defineAsyncComponent(() => import('@/Components/ApplicationLogo.vue'));
const Dropdown = defineAsyncComponent(() => import('@/Components/Dropdown.vue'));
const DropdownLink = defineAsyncComponent(() => import('@/Components/DropdownLink.vue'));
const NavLink = defineAsyncComponent(() => import('@/Components/NavLink.vue'));

const showingNotificationDropdown = ref(false);
const unreadNotificationCount = ref(0);
const notifications = ref([]);
const loading = ref(false);
let notificationInterval = null;

const page = usePage();
const user = computed(() => page.props.auth.user);

const fetchNotifications = async () => {
    if (!user.value || loading.value) return;
    try {
        const [countResponse, notificationsResponse] = await Promise.all([
            axios.get(route('notifications.unread-count')),
            axios.get(route('notifications.recent'))
        ]);
        unreadNotificationCount.value = countResponse.data.count;
        notifications.value = notificationsResponse.data.data;
    } catch (error) { console.error('Error fetching notifications:', error); }
};

const handleNotificationClick = async (notification) => {
  try {
    // Mark it as read in backend
    await axios.post(route('notifications.read', notification.id));

    // Remove it from local state immediately
    notifications.value = notifications.value.filter(n => n.id !== notification.id);
    unreadNotificationCount.value = Math.max(unreadNotificationCount.value - 1, 0);

    // Redirect to notification link (if any)
    if (notification.data.url) {
      window.location.href = notification.data.url;
    }
  } catch (error) {
    console.error('Error marking notification as read:', error);
  }
};


const markAllAsRead = async () => {
    loading.value = true;
    try {
        await axios.post(route('notifications.mark-all-read'));
        await fetchNotifications();
        showingNotificationDropdown.value = false;
    } catch (error) { console.error('Error marking all as read:', error);
    } finally { loading.value = false; }
};

const toggleNotificationDropdown = () => { showingNotificationDropdown.value = !showingNotificationDropdown.value; if (showingNotificationDropdown.value) fetchNotifications(); };
const closeNotificationDropdown = () => { showingNotificationDropdown.value = false; };
const formatDate = (dateString) => new Date(dateString).toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
const getNotificationIcon = (type) => ({'leave_request':'📝','leave_approved':'✅','leave_rejected':'❌'}[type]||'📢');

const userDesignation = computed(() => user.value?.designation || 'Employee');
const showingSidebar = ref(false);

const navigationItems = computed(() => {
    if (!user.value?.permissions) return [];
    const items = [
        { name: 'Dashboard', route: 'dashboard', active: route().current('dashboard'), show: true, icon: 'Dashboard' },
        { name: 'Projects', route: 'projects.index', active: route().current('projects.*'), show: user.value.permissions.includes('assign projects') || user.value.permissions.includes('view all projects progress'), icon: 'Projects' },
        { name: 'Company Hierarchy', route: 'company.hierarchy', active: route().current('company.hierarchy'), show: true, icon: 'Company Hierarchy' },
        { name: 'Working Hours', route: 'hours.index', active: route().current('hours.index'), show: true, icon: 'Working Hours' },
        { name: 'Apply for Leave', route: 'leave.index', active: route().current('leave.index'), show: user.value.permissions.includes('apply for leave'), icon: 'Apply for Leave' },
        { name: 'Your Requests', route: 'leave.fullRequests', active: route().current('leave.fullRequests'), show: true, icon: 'Your Requests'},
        { name: 'Leave Calendar', route: 'leaves.calendar', active: route().current('leaves.calendar'), show: user.value.permissions.includes('manage leave applications'), icon: 'Leave Calendar' },
        { name: 'Manage Requests', route: 'leave.manageRequests', active: route().current('leave.manageRequests'), show: user.value.permissions.includes('manage leave applications'), icon: 'Manage Requests' },
        { name: 'Leave Audit Logs', route: 'leave.logs', active: route().current('leave.logs'), show: user.value.permissions.includes('manage leave applications'), icon: 'Leave Logs' },
        { name: 'Manage Users', route: 'users.index', active: route().current('users.index'), show: user.value.permissions.includes('manage employees'), icon: 'Manage Users' },
        { name: 'Manage Teams', route: 'teams.index', active: route().current('teams.index'), show: user.value.permissions.includes('manage employees'), icon: 'Manage Teams' },
        { name: 'Manage Roles', route: 'roles.index', active: route().current('roles.index'), show: user.value.permissions.includes('manage roles'), icon: 'Manage Roles' },
        { name: 'Mail Logs', route: 'mail-logs.index', active: route().current('mail-logs.index'), show: user.value.permissions.includes('view mail logs'), icon: 'Mail Logs' },
    ];
    return items.filter(item => item.show);
});

const icons = {
    Dashboard: `<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>`,
    'Mail Logs': `<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>`,
    'Leave Calendar': `<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>`,
    'Manage Roles': `<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 20.944A12.02 12.02 0 0012 22a12.02 12.02 0 009-1.056c.343-.344.664-.714.944-1.123l-2.432-2.432z" /></svg>`,
    'Manage Requests': `<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>`,
    'Leave Logs': `<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>`,
    Projects: `<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>`,
    'Apply for Leave': `<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>`,
    'Your Requests': `<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 17l4-4-4-4m0 8V5a2 2 0 012-2h6a2 2 0 012 2v14a2 2 0 01-2 2H8a2 2 0 01-2-2z" /></svg>`,
    'Working Hours': `<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
    'Manage Users': `<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656-.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>`,
    'Manage Teams': `<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656-.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>`,
    'Company Hierarchy': `<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>`,
};

onMounted(() => { if (user.value) { fetchNotifications(); notificationInterval = setInterval(fetchNotifications, 60000); } });
onUnmounted(() => { if (notificationInterval) clearInterval(notificationInterval); });
</script>

<template>
    <div>
        <FlashMessage />
        <div class="min-h-screen bg-gray-50 font-sans">
            <!-- Static sidebar for desktop -->
            <div class="hidden md:fixed md:inset-y-0 md:flex md:w-64 md:flex-col">
                <div class="flex flex-grow flex-col overflow-y-auto border-r border-gray-200 bg-white pt-5">
                    <div class="flex flex-shrink-0 items-center px-4 space-x-3">
                        <Link :href="route('dashboard')">
                            <ApplicationLogo class="block h-8 w-auto text-indigo-600" />
                        </Link>
                        <span class="text-xl font-bold text-gray-800">WorkSphere</span>
                    </div>
                    <nav v-if="user && user.permissions" class="mt-8 flex-1 space-y-2 px-3 pb-4">
                       <NavLink v-for="item in navigationItems"
         :key="item.name"
         :href="route(item.route)"
         :active="item.active"
         class="nav-link">
  <span v-html="icons[item.icon]" class="mr-3 flex-shrink-0 h-6 w-6"></span>
  {{ item.name }}
</NavLink>

                    </nav>
                </div>
            </div>

            <!-- Main content area -->
            <div class="md:pl-64 flex flex-col min-h-screen">
                <!-- Top bar -->
                <header class="sticky top-0 z-10 flex h-16 flex-shrink-0 items-center justify-between border-b border-gray-200 bg-white px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center">
                        <button @click="showingSidebar = true" type="button" class="border-r border-gray-200 pr-4 text-gray-500 focus:outline-none md:hidden">
                            <span class="sr-only">Open sidebar</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                        </button>
                        <div class="font-semibold text-xl text-gray-800 leading-tight pl-4 md:pl-0">
                            <slot name="header" />
                        </div>
                    </div>

                    <div class="flex items-center space-x-4">
                        <template v-if="user">
                            <!-- Notification Dropdown -->
                            <div class="relative">
                                <button @click="toggleNotificationDropdown" class="relative inline-flex items-center p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                                    <span v-if="unreadNotificationCount > 0" class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full">
                                        {{ unreadNotificationCount > 99 ? '99+' : unreadNotificationCount }}
                                    </span>
                                </button>
                                <div v-if="showingNotificationDropdown" @click="closeNotificationDropdown" class="fixed inset-0 z-40"></div>
                                <div v-if="showingNotificationDropdown" class="absolute right-0 z-50 mt-2 w-80 bg-white border border-gray-200 rounded-lg shadow-lg">
                                    <div class="p-4 border-b border-gray-200">
                                        <div class="flex items-center justify-between">
                                            <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
                                            <button v-if="notifications.length > 0" @click="markAllAsRead" :disabled="loading" class="text-xs text-indigo-600 hover:text-indigo-800 disabled:opacity-50">Mark all as read</button>
                                        </div>
                                    </div>
                                    <div class="max-h-64 overflow-y-auto">
                                        <div v-if="notifications.length === 0" class="p-4 text-center text-gray-500 text-sm">No new notifications</div>
                                        <div v-for="notification in notifications" :key="notification.id" @click="handleNotificationClick(notification)" class="p-3 border-b border-gray-100 hover:bg-gray-50 cursor-pointer transition-colors">
                                            <div class="flex items-start space-x-3">
                                                <span class="text-lg">{{ getNotificationIcon(notification.data.type) }}</span>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm text-gray-900 font-medium truncate">{{ notification.data.title }}</p>
                                                    <p class="text-xs text-gray-500 mt-1">{{ formatDate(notification.created_at) }}</p>
                                                </div>
                                                <div v-if="!notification.read_at" class="w-2 h-2 bg-indigo-600 rounded-full flex-shrink-0 mt-1.5"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- User Profile Dropdown -->
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button class="flex items-center space-x-3 rounded-lg p-1 transition hover:bg-gray-100">
                                        <img v-if="user && user.image" class="h-9 w-9 rounded-full object-cover" :src="`/storage/${user.image}`" alt="User Avatar" />
                                        <img v-else class="h-9 w-9 rounded-full object-cover" src="/storage/defaults/default-avatar.jpg" alt="Default Avatar" />
                                        <div class="hidden text-left md:block">
                                            <div class="text-sm font-semibold text-gray-800">{{ user.name }}</div>
                                            <div class="text-xs text-gray-500">{{ userDesignation }}</div>
                                        </div>
                                        <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                                    </button>
                                </template>

                                <template #content>
                                    <DropdownLink :href="route('profile.edit')"> Profile </DropdownLink>
                                    <DropdownLink :href="route('notifications.index')">Notifications</DropdownLink>
                                    <div class="border-t border-gray-100 my-1"></div>
                                    <DropdownLink :href="route('logout')" method="post" as="button">Log Out</DropdownLink>
                                    <slot name="page-actions" />
                                </template>
                            </Dropdown>
                        </template>
                    </div>
                </header>

                <main class="flex-1">
                    <slot />
                </main>
            </div>

            <!-- Mobile Sidebar (Overlay) with Animations -->
            <div class="relative z-40 md:hidden" role="dialog" aria-modal="true">
                <Transition name="fade">
                    <div v-if="showingSidebar" @click="showingSidebar = false" class="fixed inset-0 bg-gray-600 bg-opacity-75"></div>
                </Transition>
                <Transition name="slide-in">
                    <div v-if="showingSidebar" class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-white">
                        <div class="flex flex-grow flex-col overflow-y-auto border-r border-gray-200 bg-white pt-5">
                             <div class="flex flex-shrink-0 items-center px-4 space-x-3">
                                <Link :href="route('dashboard')">
                                    <ApplicationLogo class="block h-8 w-auto text-indigo-600" />
                                </Link>
                                <span class="text-xl font-bold text-gray-800">WorkSphere</span>
                            </div>
                            <nav v-if="user && user.permissions" class="mt-8 flex-1 space-y-2 px-3 pb-4">
                                <NavLink v-for="item in navigationItems" :key="item.name" :href="route(item.route)" :active="item.active" @click="showingSidebar = false">
                                    <span v-html="icons[item.icon]" class="mr-3 flex-shrink-0 h-6 w-6"></span>
                                    {{ item.name }}
                                </NavLink>
                            </nav>
                        </div>
                    </div>
                </Transition>
            </div>
        </div>
    </div>
</template>

<style>
/*
 * ===================================================================
 * START OF CORRECTION: New styles to match the Projects page design
 * ===================================================================
 */

/* General (unselected) link style */
.nav-link {
    display: flex;
    align-items: center;
    width: 100%;
    padding: 0.75rem 1rem; /* 12px 16px */
    border-radius: 0.5rem; /* 8px - a nice, subtle curve */
    font-size: 0.9rem;     /* 14.4px */
    font-weight: 500;      /* Medium weight */
    color: #374151;        /* text-gray-700 */
    transition: all 0.2s ease-in-out;
}

/* Hover state for UNSELECTED links */
.nav-link:not([aria-current="page"]):hover {
    background-color: #F3F4F6; /* bg-gray-100 */
    color: #111827;            /* text-gray-900 */
}

/* Active state (selected link) */
.nav-link[aria-current="page"] {
    background-color: #3B82F6; /* bg-blue-500, a nice medium blue */
    color: #FFFFFF;            /* White text and icon */
    font-weight: 600;          /* Semibold for the active link */
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1); /* Subtle shadow */
}

/* Ensure SVG icons inherit the link's text color correctly */
.nav-link svg {
    color: currentColor;
}


/* Styles for the mobile sidebar animation */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-in-enter-active,
.slide-in-leave-active {
  transition: transform 0.3s ease-out;
}
.slide-in-enter-from,
.slide-in-leave-to {
  transform: translateX(-100%);
}


/* Sidebar NavLink: improved selection and hover effects */
.nav-link {
  display: flex;
  align-items: center;
  width: 100%;
  padding: 0.75rem 1rem;
  border-radius: 0.5rem;
  font-size: 0.95rem;
  font-weight: 500;
  color: #374151;
  background: transparent;
  transition: background-color 0.2s, color 0.2s, box-shadow 0.3s;
  position: relative;
}

/* Hover effect for non-active links */
.nav-link:not([aria-current="page"]):hover {
  background-color: #EFF6FF; /* light blue */
  color: #2563EB;            /* blue-600 */
}

/* Active (selected) sidebar item: blue background, white text, left bar */
.nav-link[aria-current="page"] {
  background-color: #3B82F6; /* blue-500 */
  color: #FFFFFF;
  font-weight: 600;
  box-shadow: 0 4px 12px -2px rgba(59,130,246,0.10);
}

/* Optional: Left accent bar for active link */
.nav-link[aria-current="page"]::before {
  content: '';
  position: absolute;
  left: 0; top: 0; bottom: 0;
  width: 4px;
  border-radius: 2px 0 0 2px;
  background: #2563EB;
  opacity: 1;
}

/* Icon inherits current color */
.nav-link svg {
  color: currentColor;
  transition: color 0.2s;
}

/* ===================================================================
 * END OF CORRECTION
 * ===================================================================
 */
</style>
